import { readFile } from 'node:fs/promises';
import { resolve } from 'node:path';
import { ASSET_CONTRACT } from '../resources/js/asset-contract.js';

const outputDirectory = resolve(process.env.VITE_BUILD_OUT_DIR?.trim() || 'public/build');
const manifestPath = resolve(outputDirectory, 'manifest.json');
const manifest = JSON.parse(await readFile(manifestPath, 'utf8'));
const entry = manifest['resources/js/app.js'];

if (!entry?.file) {
    throw new Error(`El manifest ${manifestPath} no contiene la entrada resources/js/app.js.`);
}

const bundlePath = resolve(outputDirectory, entry.file);
const bundle = await readFile(bundlePath, 'utf8');

if (!bundle.includes(ASSET_CONTRACT)) {
    throw new Error(`El bundle ${bundlePath} no corresponde al contrato frontend ${ASSET_CONTRACT}.`);
}

console.log(`Frontend verificado: ${entry.file} (${ASSET_CONTRACT}).`);
