<?php
require_once __DIR__ . '/includes/functions.php';
if (customer()) redirect('my-account.php');
$pageTitle = 'ສະໝັກສະມາຊິກ';
$err = [];
$provinces = q("SELECT * FROM provinces ORDER BY province_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $d = [];
  foreach (['first_name', 'last_name', 'gender', 'birth_date', 'phone', 'email', 'id_card_no', 'license_no', 'license_expiry', 'village_id', 'address_detail'] as $k) $d[$k] = post($k);
  $pass = (string)($_POST['password'] ?? '');

  if ($d['first_name'] === '' || $d['last_name'] === '') $err[] = 'ກະລຸນາປ້ອນຊື່ ແລະ ນາມສະກຸນ';
  if (!preg_match('/^[0-9+\- ]{8,20}$/', $d['phone'])) $err[] = 'ເບີໂທບໍ່ຖືກຕ້ອງ';
  if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $err[] = 'ອີເມວບໍ່ຖືກຕ້ອງ';
  if (strlen($pass) < 6) $err[] = 'ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ';
  if ($pass !== (string)($_POST['password2'] ?? '')) $err[] = 'ລະຫັດຜ່ານຢືນຢັນບໍ່ກົງກັນ';
  if ($d['id_card_no'] === '' && $d['license_no'] === '') $err[] = 'ກະລຸນາປ້ອນເລກບັດປະຈຳຕົວ ຫຼື ເລກໃບຂັບຂີ່ຢ່າງໜ້ອຍ 1 ຢ່າງ';
  if (!$err && q("SELECT 1 FROM customers WHERE email=?", [$d['email']])->fetch()) $err[] = 'ອີເມວນີ້ຖືກໃຊ້ແລ້ວ';

  if (!$err) {
    try {
      $idPhoto = save_upload($_FILES['id_photo'] ?? [], 'customers', true);
      $licPhoto = save_upload($_FILES['license_photo'] ?? [], 'customers', true);
      q(
        "INSERT INTO customers (first_name,last_name,gender,birth_date,phone,email,password_hash,id_card_no,license_no,license_expiry,
                   village_id,address_detail,id_photo,license_photo,source,status)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'web','active')",
        [
          $d['first_name'],
          $d['last_name'],
          $d['gender'] ?: null,
          $d['birth_date'] ?: null,
          $d['phone'],
          $d['email'],
          password_hash($pass, PASSWORD_DEFAULT),
          $d['id_card_no'],
          $d['license_no'],
          $d['license_expiry'] ?: null,
          $d['village_id'] ?: null,
          $d['address_detail'],
          $idPhoto,
          $licPhoto
        ]
      );
      $cid = (int)db()->lastInsertId();
      notify('new_member', 'ສະມາຊິກໃໝ່', $d['first_name'] . ' ' . $d['last_name'] . ' · ' . $d['phone'], 'customers', $cid);
      session_regenerate_id(true);
      $_SESSION['customer_id'] = $cid;
      flash('success', 'ສະໝັກສະມາຊິກສຳເລັດ ທ່ານສາມາດຈອງລົດໄດ້ແລ້ວ');
      redirect('index.php');
    } catch (Exception $ex) {
      $err[] = $ex->getMessage();
    }
  }
}
$old = fn($k) => e($_POST[$k] ?? '');
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4" style="max-width:820px">
    <div class="panel">
        <h1 class="h4 mb-1">ສະໝັກສະມາຊິກ</h1>
        <p class="text-muted">ຂໍ້ມູນນີ້ໃຊ້ເພື່ອກຽມສັນຍາເຊົ່າ ແລະ ໃບບິນ. ພະນັກງານຈະເຫັນການສະໝັກຂອງທ່ານທັນທີ.</p>
        <?php if ($err): ?><div class="alert alert-danger">
            <ul class="mb-0"><?php foreach ($err as $m) echo '<li>' . e($m) . '</li>'; ?></ul>
        </div><?php endif; ?>
        <form method="post" enctype="multipart/form-data" class="row g-3">
            <?= csrf_field() ?>
            <div class="col-md-6"><label class="form-label">ຊື່ *</label><input name="first_name" class="form-control"
                    value="<?= $old('first_name') ?>" required></div>
            <div class="col-md-6"><label class="form-label">ນາມສະກຸນ *</label><input name="last_name"
                    class="form-control" value="<?= $old('last_name') ?>" required></div>
            <div class="col-md-6"><label class="form-label">ເພດ</label>
                <select name="gender" class="form-select">
                    <option value="">-</option>
                    <option value="M" <?= ($_POST['gender'] ?? '') === 'M' ? 'selected' : '' ?>>ຊາຍ</option>
                    <option value="F" <?= ($_POST['gender'] ?? '') === 'F' ? 'selected' : '' ?>>ຍິງ</option>
                </select>
            </div>
            <div class="col-md-6"><label class="form-label">ວັນເດືອນປີເກີດ</label><input type="date" name="birth_date"
                    class="form-control" value="<?= $old('birth_date') ?>"></div>
            <div class="col-md-6"><label class="form-label">ເບີໂທ *</label><input name="phone" class="form-control"
                    value="<?= $old('phone') ?>" required placeholder="020 xxxx xxxx"></div>
            <div class="col-md-6"><label class="form-label">ອີເມວ *</label><input type="email" name="email"
                    class="form-control" value="<?= $old('email') ?>" required></div>
            <div class="col-md-6"><label class="form-label">ລະຫັດຜ່ານ *</label><input type="password" name="password"
                    class="form-control" minlength="6" required></div>
            <div class="col-md-6"><label class="form-label">ຢືນຢັນລະຫັດຜ່ານ *</label><input type="password"
                    name="password2" class="form-control" required></div>
            <hr class="my-1">
            <div class="col-md-6"><label class="form-label">ເລກບັດປະຈຳຕົວ / ໜັງສືຜ່ານແດນ</label><input name="id_card_no"
                    class="form-control" value="<?= $old('id_card_no') ?>"></div>
            <div class="col-md-3"><label class="form-label">ເລກໃບຂັບຂີ່</label><input name="license_no"
                    class="form-control" value="<?= $old('license_no') ?>"></div>
            <div class="col-md-3"><label class="form-label">ໃບຂັບຂີ່ໝົດອາຍຸ</label><input type="date"
                    name="license_expiry" class="form-control" value="<?= $old('license_expiry') ?>"></div>
            <div class="col-md-6"><label class="form-label">ຮູບບັດປະຈຳຕົວ</label><input type="file" name="id_photo"
                    accept="image/*" class="form-control"></div>
            <div class="col-md-6"><label class="form-label">ຮູບໃບຂັບຂີ່</label><input type="file" name="license_photo"
                    accept="image/*" class="form-control"></div>
            <hr class="my-1">
            <div class="col-md-4"><label class="form-label">ແຂວງ</label>
                <select id="province" class="form-select">
                    <option value="">-- ເລືອກແຂວງ --</option>
                    <?php foreach ($provinces as $p) echo '<option value="' . $p['province_id'] . '">' . e($p['province_name']) . '</option>'; ?>
                </select>
            </div>
            <div class="col-md-4"><label class="form-label">ເມືອງ</label><select id="district" class="form-select">
                    <option value="">-- ເລືອກເມືອງ --</option>
                </select></div>
            <div class="col-md-4"><label class="form-label">ບ້ານ</label><select name="village_id" id="village"
                    class="form-select">
                    <option value="">-- ເລືອກບ້ານ --</option>
                </select></div>
            <div class="col-12"><label class="form-label">ທີ່ຢູ່ເພີ່ມເຕີມ</label><input name="address_detail"
                    class="form-control" value="<?= $old('address_detail') ?>"></div>
            <div class="col-12 d-grid d-md-flex gap-2">
                <button class="btn btn-warning px-4">ສະໝັກສະມາຊິກ</button>
                <a class="btn btn-link" href="<?= url('login.php') ?>">ມີບັນຊີແລ້ວ?</a>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>