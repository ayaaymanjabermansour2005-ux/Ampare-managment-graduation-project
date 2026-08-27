<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * Apply a centralized set of HTTP security response headers.
     *
     * Configuration lives in config/security-headers.php so the concrete
     * allow-listed origins (fonts, map tiles, Reverb) stay in one place
     * instead of being scattered or duplicated across controllers.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $response->headers->set('X-Content-Type-Options', config('security-headers.content_type_options'));
        $response->headers->set('X-Frame-Options', config('security-headers.frame_options'));
        $response->headers->set('Referrer-Policy', config('security-headers.referrer_policy'));

        if ($this->shouldSendHsts($request)) {
            $response->headers->set('Strict-Transport-Security', $this->hstsValue());
        }

        $response->headers->set('Content-Security-Policy', $this->cspValue());

        return $response;
    }

    /**
     * HSTS is only meaningful — and only safe — once the app is actually
     * running in production over HTTPS. Emitting it on a plain-HTTP local
     * dev server would tell browsers to force HTTPS there too, breaking
     * `php artisan serve` / `composer run dev` for anyone who visits once.
     */
    private function shouldSendHsts(Request $request): bool
    {
        return (bool) config('security-headers.hsts.enabled')
            && app()->isProduction()
            && $request->isSecure();
    }

    private function hstsValue(): string
    {
        $value = 'max-age='.(int) config('security-headers.hsts.max_age');

        if (config('security-headers.hsts.include_sub_domains')) {
            $value .= '; includeSubDomains';
        }

        if (config('security-headers.hsts.preload')) {
            $value .= '; preload';
        }

        return $value;
    }

    private function cspValue(): string
    {
        $directives = config('security-headers.csp');

        $directives['connect-src'] = array_values(array_unique(array_merge(
            $directives['connect-src'] ?? ["'self'"],
            $this->reverbConnectSources(),
        )));

        if (app()->environment('local', 'testing')) {
            $directives = $this->withLocalDevSources($directives);
        }

        $parts = [];

        foreach ($directives as $directive => $sources) {
            if (empty($sources)) {
                continue;
            }

            $parts[] = $directive.' '.implode(' ', $sources);
        }

        return implode('; ', $parts);
    }

    /**
     * The Reverb WebSocket origin the frontend actually connects to
     * (resources/js/plugins/echo.js), derived from the same VITE_REVERB_*
     * variables so this can never drift out of sync with the real client.
     */
    private function reverbConnectSources(): array
    {
        $host = config('reverb.client.host');

        if (! $host) {
            return [];
        }

        $port = config('reverb.client.port', 8080);
        $scheme = config('reverb.client.scheme', 'https') === 'https' ? 'wss' : 'ws';

        return ["{$scheme}://{$host}:{$port}"];
    }

    /**
     * Vite's dev server (HMR) needs its own origin in script-src/connect-src
     * plus a websocket connect-src for live reload — only ever added outside
     * production. vite.config.js pins the dev server to 127.0.0.1 only (see
     * its `server.host` comment), so that's the only host ever listed here:
     * `localhost` can resolve to `::1` on some machines and `[::1]` is not a
     * valid CSP host-source token (brackets aren't part of the grammar), so
     * either would just add a source the browser rejects as invalid without
     * Vite ever actually serving from it.
     */
    private function withLocalDevSources(array $directives): array
    {
        $viteOrigins = [
            'http://127.0.0.1:5173',
        ];
        $viteWsOrigins = [
            'ws://127.0.0.1:5173',
        ];

        $directives['script-src'] = array_merge($directives['script-src'] ?? [], $viteOrigins);
        $directives['connect-src'] = array_merge($directives['connect-src'] ?? [], $viteOrigins, $viteWsOrigins);
        // Vite's HMR client injects <style> tags at runtime for CSS hot-reload.
        $directives['style-src'] = array_merge($directives['style-src'] ?? [], $viteOrigins);
        // Font Awesome's CSS (served through the dev server, unbundled) references
        // its .woff2 files by url() — without the Vite origin here those requests
        // are CSP-blocked and every fa-* icon renders as an empty box in local dev.
        $directives['font-src'] = array_merge($directives['font-src'] ?? [], $viteOrigins);

        return $directives;
    }
}
