<?php
// Модуль бэкапа и восстановления
header('Content-Type: application/json; charset=UTF-8');

$backupDir = __DIR__ . '/../data/backups';
if (!is_dir($backupDir) && !@mkdir($backupDir, 0755, true)) {
    echo json_encode(['error' => 'Не удалось создать директорию для бэкапов: ' . $backupDir]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$siteRoot = realpath(__DIR__ . '/..') ?: (__DIR__ . '/..');

// ============================================================
// GET /admin/backup — список бэкапов
// ============================================================
if ($method === 'GET' && !$action) {
    $files = glob($backupDir . '/*.zip') ?: [];
    $backups = [];
    foreach ($files as $f) {
        $name = basename($f);
        $size = @filesize($f) ?: 0;
        $backups[] = [
            'name' => $name,
            'size' => $size,
            'sizeHuman' => round($size / 1024 / 1024, 2) . ' MB',
            'date' => date('Y-m-d H:i:s', filemtime($f)),
        ];
    }
    usort($backups, fn($a, $b) => strcmp($b['date'], $a['date']));
    echo json_encode(['backups' => $backups]);
    exit;
}

// ============================================================
// POST /admin/backup?action=create — создать бэкап
// ============================================================
if ($method === 'POST' && $action === 'create') {
    $timestamp = date('Y-m-d_H-i-s');
    $backupName = "backup_{$timestamp}";
    $tempDir = sys_get_temp_dir() . '/' . $backupName;
    $zipPath = $backupDir . '/' . $backupName . '.zip';

    if (!@mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
        echo json_encode(['error' => 'Не удалось создать временную папку: ' . $tempDir]);
        exit;
    }

    // 1. Дамп базы данных
    try {
        $db = getDB();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sqlDump = "-- Космозайм Database Backup\n-- Created: $timestamp\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            if ($table === 'recover_your_data_info') continue;
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $create['Create Table'] . ";\n\n";

            $rows = $db->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            if ($rows) {
                $cols = array_keys($rows[0]);
                $colList = '`' . implode('`, `', $cols) . '`';
                foreach ($rows as $row) {
                    $vals = array_map(function($v) use ($db) {
                        return $v === null ? 'NULL' : $db->quote($v);
                    }, array_values($row));
                    $sqlDump .= "INSERT INTO `$table` ($colList) VALUES (" . implode(', ', $vals) . ");\n";
                }
                $sqlDump .= "\n";
            }
        }
        $sqlDump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents($tempDir . '/database.sql', $sqlDump);
    } catch (Exception $e) {
        deleteDir($tempDir);
        echo json_encode(['error' => 'Ошибка дампа БД: ' . $e->getMessage()]);
        exit;
    }

    // 2. Копируем важные файлы
    $filesToBackup = ['config.php', '.env', '.htaccess', 'index.php', 'favicon.svg'];
    $dirsToBackup = ['includes', 'pages', '_api', '_admin', 'data', 'cron', 'images'];

    foreach ($filesToBackup as $file) {
        $src = $siteRoot . '/' . $file;
        $dst = $tempDir . '/' . $file;
        if (file_exists($src)) {
            @mkdir(dirname($dst), 0755, true);
            @copy($src, $dst);
        }
    }

    $excludeDirs = [
        realpath($siteRoot . '/data/backups') ?: ($siteRoot . '/data/backups'),
        realpath($siteRoot . '/data/page_cache') ?: ($siteRoot . '/data/page_cache'),
        realpath($siteRoot . '/data/geo_cache') ?: ($siteRoot . '/data/geo_cache'),
        realpath($siteRoot . '/.git') ?: ($siteRoot . '/.git'),
        realpath($siteRoot . '/node_modules') ?: ($siteRoot . '/node_modules'),
        realpath($siteRoot . '/.next') ?: ($siteRoot . '/.next'),
    ];
    $excludeFilePatterns = ['*.log'];

    foreach ($dirsToBackup as $dir) {
        $src = $siteRoot . '/' . $dir;
        $dst = $tempDir . '/' . $dir;
        if (is_dir($src)) {
            copyDir($src, $dst, $excludeDirs, $excludeFilePatterns);
        }
    }

    // 3. Создаём ZIP (с fallback)
    $zipResult = createBackupZip($tempDir, $zipPath);
    if ($zipResult !== true) {
        deleteDir($tempDir);
        echo json_encode(['error' => 'Ошибка создания ZIP: ' . $zipResult]);
        exit;
    }

    // 4. Удаляем временную папку
    deleteDir($tempDir);

    $size = @filesize($zipPath) ?: 0;
    echo json_encode([
        'success' => true,
        'backup' => $backupName . '.zip',
        'size' => round($size / 1024 / 1024, 2) . ' MB'
    ]);
    exit;
}

// ============================================================
// GET /admin/backup?action=download&name=xxx — скачать бэкап
// ============================================================
if ($method === 'GET' && $action === 'download') {
    $name = basename($_GET['name'] ?? '');
    $file = $backupDir . '/' . $name;

    if (!$name || !file_exists($file) || !str_ends_with($name, '.zip')) {
        http_response_code(404);
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($file));
    readfile($file);
    exit;
}

// ============================================================
// POST /admin/backup?action=restore&name=xxx — восстановить из бэкапа
// ============================================================
if ($method === 'POST' && $action === 'restore') {
    $name = basename($_GET['name'] ?? '');
    $file = $backupDir . '/' . $name;

    if (!$name || !file_exists($file) || !str_ends_with($name, '.zip')) {
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    if (!class_exists('ZipArchive')) {
        echo json_encode(['error' => 'Восстановление требует расширение ZipArchive на сервере']);
        exit;
    }

    $tempDir = sys_get_temp_dir() . '/restore_' . time();
    @mkdir($tempDir, 0755, true);

    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        echo json_encode(['error' => 'Cannot open ZIP']);
        exit;
    }
    $zip->extractTo($tempDir);
    $zip->close();

    $errors = [];

    // 1. Восстанавливаем БД
    $sqlFile = $tempDir . '/database.sql';
    if (file_exists($sqlFile)) {
        try {
            $sql = file_get_contents($sqlFile);
            $db = getDB();
            $db->exec($sql);
        } catch (Exception $e) {
            $errors[] = 'DB restore error: ' . $e->getMessage();
        }
    }

    // 2. Восстанавливаем файлы (кроме .env и config.php)
    $safeFiles = ['index.php', 'favicon.svg', '.htaccess'];
    foreach ($safeFiles as $f) {
        $src = $tempDir . '/' . $f;
        $dst = $siteRoot . '/' . $f;
        if (file_exists($src)) @copy($src, $dst);
    }

    foreach (['includes', 'pages', '_api', 'cron', '_admin'] as $dir) {
        $src = $tempDir . '/' . $dir;
        $dst = $siteRoot . '/' . $dir;
        if (is_dir($src)) copyDir($src, $dst, [], []);
    }

    $imgSrc = $tempDir . '/images';
    $imgDst = $siteRoot . '/images';
    if (is_dir($imgSrc)) restoreImagesDir($imgSrc, $imgDst);

    deleteDir($tempDir);

    require_once __DIR__ . '/../includes/page-cache.php';
    pageCacheClear();

    if ($errors) echo json_encode(['success' => true, 'warnings' => $errors]);
    else echo json_encode(['success' => true, 'message' => 'Восстановление завершено']);
    exit;
}

// ============================================================
// DELETE /admin/backup?name=xxx — удалить бэкап
// ============================================================
if ($method === 'DELETE') {
    $name = basename($_GET['name'] ?? '');
    $file = $backupDir . '/' . $name;

    if (!$name || !file_exists($file) || !str_ends_with($name, '.zip')) {
        echo json_encode(['error' => 'Backup not found']);
        exit;
    }

    @unlink($file);
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['error' => 'Unknown action']);

// ============================================================
// Helper functions
// ============================================================
function copyDir($src, $dst, array $excludeDirs = [], array $excludeFilePatterns = []) {
    $realSrc = realpath($src) ?: $src;
    foreach ($excludeDirs as $excluded) {
        if ($excluded && str_starts_with((string)($realSrc), $excluded)) return;
    }

    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        $realPath = realpath($srcPath) ?: $srcPath;

        foreach ($excludeDirs as $excluded) {
            if ($excluded && str_starts_with((string)($realPath), $excluded)) continue 2;
        }

        if (!is_dir($srcPath)) {
            foreach ($excludeFilePatterns as $pattern) {
                if (fnmatch($pattern, basename($srcPath))) continue 2;
            }
        }

        if (is_dir($srcPath)) copyDir($srcPath, $dstPath, $excludeDirs, $excludeFilePatterns);
        else @copy($srcPath, $dstPath);
    }
}

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) deleteDir($path);
        else @unlink($path);
    }
    @rmdir($dir);
}

