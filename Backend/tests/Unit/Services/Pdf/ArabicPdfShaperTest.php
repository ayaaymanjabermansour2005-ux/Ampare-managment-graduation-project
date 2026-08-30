<?php

namespace Tests\Unit\Services\Pdf;

use App\Services\Pdf\ArabicPdfShaper;
use Tests\TestCase;

class ArabicPdfShaperTest extends TestCase
{
    /**
     * dompdf لا يشكّل الحروف العربية بنفسه — هذا الاختبار يثبت أن
     * ArabicPdfShaper يحوّل الحروف العربية الأساسية (Unicode Arabic block,
     * U+0600–U+06FF) إلى أشكالها التقديمية الصحيحة (Arabic Presentation
     * Forms-A/B, U+FB50–U+FDFF / U+FE70–U+FEFF) — أي دليل حقيقي على أن
     * التشكيل يحدث فعليًا، وليس مجرد افتراض.
     */
    public function test_arabic_text_is_converted_to_presentation_form_glyphs(): void
    {
        $shaper = new ArabicPdfShaper;

        $shaped = $shaper->shapeHtml('<h1>فاتورة</h1>');
        $shapedText = strip_tags($shaped);

        $this->assertNotSame('فاتورة', $shapedText);
        $this->assertMatchesRegularExpression('/[\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $shapedText);
    }

    public function test_html_tags_and_attributes_are_left_untouched(): void
    {
        $shaper = new ArabicPdfShaper;

        $html = '<div class="header" data-id="1"><h1>فاتورة رقم #12</h1></div>';
        $shaped = $shaper->shapeHtml($html);

        $this->assertStringContainsString('<div class="header" data-id="1">', $shaped);
        $this->assertStringContainsString('</h1></div>', $shaped);
    }

    public function test_non_arabic_text_is_left_unchanged(): void
    {
        $shaper = new ArabicPdfShaper;

        $html = '<p>Invoice #12 — 2026-08-25</p>';
        $shaped = $shaper->shapeHtml($html);

        $this->assertSame($html, $shaped);
    }

    public function test_western_digits_are_preserved_not_converted_to_eastern_arabic_numerals(): void
    {
        $shaper = new ArabicPdfShaper;

        $shaped = $shaper->shapeHtml('<p><bdi>1234.56</bdi> ₪</p>');

        $this->assertStringContainsString('1234.56', $shaped);
    }

    /**
     * اكتشاف حقيقي أثناء التطوير: المكتبة (ArPHP\I18N\Arabic::utf8Glyphs)
     * تُعيد ترتيب أي رقم عشري أو تاريخ (يحتوي "." أو "-") يظهر ضمن نفس
     * العقدة النصية مع كلام عربي — مثال: "المبلغ: 1234.56" تتحول إلى
     * "56.1234 :...", و"2026-08-25" تتحول إلى "25-08-2026". هذا يخرّب أي
     * مبلغ مالي أو تاريخ بالفاتورة/العقد الفعليين. الحل: قوالب PDF يجب أن
     * تحيط كل قيمة رقمية/تاريخ تظهر بجانب نص عربي بوسم <bdi> (Bidirectional
     * Isolate) — وهذا يفصلها كعقدة نصية مستقلة فيتجاهلها الـ Shaper تمامًا
     * (invoice.blade.php و subscription-contract.blade.php معدَّلان بالفعل
     * بهذا الأسلوب). هذا الاختبار يوثّق المشكلة الأصلية صراحةً حتى لا يُعاد
     * إدخالها لاحقًا بالخطأ.
     */
    public function test_decimal_numbers_without_bdi_isolation_get_corrupted_when_mixed_with_arabic(): void
    {
        $shaper = new ArabicPdfShaper;

        $shaped = $shaper->shapeHtml('<p>المبلغ: 1234.56</p>');

        $this->assertStringNotContainsString('1234.56', $shaped);
    }
}
