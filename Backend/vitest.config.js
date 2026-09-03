import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        vue({
            // Mirror vite.config.js: keep root-absolute src paths (e.g. "/images/logo.png",
            // served from public/) as literal strings instead of asset imports. Without this,
            // Vue's compiler turns them into `new URL(...)`-based imports that Vite resolves
            // via a file:// URL — which throws on Windows for a path with no drive letter.
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': '/resources/js',
        },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.spec.js'],
    },
});