function addDirToZip($zip, $dir, $zipPath) {
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $filePath = $dir . '/' . $file;
        $zipFilePath = $zipPath ? $zipPath . '/' . $file : $file;
        if (is_dir($filePath)) {
            $zip->addEmptyDir($zipFilePath);
            addDirToZip($zip, $filePath, $zipFilePath);
        } else {
            $zip->addFile($filePath, $zipFilePath);
        }
    }
}

function createBackupZip(string $sourceDir, string $zipPath) {
    // 1. Стандартный ZipArchive
    if (class_exists('ZipArchive')) {
        $zip = new ZipArchive();
        $opened = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened === true) {
            addDirToZip($zip, $sourceDir, '');
            $zip->close();
            if (file_exists($zipPath)) return true;
        }
    }

    // 2. Fallback: системная zip команда
    $zipBinary = trim((string)@shell_exec('command -v zip 2>/dev/null'));
    if ($zipBinary) {
        $cwd = getcwd();
        chdir($sourceDir);
        $cmd = escapeshellcmd($zipBinary) . ' -rq ' . escapeshellarg($zipPath) . ' .';
        @exec($cmd, $out, $code);
        chdir($cwd);
        if ($code === 0 && file_exists($zipPath)) return true;
        return 'системная команда zip завершилась с кодом ' . $code;
    }

    return 'на сервере недоступны ни ZipArchive, ни команда zip';
}

function restoreImagesDir($src, $dst) {
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        if (is_dir($srcPath)) {
            restoreImagesDir($srcPath, $dstPath);
        } elseif (!file_exists($dstPath)) {
            @copy($srcPath, $dstPath);
        }
    }
}
