<?php
// Модуль бэкапа и восстановления
// Доступ только для авторизованных админов (проверка в router.php)

header('Content-Type: application/json; charset=UTF-8');

$backupDir = __DIR__ . '/../data/backups';
if (!is_dir($backupDir)) @mkdir($backupDir, 0755, true);

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ============================================================
// GET /admin/backup — список бэкапов
// ============================================================
if ($method === 'GET' && !$action) {
    $files = glob($backupDir . '/*.zip');
    $backups = [];
    foreach ($files as $f) {
        $name = basename($f);
        $backups[] = [
            'name' => $name,
            'size' => filesize($f),
            'sizeHuman' => round(filesize($f) / 1024 / 1024, 2) . ' MB',
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
    
    @mkdir($tempDir, 0755, true);
    
    // 1. Дамп базы данных
    try {
        $db = getDB();
        $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        $sqlDump = "-- Космозайм Database Backup\n-- Created: $timestamp\n\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        
        foreach ($tables as $table) {
            if ($table === 'recover_your_data_info') continue; // системная таблица хостинга
            
            // Структура
            $create = $db->query("SHOW CREATE TABLE `$table`")->fetch();
            $sqlDump .= "DROP TABLE IF EXISTS `$table`;\n";
            $sqlDump .= $create['Create Table'] . ";\n\n";
            
            // Данные
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
        echo json_encode(['error' => 'DB dump failed: ' . $e->getMessage()]);
        exit;
    }
    
    // 2. Копируем важные файлы
    $filesToBackup = [
        'config.php',
        '.env',
        '.htaccess',
        'index.php',
        'favicon.svg',
    ];
    $dirsToBackup = [
        'includes',
        'pages',
        '_api',
        '_admin',
        'data',
        'cron',
        'images',
    ];
    
    $siteRoot = __DIR__ . '/..';
    
    foreach ($filesToBackup as $file) {
        $src = $siteRoot . '/' . $file;
        if (file_exists($src)) {
            copy($src, $tempDir . '/' . $file);
        }
    }
    
    foreach ($dirsToBackup as $dir) {
        $src = $siteRoot . '/' . $dir;
        if (is_dir($src)) {
            copyDir($src, $tempDir . '/' . $dir);
        }
    }
    
    // 3. Создаём ZIP
    $zip = new ZipArchive();
    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        echo json_encode(['error' => 'Cannot create ZIP']);
        exit;
    }
    
    addDirToZip($zip, $tempDir, '');
    $zip->close();
    
    // 4. Удаляем временную папку
    deleteDir($tempDir);
    
    $size = filesize($zipPath);
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
    
    $tempDir = sys_get_temp_dir() . '/restore_' . time();
    @mkdir($tempDir, 0755, true);
    
    // Распаковываем
    $zip = new ZipArchive();
    if ($zip->open($file) !== true) {
        echo json_encode(['error' => 'Cannot open ZIP']);
        exit;
    }
    $zip->extractTo($tempDir);
    $zip->close();
    
    $siteRoot = __DIR__ . '/..';
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
    
    // 2. Восстанавливаем файлы (кроме .env и config.php — опасно)
    $safeDirs = ['includes', 'pages', '_api', 'cron', 'images', 'data/cities.php', 'data/glossary.php', 'data/loan-types.php'];
    $safeFiles = ['index.php', 'favicon.svg', '.htaccess'];
    
    foreach ($safeFiles as $f) {
        $src = $tempDir . '/' . $f;
        $dst = $siteRoot . '/' . $f;
        if (file_exists($src)) {
            @copy($src, $dst);
        }
    }
    
    foreach (['includes', 'pages', '_api', 'cron'] as $dir) {
        $src = $tempDir . '/' . $dir;
        $dst = $siteRoot . '/' . $dir;
        if (is_dir($src)) {
            copyDir($src, $dst);
        }
    }
    
    // images — только если нет файла
    $imgSrc = $tempDir . '/images';
    $imgDst = $siteRoot . '/images';
    if (is_dir($imgSrc)) {
        restoreImagesDir($imgSrc, $imgDst);
    }
    
    deleteDir($tempDir);
    
    // Очищаем кэш
    require_once __DIR__ . '/../includes/page-cache.php';
    pageCacheClear();
    
    if ($errors) {
        echo json_encode(['success' => true, 'warnings' => $errors]);
    } else {
        echo json_encode(['success' => true, 'message' => 'Восстановление завершено']);
    }
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
function copyDir($src, $dst) {
    if (!is_dir($dst)) @mkdir($dst, 0755, true);
    $files = scandir($src);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $srcPath = $src . '/' . $file;
        $dstPath = $dst . '/' . $file;
        if (is_dir($srcPath)) {
            copyDir($srcPath, $dstPath);
        } else {
            @copy($srcPath, $dstPath);
        }
    }
}

function deleteDir($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            deleteDir($path);
        } else {
            @unlink($path);
        }
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
            // Восстанавливаем только отсутствующие файлы
            @copy($srcPath, $dstPath);
        }
    }
}
