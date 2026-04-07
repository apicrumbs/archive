<?php
/**
 * ApiCrumbs: The Foundry Portal Generator v2.0
 * Generates All Crumbs Pages Interactive, SEO-optimized Documentation Pages
 * 
 * Local:
 * php build-docs.php url=http://localhost/ApiCrumbs/Archive/docs 
 * 
 * GitHub:
 * php build-docs.php url=https://apicrumbs.github.io/archive
 * 
 */
// 1. Configuration & Args
$urlPrefix = '';

if (isset($argv[1])) {
    parse_str($argv[1], $arg);

    if (isset($arg['url'])) {
        $urlPrefix = $arg['url'];
    }
}
$manifestPath = __DIR__ . '/../manifest.json';
if (!file_exists($manifestPath)) {
    die("❌ Error: manifest.json not found. Run php build-manifest.php first.\n");
}

$manifest = json_decode(file_get_contents($manifestPath), true);
$docsDir = __DIR__ . '/../docs';

// 2. Ensure Directories
$dirs = ['/crumbs', '/category', '/recipes', '/sector'];
foreach ($dirs as $dir) {
    if (!is_dir($docsDir . $dir)) mkdir($docsDir . $dir, 0755, true);
}

// 3. Group Data for Navigation
$categories = [];
foreach ($manifest['crumbs'] as $c) { $categories[$c['category']][] = $c; }
ksort($categories);


$recipeSectors = [];
foreach ($manifest['recipes'] as $r) { $recipeSectors[$r['sector']][] = $r; }
ksort($recipeSectors);


// 4. Pre-generate Sidebar HTML
$sidebarHtml = "<div class='mb-8'><a href='{$urlPrefix}/index.html' class='text-[10px] font-black text-sky-500 uppercase tracking-widest mb-4 block'>Atomic Crumbs</a>";
foreach ($categories as $cat => $items) {
    $slug = strtolower(str_replace(' ', '_', $cat));
    $sidebarHtml .= "<a href='{$urlPrefix}/category/{$slug}.html' class='block py-1 text-xs text-slate-400 hover:text-sky-400 transition'>🧩 $cat</a>";
}
$sidebarHtml .= "</div><div class='mb-8'><span class='text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-4 block'>Sector Recipes</span>";
foreach ($recipeSectors as $sec => $items) {
    $slug = strtolower(str_replace(' ', '-', $sec));
    $sidebarHtml .= "<a href='{$urlPrefix}/sector/{$slug}.html' class='block py-1 text-xs text-slate-400 hover:text-emerald-400 transition'>📂 $sec Pack</a>";
}
$sidebarHtml .= "</div>";

