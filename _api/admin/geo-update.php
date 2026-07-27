<?php
$data=json_decode(file_get_contents('php://input'),true);$db=getDB();
if(isset($data['isActive'])&&count($data)<=2){
    $db->prepare("UPDATE geo_redirects SET is_active=? WHERE id=?")->execute([$data['isActive']?1:0,$itemId]);
    @unlink(__DIR__.'/../../data/geo_rules.json');echo json_encode(['success'=>true]);exit;
}
$db->prepare("UPDATE geo_redirects SET country_code=?,country_name=?,redirect_url=?,is_active=? WHERE id=?")
   ->execute([strtoupper($data['countryCode']??''),$data['countryName']??'',$data['redirectUrl']??'',$data['isActive']??true?1:0,$itemId]);
@unlink(__DIR__.'/../../data/geo_rules.json');echo json_encode(['success'=>true]);
