import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    css: {
        preprocessorOptions: {
            scss: {
                silenceDeprecations: [
                    'import',
                    'mixed-decls',
                    'color-functions',
                    'global-builtin',
                ],
            },
        },
    },
    plugins: [
        laravel({
            input: [
                'public/assets/scss/style.scss',
            ],
            refresh: [
                'resources/views/**',
            ],
        }),
    ],
});
