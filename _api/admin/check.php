<?php
if (isAdmin()) {
    echo json_encode(['ok' => true]);
} else {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
}
