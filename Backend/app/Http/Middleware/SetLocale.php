<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    private const SUPPORTED_LOCALES = ['ar', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        // الترتيب: Accept-Language (بيرسله axios تلقائيًا لكل نداءات الـAPI العادية)
        // أولًا، وإلا ?lang= كـ fallback — مطلوب لأن روابط التنزيل المباشرة
        // (تصدير Excel، PDF...) بتُفتَح كـ <a href target="_blank"> أي تصفّح
        // مباشر من المتصفح مش عبر axios، فهيدا مش بيضيف هيدر Accept-Language
        // المخصّص؛ فبنعتمد على query param بدلها لنفس الغرض.
        $locale = $request->header('Accept-Language') ?: $request->query('lang');

        if (in_array($locale, self::SUPPORTED_LOCALES, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
