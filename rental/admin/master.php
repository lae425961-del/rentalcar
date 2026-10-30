<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = post('act');
  try {
    if ($act === 'add_province' && post('name') !== '') q("INSERT INTO provinces (province_name) VALUES (?)", [post('name')]);
    elseif ($act === 'add_district' && post('name') !== '' && post('parent')) q("INSERT INTO districts (province_id,district_name) VALUES (?,?)", [(int)post('parent'), post('name')]);
    elseif ($act === 'add_village' && post('name') !== '' && post('parent')) q("INSERT INTO villages (district_id,village_name) VALUES (?,?)", [(int)post('parent'), post('name')]);
    elseif ($act === 'add_type' && post('name') !== '') q("INSERT INTO vehicle_types (type_name,category) VALUES (?,?)", [post('name'), post('category') === 'motorcycle' ? 'motorcycle' : 'car']);
    elseif ($act === 'delete') {
      $map = ['province' => ['provinces', 'province_id'], 'district' => ['districts', 'district_id'], 'village' => ['villages', 'village_id'], 'type' => ['vehicle_types', 'type_id']];
      [$tb, $pk] = $map[post('kind')] ?? [null, null];
      if (!$tb) json_out(['ok' => false, 'msg' => 'bad kind'], 400);
      q("DELETE FROM $tb WHERE $pk=?", [(int)post('id')]);
      log_activity('delete_master', $tb, (int)post('id'));
      json_out(['ok' => true, 'msg' => 'ລົບແລ້ວ']);
    } elseif ($act === 'penalty') {
      q(
        "UPDATE penalty_rules SET grace_minutes=?, rate_percent=?, fee_per_unit=? WHERE rule_id=?",
        [(int)post('grace'), post('rate') !== '' ? (float)post('rate') : null, post('fee') !== '' ? (float)post('fee') : null, (int)post('id')]
      );
      log_activity('update_penalty', 'penalty_rules', (int)post('id'));
    }
    if ($act !== 'delete') flash('success', 'ບັນທຶກແລ້ວ');
  } catch (PDOException $ex) {
    $msg = $ex->getCode() == 23000 ? 'ລົບບໍ່ໄດ້ ເພາະມີຂໍ້ມູນອື່ນນຳໃຊ້ຢູ່' : 'ເກີດຂໍ້ຜິດພາດ';
    if ($act === 'delete') json_out(['ok' => false, 'msg' => $msg]);
    flash('error', $msg);
  }
  redirect('admin/master.php?tab=' . urlencode($_GET['tab'] ?? 'province'));
}

$pageTitle = 'ຂໍ້ມູນຫຼັກ: ແຂວງ / ເມືອງ / ບ້ານ / ປະເພດລົດ';
$tab = $_GET['tab'] ?? 'province';
$provinces = q("SELECT * FROM provinces ORDER BY province_name")->fetchAll();
$districts = q("SELECT d.*, p.province_name FROM districts d JOIN provinces p USING(province_id) ORDER BY p.province_name, d.district_name")->fetchAll();
$villages = q("SELECT v.*, d.district_name FROM villages v JOIN districts d USING(district_id) ORDER BY d.district_name, v.village_name LIMIT 500")->fetchAll();
$types = q("SELECT * FROM vehicle_types ORDER BY category, type_name")->fetchAll();
$rules = q("SELECT * FROM penalty_rules ORDER BY rule_id")->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
$tabs = ['province' => 'ແຂວງ', 'district' => 'ເມືອງ', 'village' => 'ບ້ານ', 'type' => 'ປະເພດລົດ', 'penalty' => 'ອັດຕາປັບຄືນຊ້າ'];
$del = fn($kind, $id) => '<button class="btn btn-sm btn-outline-danger js-post" data-confirm="ລົບລາຍການນີ້?" data-post=\'{"act":"delete","kind":"' . $kind . '","id":' . $id . '}\'><i class="bi bi-trash"></i></button>';
?>
<ul class="nav nav-pills mb-3">
    <?php foreach ($tabs as $k => $l) echo '<li class="nav-item"><a class="nav-link ' . ($tab === $k ? 'active' : '') . '" href="?tab=' . $k . '">' . $l . '</a></li>'; ?>
