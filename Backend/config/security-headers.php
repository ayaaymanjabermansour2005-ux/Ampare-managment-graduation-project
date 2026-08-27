<?php

return [

    /*
    |--------------------------------------------------------------------------
    | HTTP Security Headers
    |--------------------------------------------------------------------------
    |
    | Centralized, environment-aware configuration for the SecurityHeaders
    | middleware (app/Http/Middleware/SecurityHeaders.php). Values here are
    | derived from the actual frontend stack (Vite + Vue 3 SPA, Font Awesome
    | bundled locally, Google Fonts, Leaflet map tiles, Laravel Reverb over
    | WebSockets) so the resulting Content-Security-Policy does not silently
    | break any of them. Do not add broad exceptions here without checking
    | which concrete asset/origin requires them.
    |
    */

    // HSTS is only ever emitted when the app is in production AND the
    // request actually arrived over HTTPS — never on plain HTTP or in
    // local/testing, where it would just make local development painful.
    'hsts' => [
        'enabled' => env('SECURITY_HSTS_ENABLED', true),
        'max_age' => env('SECURITY_HSTS_MAX_AGE', 31536000), // 1 year
        'include_sub_domains' => true,
        'preload' => false,
    ],

    'frame_options' => 'DENY',

    'content_type_options' => 'nosniff',

    'referrer_policy' => 'strict-origin-when-cross-origin',

    // Content-Security-Policy directives. 'self' plus the concrete external
    // origins this application actually loads from. Additional origins
    // needed only for local development (Vite HMR) are merged in by the
    // middleware itself when app()->environment('local', 'testing').
    'csp' => [
        'default-src' => ["'self'"],
        'base-uri' => ["'self'"],
        'object-src' => ["'none'"],
        'frame-ancestors' => ["'none'"],
        'form-action' => ["'self'"],
        'script-src' => ["'self'"],
        // worker-src falls back to script-src (which doesn't include blob:)
        // when not set explicitly. The app spawns same-origin blob: workers
        // (e.g. PWA/service-worker tooling), so this is scoped narrowly
        // instead of widening script-src or default-src for that need.
        'worker-src' => ["'self'", 'blob:'],
        // Vue's `:style` bindings render as inline `style` attributes, which
        // CSP's style-src also governs — 'unsafe-inline' here only permits
        // inline *styling*, never script execution, so it does not weaken
        // the XSS protection script-src provides.
        'style-src' => ["'self'", "'unsafe-inline'", 'https://fonts.googleapis.com'],
        'font-src' => ["'self'", 'https://fonts.gstatic.com', 'data:'],
        'img-src' => [
            "'self'",
            'data:',
            'blob:',
            'https://*.tile.openstreetmap.org',
            'https://*.basemaps.cartocdn.com',
        ],
        // connect-src for API/fetch/XHR calls plus the Reverb WebSocket.
        // The Reverb host/port/scheme are read from the same VITE_REVERB_*
        // variables the frontend Echo client (resources/js/plugins/echo.js)
        // already uses, so this can never drift out of sync with what the
        // browser actually tries to connect to.
        'connect-src' => ["'self'"],
    ],
];
