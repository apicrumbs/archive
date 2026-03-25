<?php
$archiveDir = __DIR__ . '/../';
$outputPath  = __DIR__ . '/../manifest.json';

$manifest = [
    'version' => '2.0.0',
    'last_updated' => date('c'),
    'crumbs'  => [],
    'recipes' => []
];

// 1. Merge all Crumb JSONs
foreach (glob("$archiveDir/crumbs/*/*.json") as $file) {
    $manifest['crumbs'][] = json_decode(file_get_contents($file), true);
}

// 2. Merge all Recipe JSONs
foreach (glob("$archiveDir/recipes/*.json") as $file) {
    $manifest['recipes'][] = json_decode(file_get_contents($file), true);
}

file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT));
echo "🚀 Global Manifest Compiled: " . count($manifest['crumbs']) . " Crumbs synchronized.\n";
