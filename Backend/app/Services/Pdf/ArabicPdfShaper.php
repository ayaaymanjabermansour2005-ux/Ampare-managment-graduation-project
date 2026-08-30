<?php

namespace App\Services\Pdf;

use ArPHP\I18N\Arabic;

/**
 * dompdf لا يقوم بأي تشكيل حروف عربي (letter joining/shaping) ولا بإعادة
 * ترتيب bidi حقيقية — فقط يقرأ خاصية CSS الـ direction كنص، فتظهر الحروف
 * العربية منفصلة عن بعضها بدل الشكل المتصل الصحيح. الحل المعتمَد هنا: تمرير
 * الـ HTML المُصيَّر عبر ArPHP\I18N\Arabic::utf8Glyphs() (مكتبة خارجية
 * راسخة لهذا الغرض تحديدًا) قبل تسليمه لـ dompdf، بحيث نُعيد تشكيل نصوص
 * العقد/الفاتورة فقط (بدون المساس ببنية الـ HTML نفسها عبر تخطّي كل tag).
 */
class ArabicPdfShaper
{
    public function shapeHtml(string $html): string
    {
        $arabic = new Arabic;

        return (string) preg_replace_callback(
            '/>([^<]+)</u',
            function (array $matches) use ($arabic) {
                $text = $matches[1];

                if (! preg_match('/\p{Arabic}/u', $text)) {
                    return '>'.$text.'<';
                }

                return '>'.$arabic->utf8Glyphs($text, 5000, false).'<';
            },
            $html
        );
    }
}
