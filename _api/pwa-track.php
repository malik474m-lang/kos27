<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo '{}'; exit; }

$data = json_decode(file_get_contents('php://input'), true);
$event = $data['event'] ?? '';
if (!in_array($event, ['install','visit','prompt_shown','prompt_closed'])) { echo '{}'; exit; }

$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
$platform = preg_match('/iPhone|iPad|iPod/i', $ua) ? 'ios' : (preg_match('/Android/i', $ua) ? 'android' : 'desktop');
$device = ''; $browser = 'Other'; $osVer = '';
if (preg_match('/iPhone/', $ua)) $device = 'iPhone';
elseif (preg_match('/iPad/', $ua)) $device = 'iPad';
elseif (preg_match('/; ([^;)]+) Build/i', $ua, $m)) $device = trim($m[1]);
if (preg_match('/OS (\d+[_\.]\d+)/i', $ua, $m)) $osVer = 'iOS '.str_replace('_','.',$m[1]);
elseif (preg_match('/Android ([\d\.]+)/i', $ua, $m)) $osVer = 'Android '.$m[1];
if (preg_match('/Chrome/i', $ua)) $browser = 'Chrome';
elseif (preg_match('/Safari/i', $ua)) $browser = 'Safari';
elseif (preg_match('/Firefox/i', $ua)) $browser = 'Firefox';
$ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '')[0]);

try {
    $db = getDB();
    $db->exec("CREATE TABLE IF NOT EXISTS pwa_stats (id INT AUTO_INCREMENT PRIMARY KEY, event_type VARCHAR(50), platform VARCHAR(50), device_model VARCHAR(100), browser VARCHAR(100), os_version VARCHAR(50), is_standalone TINYINT DEFAULT 0, screen_width INT, screen_height INT, user_agent TEXT, ip VARCHAR(45), page_url VARCHAR(500), referrer VARCHAR(500), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    $db->prepare("INSERT INTO pwa_stats (event_type,platform,device_model,browser,os_version,is_standalone,screen_width,screen_height,user_agent,ip,page_url,referrer) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)")
       ->execute([$event,$platform,$device?:null,$browser,$osVer?:null,$data['standalone']??0,$data['screenWidth']??null,$data['screenHeight']??null,$ua,$ip,$data['url']??null,$data['referrer']??null]);
} catch(Exception $e) {}
echo json_encode(['ok'=>true]);
