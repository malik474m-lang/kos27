<?php
/**
 * Централизованное логирование ошибок для админки
 */

function getErrorLogPath(): string {
    return __DIR__ . '/../data/error-log.json';
}

function logAppError(string $source, string $message, string $level = 'error', array $context = []): void {
    $logFile = getErrorLogPath();
    $maxEntries = 200;

    $entries = [];
    if (file_exists($logFile)) {
        $entries = json_decode(file_get_contents($logFile), true) ?: [];
    }

    $entries[] = [
        'time' => date('Y-m-d H:i:s'),
        'level' => $level,
        'source' => $source,
        'message' => mb_substr($message, 0, 500),
        'context' => $context ? array_map(fn($v) => mb_substr((string)$v, 0, 200), $context) : null,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
        'uri' => mb_substr($_SERVER['REQUEST_URI'] ?? '', 0, 200),
    ];

    // Ограничиваем размер лога
    if (count($entries) > $maxEntries) {
        $entries = array_slice($entries, -$maxEntries);
    }

    @file_put_contents($logFile, json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), LOCK_EX);
}

function getAppErrors(int $limit = 50): array {
    $logFile = getErrorLogPath();
    if (!file_exists($logFile)) return [];
    $entries = json_decode(file_get_contents($logFile), true) ?: [];
    return array_slice(array_reverse($entries), 0, $limit);
}

function clearAppErrors(): int {
    $logFile = getErrorLogPath();
    if (!file_exists($logFile)) return 0;
    $entries = json_decode(file_get_contents($logFile), true) ?: [];
    $count = count($entries);
    @file_put_contents($logFile, '[]');
    return $count;
}

// Глобальный обработчик ошибок PHP
function kosmozaimErrorHandler(int $errno, string $errstr, string $errfile, int $errline): bool {
    $level = match($errno) {
        E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'error',
        E_WARNING, E_USER_WARNING, E_CORE_WARNING => 'warning',
        E_NOTICE, E_USER_NOTICE => 'notice',
        default => 'warning',
    };
    // Не логируем notice
    if ($level === 'notice') return false;

    logAppError('php', $errstr, $level, [
        'file' => basename($errfile),
        'line' => $errline,
    ]);
    return false; // Продолжить стандартную обработку
}

function kosmozaimShutdownHandler(): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        logAppError('php_fatal', $error['message'], 'critical', [
            'file' => basename($error['file']),
            'line' => $error['line'],
        ]);
    }
}
