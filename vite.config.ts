import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    server: {
        // En dev las páginas se sirven desde subdominios {slug}.lvh.me:8000,
        // pero los assets vienen del servidor Vite (127.0.0.1:5173). Sin esto,
        // los scripts se bloquean por CORS y el JS no hidrata (forms nativos → 419).
        cors: {
            origin: [
                /^https?:\/\/(?:[^/.]+\.)*lvh\.me(?::\d+)?$/,
                /^https?:\/\/localhost(?::\d+)?$/,
            ],
        },
    },
});
