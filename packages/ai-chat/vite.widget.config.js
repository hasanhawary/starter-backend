import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    publicDir: false,
    build: {
        lib: {
            entry: resolve(__dirname, 'resources/assets/js/ai-chat-widget/ai-chat-widget.js'),
            name: 'AIChatWidget',
            fileName: () => 'ai-chat-widget.min.js',
            formats: ['iife'],
        },
        outDir: resolve(__dirname, '../../public/vendor/ai-chat'),
        emptyOutDir: true,
        minify: 'terser',
        sourcemap: false,
        rollupOptions: {
            output: {
                extend: true,
            },
        },
    },
});
