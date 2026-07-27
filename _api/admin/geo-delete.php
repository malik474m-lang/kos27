<?php
$db=getDB();$db->prepare("DELETE FROM geo_redirects WHERE id=?")->execute([$itemId]);
@unlink(__DIR__.'/../../data/geo_rules.json');echo json_encode(['success'=>true]);
