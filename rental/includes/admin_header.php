<?php
require_once __DIR__ . '/functions.php';
$adm = require_admin();
$pageTitle = $pageTitle ?? 'ແຜງຄວບຄຸມ';
$cur = basename($_SERVER['SCRIPT_NAME']);
$menu = [
  ['index.php',     'bi-speedometer2',   'ແຜງຄວບຄຸມ',  ['admin','staff','cashier']],
  ['bookings.php',  'bi-calendar-check', 'ການຈອງ',      ['admin','staff']],
  ['rentals.php',   'bi-key',            'ການເຊົ່າ / ຄືນລົດ', ['admin','staff','cashier']],
  ['customers.php', 'bi-people',         'ລູກຄ້າ',      ['admin','staff']],
  ['vehicles.php',  'bi-car-front',      'ຂໍ້ມູນລົດ',   ['admin','staff']],
  ['master.php',    'bi-geo-alt',        'ແຂວງ/ເມືອງ/ບ້ານ/ປະເພດ', ['admin']],
  ['reports.php',   'bi-bar-chart-line', 'ລາຍງານ',      ['admin','cashier']],
  ['users.php',     'bi-person-gear',    'ຜູ້ນຳໃຊ້ລະບົບ', ['admin']],
];
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> | ຫຼັງບ້ານ <?= APP_NAME ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&family=Sora:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>

<body class="adm">
    <div class="adm-wrap">
        <aside class="adm-side" id="side">
            <div class="brand p-3"><i class="bi bi-signpost-2-fill"></i> <?= APP_NAME ?><small
                    class="d-block fw-normal opacity-75">ຫຼັງບ້ານ</small></div>
            <nav class="nav flex-column px-2">
                <?php foreach ($menu as [$f, $ic, $label, $roles]): if (!in_array($adm['role_name'], $roles, true)) continue; ?>
                <a class="nav-link <?= $cur === $f ? 'active' : '' ?>" href="<?= url('admin/' . $f) ?>"><i
                        class="bi <?= $ic ?>"></i> <?= $label ?></a>
                <?php endforeach; ?>
            </nav>
            <div class="p-3 mt-auto small opacity-75">
                <?= e($adm['full_name']) ?> (<?= e($adm['role_name']) ?>)<br>
                <a class="text-warning" href="<?= url('admin/logout.php') ?>">ອອກຈາກລະບົບ</a>
            </div>
        </aside>
        <main class="adm-main">
            <header class="adm-top d-flex align-items-center gap-3">
                <button class="btn btn-light d-lg-none"
                    onclick="document.getElementById('side').classList.toggle('open')"><i
                        class="bi bi-list"></i></button>
                <h1 class="h5 m-0 flex-grow-1"><?= e($pageTitle) ?></h1>
                <a class="btn btn-sm btn-outline-secondary" href="<?= url() ?>" target="_blank"><i
                        class="bi bi-box-arrow-up-right"></i> ເບິ່ງເວັບ</a>
                <div class="dropdown">
                    <button class="btn btn-light position-relative" data-bs-toggle="dropdown"
                        data-bs-auto-close="outside" id="bellBtn">
                        <i class="bi bi-bell-fill"></i>
                        <span
                            class="badge rounded-pill bg-danger position-absolute top-0 start-100 translate-middle d-none"
                            id="bellCount">0</span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end p-0 notif-menu">
                        <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                            <strong>ແຈ້ງເຕືອນ</strong>
                            <a href="#" id="readAll" class="small">ອ່ານທັງໝົດ</a>
                        </div>
                        <div id="notifList">
                            <div class="p-3 text-muted small">ກຳລັງໂຫຼດ...</div>
                        </div>
                    </div>
                </div>
            </header>
            <div class="p-3 p-lg-4">