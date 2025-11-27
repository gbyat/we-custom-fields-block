#!/usr/bin/env node

// Convert a .po file into a Jed 1.x JSON file for WordPress/Gutenberg
// Minimal parser for simple msgid/msgstr (no plurals/context handling here)

const fs = require('fs');
const path = require('path');

function parsePO(content) {
    const entries = [];
    let msgid = null;
    let msgstr = null;
    let state = null; // 'msgid' | 'msgstr' | null

    const lines = content.split(/\r?\n/);
    for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        if (line.startsWith('msgid ')) {
            if (msgid !== null && msgstr !== null) {
                entries.push({ msgid, msgstr });
            }
            msgid = line.replace(/^msgid\s+"/, '').replace(/"$/, '');
            msgstr = '';
            state = 'msgid';
            continue;
        }
        if (line.startsWith('msgstr ')) {
            msgstr = line.replace(/^msgstr\s+"/, '').replace(/"$/, '');
            state = 'msgstr';
            continue;
        }
        if (line.startsWith('msgctxt ')) {
            // ignore context for this minimal converter
            continue;
        }
        if (/^\s*".*"\s*$/.test(line)) {
            const chunk = line.trim().replace(/^"/, '').replace(/"$/, '');
            if (state === 'msgid') msgid += chunk;
            if (state === 'msgstr') msgstr += chunk;
            continue;
        }
        // On blank or other lines, if we have a pair collected, push it
        if (line.trim() === '' && msgid !== null && msgstr !== null) {
            entries.push({ msgid, msgstr });
            msgid = null;
            msgstr = null;
            state = null;
        }
    }
    if (msgid !== null && msgstr !== null) {
        entries.push({ msgid, msgstr });
    }
    // Remove header (empty msgid)
    return entries.filter(e => e.msgid !== '');
}

function buildJed(domain, locale, entries) {
    const translation = {};
    for (const { msgid, msgstr } of entries) {
        if (!msgid) continue;
        // Unescape strings
        const unescapedMsgid = msgid.replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        const unescapedMsgstr = msgstr.replace(/\\n/g, '\n').replace(/\\"/g, '"').replace(/\\\\/g, '\\');
        translation[unescapedMsgid] = [unescapedMsgstr];
    }
    return {
        locale_data: {
            [domain]: Object.assign({ '': { domain, lang: locale } }, translation)
        }
    };
}

function main() {
    const input = process.argv[2];
    const domain = 'we-custom-fields-block';
    if (!input) {
        console.error('Usage: node scripts/po-to-jed.js <path-to-po>');
        process.exit(1);
    }
    const poPath = path.resolve(input);
    if (!fs.existsSync(poPath)) {
        console.error('PO not found:', poPath);
        process.exit(1);
    }
    const po = fs.readFileSync(poPath, 'utf8');
    const entries = parsePO(po);

    // derive locale from filename: domain-locale.po (e.g., we-custom-fields-block-de_DE.po)
    const base = path.basename(poPath, '.po');
    // Match locale pattern: de_DE, en_US, etc. (two letters, underscore, two letters)
    const localeMatch = base.match(/-([a-z]{2}_[A-Z]{2})$/);
    const locale = localeMatch ? localeMatch[1] : 'de_DE';

    const jed = buildJed(domain, locale, entries);
    const outDir = path.dirname(poPath);
    const fileA = path.join(outDir, `${domain}-${locale}.json`);
    fs.writeFileSync(fileA, JSON.stringify(jed));
    console.log('Wrote:', fileA);
    console.log('Run "node scripts/hash-json.js" to generate hashed JSON files for WordPress');
}

if (require.main === module) main();

