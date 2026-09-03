import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: false, 
            workbox: {
                navigateFallbackDenylist: [/^\/api\//],
                runtimeCaching: [
                    {
                        urlPattern: /\/api\/(invoices|subscriptions|generators|subscriber-meters)/,
                        handler: 'NetworkFirst',
                        method: 'GET',
                        options: {
                            cacheName: 'ampare-api-cache',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                    {
                        // بوابة الفني: يسمح بعرض آخر بيانات مُحمَّلة (مهامي/القراءات/دفعاتي/محادثتي)
                        // حتى بدون اتصال، بنفس آلية NetworkFirst المستخدمة أعلاه.
                        urlPattern: /\/api\/v1\/(technician-tasks|meter-readings|technician-payments|conversations|technicians)/,
                        handler: 'NetworkFirst',
                        method: 'GET',
                        options: {
                            cacheName: 'ampare-technician-api-cache',
                            networkTimeoutSeconds: 3,
                            expiration: {
                                maxEntries: 200,
                                maxAgeSeconds: 60 * 60 * 24,
                            },
                            cacheableResponse: {
                                statuses: [0, 200],
                            },
                        },
                    },
                ],
            },
            manifest: {
                name: 'Ampare Management',
                short_name: 'Ampare',
                start_url: '/',
                display: 'standalone',
                background_color: '#ffffff',
                theme_color: '#1d4ed8',
                icons: [],
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    build: {
        rollupOptions: {
            output: {
                // FIX: main "app" chunk كان 949 كيلوبايت (فوق حد التحذير
                // الافتراضي 500 كيلوبايت لـ Vite) بدون أي code-splitting
                // للمكتبات الخارجية الكبيرة. chart.js وleaflet مقسومين
                // أصلًا لوحدهم (dynamic import() موجود مسبقًا بمكان آخر
                // بالكود)، فبنكمّل نفس المبدأ يدويًا على باقي المكتبات
                // الكبيرة اللي كانت لسا داخل الحزمة الرئيسية. التقسيم
                // بالاسم فقط (مو بترتيب التنفيذ)، فـ Rollup بيتكفّل تلقائيًا
                // بترتيب تحميل الحزم المتداخلة بشكل صحيح.
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('primevue') || id.includes('primeicons')) return 'primevue-vendor';
                    if (id.includes('/three/') || id.includes('\\three\\')) return 'three-vendor';
                    if (id.includes('motion')) return 'motion-vendor';
                    if (id.includes('@fortawesome')) return 'fontawesome-vendor';
                    if (id.includes('jspdf') || id.includes('html2canvas')) return 'pdf-vendor';
                    if (id.includes('datatables') || id.includes('@tanstack/vue-table')) return 'datatables-vendor';
                    if (id.includes('laravel-echo') || id.includes('pusher-js')) return 'realtime-vendor';
                    // Both were previously falling into the generic 'vendor' bucket below,
                    // which meant any one page needing either of them (6 dashboard/management
                    // views for charts; the admin generator map and the public landing page's
                    // map section for Leaflet) downloaded that whole ~565 KB shared bundle —
                    // sweetalert-adjacent one-off libraries and all. Each is genuinely used by
                    // several separate routes, so unlike a single-route dependency it's worth
                    // giving them their own named chunk rather than letting Rollup's default
                    // per-entry splitting duplicate them across those routes' chunks.
                    if (id.includes('chart.js') || id.includes('vue-chartjs')) return 'chart-vendor';
                    if (id.includes('leaflet')) return 'map-vendor';
                    if (
                        id.includes('/vue/') || id.includes('\\vue\\') ||
                        id.includes('vue-router') || id.includes('pinia') || id.includes('vue-i18n')
                    ) return 'vue-vendor';
                    return 'vendor';
                },
            },
        },
    },
    server: {
        // Pin the dev server to IPv4 loopback explicitly. Without this, Vite
        // resolves the bare "localhost" host and — on Windows machines where
        // Node prefers IPv6 — binds only to `::1`. Laravel's `@vite()` then
        // emits asset URLs like `http://[::1]:5173`, which is unreachable
        // over IPv4 and leaves the SPA unable to load (white screen), since
        // the app is served/accessed via the IPv4 address (127.0.0.1:8000).
        host: '127.0.0.1',
        strictPort: true,
        origin: 'http://127.0.0.1:5173',
        // laravel-vite-plugin defaults `server.cors.origin` to whatever
        // `server.origin` above is set to (see its `config()` hook). Since we
        // pin `origin` to the Vite dev server's own address for correct asset
        // URL generation, that default would make the Vite dev server only
        // accept CORS requests whose Origin header equals itself — never the
        // Laravel app at 127.0.0.1:8000 that actually loads @vite/client and
        // resources/js/app.js. Set it explicitly to the real caller instead.
        cors: {
            origin: ['http://127.0.0.1:8000'],
        },
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
