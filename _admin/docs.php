<?php
require_once __DIR__ . '/../config.php';
requireAdmin();

$docFile = $_GET['file'] ?? 'README.md';
$docFile = basename($docFile);
$docPath = __DIR__ . '/../docs/' . $docFile;

$content = '';
$title = 'Документация';

if (file_exists($docPath)) {
    $content = file_get_contents($docPath);
    $content = str_replace("\xEF\xBB\xBF", '', $content);
    if (preg_match('/^#\s+(.+)$/m', $content, $m)) { $title = $m[1]; }
} else {
    $content = "Файл $docFile не найден.";
}

$files = glob(__DIR__ . '/../docs/*.{md,txt}', GLOB_BRACE);
$fileList = [];
if($files) {
    foreach ($files as $f) {
        $name = basename($f);
        if ($name === '.htaccess') continue;
        $fileList[] = $name;
    }
}
sort($fileList);

require_once __DIR__ . '/../includes/minify.php';
ob_start('minifyHtmlOutput');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> — База знаний</title>
    <link rel="stylesheet" href="/assets/tailwind.css?v=20260801">
    <style>
        body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}
        .prose h1 { font-size: 2rem; font-weight: 800; margin-bottom: 1.5rem; color: #111827; border-bottom: 2px solid #e5e7eb; padding-bottom: 0.5rem; }
        .prose h2 { font-size: 1.5rem; font-weight: 700; margin-top: 2rem; margin-bottom: 1rem; color: #1f2937; }
        .prose h3 { font-size: 1.25rem; font-weight: 600; margin-top: 1.5rem; margin-bottom: 0.75rem; color: #374151; }
        .prose p { margin-bottom: 1rem; line-height: 1.6; color: #4b5563; }
        .prose ul { list-style-type: disc; padding-left: 1.5rem; margin-bottom: 1rem; }
        .prose li { margin-bottom: 0.5rem; }
        .prose code { background: #f3f4f6; padding: 0.2rem 0.4rem; border-radius: 0.25rem; font-family: monospace; font-size: 0.9em; color: #eb5757; }
        .prose pre { background: #1f2937; color: #f9fafb; padding: 1rem; border-radius: 0.5rem; overflow-x: auto; margin-bottom: 1rem; font-size: 0.875rem; line-height: 1.5; }
        .prose pre code { background: transparent; color: inherit; padding: 0; }
        .prose a { color: #2563eb; text-decoration: underline; }
        .active-doc { background: #eff6ff; color: #1d4ed8; border-left: 4px solid #2563eb; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="bg-gray-900 text-white">
    <div class="max-w-7xl mx-auto px-4 py-4 flex items-center justify-between">
        <div class="flex items-center space-x-3">
            <span class="text-2xl">📚</span>
            <h1 class="text-lg font-bold">База знаний проекта</h1>
        </div>
        <div class="flex items-center space-x-4">
            <a href="/admin" class="text-gray-300 hover:text-white text-sm">← В админку</a>
            <a href="/admin/about" class="text-gray-300 hover:text-white text-sm">О системе</a>
        </div>
    </div>
</div>

<div class="max-w-7xl mx-auto px-4 py-8 flex flex-col md:flex-row gap-8">
    <aside class="w-full md:w-64 flex-shrink-0">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden sticky top-8">
            <div class="p-4 border-b border-gray-100 bg-gray-50">
                <h3 class="font-bold text-gray-900 text-sm uppercase tracking-wider">Документы</h3>
            </div>
            <nav class="p-2 space-y-1">
                <?php foreach ($fileList as $f): ?>
                <a href="/admin/docs?file=<?= urlencode($f) ?>" class="block px-3 py-2 rounded-lg text-sm transition-colors <?= $f === $docFile ? 'active-doc font-bold' : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900' ?>">
                    <?= e(str_replace(['.md', '.txt'], '', $f)) ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </aside>

    <main class="flex-1 min-w-0">
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm p-6 sm:p-10">
            <article class="prose max-w-none">
                <?php
                $html = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');
                $html = preg_replace('/^# (.+)$/m', '<h1>$1</h1>', $html);
                $html = preg_replace('/^## (.+)$/m', '<h2>$1</h2>', $html);
                $html = preg_replace('/^### (.+)$/m', '<h3>$1</h3>', $html);
                $html = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $html);
                $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);
                $html = preg_replace_callback('/```(\w*)\n([\s\S]+?)\n```/', function($m) {
                    return '<pre><code>' . $m[2] . '</code></pre>';
                }, $html);
                $html = preg_replace_callback('/((?:^- .+(?:\n|$))+)/m', function($m) {
                    $items = preg_replace('/^- (.+)$/m', '<li>$1</li>', $m[1]);
                    return '<ul>' . $items . '</ul>';
                }, $html);
                $html = preg_replace('/\[(.+?)\]\(\.\/(.+?)\)/', '<a href="/admin/docs?file=$2">$1</a>', $html);
                $lines = explode("\n", $html);
                $processed = '';
                foreach ($lines as $line) {
                    if (trim($line) === '') continue;
                    if (str_starts_with(trim($line), '<')) {
                        $processed .= $line . "\n";
                    } else {
                        $processed .= "<p>$line</p>\n";
                    }
                }
                echo $processed;
                ?>
            </article>
        </div>
    </main>
</div>

</body>
</html>
