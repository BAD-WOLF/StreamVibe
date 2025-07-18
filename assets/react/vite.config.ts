import react from '@vitejs/plugin-react';
import path from 'path';
import {defineConfig} from 'vite';
import tailwindcss from "@tailwindcss/vite";

export default defineConfig({
    plugins: [
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@app': path.resolve(__dirname, 'src/app'),
            '@auth': path.resolve(__dirname, 'src/features/auth'),
            '@content': path.resolve(__dirname, 'src/features/Content'),
            '@layout': path.resolve(__dirname, 'src/features/layout'),
            '@shared': path.resolve(__dirname, 'src/shared'),
        },
    },
})
