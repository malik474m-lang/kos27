<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/plain; charset=UTF-8');

$config = __DIR__ . '/../config.php';
if (!file_exists($config)) {
    $config = __DIR__ . '/config.php';
}
if (!file_exists($config)) {
    exit("config.php not found\n");
}

$content = file_get_contents($config);
if (strpos($content, "function str_starts_with") !== false) {
    exit("polyfills already installed\n");
}

$needle = "ini_set('default_charset', 'UTF-8');\n";
$poly = $needle . "\n// PHP 7.4 compatibility polyfills\nif (!function_exists('str_contains')) {\n    function str_contains($haystack, $needle) {\n        return $needle !== '' && mb_strpos((string)$haystack, (string)$needle) !== false;\n    }\n}\nif (!function_exists('str_starts_with')) {\n    function str_starts_with($haystack, $needle) {\n        return $needle === '' || strncmp((string)$haystack, (string)$needle, strlen((string)$needle)) === 0;\n    }\n}\nif (!function_exists('str_ends_with')) {\n    function str_ends_with($haystack, $needle) {\n        if ($needle === '') return true;\n        $haystack = (string)$haystack;\n        $needle = (string)$needle;\n        return substr($haystack, -strlen($needle)) === $needle;\n    }\n}\n";

if (strpos($content, $needle) === false) {
    exit("needle not found in config.php\n");
}

$content = str_replace($needle, $poly, $content, $count);
file_put_contents($config, $content);

echo "patched: $config\n";
