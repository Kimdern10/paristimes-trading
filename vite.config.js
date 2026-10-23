import tailwindcss from '@tailwindcss/vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/passkeys.js',
                'resources/css/site.css',
                'resources/js/site.js',
            ],
            refresh: true,
            fonts: [
                bunny('Bricolage Grotesque', {
                    weights: [700, 800],
                    preload: [{ weight: 700 }],
                    optimizedFallbacks: false,
                }),
                bunny('Manrope', {
                    weights: [400, 600, 700],
                    preload: [{ weight: 400 }, { weight: 600 }],
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
