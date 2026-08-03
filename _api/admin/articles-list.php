<?php
require_once __DIR__ . '/../../includes/content-quality.php';
try { $db = getDB(); $db->query("SELECT content_status FROM articles LIMIT 1"); } catch (Exception $e) { try { $db = getDB(); $db->exec("ALTER TABLE articles ADD COLUMN content_status varchar(20) NOT NULL DEFAULT 'draft' AFTER is_published, ADD COLUMN quality_score int(11) NOT NULL DEFAULT 0 AFTER content_status"); } catch (Exception $e2) {} }
if (apiCacheStart('admin_articles', 30)) exit;
$db = getDB();
apiCacheEnd($db->query("SELECT * FROM articles ORDER BY created_at DESC")->fetchAll());