// 5. The Master Layout Wrapper
$buildLayout = function($urlPrefix, $title, $content, $manifest) use ($sidebarHtml) {
    $mJson = json_encode($manifest);
    $updated = date('Y-m-d');
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>$title | ApiCrumbs Foundry</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style> body { background: #0b0e14; color: #94a3b8; } .glass { background: rgba(15, 23, 42, 0.8); backdrop-filter: blur(12px); } </style>
</head>
<body class="flex">
    <nav class="w-80 h-screen sticky top-0 border-r border-slate-800 p-8 overflow-y-auto bg-[#0b0e14]">
        <a href="{$urlPrefix}/" class="mb-10 block flex items-center gap-3">
            <div class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center font-black text-white italic text-xl shadow-lg shadow-sky-500/20"><img src="https://www.apicrumbs.com/logo.svg"></div>
            <div class="flex flex-col leading-none">
                <span class="text-white font-black text-2xl tracking-tighter italic">ApiCrumbs</span>
                <span class="text-[8px] text-slate-500 font-bold uppercase tracking-[0.4em] mt-1">Foundry Hub</span>
            </div>
        </a>
        <input type="text" id="cSearch" placeholder="Search 10,000 crumbs..." class="w-full bg-slate-900 border border-slate-800 rounded-xl p-3 text-xs text-white outline-none focus:ring-1 ring-sky-500 mb-8">
        <div id="sLinks">$sidebarHtml</div>
    </nav>
    <main class="flex-1 min-h-screen flex flex-col">
        <header class="border-b border-slate-800 px-12 py-4 flex justify-between items-center text-[10px] font-mono tracking-widest uppercase">
            <span>REGISTRY_SYNC: <span class="text-white">$updated</span></span>
            <div class="font-black text-sky-500 uppercase">Status: Operational</div>
        </header>
        <div class="p-16 max-w-5xl flex-1">$content</div>
    </main>
    <script>
        const archive = $mJson;
        const urlPrefix = '{$urlPrefix}';
        const sidebar = document.getElementById('sLinks');
        const orig = sidebar.innerHTML;
        document.getElementById('cSearch').addEventListener('input', (e) => {
            const q = e.target.value.toLowerCase().trim();
            if(!q){ sidebar.innerHTML = orig; return; }
            const res = archive.crumbs.filter(c => c.name.toLowerCase().includes(q) || c.id.toLowerCase().includes(q));
            let html = '<div class="text-[10px] font-black text-sky-500 uppercase mb-4">Results</div>';
            res.forEach(p => { 
                const slug = p.id.replace(/[\/\._]/g, '_').toLowerCase();
                html += `<a href="\${urlPrefix}/crumbs/\${slug}.html" class="block py-1 text-sm text-slate-300 hover:text-sky-400">🧩 \${p.name}</a>`;
            });
            sidebar.innerHTML = html;
        });
        async function copyToClipboard(btn, id) {
            const txt = document.getElementById(id).innerText;
            await navigator.clipboard.writeText(txt);
            const old = btn.innerText; btn.innerText = "COPIED!";
            setTimeout(() => btn.innerText = old, 2000);
        }
    </script>
</body></html>
HTML;
};

// 6. Generate HOME DASHBOARD (index.html)
$totalC = count($manifest['crumbs']);
$totalR = count($manifest['recipes']);
$home = "
<div class='mb-24'><h1 class='text-8xl font-black text-white italic tracking-tighter mb-8 leading-none'>FOUNDRY<br><span class='text-sky-600'>REGISTRY.</span></h1>
<p class='text-2xl text-slate-400 max-w-2xl'>Refining APIs into high-signal context. Choose an atomic Crumb or a pre-stitched Industry Pack.</p>
<div class='flex gap-12 mt-12 font-mono text-[10px] tracking-widest uppercase border-t border-slate-900 pt-8'>
    <div><span class='text-slate-600 block'>Crumbs</span><span class='text-white text-xl font-black'>$totalC</span></div>
    <div><span class='text-slate-600 block'>Recipes</span><span class='text-white text-xl font-black'>$totalR</span></div>
</div></div>
<div class='grid lg:grid-cols-2 gap-16'>
    <div><h2 class='text-2xl font-black text-white italic mb-10'>🧩 Atomic Crumbs</h2><div class='grid gap-4'>";
foreach ($categories as $cat => $items) {
    $slug = strtolower(str_replace(' ', '-', $cat));
    $home .= "<a href='{$urlPrefix}/category/{$slug}.html' class='p-6 rounded-2xl bg-slate-900/40 border border-slate-800 hover:border-sky-500 transition flex justify-between'>
    <span class='text-white font-bold'>$cat</span><span class='text-[10px] text-slate-500'>" . count($items) . " Items</span></a>";
}
$home .= "</div></div><div><h2 class='text-2xl font-black text-white italic mb-10'>📂 Industry Packs</h2><div class='grid gap-4'>";
foreach ($recipeSectors as $sec => $items) {
    $slug = strtolower(str_replace(' ', '-', $sec));
    $home .= "<a href='{$urlPrefix}/sector/{$slug}.html' class='p-6 rounded-2xl bg-slate-900/40 border border-slate-800 hover:border-emerald-500 transition flex justify-between'>
    <span class='text-white font-bold'>$sec</span><span class='text-[10px] text-slate-500'>" . count($items) . " Packs</span></a>";
}
$home .= "</div></div></div>";
file_put_contents("$docsDir/index.html", $buildLayout($urlPrefix, "Foundry Hub", $home, $manifest));

// 7. Generate SECTOR PORTALS
foreach ($recipeSectors as $sec => $items) {
    $slug = strtolower(str_replace(' ', '-', $sec));
    $content = "<h1 class='text-5xl font-black text-white mb-12 italic'>$sec <span class='text-slate-700'>Solutions</span></h1><div class='grid gap-4'>";
    foreach ($items as $r) {
        $content .= "<a href='{$urlPrefix}/recipes/{$r['id']}.html' class='p-8 bg-slate-900/50 border border-slate-800 rounded-3xl hover:border-emerald-500 transition flex justify-between items-center'>
        <div><div class='text-xl text-white font-black'>{$r['name']}</div><div class='text-xs text-slate-500 mt-1'>{$r['description']}</div></div>
        <span class='text-emerald-500'>→</span></a>";
    }
    $content .= "</div>";
    file_put_contents("$docsDir/sector/$slug.html", $buildLayout($urlPrefix, "$sec Pack", $content, $manifest));
}

// 8. Generate INDIVIDUAL RECIPES
foreach ($manifest['recipes'] as $r) {
    $slug = str_replace(['/', '.'], '-', strtolower($r['id']));
    $slug = str_replace('/', '-', $r['id']);
    
    $crumbListHtml = "";
    foreach ($r['crumbs'] as $cId) {
        $crumbListHtml .= "
        <div class='flex items-center gap-6 p-6 bg-slate-900/80 border border-slate-800 rounded-2xl mb-4'>
            <div class='text-2xl'>🧩</div>
            <div class='flex-1'>
                <div class='text-white font-bold font-mono'>$cId</div>
                <div class='text-[10px] text-slate-500 uppercase tracking-widest'>Required Sequence Layer</div>
            </div>
            <a href='{$urlPrefix}/crumbs/" . str_replace(['/','.'], '-', $cId) . ".html' class='text-sky-500 text-xs font-bold hover:underline'>View Docs →</a>
        </div>";
        if ($cId !== end($r['crumbs'])) {
            $crumbListHtml .= "<div class='h-8 w-px bg-slate-800 ml-12 my-2'></div>";
        }
    }
    $recipeId = $r['id'];
    $sector   = $r['sector'] ?? 'General';
    $sectorLink = strtolower($sector);
    // 1. The Breadcrumb Component (High-Signal UI)
    $breadcrumbHtml = "
    <nav class='flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] mb-8'>
        <a href='{$urlPrefix}/index.html' class='text-slate-600 hover:text-sky-500 transition'>Archive</a>
        <span class='text-slate-800'>/</span>
        <a href='{$urlPrefix}/sector/$sectorLink.html' class='text-slate-600 hover:text-emerald-500 transition'>$sector</a>
    </nav>";

    // 1. Build the PHP snippet for the clipboard
    $crumbClasses = array_map(function($cId) {
        // Basic class name guesser (e.g., geo_anchor -> GeoAnchorCrumb)
        $name = str_replace(['_', '-'], ' ', $cId);
        $name = str_replace(' ', '', ucwords($name));
        return "new {$name}Crumb()";
    }, $r['crumbs']);

    $phpSnippet = "\$api = (new ApiCrumbs())\n    ->withCrumbs([\n        " . implode(",\n        ", $crumbClasses) . "\n    ]);\n\necho \$api->stitch(\$targetId);";

    // 2. The "Copy Stitch" Component
    $copyButtonHtml = "
    <div class='bg-slate-900 border border-slate-800 rounded-3xl p-8 mb-12 relative group overflow-hidden'>
        <div class='flex justify-between items-center mb-6'>
            <div>
                <h4 class='text-white font-bold text-sm mb-1'>Stitch Implementation</h4>
                <p class='text-[10px] text-slate-500 uppercase tracking-widest'>Copy this logic into your PHP application</p>
            </div>
            <button onclick=\"copyToClipboard(this, 'stitch-code-{$r['id']}')\" 
                    class='bg-sky-600 hover:bg-sky-500 text-white px-4 py-2 rounded-xl text-[10px] font-black transition shadow-lg shadow-sky-900/20'>
                COPY CODE
            </button>
        </div>
        <pre id='stitch-code-{$r['id']}' class='text-xs text-sky-400 font-mono leading-relaxed bg-black/40 p-6 rounded-xl border border-slate-800/50'>" . htmlspecialchars($phpSnippet) . "</pre>
        
        <!-- Decorative background element -->
        <div class='absolute -right-4 -bottom-4 text-slate-800/20 text-8xl font-black italic select-none group-hover:text-sky-500/10 transition'>STITCH</div>
    </div>";

    $recipeDetail = "
    $breadcrumbHtml
    <h1 class='text-5xl font-black text-white mb-6 italic'>{$r['name']}</h1>
    <div class='bg-emerald-500/10 border border-emerald-500/20 p-6 rounded-2xl mb-12 text-emerald-400 text-sm'>
        <strong>Logic Flow:</strong> This recipe uses a Directed Acyclic Graph (DAG) to resolve dependencies in the order listed below.
    </div>
    <div class='max-w-2xl'>$crumbListHtml</div>

    $copyButtonHtml
    ";
    
    file_put_contents("$docsDir/recipes/{$slug}.html", $buildLayout($urlPrefix, $r['name'], $recipeDetail, $manifest));
}

// 9. Generate CRUMB PAGES
foreach ($manifest['crumbs'] as $p) {
    $slug = str_replace(['/', '.'], '-', strtolower($p['id']));
    $slug = str_replace('/', '-', $p['id']);
    $isPro = ($p['tier'] === 'pro');
    $classNameParts = explode('.', $p['name']);
    foreach ($classNameParts as &$classNamePart) {
        $classNamePart = ucwords($classNamePart);
    }
    $className = implode('', $classNameParts);
    $className = str_replace(' ', '', $className) . "Crumb";
    $className = str_replace('-', '', $className);
    $className = str_replace('.', '', $className);
    $namespace = "ApiCrumbs\Crumbs\\" . str_replace(' ', '', $p['category']);
    $requires_key = $p['requires_key'];
    $constructor = $requires_key ? '["'. $requires_key .'" => "API_KEY"]' : '';
    $requires_key_label = $requires_key ? $requires_key : 'NO';

    $crumbId = $p['id'];
    $category   = $p['category'] ?? 'General';
    $categoryLink = strtolower($category);

    // 1. The Breadcrumb Component (High-Signal UI)
    $breadcrumbHtml = "
    <nav class='flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] mb-8'>
        <a href='{$urlPrefix}/index.html' class='text-slate-600 hover:text-sky-500 transition'>Archive</a>
        <span class='text-emerald-800'>/</span>
        <a href='{$urlPrefix}/category/{$categoryLink}.html' class='text-slate-600 hover:text-emerald-500 transition'>$category</a>
    </nav>";

    $roi = $p['token_savings'] ?? '90';
     $badge =  "<span class='bg-sky-500/10 text-sky-500 border border-sky-500/20 px-4 py-1 rounded-full text-xs font-black'>CORE FREE</span>";
    
    $content = "
    $breadcrumbHtml 
    <div class='flex items-center gap-4 mb-8'>$badge <span class='text-black-500 font-mono text-sm'>{$p['id']}</span> <span  class='text-black-300 font-mono text-sm'>v{$p['version']}</span></div>
    <div class='flex justify-between items-start mb-12'>
        <div><h1 class='text-6xl font-black text-white italic tracking-tighter'>{$p['name']}</h1><p class='text-xl text-black-400 mt-4'>{$p['description']}</p></div>
        <div class='text-right'><div class='text-40px font-black text-emerald-500'>$roi%</div><div class='text-[10px] text-black-500 uppercase font-bold tracking-widest'>Token ROI</div></div>
    </div>

    <div class='grid md:grid-cols-1 gap-8 mb-12'>
        <!-- CLI Install with Copy -->
        <div class='bg-black border border-black-800 p-8 rounded-2xl relative group'>
            <button onclick=\"copyToClipboard(this, 'cli-{$slug}')\" class='absolute top-4 right-4 text-[10px] font-black text-black-500 hover:text-white uppercase tracking-widest transition'>Copy</button>
            <div class='text-sky-500 font-bold text-xs uppercase mb-4 tracking-widest'>CLI Installation</div>
            <code id='cli-{$slug}' class='text-lg text-sm text-emerald-400 font-mono'>php vendor/bin/crumb install {$p['id']}</code>
        </div>
        <div class='bg-black-900/50 border border-black-800 p-8 rounded-2xl'>
            <div class='text-black-500 font-bold text-xs uppercase mb-4 tracking-widest'>Class Reference</div>
            <div class='text-white font-mono text-sm'>{$namespace}\\{$className}</div>
        </div>
    </div>

    <!-- Implementation Example with Copy -->
    <div class='mb-12'>
        <h2 class='text-2xl font-black text-white mb-6 uppercase italic tracking-tight'>Implementation Example</h2>
        <div class='bg-[#0d1117] border border-black-800 rounded-2xl overflow-hidden relative'>
            <button onclick=\"copyToClipboard(this, 'code-{$slug}')\" class='absolute top-3 right-4 text-[10px] font-black text-black-500 hover:text-white uppercase tracking-widest transition'>Copy Code</button>
            <div class='bg-black-800/50 px-4 py-2 text-[10px] text-black-500 font-mono uppercase tracking-widest border-b border-black-800'>PHP 8.2+ Context Injection</div>
            <pre id='code-{$slug}' class='p-8 text-sm leading-relaxed overflow-x-auto'><code class='text-sky-300'>use</code> <code class='text-white'>ApiCrumbs\Framework\ApiCrumbs;</code>
<code class='text-sky-300'>use</code> <code class='text-white'>{$namespace}\\{$className};</code>

<code class='text-white'>\$crumbs = new ApiCrumbs();</code>
<code class='text-white'>\$crumbs->registerCrumb(new {$className}({$constructor}));</code>

<code class='text-sky-300'>echo</code> <code class='text-white'>\$crumbs->stitch('{$p['example_id']}');</code></pre>
        </div>
    </div>
    <div class='bg-black-900/30 border border-black-800 rounded-3xl p-10 font-mono text-sm'>
        <div class='flex justify-between border-b border-black-800 pb-4 mb-4'><span class='text-black-500'>ID</span><span class='text-white'>{$p['id']}</span></div>
        <div class='flex justify-between border-b border-black-800 pb-4 mb-4'><span class='text-black-500'>VERSION</span><span class='text-sky-400 text-right'>{$p['version']}</span></div>
        <div class='flex justify-between border-b border-black-800 pb-4 mb-4'><span class='text-black-500'>CLASS</span><span class='text-white text-right'>{$p['class']}</span></div>
        <div class='flex justify-between border-b border-black-800 pb-4 mb-4'><span class='text-black-500'>REQUIRES KEY</span><span class='text-sky-400 text-right'>{$requires_key_label}</span></div>
        <div class='flex justify-between'><span class='text-black-500'>CAPABILITIES</span><span class='text-white text-right'>{$p['capabilities']}</span></div>
    </div>";
    file_put_contents("$docsDir/crumbs/$slug.html", $buildLayout($urlPrefix, $p['name'], $content, $manifest));
}


// [Category Portals]
foreach ($categories as $cat => $crumbs) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $catContent = "<h1 class='text-5xl font-black text-white mb-12'>$cat <span class='text-sky-500'>Pack</span></h1><div class='grid gap-3'>";
    foreach ($crumbs as $p) {
        $slug = str_replace(['/', '.'], '-', strtolower($p['id']));
        $catContent .= "<a href='{$urlPrefix}/crumbs/{$slug}.html' class='p-6 bg-black-900 border border-black-800 rounded-2xl flex justify-between items-center hover:bg-black-800 transition'>
            <span class='font-bold text-white'>{$p['name']}<br/><span class='text-black-500 mt-2 font-mono text-xs'>{$p['capabilities']}</span></span>
            <span class='text-[9px] font-black border border-black-700 px-3 py-1 rounded-full uppercase'>" . strtoupper($p['tier']) . "</span>            
        </a>";
    }
    $catContent .= "</div>";
    file_put_contents("{$docsDir}/category/{$catSlug}.html", $buildLayout($urlPrefix, "$cat Portal", $catContent, $manifest));
}
echo "🚀 Foundry Docs Built Successfully.\n";
