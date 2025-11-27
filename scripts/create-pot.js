#!/usr/bin/env node

/**
 * Create POT file from source code
 * Extracts all translatable strings and creates a template file
 */

const fs = require('fs');
const path = require('path');

// Function to extract strings from file content
function extractStrings(content, filePath) {
    const strings = [];

    // Patterns for WordPress translation functions
    const patterns = [
        // __('text', 'domain')
        /__\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // _e('text', 'domain')
        /_e\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // _x('text', 'context', 'domain')
        /_x\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`][^'"`]*['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // esc_html__('text', 'domain')
        /esc_html__\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // esc_attr__('text', 'domain')
        /esc_attr__\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // esc_html_e('text', 'domain')
        /esc_html_e\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // esc_attr_e('text', 'domain')
        /esc_attr_e\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // esc_html() with __('text', 'domain')
        /esc_html\(\s*__\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)\s*\)/g,
        // esc_attr() with __('text', 'domain')
        /esc_attr\(\s*__\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)\s*\)/g,
        // JavaScript: __('text', 'domain') from @wordpress/i18n (compiled)
        /\(0,\s*[a-zA-Z_$][a-zA-Z0-9_$]*\.__\)\s*\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g,
        // JavaScript: __('text', 'domain') direct (source) - JSX compatible
        /__\s*\(\s*['"`]([^'"`]+)['"`]\s*,\s*['"`]we-custom-fields-block['"`]\s*\)/g
    ];

    patterns.forEach(pattern => {
        let match;
        while ((match = pattern.exec(content)) !== null) {
            const text = match[1];
            if (text && text.trim()) {
                strings.push({
                    text: text,
                    file: filePath,
                    line: content.substring(0, match.index).split('\n').length
                });
            }
        }
    });

    return strings;
}

// Function to scan directory recursively
function scanDirectory(dir, extensions = ['.php', '.js']) {
    const files = [];

    function scan(currentDir) {
        const items = fs.readdirSync(currentDir);

        for (const item of items) {
            const fullPath = path.join(currentDir, item);
            const stat = fs.statSync(fullPath);

            if (stat.isDirectory()) {
                // Skip node_modules and other irrelevant directories
                if (!['node_modules', '.git', 'languages', 'build', 'dist', 'backups', '.vscode', '.idea'].includes(item)) {
                    scan(fullPath);
                }
            } else if (stat.isFile()) {
                const ext = path.extname(item);
                if (extensions.includes(ext)) {
                    files.push(fullPath);
                }
            }
        }
    }

    scan(dir);
    return files;
}

// Function to create POT content
function createPOTContent(strings) {
    const now = new Date();
    const dateStr = now.toISOString().replace('T', ' ').substring(0, 19) + '+0000';

    const header = `# Copyright (C) ${now.getFullYear()} Custom Fields Block
# This file is distributed under the same license as the Custom Fields Block package.
msgid ""
msgstr ""
"Project-Id-Version: Custom Fields Block 1.0.0\\n"
"Report-Msgid-Bugs-To: https://github.com/gbyat/we-custom-fields-block/issues\\n"
"POT-Creation-Date: ${dateStr}\\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"Language: \\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=INTEGER; plural=EXPRESSION;\\n"
"X-Poedit-SourceCharset: UTF-8\\n"
"X-Poedit-KeywordsList: __;_e;_x;_ex;_n;_nx;esc_attr__;esc_html__;esc_attr_e;esc_html_e;_n_noop;_nx_noop\\n"
"X-Poedit-Basepath: ..\\n"
"X-Poedit-SearchPath-0: .\\n"
"X-Poedit-SearchPath-1: src\\n"

`;

    // Remove duplicates and sort
    const uniqueStrings = [];
    const seen = new Set();

    strings.forEach(item => {
        if (!seen.has(item.text)) {
            seen.add(item.text);
            uniqueStrings.push(item);
        }
    });

    // Sort by text
    uniqueStrings.sort((a, b) => a.text.localeCompare(b.text));

    // Create entries
    let content = header;
    uniqueStrings.forEach(item => {
        const relativePath = path.relative(process.cwd(), item.file).replace(/\\/g, '/');
        content += `#: ${relativePath}:${item.line}\n`;
        content += `msgid "${item.text}"\n`;
        content += `msgstr ""\n\n`;
    });

    return content;
}

function main() {
    const sourceDir = process.cwd();
    const outputFile = path.join(sourceDir, 'languages', 'we-custom-fields-block.pot');

    console.log('Scanning source files...');

    // Scan for PHP and JS files
    const files = scanDirectory(sourceDir);
    console.log(`Found ${files.length} files to scan`);
    console.log('Files:', files.map(f => path.relative(process.cwd(), f)).join(', '));

    let allStrings = [];

    files.forEach(file => {
        try {
            const content = fs.readFileSync(file, 'utf8');
            const strings = extractStrings(content, file);
            if (strings.length > 0) {
                console.log(`Found ${strings.length} strings in ${path.relative(process.cwd(), file)}`);
            }
            allStrings = allStrings.concat(strings);
        } catch (error) {
            console.warn(`Warning: Could not read ${file}: ${error.message}`);
        }
    });

    console.log(`Found ${allStrings.length} total strings`);

    // Create POT content
    const potContent = createPOTContent(allStrings);

    // Ensure languages directory exists
    const languagesDir = path.dirname(outputFile);
    if (!fs.existsSync(languagesDir)) {
        fs.mkdirSync(languagesDir, { recursive: true });
    }

    // Write POT file
    fs.writeFileSync(outputFile, potContent, 'utf8');

    console.log(`✅ Created POT file: ${outputFile}`);
    console.log(`📊 Unique strings: ${new Set(allStrings.map(s => s.text)).size}`);
}

if (require.main === module) {
    main();
}

module.exports = { extractStrings, scanDirectory, createPOTContent };

