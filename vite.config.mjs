import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';
import postcss from './postcss.config.js'; // Import PostCSS config

export default defineConfig({
    cacheDir: 'assets/build/cache',
    plugins: [
        laravel({
            input: [
                './assets/js/src/bento-wordpress-sdk.js',
                './assets/js/src/mail-logs.jsx',
                './assets/css/app.css' // Include your main CSS file
            ],
            publicDirectory: 'assets',
            buildDirectory: 'build'
        })
    ],
    css: {
        postcss, // Ensure Vite uses PostCSS (includes Tailwind)
    },
    resolve: {
        alias: {
            '@': path.resolve('./assets'),
        },
    },
    build: {
        manifest: 'manifest.json',
        assetsDir: '',
        outDir: 'assets/build',
        rollupOptions: {
            input: {
                'mail-logs': './assets/js/src/mail-logs.jsx',
                'bento-wordpress-sdk': './assets/js/src/bento-wordpress-sdk.js'
            },
            output: {
                entryFileNames: '[name]-[hash].js',
                chunkFileNames: '[name]-[hash].js',
                assetFileNames: '[name]-[hash].[ext]',
            }
        }
    }
});