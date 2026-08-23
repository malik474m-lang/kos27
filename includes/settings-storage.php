<?php
/** Безопасное хранение runtime-настроек с секретами. */

function loadJsonSettingsSafe(string $path): array {
    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $data = json_decode((string)$raw, true);
        if (is_array($data)) return $data;
    }

    $backup = $path . '.bak';
    if (file_exists($backup)) {
        $raw = file_get_contents($backup);
        $data = json_decode((string)$raw, true);
        if (is_array($data)) {
            $dir = dirname($path);
            if (!is_dir($dir)) @mkdir($dir, 0755, true);
            @file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
            @chmod($path, 0600);
            return $data;
        }
    }

    return [];
}

function saveJsonSettingsSafe(string $path, array $data): bool {
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) return false;

    if (file_exists($path)) {
        $raw = file_get_contents($path);
        $current = json_decode((string)$raw, true);
        // Не заменяем полезный backup пустым/битым содержимым.
        if (is_array($current) && !empty($current)) {
            @file_put_contents($path . '.bak', json_encode($current, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);
            @chmod($path . '.bak', 0600);
        }
    }

    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) return false;

    $tmp = $path . '.tmp.' . getmypid() . '.' . bin2hex(random_bytes(4));
    if (@file_put_contents($tmp, $json, LOCK_EX) === false) return false;
    @chmod($tmp, 0600);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        return false;
    }
    @chmod($path, 0600);

    // После самой первой записи тоже создаём резервную копию.
    if (!file_exists($path . '.bak')) {
        @file_put_contents($path . '.bak', $json, LOCK_EX);
        @chmod($path . '.bak', 0600);
    }
    return true;
}
