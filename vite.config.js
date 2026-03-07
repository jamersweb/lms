import { defineConfig } from 'vite';
import path from 'path';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    resolve: {
        alias: {
            // Route wrapper that sanitizes null params to prevent Ziggy toString errors
            'ziggy-js': path.resolve(__dirname, 'resources/js/ziggy-safe.js'),
        },
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        hmr: {
            host: 'localhost',
            protocol: 'ws',
        },
        strictPort: false,
        cors: true,
    },
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    build: {
        minify: 'esbuild',
        target: 'esnext',
        cssCodeSplit: true,
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['vue', '@inertiajs/vue3'],
                    'ziggy': ['ziggy-js'],
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
    // Keep console in production so we can see runtime errors (e.g. white screen debugging)
    // esbuild: {
    //     drop: process.env.NODE_ENV === 'production' ? ['console', 'debugger'] : [],
    // },
    optimizeDeps: {
        include: ['vue', '@inertiajs/vue3', 'ziggy-js'],
    },
});