</ul>
<div class="panel">
    <?php if ($tab === 'province'): ?>
    <form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="act" value="add_province">
        <div class="col-md-5"><input name="name" class="form-control" placeholder="ຊື່ແຂວງ" required></div>
        <div class="col-auto"><button class="btn btn-primary">ເພີ່ມ</button></div>
    </form>
    <table class="table table-sm">
        <?php foreach ($provinces as $r) echo '<tr><td>' . e($r['province_name']) . '</td><td class="text-end">' . $del('province', $r['province_id']) . '</td></tr>'; ?>
    </table>
    <?php elseif ($tab === 'district'): ?>
    <form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="act" value="add_district">
        <div class="col-md-4"><select name="parent" class="form-select" required>
                <option value="">-- ແຂວງ --</option>
                <?php foreach ($provinces as $p) echo '<option value="' . $p['province_id'] . '">' . e($p['province_name']) . '</option>'; ?>
            </select></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="ຊື່ເມືອງ" required></div>
        <div class="col-auto"><button class="btn btn-primary">ເພີ່ມ</button></div>
    </form>
    <table class="table table-sm">
        <?php foreach ($districts as $r) echo '<tr><td>' . e($r['district_name']) . '</td><td class="text-muted">' . e($r['province_name']) . '</td><td class="text-end">' . $del('district', $r['district_id']) . '</td></tr>'; ?>
    </table>
    <?php elseif ($tab === 'village'): ?>
    <form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="act" value="add_village">
        <div class="col-md-4"><select name="parent" class="form-select" required>
                <option value="">-- ເມືອງ --</option>
                <?php foreach ($districts as $d) echo '<option value="' . $d['district_id'] . '">' . e($d['province_name'] . ' › ' . $d['district_name']) . '</option>'; ?>
            </select></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="ຊື່ບ້ານ" required></div>
        <div class="col-auto"><button class="btn btn-primary">ເພີ່ມ</button></div>
    </form>
    <table class="table table-sm">
        <?php foreach ($villages as $r) echo '<tr><td>' . e($r['village_name']) . '</td><td class="text-muted">' . e($r['district_name']) . '</td><td class="text-end">' . $del('village', $r['village_id']) . '</td></tr>'; ?>
    </table>
    <?php elseif ($tab === 'type'): ?>
    <form method="post" class="row g-2 mb-3"><?= csrf_field() ?><input type="hidden" name="act" value="add_type">
        <div class="col-md-3"><select name="category" class="form-select">
                <option value="car">ລົດໃຫຍ່</option>
                <option value="motorcycle">ລົດຈັກ</option>
            </select></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="ຊື່ປະເພດ ເຊັ່ນ SUV" required></div>
        <div class="col-auto"><button class="btn btn-primary">ເພີ່ມ</button></div>
    </form>
    <table class="table table-sm">
        <?php foreach ($types as $r) echo '<tr><td>' . e($r['type_name']) . '</td><td class="text-muted">' . ($r['category'] === 'car' ? 'ລົດໃຫຍ່' : 'ລົດຈັກ') . '</td><td class="text-end">' . $del('type', $r['type_id']) . '</td></tr>'; ?>
    </table>
    <?php else: ?>
    <p class="text-muted">ລະບົບຄິດ: ເກີນເວລາຜ່ອນຜັນ → ຄິດຕໍ່ຊົ່ວໂມງ (ສູງສຸດບໍ່ເກີນ 1 ວັນ) ແລະ ຄິດຕໍ່ວັນເມື່ອເກີນ 24
        ຊົ່ວໂມງ. ໃສ່ % ຂອງຄ່າເຊົ່າ/ວັນ ຫຼື ຈຳນວນເງິນຄົງທີ່ (ຢ່າງໃດຢ່າງໜຶ່ງ).</p>
    <table class="table align-middle">
        <thead>
            <tr>
                <th>ຫົວໜ່ວຍ</th>
                <th>ປະເພດ</th>
                <th>ຜ່ອນຜັນ (ນາທີ)</th>
                <th>% ຂອງຄ່າເຊົ່າ/ວັນ</th>
                <th>ເງິນຄົງທີ່ (₭)</th>
                <th></th>
            </tr>
        </thead>
        <?php foreach ($rules as $r): ?><tr>
            <form id="pf<?= $r['rule_id'] ?>" method="post"><?= csrf_field() ?><input type="hidden" name="act"
                    value="penalty"><input type="hidden" name="id" value="<?= $r['rule_id'] ?>"></form>
            <td><?= $r['unit'] === 'hour' ? 'ຕໍ່ຊົ່ວໂມງ' : 'ຕໍ່ວັນ' ?></td>
            <td><?= $r['category'] === 'all' ? 'ທຸກປະເພດ' : e($r['category']) ?></td>
            <td><input form="pf<?= $r['rule_id'] ?>" type="number" name="grace" class="form-control form-control-sm"
                    value="<?= (int)$r['grace_minutes'] ?>"></td>
            <td><input type="number" step="0.01" name="rate" class="form-control form-control-sm"
                    value="<?= e($r['rate_percent']) ?>"></td>
            <td><input form="pf<?= $r['rule_id'] ?>" type="number" name="fee" class="form-control form-control-sm"
                    value="<?= e($r['fee_per_unit']) ?>"></td>
            <td><button form="pf<?= $r['rule_id'] ?>" class="btn btn-sm btn-primary">ບັນທຶກ</button></td>
        </tr><?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>