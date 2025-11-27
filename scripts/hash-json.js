#!/usr/bin/env node

// Create hashed JSON copies for Gutenberg editor scripts so WP auto-loads them for block.json
// Input: languages/<domain>-<locale>.json
// Output: languages/<domain>-<locale>-<md5(path)>.json for each editor script path

const fs = require('fs');
const path = require('path');
const crypto = require('crypto');

const DOMAIN = 'we-custom-fields-block';
const LOCALES = ['de_DE'];
// Script paths relative to plugin root - these match the paths in block.json
const SCRIPT_PATHS = [
    'build/index.js' // editor script
];

function md5(input) {
    return crypto.createHash('md5').update(input).digest('hex');
}

function main() {
    const projectRoot = process.cwd();
    const languagesDir = path.join(projectRoot, 'languages');
    if (!fs.existsSync(languagesDir)) {
        console.error('languages directory not found');
        process.exit(1);
    }

    LOCALES.forEach((locale) => {
        const sourceJson = path.join(languagesDir, `${DOMAIN}-${locale}.json`);
        if (!fs.existsSync(sourceJson)) {
            console.warn(`Skip locale ${locale}: ${path.basename(sourceJson)} not found`);
            return;
        }

        SCRIPT_PATHS.forEach((relPath) => {
            const hash = md5(relPath);
            const targetJson = path.join(languagesDir, `${DOMAIN}-${locale}-${hash}.json`);
            fs.copyFileSync(sourceJson, targetJson);
            console.log('Wrote', path.relative(projectRoot, targetJson));
        });
    });
}

if (require.main === module) main();
