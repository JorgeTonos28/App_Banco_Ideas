import { defineConfig } from 'vite';
import { basename, parse, resolve } from 'node:path';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

const configuredBuildOutDir = process.env.VITE_BUILD_OUT_DIR?.trim();
const buildOutDir = configuredBuildOutDir ? resolve(configuredBuildOutDir) : null;

if (buildOutDir && (basename(buildOutDir).toLowerCase() !== 'build' || buildOutDir === parse(buildOutDir).root)) {
    throw new Error('VITE_BUILD_OUT_DIR debe apuntar a un directorio específico terminado en /build.');
}

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: buildOutDir ? {
        outDir: buildOutDir,
        emptyOutDir: false,
    } : undefined,
});
