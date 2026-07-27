<?php
$data=json_decode(file_get_contents('php://input'),true);$db=getDB();
$code=strtoupper(trim($data['countryCode']??''));
if(!$code){http_response_code(400);echo json_encode(['error'=>'Код страны обязателен']);exit;}
$ex=$db->prepare("SELECT id FROM geo_redirects WHERE country_code=?");$ex->execute([$code]);
if($ex->fetch()){http_response_code(400);echo json_encode(['error'=>"Код '$code' уже существует"]);exit;}
$db->prepare("INSERT INTO geo_redirects (country_code,country_name,redirect_url,is_active) VALUES (?,?,?,?)")
   ->execute([$code,$data['countryName']??'',$data['redirectUrl']??'',$data['isActive']??true?1:0]);
@unlink(__DIR__.'/../../data/geo_rules.json');
echo json_encode(['success'=>true,'id'=>$db->lastInsertId()]);
