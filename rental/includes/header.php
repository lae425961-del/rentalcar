<?php
require_once __DIR__ . '/functions.php';
$me = customer();
$pageTitle = $pageTitle ?? APP_NAME;
$cur = basename($_SERVER['SCRIPT_NAME']);
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf" content="<?= csrf_token() ?>">
    <title><?= e($pageTitle) ?> | <?= APP_NAME ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&family=Sora:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>

<body class="site">
    <nav class="navbar navbar-expand-lg site-nav">
        <div class="container">
            <a class="navbar-brand brand" href="<?= url() ?>"><i class="bi bi-signpost-2-fill"></i> <?= APP_NAME ?></a>
            <button class="navbar-toggler border-0" data-bs-toggle="collapse" data-bs-target="#nav"><i
                    class="bi bi-list text-white fs-2"></i></button>
            <div class="collapse navbar-collapse" id="nav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link <?= $cur === 'index.php' ? 'active' : '' ?>"
                            href="<?= url() ?>">ລົດທັງໝົດ</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('?category=car') ?>">ລົດໃຫຍ່</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('?category=motorcycle') ?>">ລົດຈັກ</a></li>
                </ul>
                <div class="d-flex gap-2 align-items-center">
                    <?php if ($me): ?>
                    <a class="btn btn-sm btn-outline-light" href="<?= url('my-account.php') ?>"><i
                            class="bi bi-person-circle"></i> <?= e($me['first_name']) ?></a>
                    <a class="btn btn-sm btn-warning" href="<?= url('logout.php') ?>">ອອກຈາກລະບົບ</a>
                    <?php else: ?>
                    <a class="btn btn-sm btn-outline-light" href="<?= url('login.php') ?>">ເຂົ້າສູ່ລະບົບ</a>
                    <a class="btn btn-sm btn-warning" href="<?= url('register.php') ?>">ສະໝັກສະມາຊິກ</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>