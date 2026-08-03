<?php
requireAdmin();
require_once __DIR__ . '/../../includes/mailer.php';

$data = json_decode(file_get_contents('php://input'), true) ?: [];
$email = trim((string)($data['email'] ?? ''));

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Некорректный email']);
    exit;
}

$result = testMailDelivery($email);
echo json_encode($result);
