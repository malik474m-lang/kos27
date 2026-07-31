<?php
try { jsonResponse(['plans' => getDB()->query("SELECT * FROM plans ORDER BY sort_order")->fetchAll()]); } catch (Exception $e) { jsonResponse(['error' => 'Error'], 500); }
