#!/usr/bin/env node

/**
 * Script de vérification PWA pour ReMmailleuse
 * Vérifie la présence de tous les fichiers requis
 */

const fs = require('fs');
const path = require('path');

const basePath = path.join(__dirname, '..');
const manifestPath = path.join(basePath, 'manifest.json');

console.log('🔍 Vérification PWA ReMmailleuse');
console.log('================================');

// Vérifier le manifest
if (!fs.existsSync(manifestPath)) {
    console.error('❌ Manifest.json manquant');
    process.exit(1);
}

const manifest = JSON.parse(fs.readFileSync(manifestPath, 'utf8'));
console.log('✅ Manifest.json trouvé');

// Vérifier les icônes
let missingIcons = 0;
let totalIcons = 0;

if (manifest.icons) {
    manifest.icons.forEach(icon => {
        totalIcons++;
        const iconPath = path.join(basePath, icon.src);
        if (fs.existsSync(iconPath)) {
            console.log(`✅ ${icon.src} (${icon.sizes})`);
        } else {
            console.log(`❌ ${icon.src} (${icon.sizes}) - MANQUANT`);
            missingIcons++;
        }
    });
}

// Vérifier les screenshots
let missingScreenshots = 0;
let totalScreenshots = 0;

if (manifest.screenshots) {
    manifest.screenshots.forEach(screenshot => {
        totalScreenshots++;
        const screenshotPath = path.join(basePath, screenshot.src);
        if (fs.existsSync(screenshotPath)) {
            console.log(`✅ ${screenshot.src} (${screenshot.sizes})`);
        } else {
            console.log(`❌ ${screenshot.src} (${screenshot.sizes}) - MANQUANT`);
            missingScreenshots++;
        }
    });
}

// Vérifier les raccourcis
let missingShortcuts = 0;
let totalShortcuts = 0;

if (manifest.shortcuts) {
    manifest.shortcuts.forEach(shortcut => {
        if (shortcut.icons) {
            shortcut.icons.forEach(icon => {
                totalShortcuts++;
                const iconPath = path.join(basePath, icon.src);
                if (fs.existsSync(iconPath)) {
                    console.log(`✅ ${icon.src} (raccourci: ${shortcut.short_name})`);
                } else {
                    console.log(`❌ ${icon.src} (raccourci: ${shortcut.short_name}) - MANQUANT`);
                    missingShortcuts++;
                }
            });
        }
    });
}

// Vérifier le service worker
const swPath = path.join(basePath, 'sw.js');
if (fs.existsSync(swPath)) {
    console.log('✅ Service Worker (sw.js)');
} else {
    console.log('❌ Service Worker (sw.js) - MANQUANT');
}

// Résumé
console.log('\n📊 Résumé de la vérification');
console.log('============================');
console.log(`Icônes principales: ${totalIcons - missingIcons}/${totalIcons} ✅`);
console.log(`Screenshots: ${totalScreenshots - missingScreenshots}/${totalScreenshots} ✅`);
console.log(`Icônes raccourcis: ${totalShortcuts - missingShortcuts}/${totalShortcuts} ✅`);

const totalMissing = missingIcons + missingScreenshots + missingShortcuts;
if (totalMissing === 0) {
    console.log('\n🎉 PWA complète ! Tous les fichiers sont présents.');
    process.exit(0);
} else {
    console.log(`\n⚠️  ${totalMissing} fichier(s) manquant(s) détecté(s).`);
    process.exit(1);
}