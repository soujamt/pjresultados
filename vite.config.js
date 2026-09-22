import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            /*
             * Fuentes del sistema interno, descargadas al compilar y servidas
             * desde el propio servidor: no dependen de un CDN externo, que en
             * la red de la Corte puede estar bloqueado. El acceso conserva Inter.
             */
            fonts: [
                bunny('Public Sans', {
                    alias: 'institucional',
                    variable: '--fuente-institucional',
                    weights: [400, 500, 600, 700],
                    preload: [{ weight: 400 }, { weight: 500 }],
                    optimizedFallbacks: false,
                }),
            ],
        }),
        tailwindcss(),
    ]),
    server: {
        cors: true,
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/storage/framework/views/**',
                '**/vendor/**',
            ],
        },
    },
});
