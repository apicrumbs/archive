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
// 1. Get Command Line Arguments
$urlPrefix = '';

if (isset($argv[1])) {
    parse_str($argv[1], $arg);

    if (isset($arg['url'])) {
        $urlPrefix = $arg['url'];
    }
}
// 2. Load Archive Manifest
$manifestPath = __DIR__ . '/../manifest.json';
if (!file_exists($manifestPath)) {
    die("❌ Error: manifest.json missing at $manifestPath\n");
}

$manifest = json_decode(file_get_contents($manifestPath), true);
$docsDir = __DIR__ . '/../docs';

// 3. Setup Directory Structure
$dirs = ['/crumbs', '/category'];
foreach ($dirs as $dir) {
    if (!is_dir($docsDir . $dir)) mkdir($docsDir . $dir, 0755, true);
}

// 4. Group Data for Sidebar
$categories = [];
foreach ($manifest['crumbs'] as $p) {
    $categories[$p['category']][] = $p;
}
ksort($categories);

// 5. Pre-generate Sidebar HTML (Initial State)
$sidebarHtml = "<div class='mb-8 cat-group'><a href='{$urlPrefix}/recipes.html' class='text-[10px] font-black text-sky-500 uppercase tracking-widest mb-4 block'>Recipes</a></div>";
foreach ($categories as $cat => $crumbs) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $sidebarHtml .= "<div class='mb-8 cat-group'><a href='{$urlPrefix}/category/{$catSlug}.html' class='text-[10px] font-black text-sky-500 uppercase tracking-widest mb-4 block'>$cat</a>";
    foreach ($crumbs as $p) {
        $slug = str_replace(['/', '.'], '-', strtolower($p['id']));
        $lock = ($p['tier'] === 'pro') ? "🔒 " : "🧩 ";
        $sidebarHtml .= "<a href='{$urlPrefix}/crumbs/{$slug}.html' class='block py-1.5 text-sm text-black-400 hover:text-sky-400 transition truncate' title='{$p['name']}'>$lock{$p['name']}</a>";
    }
    $sidebarHtml .= "</div>";
}

// 6. Master Layout with Manifest-Driven JS Search
$buildLayout = function($urlPrefix, $title, $content, $manifest) use ($sidebarHtml) {
    $manifestJson = json_encode($manifest);
    $updated = date('Y-m-d');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><title>$title | ApiCrumbs</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background: #000; color: #94a3b8; scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-thumb { background: #1e293b; border-radius: 10px; }
    </style>
</head>
<body class="flex">
    <!-- Sidebar -->
    <nav class="w-80 h-screen sticky top-0 border-r border-black-800 p-8 overflow-y-auto bg-[#000000]">
        <a href="{$urlPrefix}/" class="mb-10 block flex items-center gap-3">
            <div class="w-10 h-10 bg-sky-600 rounded-lg flex items-center justify-center font-black text-white italic text-xl shadow-lg shadow-sky-500/20">C</div>
            <div class="flex flex-col leading-none">
                <span class="text-white font-black text-2xl tracking-tighter italic">ApiCrumbs</span>
                <span class="text-[8px] text-black-500 font-bold uppercase tracking-[0.4em] mt-1">Archive Foundry</span>
            </div>
        </a>
       
        <div class="relative mb-8">
            <input type="text" id="cSearch" placeholder="Search 10,000 crumbs..." class="w-full bg-black-900 border border-black-800 rounded-xl p-3 text-xs text-white outline-none focus:ring-1 ring-sky-500 transition">
        </div>
        
        <div id="sLinks">$sidebarHtml</div>
    </nav>
    
    <main class="flex-1 min-h-screen flex flex-col">
        <header class="border-b border-black-800 px-12 py-4 flex justify-between items-center text-[10px] font-mono tracking-widest uppercase">
            <span>UPDATED: <span class="text-white">$updated</span></span>
            <div class="font-black text-sky-500">✅ COMPLIANT</div>
        </header>
        <div class="p-16 max-w-5xl flex-1">$content</div>
    </main>

    <script>
        const archive = $manifestJson;
        const urlPrefix = '{$urlPrefix}';
        const sidebar = document.getElementById('sLinks');
        const originalHtml = sidebar.innerHTML;

        document.getElementById('cSearch').addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            if (!query) { sidebar.innerHTML = originalHtml; return; }

            // Deep search across ID, Name, Category, and Capabilities
            const results = archive.crumbs.filter(c => 
                c.name.toLowerCase().includes(query) ||
                c.id.toLowerCase().includes(query) ||
                c.category.toLowerCase().includes(query) ||
                (c.capabilities && c.capabilities.toLowerCase().includes(query))
            );

            if (results.length > 0) {
                let html = '<div class="text-[10px] font-black text-sky-500 uppercase tracking-widest mb-4">Results</div>';
                results.forEach(p => {
                    const slug = p.id.replace(/[\/\.]/g, '-').toLowerCase();
                    const lock = (p.tier === 'pro') ? "🔒 " : "🧩 ";
                    html += `<a href="\${urlPrefix}/crumbs/\${slug}.html" class="block py-1.5 text-sm text-black-300 hover:text-sky-400 truncate">\${lock}\${p.name}</a>`;
                });
                sidebar.innerHTML = html;
            } else {
                sidebar.innerHTML = '<div class="text-xs text-black-600 italic">No matches found.</div>';
            }
        });
    </script>
    <script>
       
        // Copy to Clipboard Utility
        async function copyToClipboard(button, textId) {
            const text = document.getElementById(textId).innerText;
            try {
                await navigator.clipboard.writeText(text);
                const original = button.innerHTML;
                button.innerHTML = "COPIED!";
                button.classList.add('copy-success');
                setTimeout(() => {
                    button.innerHTML = original;
                    button.classList.remove('copy-success');
                }, 2000);
            } catch (err) {
                console.error('Failed to copy: ', err);
            }
        }
    </script>
