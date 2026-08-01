<?php
header('Content-Type: application/json; charset=UTF-8');

try {
    if (apiCacheStart('admin_subscribers', 20)) exit;
    $db = getDB();

    if (!function_exists('dbDateColumn')) {
        function dbDateColumn(string $table, array $preferredColumns): string {
            global $db;
            foreach ($preferredColumns as $column) {
                try {
                    $stmt = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");
                    if ($stmt && $stmt->fetch()) return $column;
                } catch (Exception $e) {}
            }
            return $preferredColumns[0];
        }
    }

    $dateColumn = dbDateColumn('subscribers', ['subscribed_at', 'created_at']);
    $rows = $db->query("SELECT * FROM subscribers ORDER BY {$dateColumn} DESC")->fetchAll();
    apiCacheEnd($rows);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Subscribers API: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
