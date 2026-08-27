<?php

namespace App\Support;

/**
 * Money
 * -----
 * طبقة حسابية مالية موحّدة تعتمد bcmath بدل عمليات float المباشرة
 * (round(), +, -, *, /, ==, <, >) المنتشرة سابقًا عبر الخدمات المالية.
 *
 * لماذا: تمثيل float الثنائي لا يخزّن كل الكسور العشرية بدقة (مثال
 * كلاسيكي: 0.1 + 0.2 !== 0.3 في PHP)، وهذا يتراكم عبر آلاف العمليات
 * المالية (فواتير، دفعات، عمولات، خصومات) وقد يُنتج فروقات صغيرة لكنها
 * خطيرة في سياق محاسبي. bcmath يجري الحسابات على تمثيل نصي للأرقام
 * بدقة عشرية ثابتة ومضبوطة صراحةً، فيُزيل هذا الخطأ كليًا.
 *
 * طريقة الاستخدام: المدخلات والمخرجات تبقى float عادية (نفس ما يتوقعه
 * باقي النظام: قاعدة البيانات decimal(10,2)، الفرونت، الـ API Resources)
 * — التحويل إلى/من bcmath يحدث داخليًا وبشكل شفاف تمامًا.
 *
 * الدقة الافتراضية: منزلتان عشريتان، تطابق أعمدة decimal(10,2) في كل
 * الجداول المالية (invoices, payments, platform_commissions...). يمكن
 * تمرير scale مختلف صراحةً عند الحاجة (مثال: exchange_rate بدقة 4).
 */
final class Money
{
    private const DEFAULT_SCALE = 2;

    /**
     * دقة داخلية إضافية تُستخدم في الخطوات الوسيطة (الضرب/القسمة) قبل
     * التقريب النهائي لمنزلة الدقة المطلوبة، لتفادي فقدان دقة مبكر.
     */
    private const INTERMEDIATE_EXTRA_SCALE = 6;

    private function __construct()
    {
        // كائن Static فقط عمدًا — لا حاجة لنسخ منه.
    }

    public static function add(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        return (float) bcadd(self::toBc($a), self::toBc($b), $scale);
    }

    public static function sub(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        return (float) bcsub(self::toBc($a), self::toBc($b), $scale);
    }

    public static function mul(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        $raw = bcmul(self::toBc($a), self::toBc($b), $scale + self::INTERMEDIATE_EXTRA_SCALE);

        return (float) self::bcRound($raw, $scale);
    }

    public static function div(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        if (self::equals($b, 0.0, $scale + self::INTERMEDIATE_EXTRA_SCALE)) {
            throw new \DivisionByZeroError('Money::div — القاسم يساوي صفرًا.');
        }

        $raw = bcdiv(self::toBc($a), self::toBc($b), $scale + self::INTERMEDIATE_EXTRA_SCALE);

        return (float) self::bcRound($raw, $scale);
    }

    /**
     * نسبة مئوية من مبلغ (مثال شائع: عمولة المنصة، نسبة خصم عرض).
     * percentage(1000, 10) => 100.0
     */
    public static function percentage(float $amount, float $percent, int $scale = self::DEFAULT_SCALE): float
    {
        $numerator = bcmul(self::toBc($amount), self::toBc($percent), $scale + self::INTERMEDIATE_EXTRA_SCALE);
        $raw = bcdiv($numerator, '100', $scale + self::INTERMEDIATE_EXTRA_SCALE);

        return (float) self::bcRound($raw, $scale);
    }

    /**
     * مطابق لـ bccomp: يُعيد -1 إذا $a < $b، 0 إذا متساويان، 1 إذا $a > $b.
     */
    public static function compare(float $a, float $b, int $scale = self::DEFAULT_SCALE): int
    {
        return bccomp(self::toBc($a), self::toBc($b), $scale);
    }

    public static function equals(float $a, float $b, int $scale = self::DEFAULT_SCALE): bool
    {
        return self::compare($a, $b, $scale) === 0;
    }

    public static function greaterThan(float $a, float $b, int $scale = self::DEFAULT_SCALE): bool
    {
        return self::compare($a, $b, $scale) === 1;
    }

    public static function greaterThanOrEqual(float $a, float $b, int $scale = self::DEFAULT_SCALE): bool
    {
        return self::compare($a, $b, $scale) >= 0;
    }

    public static function lessThan(float $a, float $b, int $scale = self::DEFAULT_SCALE): bool
    {
        return self::compare($a, $b, $scale) === -1;
    }

    public static function lessThanOrEqual(float $a, float $b, int $scale = self::DEFAULT_SCALE): bool
    {
        return self::compare($a, $b, $scale) <= 0;
    }

    public static function max(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        return self::greaterThanOrEqual($a, $b, $scale)
            ? self::round($a, $scale)
            : self::round($b, $scale);
    }

    public static function min(float $a, float $b, int $scale = self::DEFAULT_SCALE): float
    {
        return self::lessThanOrEqual($a, $b, $scale)
            ? self::round($a, $scale)
            : self::round($b, $scale);
    }

    /**
     * تقريب لأقرب منزلة عشرية بطريقة Round Half Up (المطابقة لسلوك
     * round() الافتراضي في PHP)، لكن بدقة bcmath الكاملة بدل float.
     */
    public static function round(float $value, int $scale = self::DEFAULT_SCALE): float
    {
        return (float) self::bcRound(self::toBc($value), $scale);
    }

    /**
     * يحوّل float إلى تمثيل نصي بدقة عالية قبل تمريره لدوال bcmath،
     * لأن bcmath يتطلب مدخلات نصية وليست float مباشرة.
     */
    private static function toBc(float $value, int $decimals = 12): string
    {
        return sprintf('%.'.$decimals.'F', $value);
    }

    /**
     * Round Half Up عبر bcmath: إضافة 0.5 عند منزلة الدقة+1 ثم قص الباقي
     * (bcadd/bcsub بدقة $precision يقصّ تلقائيًا بلا تقريب فوقي إضافي).
     */
    private static function bcRound(string $number, int $precision): string
    {
        $isNegative = str_starts_with($number, '-');
        $half = '0.'.str_repeat('0', $precision).'5';

        return $isNegative
            ? bcsub($number, $half, $precision)
            : bcadd($number, $half, $precision);
    }
}
