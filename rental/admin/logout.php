<?php
require_once __DIR__ . '/../includes/functions.php';
if (admin()) log_activity('logout');
unset($_SESSION['user_id']);
session_regenerate_id(true);
redirect('admin/login.php');
