import react from '@vitejs/plugin-react';
import path from 'path';
import {defineConfig} from 'vite';
import tailwindcss from "@tailwindcss/vite";
import yaml from '@modyfi/vite-plugin-yaml';

export default defineConfig({
    server: {
        allowedHosts: ['ui-streamvibe.duckdns.org']
    },
    plugins: [
        yaml(),
        react(),
        tailwindcss(),
    ],
    resolve: {
        alias: {
            '@app': path.resolve(__dirname, 'src/app'),
            '@i18n': path.resolve(__dirname, 'src/i18n'),
            '@auth': path.resolve(__dirname, 'src/features/auth'),
            '@content': path.resolve(__dirname, 'src/features/Content'),
            '@layout': path.resolve(__dirname, 'src/features/layout'),
            '@shared': path.resolve(__dirname, 'src/shared'),
        },
    },
})
