<?php
/**
 * ============================================================
 *  KZM License Server — Установщик v2.0
 * ============================================================
 *  Один файл — полная установка сервера лицензирования.
 *  Все файлы сервера встроены внутрь.
 *
 *  Веб:  https://serv.kosmozaim.ru/install.php
 *  SSH:  php install.php --db-host=localhost --db-name=mydb --db-user=root --db-pass=secret --admin-pass=MyPass
 *
 *  УДАЛИТЕ install.php ПОСЛЕ УСТАНОВКИ!
 * ============================================================
 */

// Если уже установлен
if (file_exists(__DIR__ . '/config.php') && file_exists(__DIR__ . '/api/verify.php')) {
    if (php_sapi_name() !== 'cli') {
        echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Установлено</title>';
        echo '<style>body{font-family:sans-serif;text-align:center;padding:80px;background:#f1f5f9}';
        echo '.card{background:#fff;max-width:400px;margin:0 auto;padding:40px;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,.1)}</style></head>';
        echo '<body><div class="card"><div style="font-size:48px">✅</div><h2>Сервер уже установлен</h2>';
        echo '<p style="color:#666">Удалите <code>install.php</code> для безопасности.</p>';
        echo '<br><a href="/admin" style="background:#2563eb;color:#fff;padding:12px 32px;border-radius:10px;text-decoration:none;font-weight:bold">Открыть админку</a>';
        echo '</div></body></html>';
        exit;
    }
    echo "Сервер уже установлен. Удалите install.php\n";
    exit;
}

