<?php
/**
 * build-manifest.php
 * Synchronises atomic JSON files into the global archive.
 */

$archiveDir = __DIR__ . '/../';
$outputPath  = __DIR__ . '/../manifest.json';

if (!is_dir($archiveDir)) {
    mkdir($archiveDir . '/crumbs', 0755, true);
    mkdir($archiveDir . '/recipes', 0755, true);
    die("📂 Archive directories created. Drop your JSON files in /archive/crumbs/[category]/ or /archive/recipes/[sector]/ and run again.\n");
}

$manifest = [
    'version' => '2.1.0',
    'last_updated' => date('c'),
    'crumbs'  => [],
    'recipes' => []
];

// 1. Sync Crumbs
echo "🧩 Syncing Crumbs...\n";
foreach (glob("$archiveDir/crumbs/*/*.json") as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    
    // Auto-Category based on folder name
    $data['category'] = $data['category'] ?? ucfirst(basename(dirname($file)));
    $manifest['crumbs'][] = $data;
}

// 2. Sync Recipes
echo "📂 Syncing Recipes...\n";
foreach (glob("$archiveDir/recipes/*/*.json") as $file) {
    $data = json_decode(file_get_contents($file), true);
    if (!$data) continue;
    
    // Auto-Sector based on folder name
    $data['sector'] = $data['sector'] ?? ucfirst(basename(dirname($file)));
    $manifest['recipes'][] = $data;
}

file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\n🚀 Master Manifest Synchronised: " . count($manifest['crumbs']) . " Crumbs, " . count($manifest['recipes']) . " Recipes.\n";
