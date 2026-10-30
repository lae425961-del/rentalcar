<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin([], true);
$action = $_REQUEST['action'] ?? 'list';

if ($action === 'list') {
    $items = q("SELECT notification_id,type,title,message,ref_table,ref_id,is_read,
                DATE_FORMAT(created_at,'%d/%m %H:%i') AS created_at
                FROM notifications ORDER BY notification_id DESC LIMIT 20")->fetchAll();
    $unread = (int)q("SELECT COUNT(*) FROM notifications WHERE is_read=0")->fetchColumn();
    json_out(['ok' => true, 'unread' => $unread, 'items' => $items]);
}
csrf_check();
if ($action === 'read') {
    q("UPDATE notifications SET is_read=1, read_by=? WHERE notification_id=?", [$u['user_id'], (int)$_POST['id']]);
    json_out(['ok' => true]);
}
if ($action === 'read_all') {
    q("UPDATE notifications SET is_read=1, read_by=? WHERE is_read=0", [$u['user_id']]);
    json_out(['ok' => true]);
}
json_out(['ok' => false, 'msg' => 'bad action'], 400);
