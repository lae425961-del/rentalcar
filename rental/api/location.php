<?php
require_once __DIR__ . '/../includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
switch ($_GET['type'] ?? '') {
    case 'districts':
        $rows = q("SELECT district_id, district_name FROM districts WHERE province_id=? ORDER BY district_name", [$id])->fetchAll(); break;
    case 'villages':
        $rows = q("SELECT village_id, village_name FROM villages WHERE district_id=? ORDER BY village_name", [$id])->fetchAll(); break;
    default: $rows = [];
}
header('Content-Type: application/json; charset=utf-8');
echo json_encode($rows, JSON_UNESCAPED_UNICODE);
