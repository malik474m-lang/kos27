<?php
// Медиа-менеджер: загрузка и просмотр картинок
// GET  /api/admin/media?dir=offer  — список файлов
// POST /api/admin/media           — загрузка файла
header('Content-Type: application/json; charset=UTF-8');

$db = getDB();
$method = $_SERVER['REQUEST_METHOD'];
$baseDir = realpath(__DIR__ . '/../../images') ?: (__DIR__ . '/../../images');

$allowedDirs = [
    'offer' => $baseDir . '/offer',
    'articles' => $baseDir . '/articles',
];

$maxFileSize = 5 * 1024 * 1024; // 5MB
$allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml', 'image/webp', 'image/gif'];
$allowedExts = ['jpg', 'jpeg', 'png', 'svg', 'webp', 'gif'];

// ============================================================
// GET — список файлов в папке
// ============================================================
if ($method === 'GET') {
    $dir = $_GET['dir'] ?? '';
    
    if (!isset($allowedDirs[$dir])) {
        echo json_encode(['error' => 'Неверная папка. Доступные: ' . implode(', ', array_keys($allowedDirs))]);
        exit;
    }
    
    $path = $allowedDirs[$dir];
    if (!is_dir($path)) {
        @mkdir($path, 0755, true);
    }
    
    $files = [];
    $entries = scandir($path);
    foreach ($entries as $f) {
        if ($f === '.' || $f === '..') continue;
        $fullPath = $path . '/' . $f;
        if (!is_file($fullPath)) continue;
        
        $ext = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) continue;
        
        $files[] = [
            'name' => $f,
            'url' => '/images/' . $dir . '/' . $f,
            'size' => filesize($fullPath),
            'sizeHuman' => round(filesize($fullPath) / 1024, 1) . ' KB',
            'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
            'ext' => $ext,
        ];
    }
    
    // Сортируем по дате (новые сверху)
    usort($files, fn($a, $b) => strcmp($b['modified'], $a['modified']));
    
    echo json_encode(['files' => $files, 'dir' => $dir, 'path' => '/images/' . $dir . '/']);
    exit;
}

// ============================================================
// POST — загрузка файла
// ============================================================
if ($method === 'POST') {
    $dir = $_POST['dir'] ?? '';
    
    if (!isset($allowedDirs[$dir])) {
        echo json_encode(['error' => 'Неверная папка']);
        exit;
    }
    
    if (empty($_FILES['file'])) {
        echo json_encode(['error' => 'Файл не передан']);
        exit;
    }
    
    $file = $_FILES['file'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'Файл слишком большой (лимит сервера)',
            UPLOAD_ERR_FORM_SIZE => 'Файл слишком большой',
            UPLOAD_ERR_PARTIAL => 'Файл загружен частично',
            UPLOAD_ERR_NO_FILE => 'Файл не выбран',
        ];
        echo json_encode(['error' => $errors[$file['error']] ?? 'Ошибка загрузки']);
        exit;
    }
    
    if ($file['size'] > $maxFileSize) {
        echo json_encode(['error' => 'Файл слишком большой (макс. 5MB)']);
        exit;
    }
    
    $mimeType = mime_content_type($file['tmp_name']) ?: $file['type'];
    if (!in_array($mimeType, $allowedTypes)) {
        echo json_encode(['error' => 'Неподдерживаемый формат: ' . $mimeType . '. Разрешены: JPG, PNG, SVG, WebP, GIF']);
        exit;
    }
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExts)) {
        echo json_encode(['error' => 'Неподдерживаемое расширение: .' . $ext]);
        exit;
    }
    
    // Имя файла: очищаем или генерируем
    $customName = trim($_POST['customName'] ?? '');
    if ($customName) {
        // Очищаем пользовательское имя
        $customName = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ._-]/u', '-', $customName);
        $customName = preg_replace('/-+/', '-', $customName);
        $customName = trim($customName, '-');
        if (!pathinfo($customName, PATHINFO_EXTENSION)) {
            $customName .= '.' . $ext;
        }
        $fileName = $customName;
    } else {
        // Оригинальное имя, очищенное
        $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
        $baseName = preg_replace('/[^a-zA-Z0-9а-яА-ЯёЁ._-]/u', '-', $baseName);
        $baseName = preg_replace('/-+/', '-', trim($baseName, '-'));
        $fileName = $baseName . '.' . $ext;
    }
    
    $targetDir = $allowedDirs[$dir];
    if (!is_dir($targetDir)) @mkdir($targetDir, 0755, true);
    
    $targetPath = $targetDir . '/' . $fileName;
    
    // Если файл существует — добавляем суффикс
    if (file_exists($targetPath)) {
        $base = pathinfo($fileName, PATHINFO_FILENAME);
        $cnt = 1;
        while (file_exists($targetDir . '/' . $base . '-' . $cnt . '.' . $ext)) $cnt++;
        $fileName = $base . '-' . $cnt . '.' . $ext;
        $targetPath = $targetDir . '/' . $fileName;
    }
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        echo json_encode(['error' => 'Не удалось сохранить файл']);
        exit;
    }
    
    $url = '/images/' . $dir . '/' . $fileName;
    
    echo json_encode([
        'success' => true,
        'url' => $url,
        'name' => $fileName,
        'size' => filesize($targetPath),
        'sizeHuman' => round(filesize($targetPath) / 1024, 1) . ' KB',
    ]);
    exit;
}

// ============================================================
// DELETE — удаление файла
// ============================================================
if ($method === 'DELETE') {
    $data = json_decode(file_get_contents('php://input'), true);
    $dir = $data['dir'] ?? '';
    $name = $data['name'] ?? '';
    
    if (!isset($allowedDirs[$dir]) || !$name) {
        echo json_encode(['error' => 'Неверные параметры']);
        exit;
    }
    
    $name = basename($name); // Защита от path traversal
    $filePath = $allowedDirs[$dir] . '/' . $name;
    
    if (file_exists($filePath) && is_file($filePath)) {
        @unlink($filePath);
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Файл не найден']);
    }
    exit;
}

echo json_encode(['error' => 'Method not allowed']);
