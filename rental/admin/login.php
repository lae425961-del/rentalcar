<?php
require_once __DIR__ . '/../includes/functions.php';
if (admin()) redirect('admin/index.php');
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $_SESSION['atry'] = array_filter($_SESSION['atry'] ?? [], fn($t) => $t > time() - 600);
    if (count($_SESSION['atry']) >= 5) {
        $err = 'ພະຍາຍາມຫຼາຍເກີນໄປ ກະລຸນາລໍຖ້າ 10 ນາທີ';
    } else {
        $u = q("SELECT * FROM users WHERE username=? AND status='active'", [post('username')])->fetch();
        if ($u && password_verify((string)$_POST['password'], $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $u['user_id'];
            $_SESSION['atry'] = [];
            q("UPDATE users SET last_login=NOW() WHERE user_id=?", [$u['user_id']]);
            log_activity('login');
            redirect('admin/index.php');
        }
        $_SESSION['atry'][] = time();
        $err = 'ຊື່ຜູ້ໃຊ້ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ';
    }
}
?>
<!DOCTYPE html>
<html lang="lo"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>ເຂົ້າສູ່ລະບົບຫຼັງບ້ານ | <?= APP_NAME ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&family=Sora:wght@600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="<?= url('assets/css/style.css') ?>" rel="stylesheet"></head>
<body style="background:var(--mekong)">
<div class="container d-flex align-items-center" style="min-height:100vh;max-width:420px">
  <div class="panel w-100" style="border-top:6px solid var(--saffron)">
    <div class="brand text-dark mb-3 fs-4" style="color:var(--mekong)!important"><?= APP_NAME ?> <small class="fw-normal text-muted fs-6">ຫຼັງບ້ານ</small></div>
    <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>
    <form method="post"><?= csrf_field() ?>
      <div class="mb-3"><label class="form-label">ຊື່ຜູ້ໃຊ້</label><input name="username" class="form-control" required autofocus></div>
      <div class="mb-3"><label class="form-label">ລະຫັດຜ່ານ</label><input type="password" name="password" class="form-control" required></div>
      <button class="btn btn-primary w-100">ເຂົ້າສູ່ລະບົບ</button>
    </form>
  </div>
</div></body></html>
