<?php

namespace App\Support;

use Illuminate\Support\Facades\Lang;

/**
 * ترجمة نصوص التصدير (Excel) حسب لغة الطلب الحالية (app()->getLocale()).
 *
 * المشكلة: دوال ->label() على الـ Enums (SubscriptionStatus, InvoiceStatus...)
 * ثابتة بالعربي دائمًا (مستخدمة بأماكن كتير بالتطبيق غير التصدير)، فتعديلها
 * مباشرة خطر واسع النطاق. الحل هنا: طبقة ترجمة اختيارية فوقها خاصة بالتصدير
 * فقط — بتدوّر عن مفتاح `exports.enum.<EnumClassName>.<value>` بملفات
 * lang/{locale}/exports.php، ولو مش موجود بترجع لنفس ->label() الافتراضي
 * (عربي) كـ fallback آمن بدل ما تطلع سلسلة المفتاح نفسها.
 */
class ExportLabel
{
    public static function forEnum(mixed $enum): string
    {
        if ($enum === null) {
            return '';
        }

        $key = 'exports.enum.'.class_basename($enum::class).'.'.$enum->value;

        if (Lang::has($key)) {
            return __($key);
        }

        return method_exists($enum, 'label') ? $enum->label() : (string) $enum->value;
    }

    /**
     * ترجمة رأس عمود بسيط تحت مساحة exports.headings.*، مع fallback لنفس
     * المفتاح لو الترجمة غير موجودة (بيصير ظاهر كنص المفتاح، سيناريو ما بيصير
     * طالما كل المفاتيح المستخدمة معرّفة بملفي ar/en).
     */
    public static function heading(string $key): string
    {
        return __('exports.headings.'.$key);
    }
}
