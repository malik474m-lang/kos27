<?php
startAdminSession();
session_destroy();
echo json_encode(['success' => true]);
