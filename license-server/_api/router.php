<?php
$p = substr($uri, 4);
if ($p === '/check' || $p === '/verify') { require __DIR__ . '/check.php'; exit; }
if ($p === '/activate') { require __DIR__ . '/activate.php'; exit; }
if ($p === '/info') { require __DIR__ . '/info.php'; exit; }
if (strpos($p, '/admin/') === 0) { require __DIR__ . '/admin-router.php'; exit; }
jsonResponse(['error' => 'Unknown endpoint'], 404);
