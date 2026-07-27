<?php
$cc = $_GET['cc'] ?? '';
if (!$cc) { echo json_encode(['redirect'=>null,'country'=>null]); exit; }

$cc = strtoupper($cc);
$db = getDB();

$stmt = $db->prepare("SELECT redirect_url, country_name FROM geo_redirects WHERE country_code = ? AND is_active = 1 LIMIT 1");
$stmt->execute([$cc]);
$exact = $stmt->fetch();

if ($exact) {
    if ($exact['redirect_url'] && trim($exact['redirect_url'])) {
        echo json_encode(['redirect'=>$exact['redirect_url'],'country'=>$cc,'countryName'=>$exact['country_name']]);
    } else {
        echo json_encode(['redirect'=>null,'country'=>$cc]);
    }
    exit;
}

$stmt = $db->prepare("SELECT redirect_url, country_name FROM geo_redirects WHERE country_code = '*' AND is_active = 1 LIMIT 1");
$stmt->execute();
$wild = $stmt->fetch();

if ($wild && $wild['redirect_url']) {
    echo json_encode(['redirect'=>$wild['redirect_url'],'country'=>$cc,'countryName'=>$wild['country_name'] ?: $cc]);
} else {
    echo json_encode(['redirect'=>null,'country'=>$cc]);
}