// ============================================================
// ВСТРОЕННЫЕ ФАЙЛЫ СЕРВЕРА
// ============================================================
$SERVER_FILES = [
    '.htaccess' => 'AddDefaultCharset UTF-8

RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Защита конфигурации
<FilesMatch "\\.(env|sql|log|json)$">
    Require all denied
</FilesMatch>
<Files "config.php">
    Require all denied
</Files>
<Files "database.sql">
    Require all denied
</Files>

# Запрет листинга директорий
Options -Indexes

# Security headers
<IfModule mod_headers.c>
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "no-referrer"
</IfModule>
',
    'admin/api.php' => '<?php
/**
 * API админки сервера лицензий
 */
header(\'Content-Type: application/json; charset=UTF-8\');

$db = getDB();
$method = $_SERVER[\'REQUEST_METHOD\'];
$action = $_GET[\'action\'] ?? \'\';

// === ЛОГИН (доступен без авторизации) ===
if ($action === \'login\' && $method === \'POST\') {
    $data = json_decode(file_get_contents(\'php://input\'), true);
    $username = trim($data[\'username\'] ?? \'\');
    $password = $data[\'password\'] ?? \'\';
    
    $rate = checkRateLimit(\'admin_login\', 5, 300);
    if (!$rate[\'allowed\']) {
        http_response_code(429);
        echo json_encode([\'error\' => \'Слишком много попыток\']);
        exit;
    }
    
    $stmt = $db->prepare("SELECT * FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($password, $admin[\'password_hash\'])) {
        http_response_code(401);
        echo json_encode([\'error\' => \'Неверные данные\']);
        exit;
    }
    
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([\'lifetime\' => 86400, \'path\' => \'/\', \'httponly\' => true, \'samesite\' => \'Strict\']);
        session_start();
    }
    session_regenerate_id(true);
    $_SESSION[\'lic_admin_id\'] = $admin[\'id\'];
    $_SESSION[\'lic_admin_user\'] = $admin[\'username\'];
    
    echo json_encode([\'success\' => true]);
    exit;
}

// === ЛОГАУТ ===
if ($action === \'logout\' && $method === \'POST\') {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([\'lifetime\' => 86400, \'path\' => \'/\', \'httponly\' => true, \'samesite\' => \'Strict\']);
        session_start();
    }
    session_destroy();
    echo json_encode([\'success\' => true]);
    exit;
}

// Проверка авторизации для остальных действий
$token = $_SERVER[\'HTTP_X_ADMIN_TOKEN\'] ?? $_GET[\'token\'] ?? \'\';
if ($token !== ADMIN_API_TOKEN) {
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([\'lifetime\' => 86400, \'path\' => \'/\', \'httponly\' => true, \'samesite\' => \'Strict\']);
        session_start();
    }
    if (empty($_SESSION[\'lic_admin_id\'])) {
        http_response_code(401);
        echo json_encode([\'error\' => \'Unauthorized\']);
        exit;
    }
}

// === Список лицензий ===
if ($action === \'list\') {
    $licenses = $db->query("SELECT l.*, 
        (SELECT COUNT(*) FROM license_log ll WHERE ll.license_id = l.id AND ll.action = \'verify\' AND ll.created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)) as checks_24h
        FROM licenses l ORDER BY l.id DESC")->fetchAll();
    echo json_encode([\'licenses\' => $licenses]);
    exit;
}

// === Создать лицензию ===
if ($action === \'create\' && $method === \'POST\') {
    $data = json_decode(file_get_contents(\'php://input\'), true);
    $key = generateLicenseKey();
    $domain = normalizeDomain($data[\'domain\'] ?? \'\');
    $plan = in_array($data[\'plan\'] ?? \'\', [\'trial\',\'basic\',\'pro\',\'enterprise\']) ? $data[\'plan\'] : \'basic\';
    $expiresAt = !empty($data[\'expires_at\']) ? $data[\'expires_at\'] : null;
    $features = $data[\'features\'] ?? null;
    if ($features && is_array($features)) $features = json_encode($features);
    
    $db->prepare("INSERT INTO licenses (license_key, domain, product, plan, status, owner_name, owner_email, expires_at, features, notes, max_activations) VALUES (?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([
           $key, $domain, $data[\'product\'] ?? \'kosmozaim\', $plan, \'active\',
           $data[\'owner_name\'] ?? null, $data[\'owner_email\'] ?? null,
           $expiresAt, $features, $data[\'notes\'] ?? null,
           (int)($data[\'max_activations\'] ?? 1),
       ]);
    
    echo json_encode([\'success\' => true, \'license_key\' => $key, \'id\' => $db->lastInsertId()]);
    exit;
}

// === Обновить лицензию ===
if ($action === \'update\' && $method === \'POST\') {
    $data = json_decode(file_get_contents(\'php://input\'), true);
    $id = (int)($data[\'id\'] ?? 0);
    if (!$id) { echo json_encode([\'error\' => \'id required\']); exit; }
    
    $sets = [];
    $params = [];
    foreach ([\'domain\',\'plan\',\'status\',\'owner_name\',\'owner_email\',\'expires_at\',\'notes\',\'max_activations\'] as $field) {
        if (array_key_exists($field, $data)) {
            if ($field === \'domain\') $data[$field] = normalizeDomain($data[$field]);
            $sets[] = "`$field` = ?";
            $params[] = $data[$field];
        }
    }
    if (!empty($data[\'features\'])) {
        $sets[] = "`features` = ?";
        $params[] = is_array($data[\'features\']) ? json_encode($data[\'features\']) : $data[\'features\'];
    }
    if ($sets) {
        $params[] = $id;
        $db->prepare("UPDATE licenses SET " . implode(\', \', $sets) . " WHERE id = ?")->execute($params);
    }
    echo json_encode([\'success\' => true]);
    exit;
}

// === Удалить лицензию ===
if ($action === \'delete\' && $method === \'POST\') {
    $data = json_decode(file_get_contents(\'php://input\'), true);
    $id = (int)($data[\'id\'] ?? 0);
    if ($id) {
        $db->prepare("DELETE FROM licenses WHERE id = ?")->execute([$id]);
        $db->prepare("DELETE FROM license_log WHERE license_id = ?")->execute([$id]);
    }
    echo json_encode([\'success\' => true]);
    exit;
}

// === Логи ===
if ($action === \'logs\') {
    $licId = (int)($_GET[\'license_id\'] ?? 0);
    $limit = min((int)($_GET[\'limit\'] ?? 100), 500);
    $where = $licId ? "WHERE license_id = $licId" : \'\';
    $logs = $db->query("SELECT * FROM license_log $where ORDER BY created_at DESC LIMIT $limit")->fetchAll();
    echo json_encode([\'logs\' => $logs]);
    exit;
}

// === Статистика ===
if ($action === \'stats\') {
    $stats = [
        \'total\' => (int)$db->query("SELECT COUNT(*) FROM licenses")->fetchColumn(),
        \'active\' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = \'active\'")->fetchColumn(),
        \'expired\' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = \'expired\'")->fetchColumn(),
        \'suspended\' => (int)$db->query("SELECT COUNT(*) FROM licenses WHERE status = \'suspended\'")->fetchColumn(),
        \'checks_today\' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action IN (\'verify\',\'heartbeat\') AND created_at > CURDATE()")->fetchColumn(),
        \'activations_today\' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = \'activate\' AND created_at > CURDATE()")->fetchColumn(),
        \'denied_today\' => (int)$db->query("SELECT COUNT(*) FROM license_log WHERE action = \'denied\' AND created_at > CURDATE()")->fetchColumn(),
    ];
    echo json_encode($stats);
    exit;
}


// === Смена пароля ===
if ($action === \'change-password\' && $method === \'POST\') {
    $data = json_decode(file_get_contents(\'php://input\'), true);
    $currentPass = $data[\'current_password\'] ?? \'\';
    $newPass = $data[\'new_password\'] ?? \'\';
    
    if (!$currentPass || !$newPass) {
        echo json_encode([\'error\' => \'Заполните все поля\']);
        exit;
    }
    if (mb_strlen($newPass) < 6) {
        echo json_encode([\'error\' => \'Минимум 6 символов\']);
        exit;
    }
    
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $adminId = $_SESSION[\'lic_admin_id\'] ?? 0;
    
    $stmt = $db->prepare("SELECT * FROM admins WHERE id = ? LIMIT 1");
    $stmt->execute([$adminId]);
    $admin = $stmt->fetch();
    
    if (!$admin || !password_verify($currentPass, $admin[\'password_hash\'])) {
        echo json_encode([\'error\' => \'Неверный текущий пароль\']);
        exit;
    }
    
    $newHash = password_hash($newPass, PASSWORD_BCRYPT, [\'cost\' => 12]);
    $db->prepare("UPDATE admins SET password_hash = ? WHERE id = ?")
       ->execute([$newHash, $adminId]);
    
    echo json_encode([\'success\' => true, \'message\' => \'Пароль изменён\']);
    exit;
}

echo json_encode([\'error\' => \'Unknown action\']);
',
    'admin/index.php' => '<?php
/**
 * Админка сервера лицензий
 */
require_once __DIR__ . \'/../config.php\';
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([\'lifetime\' => 86400, \'path\' => \'/\', \'httponly\' => true, \'samesite\' => \'Strict\']);
    session_start();
}
$isAuth = !empty($_SESSION[\'lic_admin_id\']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>License Server — Космозайм</title>
<script src="https://cdn.tailwindcss.com?v=3.4.17"></script>
<style>body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;}.sel-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;background:white;font-size:0.875rem;}.input-f{width:100%;border:1px solid #d1d5db;border-radius:0.5rem;padding:0.5rem 0.75rem;font-size:0.875rem;}</style>
</head>
<body class="bg-gray-100 min-h-screen">

<?php if (!$isAuth): ?>
<!-- Логин -->
<div class="flex items-center justify-center min-h-screen">
<div class="bg-white rounded-2xl shadow-lg p-8 w-full max-w-sm">
<div class="text-center mb-6"><span class="text-4xl">🔐</span><h1 class="text-xl font-bold mt-2">License Server</h1></div>
<form onsubmit="return doLogin(event)">
<div class="mb-4"><input type="text" id="lg-user" placeholder="Логин" required class="input-f"></div>
<div class="mb-4"><input type="password" id="lg-pass" placeholder="Пароль" required class="input-f"></div>
<div id="lg-err" class="hidden text-red-600 text-sm mb-3"></div>
<button type="submit" class="w-full bg-blue-600 text-white py-2.5 rounded-lg font-semibold hover:bg-blue-700">Войти</button>
</form>
</div>
</div>
<script>
function doLogin(e){e.preventDefault();
fetch(\'/admin/api?action=login\',{method:\'POST\',headers:{\'Content-Type\':\'application/json\'},body:JSON.stringify({username:document.getElementById(\'lg-user\').value,password:document.getElementById(\'lg-pass\').value})})
.then(r=>r.json()).then(d=>{if(d.success)location.reload();else{var el=document.getElementById(\'lg-err\');el.textContent=d.error;el.classList.remove(\'hidden\');}});
return false;}
</script>

<?php else: ?>
<!-- Админка -->
<div class="bg-gray-900 text-white"><div class="max-w-6xl mx-auto px-4 py-4 flex justify-between items-center"><div class="flex items-center gap-3"><span class="text-2xl">🔑</span><h1 class="font-bold">License Server</h1></div><div class="flex items-center gap-4"><span class="text-gray-400 text-sm"><?= e($_SESSION[\'lic_admin_user\'] ?? \'\') ?></span><button onclick="showChangePw()" class="text-gray-300 hover:text-white text-sm">🔑 Пароль</button><button onclick="logout()" class="text-gray-300 hover:text-white text-sm">Выйти</button></div></div></div>

<div class="max-w-6xl mx-auto px-4 py-8">
<div id="stats" class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8"></div>

<div class="flex justify-between items-center mb-4">
<h2 class="text-xl font-bold">Лицензии</h2>
<button onclick="showCreate()" class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700">+ Создать</button>
</div>
<div id="list" class="space-y-3"></div>

<h2 class="text-xl font-bold mt-10 mb-4">Последние события</h2>
<div id="logs" class="bg-white rounded-xl border overflow-hidden"></div>
</div>
<div id="M"></div>

<script>
var A=\'/admin/api\';
function ap(u,o){return fetch(A+u,Object.assign({headers:{\'Content-Type\':\'application/json\'},credentials:\'same-origin\'},o||{})).then(async function(r){var t=await r.text(); try{return JSON.parse(t);}catch(e){throw new Error(\'Невалидный ответ API: \'+t.substring(0,200));}});}
function e(s){if(!s)return\'\';var d=document.createElement(\'div\');d.textContent=s;return d.innerHTML;}
function modal(h){document.getElementById(\'M\').innerHTML=\'<div style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:50;display:flex;align-items:flex-start;justify-content:center;padding:2rem 1rem;overflow-y:auto" onclick="if(event.target===this)cm()"><div style="background:#fff;border-radius:16px;padding:24px;width:100%;max-width:560px;margin-top:40px" onclick="event.stopPropagation()">\'+h+\'</div></div>\';}
function cm(){document.getElementById(\'M\').innerHTML=\'\';}

function load(){
ap(\'?action=stats\').then(d=>{
if(d.error) throw new Error(d.error);
var h=\'\';
[[\'🔑\',\'Всего\',d.total],[\'✅\',\'Активных\',d.active],[\'⏰\',\'Истёкших\',d.expired],[\'🚫\',\'Отказов сегодня\',d.denied_today]].forEach(c=>{
h+=\'<div class=\\"bg-white rounded-xl border p-4 text-center\\"><p class=\\"text-2xl font-bold\\">\'+(c[2]||0)+\'</p><p class=\\"text-xs text-gray-500\\">\'+c[0]+\' \'+c[1]+\'</p></div>\';});
document.getElementById(\'stats\').innerHTML=h;
}).catch(function(err){document.getElementById(\'stats\').innerHTML=\'<div class=\\"col-span-full bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\\">Ошибка загрузки статистики: \'+e(err.message)+\'</div>\';});

ap(\'?action=list\').then(d=>{
if(d.error) throw new Error(d.error);
var h=\'\';
(d.licenses||[]).forEach(l=>{
var st={\'active\':\'bg-green-100 text-green-700\',\'expired\':\'bg-red-100 text-red-700\',\'suspended\':\'bg-yellow-100 text-yellow-700\',\'revoked\':\'bg-gray-100 text-gray-500\'};
var exp=l.expires_at?new Date(l.expires_at).toLocaleDateString(\'ru-RU\'):\'∞\';
h+=\'<div class="bg-white rounded-xl border p-4">\';
h+=\'<div class="flex items-center justify-between mb-2"><code class="text-sm font-bold bg-gray-100 px-2 py-1 rounded">\'+e(l.license_key)+\'</code><span class="text-xs px-2 py-0.5 rounded \'+(st[l.status]||\'\')+\'">\'+(l.status)+\'</span></div>\';
h+=\'<div class="text-sm text-gray-600"><span class="font-medium">\'+e(l.domain||\'не привязан\')+\'</span> • \'+e(l.plan)+\' • до \'+exp+\'</div>\';
h+=\'<div class="text-xs text-gray-400 mt-1">\'+e(l.owner_name||\'\')+\' \'+e(l.owner_email||\'\')+\' • Проверок за 24ч: \'+(l.checks_24h||0)+\'</div>\';
h+=\'<div class="flex gap-2 mt-2">\';
h+=\'<button onclick="editLic(\'+l.id+\')" class="text-blue-600 text-xs hover:underline">Ред.</button>\';
h+=\'<button onclick="showLogs(\'+l.id+\')" class="text-purple-600 text-xs hover:underline">Логи</button>\';
if(l.status===\'active\')h+=\'<button onclick="toggleLic(\'+l.id+\',\\\'suspended\\\')" class="text-yellow-600 text-xs hover:underline">Приостановить</button>\';
if(l.status===\'suspended\')h+=\'<button onclick="toggleLic(\'+l.id+\',\\\'active\\\')" class="text-green-600 text-xs hover:underline">Активировать</button>\';
h+=\'<button onclick="delLic(\'+l.id+\')" class="text-red-500 text-xs hover:underline">Удалить</button>\';
h+=\'</div></div>\';});
document.getElementById(\'list\').innerHTML=h||\'<p class=\\"text-gray-400 text-center py-8\\">Нет лицензий</p>\';
}).catch(function(err){document.getElementById(\'list\').innerHTML=\'<div class=\\"bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\\">Ошибка загрузки лицензий: \'+e(err.message)+\'</div>\';});

ap(\'?action=logs&limit=30\').then(d=>{
if(d.error) throw new Error(d.error);
var h=\'<table class=\\"w-full text-xs\\"><thead class=\\"bg-gray-50\\"><tr><th class=\\"px-3 py-2 text-left\\">Время</th><th class=\\"px-3 py-2\\">Действие</th><th class=\\"px-3 py-2\\">Ключ</th><th class=\\"px-3 py-2\\">Домен</th><th class=\\"px-3 py-2\\">IP</th><th class=\\"px-3 py-2\\">Код</th></tr></thead><tbody>\';
(d.logs||[]).forEach(l=>{
var acol={\'activate\':\'text-green-600\',\'verify\':\'text-blue-600\',\'denied\':\'text-red-600\',\'heartbeat\':\'text-gray-500\',\'deactivate\':\'text-yellow-600\',\'error\':\'text-red-600\'};
h+=\'<tr class="border-t"><td class="px-3 py-1.5">\'+new Date(l.created_at).toLocaleString(\'ru-RU\',{hour:\'2-digit\',minute:\'2-digit\',day:\'2-digit\',month:\'2-digit\'})+\'</td><td class="px-3 py-1.5 font-medium \'+(acol[l.action]||\'\')+\'">\'+e(l.action)+\'</td><td class="px-3 py-1.5 font-mono">\'+e((l.license_key||\'\').substr(-12))+\'</td><td class="px-3 py-1.5">\'+e(l.domain||\'-\')+\'</td><td class="px-3 py-1.5 text-gray-400">\'+e(l.ip||\'\')+\'</td><td class="px-3 py-1.5">\'+(l.response_code||\'\')+\'</td></tr>\';});
h+=\'</tbody></table>\';
document.getElementById(\'logs\').innerHTML=h;
}).catch(function(err){document.getElementById(\'logs\').innerHTML=\'<div class=\\"bg-red-50 border border-red-200 text-red-700 rounded-xl p-4\\">Ошибка загрузки логов: \'+e(err.message)+\'</div>\';});
}

function showCreate(){
modal(\'<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Создать лицензию</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>\'+
\'<form onsubmit="return createLic(event)"><div class="space-y-3">\'+
\'<div><label class="block text-xs font-medium mb-1">Домен</label><input id="cr-domain" class="input-f" placeholder="example.com"></div>\'+
\'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="cr-plan" class="sel-f"><option value="trial">Trial</option><option value="basic" selected>Basic</option><option value="pro">Pro</option><option value="enterprise">Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="cr-exp" class="input-f"></div></div>\'+
\'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="cr-name" class="input-f"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="cr-email" class="input-f" type="email"></div></div>\'+
\'<div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="cr-notes" class="input-f" rows="2"></textarea></div>\'+
\'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Создать</button></div></form>\');
}

function createLic(ev){ev.preventDefault();
ap(\'?action=create\',{method:\'POST\',body:JSON.stringify({
domain:document.getElementById(\'cr-domain\').value,
plan:document.getElementById(\'cr-plan\').value,
expires_at:document.getElementById(\'cr-exp\').value||null,
owner_name:document.getElementById(\'cr-name\').value,
owner_email:document.getElementById(\'cr-email\').value,
notes:document.getElementById(\'cr-notes\').value
})}).then(d=>{
if(d.success){cm();alert(\'✅ Ключ создан:\\\\n\'+d.license_key);load();}else alert(\'❌ \'+d.error);
});return false;}

function toggleLic(id,st){
ap(\'?action=update\',{method:\'POST\',body:JSON.stringify({id:id,status:st})}).then(()=>load());
}
function delLic(id){if(!confirm(\'Удалить лицензию?\'))return;
ap(\'?action=delete\',{method:\'POST\',body:JSON.stringify({id:id})}).then(()=>load());
}
function editLic(id){
ap(\'?action=list\').then(d=>{
var l=(d.licenses||[]).find(x=>x.id==id);if(!l)return;
modal(\'<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Редактировать</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>\'+
\'<div class="mb-3"><label class="block text-xs font-medium mb-1">Ключ</label><code class="text-sm bg-gray-100 px-2 py-1 rounded block">\'+e(l.license_key)+\'</code></div>\'+
\'<form onsubmit="return saveLic(event,\'+id+\')"><div class="space-y-3">\'+
\'<div><label class="block text-xs font-medium mb-1">Домен</label><input id="ed-domain" class="input-f" value="\'+e(l.domain||\'\')+\'"></div>\'+
\'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">План</label><select id="ed-plan" class="sel-f"><option value="trial"\'+(l.plan===\'trial\'?\' selected\':\'\')+\'>Trial</option><option value="basic"\'+(l.plan===\'basic\'?\' selected\':\'\')+\'>Basic</option><option value="pro"\'+(l.plan===\'pro\'?\' selected\':\'\')+\'>Pro</option><option value="enterprise"\'+(l.plan===\'enterprise\'?\' selected\':\'\')+\'>Enterprise</option></select></div><div><label class="block text-xs font-medium mb-1">Статус</label><select id="ed-status" class="sel-f"><option value="active"\'+(l.status===\'active\'?\' selected\':\'\')+\'>Active</option><option value="suspended"\'+(l.status===\'suspended\'?\' selected\':\'\')+\'>Suspended</option><option value="expired"\'+(l.status===\'expired\'?\' selected\':\'\')+\'>Expired</option><option value="revoked"\'+(l.status===\'revoked\'?\' selected\':\'\')+\'>Revoked</option></select></div></div>\'+
\'<div><label class="block text-xs font-medium mb-1">Срок до</label><input type="date" id="ed-exp" class="input-f" value="\'+(l.expires_at?l.expires_at.substr(0,10):\'\')+\'"></div>\'+
\'<div class="grid grid-cols-2 gap-3"><div><label class="block text-xs font-medium mb-1">Владелец</label><input id="ed-name" class="input-f" value="\'+e(l.owner_name||\'\')+\'"></div><div><label class="block text-xs font-medium mb-1">Email</label><input id="ed-email" class="input-f" value="\'+e(l.owner_email||\'\')+\'"></div></div>\'+
\'<div><label class="block text-xs font-medium mb-1">Заметки</label><textarea id="ed-notes" class="input-f" rows="2">\'+e(l.notes||\'\')+\'</textarea></div>\'+
\'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold">Сохранить</button></div></form>\');
});
}
function saveLic(ev,id){ev.preventDefault();
ap(\'?action=update\',{method:\'POST\',body:JSON.stringify({
id:id,domain:document.getElementById(\'ed-domain\').value,plan:document.getElementById(\'ed-plan\').value,status:document.getElementById(\'ed-status\').value,
expires_at:document.getElementById(\'ed-exp\').value||null,owner_name:document.getElementById(\'ed-name\').value,owner_email:document.getElementById(\'ed-email\').value,notes:document.getElementById(\'ed-notes\').value
})}).then(d=>{if(d.success){cm();load();}else alert(\'❌ \'+(d.error||\'Ошибка\'));});return false;}

function showLogs(id){
ap(\'?action=logs&license_id=\'+id+\'&limit=50\').then(d=>{
var h=\'<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">Логи лицензии #\'+id+\'</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>\';
h+=\'<div class="max-h-96 overflow-y-auto"><table class="w-full text-xs"><thead class="bg-gray-50"><tr><th class="px-2 py-1">Время</th><th class="px-2 py-1">Действие</th><th class="px-2 py-1">Домен</th><th class="px-2 py-1">IP</th><th class="px-2 py-1">Сообщение</th></tr></thead><tbody>\';
(d.logs||[]).forEach(l=>{h+=\'<tr class="border-t"><td class="px-2 py-1">\'+new Date(l.created_at).toLocaleString(\'ru-RU\')+\'</td><td class="px-2 py-1 font-medium">\'+e(l.action)+\'</td><td class="px-2 py-1">\'+e(l.domain||\'\')+\'</td><td class="px-2 py-1">\'+e(l.ip||\'\')+\'</td><td class="px-2 py-1 text-gray-500">\'+e(l.message||\'\')+\'</td></tr>\';});
h+=\'</tbody></table></div>\';
modal(h);
});
}

function showChangePw(){
modal(\'<div class="flex justify-between mb-4"><h3 class="text-lg font-bold">🔑 Смена пароля</h3><button onclick="cm()" class="text-gray-400 text-xl">&times;</button></div>\'+
\'<form onsubmit="return doChangePw(event)"><div class="space-y-4">\'+
\'<div><label class="block text-sm font-medium mb-1">Текущий пароль</label><input type="password" id="cp-old" class="input-f" required></div>\'+
\'<div><label class="block text-sm font-medium mb-1">Новый пароль</label><input type="password" id="cp-new" class="input-f" required minlength="6"></div>\'+
\'<div><label class="block text-sm font-medium mb-1">Повторите</label><input type="password" id="cp-confirm" class="input-f" required minlength="6"></div>\'+
\'<div id="cp-err" class="hidden text-red-600 text-sm"></div>\'+
\'</div><div class="flex justify-end gap-3 mt-4"><button type="button" onclick="cm()" class="px-4 py-2 text-gray-600">Отмена</button><button type="submit" id="cp-btn" class="bg-blue-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-blue-700">Сохранить</button></div></form>\');
}

function doChangePw(ev){ev.preventDefault();
var o=document.getElementById(\'cp-old\').value;
var n=document.getElementById(\'cp-new\').value;
var c=document.getElementById(\'cp-confirm\').value;
var err=document.getElementById(\'cp-err\');
err.classList.add(\'hidden\');
if(n!==c){err.textContent=\'Пароли не совпадают\';err.classList.remove(\'hidden\');return false;}
if(n.length<6){err.textContent=\'Минимум 6 символов\';err.classList.remove(\'hidden\');return false;}
var btn=document.getElementById(\'cp-btn\');btn.disabled=true;btn.textContent=\'⏳\';
ap(\'?action=change-password\',{method:\'POST\',body:JSON.stringify({current_password:o,new_password:n})}).then(function(d){
btn.disabled=false;btn.textContent=\'Сохранить\';
if(d.success){cm();alert(\'✅ \'+(d.message||\'Пароль изменён\'));}
else{err.textContent=d.error||\'Ошибка\';err.classList.remove(\'hidden\');}
}).catch(function(){btn.disabled=false;btn.textContent=\'Сохранить\';err.textContent=\'Ошибка соединения\';err.classList.remove(\'hidden\');});
return false;}

function logout(){fetch(\'/admin/api?action=logout\',{method:\'POST\'}).then(()=>location.reload());}
load();
</script>
<?php endif; ?>
</body>
</html>
',
    'api/activate.php' => '<?php
/**
 * Активация лицензии
 * POST /api/activate
 * Body: {"license_key": "KZM-...", "domain": "example.com", "hardware_hash": "..."}
 */

$rate = checkRateLimit(\'activate\', 10, 300); // 10 попыток за 5 мин
if (!$rate[\'allowed\']) {
    logAction(\'denied\', null, null, null, 429, \'Rate limit exceeded\');
    jsonError(\'Слишком много запросов. Подождите.\', 429);
}

$data = json_decode(file_get_contents(\'php://input\'), true);
$licenseKey = trim($data[\'license_key\'] ?? \'\');
$domain = normalizeDomain($data[\'domain\'] ?? \'\');
$hardwareHash = trim($data[\'hardware_hash\'] ?? \'\');

if (!$licenseKey || !$domain) {
    logAction(\'error\', null, $licenseKey, $domain, 400, \'Missing params\');
    jsonError(\'Укажите license_key и domain\');
}

// Валидация формата ключа
if (!preg_match(\'/^KZM-[A-F0-9]{6}-[A-F0-9]{6}-[A-F0-9]{6}-[A-F0-9]{6}$/\', $licenseKey)) {
    logAction(\'denied\', null, $licenseKey, $domain, 400, \'Invalid key format\');
    jsonError(\'Неверный формат лицензионного ключа\');
}

$db = getDB();

// Находим лицензию
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$license = $stmt->fetch();

if (!$license) {
    logAction(\'denied\', null, $licenseKey, $domain, 404, \'Key not found\');
    jsonError(\'Лицензионный ключ не найден\', 404);
}

// Проверка статуса
if ($license[\'status\'] !== \'active\') {
    logAction(\'denied\', (int)$license[\'id\'], $licenseKey, $domain, 403, \'License \' . $license[\'status\']);
    jsonError(\'Лицензия \' . match($license[\'status\']) {
        \'suspended\' => \'приостановлена\',
        \'expired\' => \'истекла\',
        \'revoked\' => \'отозвана\',
        default => \'неактивна\'
    }, 403);
}

// Проверка срока
if ($license[\'expires_at\'] && strtotime($license[\'expires_at\']) < time()) {
    $db->prepare("UPDATE licenses SET status = \'expired\' WHERE id = ?")->execute([$license[\'id\']]);
    logAction(\'denied\', (int)$license[\'id\'], $licenseKey, $domain, 403, \'Expired\');
    jsonError(\'Срок лицензии истёк\', 403);
}

// Проверка домена
if ($license[\'domain\'] && $license[\'domain\'] !== $domain) {
    // Попытка активации на другом домене — БЛОКИРОВКА
    $db->prepare("UPDATE licenses SET status = \'suspended\' WHERE id = ? AND status = \'active\'")
       ->execute([$license[\'id\']]);
    
    logAction(\'denied\', (int)$license[\'id\'], $licenseKey, $domain, 403, 
        \'ACTIVATE DOMAIN CHANGED: \' . $license[\'domain\'] . \' → \' . $domain . \'. License SUSPENDED.\');
    
    jsonError(\'Лицензия привязана к домену \' . $license[\'domain\'] . \'. Попытка активации на другом домене — лицензия заблокирована. Обратитесь к администратору.\', 403);
}

// Проверка количества активаций
if ((int)$license[\'activations_count\'] >= (int)$license[\'max_activations\'] && $license[\'domain\'] !== $domain) {
    logAction(\'denied\', (int)$license[\'id\'], $licenseKey, $domain, 403, \'Max activations reached\');
    jsonError(\'Достигнут лимит активаций\', 403);
}

// Активируем — привязываем домен
$db->prepare("UPDATE licenses SET domain = ?, hardware_hash = ?, activations_count = activations_count + 1, last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([$domain, $hardwareHash ?: null, getClientIp(), $license[\'id\']]);

logAction(\'activate\', (int)$license[\'id\'], $licenseKey, $domain, 200, \'Activated successfully\');

// Генерируем зашифрованный токен активации
$activationData = json_encode([
    \'license_id\' => $license[\'id\'],
    \'key\' => $licenseKey,
    \'domain\' => $domain,
    \'plan\' => $license[\'plan\'],
    \'features\' => json_decode($license[\'features\'] ?? \'{}\', true),
    \'expires\' => $license[\'expires_at\'],
    \'activated_at\' => date(\'Y-m-d H:i:s\'),
]);
$encryptedToken = encryptData($activationData);

jsonResponse([
    \'valid\' => true,
    \'license\' => [
        \'key\' => $licenseKey,
        \'domain\' => $domain,
        \'plan\' => $license[\'plan\'],
        \'features\' => json_decode($license[\'features\'] ?? \'{}\', true),
        \'expires_at\' => $license[\'expires_at\'],
    ],
    \'activation_token\' => $encryptedToken,
    \'message\' => \'Лицензия активирована\',
]);
',
    'api/deactivate.php' => '<?php
/**
 * Деактивация лицензии
 * POST /api/deactivate
 */
$rate = checkRateLimit(\'deactivate\', 5, 300);
if (!$rate[\'allowed\']) { jsonError(\'Rate limit\', 429); }

$data = json_decode(file_get_contents(\'php://input\'), true);
$licenseKey = trim($data[\'license_key\'] ?? \'\');
$domain = normalizeDomain($data[\'domain\'] ?? \'\');

if (!$licenseKey || !$domain) { jsonError(\'Missing params\'); }

$db = getDB();
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? AND domain = ? LIMIT 1");
$stmt->execute([$licenseKey, $domain]);
$lic = $stmt->fetch();

if (!$lic) {
    logAction(\'denied\', null, $licenseKey, $domain, 404, \'Not found for deactivation\');
    jsonError(\'Лицензия не найдена для этого домена\', 404);
}

$db->prepare("UPDATE licenses SET domain = \'\', hardware_hash = NULL, activations_count = GREATEST(activations_count - 1, 0) WHERE id = ?")
   ->execute([$lic[\'id\']]);

logAction(\'deactivate\', (int)$lic[\'id\'], $licenseKey, $domain, 200, \'Deactivated\');
jsonResponse([\'valid\' => true, \'message\' => \'Лицензия деактивирована. Можно привязать к другому домену.\']);
',
    'api/heartbeat.php' => '<?php
/**
 * Heartbeat — фоновая проверка (из крона клиента)
 * POST /api/heartbeat
 * 
 * При несовпадении домена — автоматическая блокировка лицензии
 */
$rate = checkRateLimit(\'heartbeat\', 120, 60);
if (!$rate[\'allowed\']) { jsonError(\'Rate limit\', 429); }

$data = json_decode(file_get_contents(\'php://input\'), true);
$licenseKey = trim($data[\'license_key\'] ?? \'\');
$domain = normalizeDomain($data[\'domain\'] ?? \'\');

if (!$licenseKey || !$domain) { jsonError(\'Missing params\'); }

$db = getDB();
$stmt = $db->prepare("SELECT id, domain, status, expires_at FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$lic = $stmt->fetch();

if (!$lic) {
    logAction(\'heartbeat\', null, $licenseKey, $domain, 404, \'Key not found\');
    jsonResponse([\'valid\' => false], 404);
}

// Домен изменился — БЛОКИРОВКА
if ($lic[\'domain\'] && $lic[\'domain\'] !== $domain) {
    $db->prepare("UPDATE licenses SET status = \'suspended\' WHERE id = ? AND status = \'active\'")
       ->execute([$lic[\'id\']]);
    
    logAction(\'heartbeat\', (int)$lic[\'id\'], $licenseKey, $domain, 403, 
        \'DOMAIN CHANGED: \' . $lic[\'domain\'] . \' → \' . $domain . \'. License SUSPENDED.\');
    
    jsonResponse([\'valid\' => false, \'reason\' => \'domain_changed\', \'action\' => \'suspended\'], 403);
}

// Статус не active
if ($lic[\'status\'] !== \'active\') {
    logAction(\'heartbeat\', (int)$lic[\'id\'], $licenseKey, $domain, 403, \'Status: \' . $lic[\'status\']);
    jsonResponse([\'valid\' => false, \'reason\' => $lic[\'status\']], 403);
}

// Срок истёк
if ($lic[\'expires_at\'] && strtotime($lic[\'expires_at\']) < time()) {
    $db->prepare("UPDATE licenses SET status = \'expired\' WHERE id = ?")->execute([$lic[\'id\']]);
    logAction(\'heartbeat\', (int)$lic[\'id\'], $licenseKey, $domain, 403, \'Expired\');
    jsonResponse([\'valid\' => false, \'reason\' => \'expired\'], 403);
}

$db->prepare("UPDATE licenses SET last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([getClientIp(), $lic[\'id\']]);

logAction(\'heartbeat\', (int)$lic[\'id\'], $licenseKey, $domain, 200, \'OK\');
jsonResponse([\'valid\' => true]);
',
    'api/verify.php' => '<?php
/**
 * Проверка лицензии (периодическая)
 * POST /api/verify
 * Body: {"license_key": "KZM-...", "domain": "example.com"}
 * 
 * При несовпадении домена — автоматическая блокировка лицензии
 */

$rate = checkRateLimit(\'verify\', 60, 60);
if (!$rate[\'allowed\']) {
    jsonError(\'Rate limit\', 429);
}

$data = json_decode(file_get_contents(\'php://input\'), true);
$licenseKey = trim($data[\'license_key\'] ?? \'\');
$domain = normalizeDomain($data[\'domain\'] ?? \'\');

if (!$licenseKey || !$domain) {
    jsonError(\'Укажите license_key и domain\');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM licenses WHERE license_key = ? LIMIT 1");
$stmt->execute([$licenseKey]);
$license = $stmt->fetch();

if (!$license) {
    logAction(\'denied\', null, $licenseKey, $domain, 404, \'Key not found\');
    jsonError(\'Лицензия не найдена\', 404);
}

// Проверка домена — при несовпадении БЛОКИРУЕМ лицензию
if ($license[\'domain\'] && $license[\'domain\'] !== $domain) {
    // Блокируем
    $db->prepare("UPDATE licenses SET status = \'suspended\' WHERE id = ? AND status = \'active\'")
       ->execute([$license[\'id\']]);
    
    logAction(\'denied\', (int)$license[\'id\'], $licenseKey, $domain, 403, 
        \'DOMAIN CHANGED: \' . $license[\'domain\'] . \' → \' . $domain . \'. License SUSPENDED.\');
    
    jsonResponse([
        \'valid\' => false, 
        \'error\' => \'Обнаружена смена домена. Лицензия заблокирована.\',
        \'expected_domain\' => $license[\'domain\'],
        \'actual_domain\' => $domain,
        \'action\' => \'suspended\',
    ], 403);
}

// Проверка срока
if ($license[\'expires_at\'] && strtotime($license[\'expires_at\']) < time()) {
    $db->prepare("UPDATE licenses SET status = \'expired\' WHERE id = ?")->execute([$license[\'id\']]);
    logAction(\'verify\', (int)$license[\'id\'], $licenseKey, $domain, 403, \'Expired\');
    jsonResponse([\'valid\' => false, \'error\' => \'Срок лицензии истёк\', \'expired_at\' => $license[\'expires_at\']], 403);
}

// Проверка статуса
if ($license[\'status\'] !== \'active\') {
    logAction(\'verify\', (int)$license[\'id\'], $licenseKey, $domain, 403, \'Status: \' . $license[\'status\']);
    jsonResponse([\'valid\' => false, \'error\' => \'Лицензия неактивна\', \'status\' => $license[\'status\']], 403);
}

// Обновляем last_check
$db->prepare("UPDATE licenses SET last_check_at = NOW(), last_check_ip = ? WHERE id = ?")
   ->execute([getClientIp(), $license[\'id\']]);

logAction(\'verify\', (int)$license[\'id\'], $licenseKey, $domain, 200, \'Valid\');

$daysLeft = null;
if ($license[\'expires_at\']) {
    $daysLeft = max(0, (int)ceil((strtotime($license[\'expires_at\']) - time()) / 86400));
}

jsonResponse([
    \'valid\' => true,
    \'license\' => [
        \'plan\' => $license[\'plan\'],
        \'status\' => $license[\'status\'],
        \'domain\' => $license[\'domain\'],
        \'features\' => json_decode($license[\'features\'] ?? \'{}\', true),
        \'expires_at\' => $license[\'expires_at\'],
        \'days_left\' => $daysLeft,
    ],
]);
',
    'index.php' => '<?php
/**
 * Сервер лицензирования — Роутер
 * serv.kosmozaim.ru
 */
require_once __DIR__ . \'/config.php\';

header(\'Content-Type: application/json; charset=UTF-8\');
header(\'X-License-Server: KZM/1.0\');

// CORS для клиентских запросов
$origin = $_SERVER[\'HTTP_ORIGIN\'] ?? \'\';
if ($origin) {
    header("Access-Control-Allow-Origin: $origin");
    header(\'Access-Control-Allow-Methods: POST, GET, OPTIONS\');
    header(\'Access-Control-Allow-Headers: Content-Type, X-License-Key, X-Signature\');
    header(\'Access-Control-Max-Age: 86400\');
}
if ($_SERVER[\'REQUEST_METHOD\'] === \'OPTIONS\') { http_response_code(204); exit; }

$uri = parse_url($_SERVER[\'REQUEST_URI\'], PHP_URL_PATH);
$uri = rtrim($uri, \'/\') ?: \'/\';

// API эндпоинты
if ($uri === \'/api/activate\' && $_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    require __DIR__ . \'/api/activate.php\'; exit;
}
if ($uri === \'/api/verify\' && $_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    require __DIR__ . \'/api/verify.php\'; exit;
}
if ($uri === \'/api/deactivate\' && $_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    require __DIR__ . \'/api/deactivate.php\'; exit;
}
if ($uri === \'/api/heartbeat\' && $_SERVER[\'REQUEST_METHOD\'] === \'POST\') {
    require __DIR__ . \'/api/heartbeat.php\'; exit;
}

// Статус
if ($uri === \'/\' || $uri === \'/api/status\') {
    jsonResponse([\'server\' => \'KZM License Server\', \'version\' => \'1.0\', \'status\' => \'online\']);
}

// Админка
if (str_starts_with($uri, \'/admin\')) {
    if ($uri === \'/admin/api\' || str_starts_with($uri, \'/admin/api\')) {
        require __DIR__ . \'/admin/api.php\'; exit;
    }
    require __DIR__ . \'/admin/index.php\'; exit;
}

http_response_code(404);
jsonResponse([\'error\' => \'Endpoint not found\'], 404);
',
];


// ============================================================
// CLI
// ============================================================
if (php_sapi_name() === 'cli') {
    echo "\n  KZM License Server — Установка v2.0\n\n";
    $opts = getopt('', ['db-host:', 'db-name:', 'db-user:', 'db-pass:', 'db-port:', 'admin-user:', 'admin-pass:', 'help']);
    if (isset($opts['help'])) {
        echo "  --db-host     Хост MySQL (default: localhost)\n";
        echo "  --db-name     Имя БД (обязательно)\n";
        echo "  --db-user     Пользователь MySQL (обязательно)\n";
        echo "  --db-pass     Пароль MySQL\n";
        echo "  --db-port     Порт (default: 3306)\n";
        echo "  --admin-user  Логин админа (default: admin)\n";
        echo "  --admin-pass  Пароль админа (обязательно)\n\n";
        exit;
    }
    $r = doInstall(
        $opts['db-host'] ?? 'localhost', $opts['db-port'] ?? '3306',
        $opts['db-name'] ?? '', $opts['db-user'] ?? '', $opts['db-pass'] ?? '',
        $opts['admin-user'] ?? 'admin', $opts['admin-pass'] ?? ''
    );
    if ($r['success']) {
        echo "  ✅ Установка завершена!\n";
        foreach ($r['steps'] as $s) echo "     ✓ $s\n";
        echo "\n  ⚠️  УДАЛИТЕ install.php!\n\n";
    } else {
        echo "  ❌ {$r['error']}\n\n"; exit(1);
    }
    exit;
}

// ============================================================
// ВЕБ
// ============================================================
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = doInstall(
        trim($_POST['db_host'] ?? 'localhost'), trim($_POST['db_port'] ?? '3306'),
        trim($_POST['db_name'] ?? ''), trim($_POST['db_user'] ?? ''), $_POST['db_pass'] ?? '',
        trim($_POST['admin_user'] ?? 'admin'), $_POST['admin_pass'] ?? ''
    );
}

$checks = [
    ['PHP ≥ 8.0', version_compare(PHP_VERSION, '8.0.0', '>=')],
    ['PDO MySQL', extension_loaded('pdo_mysql')],
    ['OpenSSL', extension_loaded('openssl')],
    ['mbstring', extension_loaded('mbstring')],
    ['Запись в директорию', is_writable(__DIR__)],
];
$allOk = !array_filter($checks, fn($c) => !$c[1]);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Установка — KZM License Server</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;background:#f1f5f9;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:20px;box-shadow:0 10px 40px rgba(0,0,0,.1);width:100%;max-width:520px;overflow:hidden}
.hdr{background:linear-gradient(135deg,#1e40af,#7c3aed);color:#fff;padding:32px;text-align:center}
.hdr h1{font-size:22px;font-weight:800;margin-top:10px}
.hdr p{opacity:.85;font-size:13px;margin-top:4px}
.bd{padding:28px}
label{display:block;font-size:13px;font-weight:600;color:#374151;margin-bottom:4px}
input{width:100%;border:2px solid #e5e7eb;border-radius:10px;padding:10px 14px;font-size:14px;margin-bottom:14px}
input:focus{outline:none;border-color:#3b82f6}
.row{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.btn{width:100%;background:#1e40af;color:#fff;border:none;padding:14px;border-radius:12px;font-size:16px;font-weight:700;cursor:pointer;margin-top:8px}
.btn:hover{background:#1d4ed8}
.btn:disabled{opacity:.5}
.chk span{display:block;padding:2px 0;font-size:13px}
.chk .y{color:#059669}.chk .n{color:#dc2626}
.ok{background:#ecfdf5;border:1px solid #10b981;border-radius:10px;padding:16px;margin-bottom:16px}
.ok h3{color:#065f46;margin-bottom:6px}
.ok p{color:#047857;font-size:13px;margin:2px 0}
.err{background:#fef2f2;border:1px solid #fca5a5;border-radius:10px;padding:14px;color:#991b1b;font-size:13px;margin-bottom:16px}
.warn{background:#fef3c7;border:1px solid #fbbf24;border-radius:10px;padding:12px;font-size:12px;color:#92400e;margin-bottom:16px}
</style>
</head>
<body>
<div class="card">
<div class="hdr"><div style="font-size:48px">🔑</div><h1>KZM License Server</h1><p>Установка сервера лицензирования v2.0</p></div>
<div class="bd">

<div class="chk" style="margin-bottom:16px">
<?php foreach ($checks as [$n,$ok]): ?>
<span class="<?= $ok?'y':'n' ?>"><?= $ok?'✅':'❌' ?> <?= $n ?></span>
<?php endforeach; ?>
</div>

<?php if ($result && $result['success']): ?>
<div class="ok">
<h3>✅ Установка завершена!</h3>
<?php foreach ($result['steps'] as $s): ?><p>✓ <?= htmlspecialchars($s) ?></p><?php endforeach; ?>
<br><p><strong>Админка:</strong> <a href="/admin">/admin</a></p>
<p style="color:#dc2626;font-weight:bold;margin-top:8px">⚠️ Удалите install.php!</p>
</div>
<?php elseif ($result): ?>
<div class="err">❌ <?= htmlspecialchars($result['error']) ?></div>
<?php endif; ?>

<?php if (!$result || !$result['success']): ?>
<div class="warn">⚠️ После установки удалите <strong>install.php</strong></div>
<form method="POST">
<label>Хост MySQL</label>
<input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" required>
<div class="row">
<div><label>Имя БД</label><input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required placeholder="license_server"></div>
<div><label>Порт</label><input name="db_port" value="<?= htmlspecialchars($_POST['db_port'] ?? '3306') ?>"></div>
</div>
<div class="row">
<div><label>Пользователь MySQL</label><input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required></div>
<div><label>Пароль MySQL</label><input type="password" name="db_pass"></div>
</div>
<hr style="border:none;border-top:1px solid #e5e7eb;margin:16px 0">
<div class="row">
<div><label>Логин админа</label><input name="admin_user" value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" required></div>
<div><label>Пароль админа</label><input type="password" name="admin_pass" required minlength="6" placeholder="мин. 6 символов"></div>
</div>
<button type="submit" class="btn" <?= $allOk?'':'disabled' ?>>🚀 Установить</button>
</form>
<?php endif; ?>

</div></div>
</body></html>
<?php

// ============================================================
// УСТАНОВКА
// ============================================================
function doInstall(string $dbHost, string $dbPort, string $dbName, string $dbUser, string $dbPass, string $adminUser, string $adminPass): array {
    global $SERVER_FILES;
    $steps = [];
    $dir = __DIR__;

    if (!$dbName || !$dbUser) return ['success' => false, 'error' => 'Укажите имя БД и пользователя'];
    if (!$adminPass || mb_strlen($adminPass) < 6) return ['success' => false, 'error' => 'Пароль админа — минимум 6 символов'];

    // 1. Подключение
    try {
        $pdo = new PDO("mysql:host=$dbHost;port=$dbPort;charset=utf8mb4", $dbUser, $dbPass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $steps[] = "MySQL подключение: OK";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'MySQL: ' . $e->getMessage()];
    }

    // 2. БД
    try {
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
        $pdo->exec("USE `$dbName`");
        $steps[] = "База '$dbName': OK";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Создание БД: ' . $e->getMessage()];
    }

    // 3. Таблицы
    $tables = [
        "CREATE TABLE IF NOT EXISTS `licenses` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `license_key` varchar(64) NOT NULL,
          `domain` varchar(255) NOT NULL DEFAULT '',
          `product` varchar(100) NOT NULL DEFAULT 'kosmozaim',
          `plan` enum('trial','basic','pro','enterprise') NOT NULL DEFAULT 'basic',
          `status` enum('active','suspended','expired','revoked') NOT NULL DEFAULT 'active',
          `owner_name` varchar(255) DEFAULT NULL,
          `owner_email` varchar(255) DEFAULT NULL,
          `issued_at` timestamp NULL DEFAULT current_timestamp(),
          `expires_at` timestamp NULL DEFAULT NULL,
          `last_check_at` timestamp NULL DEFAULT NULL,
          `last_check_ip` varchar(45) DEFAULT NULL,
          `activations_count` int(11) NOT NULL DEFAULT 0,
          `max_activations` int(11) NOT NULL DEFAULT 1,
          `features` text DEFAULT NULL,
          `notes` text DEFAULT NULL,
          `hardware_hash` varchar(64) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_license_key` (`license_key`),
          KEY `idx_domain` (`domain`),
          KEY `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `license_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `license_id` int(11) DEFAULT NULL,
          `license_key` varchar(64) DEFAULT NULL,
          `action` varchar(20) NOT NULL,
          `domain` varchar(255) DEFAULT NULL,
          `ip` varchar(45) DEFAULT NULL,
          `user_agent` varchar(500) DEFAULT NULL,
          `request_data` text DEFAULT NULL,
          `response_code` int(11) DEFAULT NULL,
          `message` varchar(500) DEFAULT NULL,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `idx_license` (`license_id`),
          KEY `idx_created` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `rate_limits` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `ip` varchar(45) NOT NULL,
          `endpoint` varchar(100) NOT NULL,
          `attempts` int(11) NOT NULL DEFAULT 1,
          `window_start` timestamp NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_ip_endpoint` (`ip`, `endpoint`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `admins` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `username` varchar(100) NOT NULL,
          `password_hash` varchar(255) NOT NULL,
          `totp_secret` varchar(64) DEFAULT NULL,
          `totp_enabled` tinyint(1) NOT NULL DEFAULT 0,
          `created_at` timestamp NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          UNIQUE KEY `uk_username` (`username`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",
    ];
    try {
        foreach ($tables as $sql) $pdo->exec($sql);
        $steps[] = "Таблицы: 4 создано";
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Таблицы: ' . $e->getMessage()];
    }

    // 4. Админ
    $hash = password_hash($adminPass, PASSWORD_BCRYPT, ['cost' => 12]);
    try {
        $chk = $pdo->prepare("SELECT id FROM admins WHERE username = ?"); $chk->execute([$adminUser]);
        if ($chk->fetch()) {
            $pdo->prepare("UPDATE admins SET password_hash = ? WHERE username = ?")->execute([$hash, $adminUser]);
            $steps[] = "Админ '$adminUser': пароль обновлён";
        } else {
            $pdo->prepare("INSERT INTO admins (username, password_hash) VALUES (?, ?)")->execute([$adminUser, $hash]);
            $steps[] = "Админ '$adminUser': создан";
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Админ: ' . $e->getMessage()];
    }

    // 5. Ключи безопасности
    $signKey = bin2hex(random_bytes(24));
    $encryptKey = bin2hex(random_bytes(24));
    $salt = bin2hex(random_bytes(16));
    $adminToken = 'lac_' . bin2hex(random_bytes(16));

    // 6. Файлы сервера
    @mkdir($dir . '/api', 0755, true);
    @mkdir($dir . '/admin', 0755, true);
    $written = 0;
    foreach ($SERVER_FILES as $name => $content) {
        $path = $dir . '/' . $name;
        $subdir = dirname($path);
        if (!is_dir($subdir)) @mkdir($subdir, 0755, true);
        if (file_put_contents($path, $content) !== false) $written++;
    }
    $steps[] = "Файлы: $written записано";

    // 7. config.php
    $dbPassEsc = addcslashes($dbPass, "'\\");
    $config = "<?php\n"
        . "// KZM License Server — config\n"
        . "// Сгенерировано: " . date('Y-m-d H:i:s') . "\n\n"
        . "mb_internal_encoding('UTF-8');\n"
        . "ini_set('default_charset', 'UTF-8');\n\n"
        . "function getDB(): PDO {\n"
        . "    static \$pdo = null;\n"
        . "    if (\$pdo) return \$pdo;\n"
        . "    \$pdo = new PDO(\"mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4\", '$dbUser', '$dbPassEsc', [\n"
        . "        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,\n"
        . "        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,\n"
        . "    ]);\n"
        . "    \$pdo->exec(\"SET NAMES utf8mb4 COLLATE utf8mb4_general_ci\");\n"
        . "    return \$pdo;\n"
        . "}\n\n"
        . "define('LICENSE_SIGN_KEY', '$signKey');\n"
        . "define('LICENSE_ENCRYPT_KEY', '$encryptKey');\n"
        . "define('LICENSE_SALT', '$salt');\n"
        . "define('ADMIN_API_TOKEN', '$adminToken');\n\n";

    // Добавляем все функции
    $config .= <<<'FUNCS'
function signResponse(array $data): string {
    return hash_hmac('sha256', json_encode($data, JSON_UNESCAPED_UNICODE), LICENSE_SIGN_KEY);
}
function encryptData(string $plaintext): string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $iv = random_bytes(16);
    return base64_encode($iv . openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv));
}
function decryptData(string $ciphertext): ?string {
    $key = hash('sha256', LICENSE_ENCRYPT_KEY, true);
    $raw = base64_decode($ciphertext);
    if (strlen($raw) < 17) return null;
    $d = openssl_decrypt(substr($raw, 16), 'aes-256-cbc', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));
    return $d !== false ? $d : null;
}
function generateLicenseKey(): string {
    $p = [];
    for ($i = 0; $i < 4; $i++) $p[] = strtoupper(bin2hex(random_bytes(3)));
    return 'KZM-' . implode('-', $p);
}
function normalizeDomain(string $d): string {
    $d = trim(strtolower($d));
    $d = preg_replace('#^https?://#', '', $d);
    $d = preg_replace('#^www\.#', '', $d);
    return rtrim($d, '/');
}
function getClientIp(): string {
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['HTTP_X_REAL_IP'] ?? $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    return trim(explode(',', $ip)[0]);
}
function checkRateLimit(string $ep, int $max = 30, int $win = 60): array {
    $ip = getClientIp(); $db = getDB();
    $db->prepare("DELETE FROM rate_limits WHERE window_start < DATE_SUB(NOW(), INTERVAL ? SECOND)")->execute([$win]);
    $s = $db->prepare("SELECT attempts FROM rate_limits WHERE ip = ? AND endpoint = ?"); $s->execute([$ip, $ep]); $r = $s->fetch();
    if (!$r) { $db->prepare("INSERT INTO rate_limits (ip, endpoint, attempts) VALUES (?,?,1)")->execute([$ip, $ep]); return ['allowed'=>true,'remaining'=>$max-1]; }
    if ((int)$r['attempts'] >= $max) return ['allowed'=>false,'remaining'=>0];
    $db->prepare("UPDATE rate_limits SET attempts = attempts + 1 WHERE ip = ? AND endpoint = ?")->execute([$ip, $ep]);
    return ['allowed'=>true,'remaining'=>$max-(int)$r['attempts']-1];
}
function logAction(string $a, ?int $lid, ?string $lk, ?string $d, int $c, ?string $m = null): void {
    try { getDB()->prepare("INSERT INTO license_log (license_id,license_key,action,domain,ip,user_agent,response_code,message) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$lid,$lk,$a,$d,getClientIp(),mb_substr($_SERVER['HTTP_USER_AGENT']??'',0,500),$c,$m?mb_substr($m,0,500):null]); } catch (Exception $e) {}
}
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code); $data['timestamp']=time(); $data['signature']=signResponse($data);
    echo json_encode($data, JSON_UNESCAPED_UNICODE); exit;
}
function jsonError(string $msg, int $code = 400): void { jsonResponse(['error'=>$msg,'valid'=>false], $code); }
function e(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
FUNCS;

    if (file_put_contents($dir . '/config.php', $config) === false) {
        return ['success' => false, 'error' => 'Не удалось записать config.php'];
    }
    $steps[] = "config.php: сгенерирован";

    // 8. Проверка
    try {
        require_once $dir . '/config.php';
        getDB()->query("SELECT 1");
        $steps[] = "Проверка подключения: OK";
    } catch (Exception $e) {
        $steps[] = "⚠️ Проверка: " . $e->getMessage();
    }

    return ['success' => true, 'steps' => $steps];
}
