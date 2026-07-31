<?php
$secret = generateTotpSecret();
try { getDB()->prepare("UPDATE admins SET totp_secret=? WHERE id=? AND totp_enabled=0")->execute([$secret, $_SESSION['admin_id']]); } catch (Exception $e) {}
$user = $_SESSION['admin_username'] ?? 'admin';
$url = "otpauth://totp/KosmoLicense:$user?secret=$secret&issuer=KosmoLicense";
jsonResponse(['secret' => $secret, 'qr_url' => "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($url)]);
