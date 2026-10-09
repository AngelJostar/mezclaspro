import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/welcome.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        rollupOptions: {
            output: {
                manualChunks(id) {
                    if (!id.includes('node_modules')) return;
                    if (id.includes('lucide')) return 'icons';
                    if (id.includes('leaflet')) return 'maps';
                    if (id.includes('laravel-echo') || id.includes('pusher-js')) return 'realtime';
                    if (id.includes('axios')) return 'http';
                    return 'vendor';
                },
            },
        },
    },
});