</body></html>
HTML;
};

// 7. Generation Loops (Index, Category, Crumb)
// [Home Index]
$homeContent = "<h1 class='text-7xl font-black text-white mb-8 italic tracking-tighter'>DATA<br><span class='text-sky-500'>REGISTRY.</span></h1>";
$homeContent .= "<div class='grid md:grid-cols-2 gap-6 mt-12'>";
foreach ($categories as $cat => $crumbs) {
    $catSlug = strtolower(str_replace(' ', '-', $cat));
    $homeContent .= "<a href='{$urlPrefix}/category/{$catSlug}.html' class='p-8 rounded-3xl bg-black-900/40 border border-black-800 hover:border-sky-500 transition group'>
        <div class='text-3xl font-black text-white group-hover:text-sky-400'>$cat</div>
        <div class='text-black-500 mt-2 font-mono text-xs'>" . count($crumbs) . " Verified Crumbs →</div>
    </a>";
}
$homeContent .= "</div>";
file_put_contents("{$docsDir}/index.html", $buildLayout($urlPrefix, "Global Archive", $homeContent, $manifest));

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

// [Crumb Detail Pages]
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

    // 1. The Breadcrumb Component (High-Signal UI)
    $breadcrumbHtml = "
    <nav class='flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] mb-8'>
        <a href='{$urlPrefix}/index.html' class='text-slate-600 hover:text-sky-500 transition'>Archive</a>
        <span class='text-slate-800'>/</span>
        <a href='{$urlPrefix}/recipes.html' class='text-slate-600 hover:text-emerald-500 transition'>Recipe Book</a>
        <span class='text-slate-800'>/</span>
        <span class='text-emerald-500'>$category</span>
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

    <div class='grid md:grid-cols-2 gap-8 mb-12'>
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
    file_put_contents("{$docsDir}/crumbs/{$slug}.html", $buildLayout($urlPrefix, $p['name'], $content, $manifest));
}

// 1. Load Recipe Manifest
//$recipesPath = __DIR__ . '/../recipes.json';
//$recipes = file_exists($recipesPath) ? json_decode(file_get_contents($recipesPath), true)['recipes'] : [];

$recipes = $manifest['recipes'];
// 2. Ensure Recipe Directory
//if (!is_dir($docsDir . '/recipes')) mkdir($docsDir . '/recipes', 0755, true);

// 3. Generate Recipe Index Content
$recipeIndexContent = "
<div class='mb-16'>
    <h1 class='text-7xl font-black text-white italic tracking-tighter mb-4'>STITCH<br><span class='text-emerald-500'>RECIPES.</span></h1>
    <p class='text-slate-400 text-xl'>Proven combinations of Crumbs for complex industry grounding.</p>
</div>
<div class='grid gap-6'>";

foreach ($recipes as $r) {
    $recipeId = $r['id'];
    $recipeIndexContent .= "
    <a href='{$urlPrefix}/recipes/{$recipeId}.html' class='p-10 bg-slate-900/40 border border-slate-800 rounded-3xl hover:border-emerald-500 transition group'>
        <div class='flex justify-between items-start'>
            <div>
                <div class='text-[10px] font-black text-emerald-500 uppercase tracking-widest mb-4'>{$r['sector']} • {$r['difficulty']}</div>
                <h3 class='text-3xl font-black text-white group-hover:text-emerald-400'>{$r['name']}</h3>
                <p class='text-slate-500 mt-4 max-w-xl'>{$r['description']}</p>
            </div>
            <div class='flex -space-x-3'>";
            // Visual "Stack" of Crumbs
            foreach (array_slice($r['crumbs'], 0, 3) as $crumbId) {
                $recipeIndexContent .= "<div class='w-12 h-12 rounded-xl bg-slate-800 border-2 border-[#0b0e14] flex items-center justify-center text-xs font-bold text-sky-500 shadow-xl'>🧩</div>";
            }
    $recipeIndexContent .= "</div></div></a>";
    
    // 4. Generate Individual Recipe Pages
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

    // 1. The Breadcrumb Component (High-Signal UI)
    $breadcrumbHtml = "
    <nav class='flex items-center gap-2 text-[10px] font-bold uppercase tracking-[0.2em] mb-8'>
        <a href='{$urlPrefix}/index.html' class='text-slate-600 hover:text-sky-500 transition'>Archive</a>
        <span class='text-slate-800'>/</span>
        <a href='{$urlPrefix}/recipes.html' class='text-slate-600 hover:text-emerald-500 transition'>Recipe Book</a>
        <span class='text-slate-800'>/</span>
        <span class='text-emerald-500'>$sector</span>
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
    
    file_put_contents("{$docsDir}/recipes/{$recipeId}.html", $buildLayout($urlPrefix, $r['name'], $recipeDetail, $manifest));
}

$recipeIndexContent .= "</div>";
file_put_contents("{$docsDir}/recipes.html", $buildLayout($urlPrefix, "Recipe Book", $recipeIndexContent, $manifest));

echo "✅ Build Complete: Wikipedia of Context Ready.\n";
