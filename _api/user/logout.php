<?php
require_once __DIR__ . '/../../includes/user-auth.php';
header('Content-Type: application/json; charset=UTF-8');
logoutUser();
echo json_encode(['success' => true]);
