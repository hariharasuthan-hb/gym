import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/frontend/app.css',
                'resources/js/frontend/app.js',
                'resources/css/admin/app.css',
                'resources/js/admin/app.js',
            ],
            refresh: true,
        }),
    ],
    server: {
        // Allow cross-origin requests so Laravel (e.g. http://0.0.0.0:8000) can load Vite assets
        cors: true,
        // Ensure dev server is reachable when Laravel uses 0.0.0.0
        host: true,
    },
});
