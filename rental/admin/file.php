<?php
// ສະແດງເອກະສານລູກຄ້າ (ເກັບນອກ web root) ສະເພາະພະນັກງານທີ່ login
require_once __DIR__ . '/../includes/functions.php';
require_admin(['admin', 'staff']);
$path = realpath(STORAGE_DIR . ($_GET['f'] ?? ''));
$root = realpath(STORAGE_DIR);
if (!$path || !$root || strpos($path, $root) !== 0 || !is_file($path)) { http_response_code(404); exit; }
header('Content-Type: ' . (new finfo(FILEINFO_MIME_TYPE))->file($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
