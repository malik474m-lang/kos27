<?php
define('LS_VERSION', '1.0.0');

$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line && $line[0] !== '#' && strpos($line, '=') !== false) {
            putenv(trim($line));
        }
    }
}

define('API_SECRET', getenv('API_SECRET') ?: 'change_this_secret');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = getenv('DB_HOST') ?: 'localhost';
    $name = getenv('DB_NAME') ?: 'license_server';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    return $pdo;
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }
function jsonResponse($data, $code = 200) { http_response_code($code); header('Content-Type: application/json'); echo json_encode($data, JSON_UNESCAPED_UNICODE); exit; }
function getClientIp() { return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1')[0]); }

function generateLicenseKey() {
    $c = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $s = [];
    for ($i = 0; $i < 4; $i++) { $p = ''; for ($j = 0; $j < 4; $j++) $p .= $c[random_int(0, 31)]; $s[] = $p; }
    return implode('-', $s);
}

function normalizeDomain($d) {
    $d = strtolower(trim($d));
    $d = preg_replace('#^https?://#', '', $d);
    $d = preg_replace('#^www\.#', '', $d);
    return explode('/', rtrim($d, '/'))[0];
}

function generateTotpSecret() { $c = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $s = ''; for ($i = 0; $i < 16; $i++) $s .= $c[random_int(0, 31)]; return $s; }

function verifyTotp($secret, $code) {
    $code = preg_replace('/\s+/', '', $code);
    if (!preg_match('/^\d{6}$/', $code)) return false;
    $t = floor(time() / 30);
    for ($i = -1; $i <= 1; $i++) if (hash_equals(calcTotp($secret, $t + $i), $code)) return true;
    return false;
}

function calcTotp($secret, $t) {
    $key = base32Dec($secret);
    $hash = hash_hmac('sha1', pack('N*', 0, $t), $key, true);
    $o = ord($hash[19]) & 0xF;
    $code = ((ord($hash[$o]) & 0x7F) << 24 | (ord($hash[$o+1]) & 0xFF) << 16 | (ord($hash[$o+2]) & 0xFF) << 8 | (ord($hash[$o+3]) & 0xFF)) % 1000000;
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

function base32Dec($in) {
    $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $b = $bl = 0; $r = '';
    for ($i = 0; $i < strlen($in); $i++) { $n = strpos($map, strtoupper($in[$i])); if ($n === false) continue; $b = ($b << 5) | $n; $bl += 5; if ($bl >= 8) { $bl -= 8; $r .= chr(($b >> $bl) & 0xFF); } }
    return $r;
}

function generateBackupCodes() { $c = []; for ($i = 0; $i < 8; $i++) $c[] = strtoupper(bin2hex(random_bytes(4))); return $c; }

function isIpBlocked($ip) {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
        $stmt->execute([$ip]);
        return ['blocked' => $stmt->fetchColumn() >= 10, 'remaining' => 900];
    } catch (Exception $e) { return ['blocked' => false]; }
}

function logLoginAttempt($u, $ip, $ok) { try { getDB()->prepare("INSERT INTO login_attempts (username, ip, success) VALUES (?,?,?)")->execute([$u, $ip, $ok ? 1 : 0]); } catch (Exception $e) {} }
function auditLog($action, $type = null, $id = null, $old = null, $new = null) { try { startSession(); getDB()->prepare("INSERT INTO audit_log (admin_id, action, entity_type, entity_id, old_data, new_data, ip) VALUES (?,?,?,?,?,?,?)")->execute([$_SESSION['admin_id'] ?? null, $action, $type, $id, $old ? json_encode($old) : null, $new ? json_encode($new) : null, getClientIp()]); } catch (Exception $e) {} }

function startSession() { if (session_status() === PHP_SESSION_NONE) { session_set_cookie_params(['lifetime' => 86400, 'path' => '/', 'httponly' => true, 'samesite' => 'Strict']); session_start(); } }
function isAdmin() { startSession(); return !empty($_SESSION['admin_id']); }
function requireAdmin() { if (!isAdmin()) { if (strpos($_SERVER['REQUEST_URI'] ?? '', '/api/') !== false) jsonResponse(['error' => 'Unauthorized'], 401); header('Location: /admin/login'); exit; } }
function getCurrentAdmin() { if (!isAdmin()) return null; try { $s = getDB()->prepare("SELECT id, username, totp_enabled FROM admins WHERE id = ?"); $s->execute([$_SESSION['admin_id']]); return $s->fetch(); } catch (Exception $e) { return null; } }
