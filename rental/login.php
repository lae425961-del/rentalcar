<?php
require_once __DIR__ . '/includes/functions.php';
if (customer()) redirect('index.php');
$pageTitle = 'ເຂົ້າສູ່ລະບົບ';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    // ກັນເດົາລະຫັດ: ຈຳກັດ 5 ຄັ້ງ / 10 ນາທີ ຕໍ່ເຊດຊັນ
    $_SESSION['try'] = array_filter($_SESSION['try'] ?? [], fn($t) => $t > time() - 600);
    if (count($_SESSION['try']) >= 5) {
        $err = 'ພະຍາຍາມຫຼາຍເກີນໄປ ກະລຸນາລໍຖ້າ 10 ນາທີ';
    } else {
        $id = post('login');
        $c = q("SELECT * FROM customers WHERE (email=? OR phone=?) AND password_hash IS NOT NULL LIMIT 1", [$id, $id])->fetch();
        if ($c && password_verify((string)$_POST['password'], $c['password_hash'])) {
            if ($c['status'] !== 'active') {
                $err = 'ບັນຊີຖືກລະງັບ ກະລຸນາຕິດຕໍ່ພະນັກງານ';
            } else {
                session_regenerate_id(true);
                $_SESSION['customer_id'] = $c['customer_id'];
                $_SESSION['try'] = [];
                $back = $_SESSION['back'] ?? '';
                unset($_SESSION['back']);
                redirect(($back && str_starts_with($back, BASE_URL)) ? $back : 'index.php');
            }
        } else {
            $_SESSION['try'][] = time();
            $err = 'ອີເມວ/ເບີໂທ ຫຼື ລະຫັດຜ່ານບໍ່ຖືກຕ້ອງ';
        }
    }
}
include __DIR__ . '/includes/header.php';
?>
<div class="container py-5" style="max-width:440px">
    <div class="panel">
        <h1 class="h4 mb-3">ເຂົ້າສູ່ລະບົບ</h1>
        <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <div class="mb-3"><label class="form-label">ອີເມວ ຫຼື ເບີໂທ</label><input name="login" class="form-control"
                    required autofocus></div>
            <div class="mb-3"><label class="form-label">ລະຫັດຜ່ານ</label><input type="password" name="password"
                    class="form-control" required></div>
            <button class="btn btn-primary w-100">ເຂົ້າສູ່ລະບົບ</button>
        </form>
        <p class="mt-3 mb-0 text-center">ຍັງບໍ່ມີບັນຊີ? <a href="<?= url('register.php') ?>">ສະໝັກສະມາຊິກ</a></p>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>