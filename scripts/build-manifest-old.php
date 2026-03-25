<?php
/**
 * build-manifest.php
 * Automates the sync between PHP classes, Recipes, and the global Archive.
 */

$crumbsDir  = __DIR__ . '/../src/Crumbs';
$recipesDir = __DIR__ . '/../recipes'; // Where your recipe JSON files live
$outputPath = __DIR__ . '/../manifest.json';

$manifest = [
    'version' => '1.2.0',
    'last_updated' => date('Y-m-d H:i:s'),
    'crumbs'  => [],
    'recipes' => []
];

// 1. Auto-Scan Crumbs via Reflection
echo "🔍 Scanning Crumbs...\n";
echo "🔍 Scanning Crumbs (Static Analysis)...\n";
$directory = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($crumbsDir));

foreach ($directory as $file) {
    if ($file->getExtension() !== 'php') continue;

    $content = file_get_contents($file->getRealPath());

    // 1. Extract the 'getName()' return value via Regex
    // Matches: public function getName(): string { return 'geo_anchor'; }
    preg_match("/getName\(\): string\s*\{\s*return\s*['\"]([^'\"]+)['\"];\s*\}/", $content, $nameMatch);
    
    // 2. Extract Version
    preg_match("/getVersion\(\): string\s*\{\s*return\s*['\"]([^'\"]+)['\"];\s*\}/", $content, $versionMatch);
    
    // 3. Extract Dependencies (Matches: return ['geo_anchor', 'etc'];)
    preg_match("/getDependencies\(\): array\s*\{\s*return\s*\[(.*?)\];\s*\}/s", $content, $depMatch);

    if (isset($nameMatch[1])) {
        $id = $nameMatch[1];
        
        // Clean up dependencies into a proper array
        $deps = [];
        if (isset($depMatch[1]) && trim($depMatch[1]) !== '') {
            preg_match_all("/['\"]([^'\"]+)['\"]/", $depMatch[1], $depItems);
            $deps = $depItems[1] ?? [];
        }

        // Determine Category from Folder Path
        $pathParts = explode(DIRECTORY_SEPARATOR, $file->getPath());
        $category = end($pathParts) ?: 'General';

        $manifest['crumbs'][] = [
            'id'           => $id,
            'name'         => ucwords(str_replace(['_', '-'], ' ', $id)),
            'version'      => $versionMatch[1] ?? '1.0.0',
            'dependencies' => $deps,
            'category'     => $category,
            'tier'         => str_contains($file->getRealPath(), 'Enterprise') ? 'pro' : 'free',
            'stats'        => ['token_savings' => 90]
        ];

        echo "  ✅ Manifest Entry: $id [$category]\n";
    }
}
// 2. Auto-Scan Recipes
echo "🔍 Scanning Recipes...\n";
if (is_dir($recipesDir)) {
    foreach (glob("$recipesDir/*.json") as $recipeFile) {
        $recipeData = json_decode(file_get_contents($recipeFile), true);
        if ($recipeData) {
            $manifest['recipes'][] = $recipeData;
            echo "  📦 Added Recipe: {$recipeData['id']}\n";
        }
    }
}

// 3. Final Write
file_put_contents($outputPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "\n🚀 Manifest Synchronised: " . count($manifest['crumbs']) . " Crumbs, " . count($manifest['recipes']) . " Recipes.\n";
