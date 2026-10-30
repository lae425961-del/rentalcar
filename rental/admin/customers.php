<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = post('act');
    if ($act === 'status') {
        $s = post('status') === 'blocked' ? 'blocked' : 'active';
        q("UPDATE customers SET status=? WHERE customer_id=?", [$s, (int)post('id')]);
        log_activity('customer_' . $s, 'customers', (int)post('id'));
        json_out(['ok' => true, 'msg' => 'ອັບເດດສະຖານະແລ້ວ']);
    }
    if ($act === 'add') {
        try {
            if (post('first_name') === '' || post('phone') === '') throw new Exception('ກະລຸນາປ້ອນຊື່ ແລະ ເບີໂທ');
            q(
                "INSERT INTO customers (first_name,last_name,gender,phone,id_card_no,license_no,village_id,address_detail,source,status)
               VALUES (?,?,?,?,?,?,?,?, 'counter','active')",
                [
                    post('first_name'),
                    post('last_name'),
                    post('gender') ?: null,
                    post('phone'),
                    post('id_card_no'),
                    post('license_no'),
                    post('village_id') ?: null,
                    post('address_detail')
                ]
            );
            log_activity('add_customer', 'customers', (int)db()->lastInsertId());
            flash('success', 'ເພີ່ມລູກຄ້າແລ້ວ');
        } catch (Exception $ex) {
            flash('error', $ex->getMessage());
        }
        redirect('admin/customers.php');
    }
    json_out(['ok' => false, 'msg' => 'bad action'], 400);
}

$pageTitle = 'ລູກຄ້າ';
$kw = trim($_GET['kw'] ?? '');
$sql = "SELECT c.*, CONCAT_WS(', ', vl.village_name, d.district_name, p.province_name) addr FROM customers c
        LEFT JOIN villages vl ON vl.village_id=c.village_id LEFT JOIN districts d ON d.district_id=vl.district_id
        LEFT JOIN provinces p ON p.province_id=d.province_id";
$p = [];
if ($kw) {
    $sql .= " WHERE c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?";
    $p = array_fill(0, 4, "%$kw%");
}
$rows = q($sql . " ORDER BY c.customer_id DESC LIMIT 300", $p)->fetchAll();
$provinces = q("SELECT * FROM provinces ORDER BY province_name")->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex gap-2 mb-3">
    <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#addModal"><i class="bi bi-person-plus"></i>
        ເພີ່ມລູກຄ້າ (ໜ້າຮ້ານ)</button>
    <form class="ms-auto"><input name="kw" class="form-control" placeholder="ຄົ້ນຫາຊື່/ເບີ/ອີເມວ" value="<?= e($kw) ?>">
    </form>
</div>
<div class="panel p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>ຊື່</th>
                <th>ຕິດຕໍ່</th>
                <th>ເອກະສານ</th>
                <th>ທີ່ຢູ່</th>
                <th>ແຫຼ່ງ</th>
                <th>ສະຖານະ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $c): ?>
            <tr>
                <td><?= e($c['first_name'] . ' ' . $c['last_name']) ?><br><small class="text-muted">ສະໝັກ
                        <?= dt($c['created_at'], 'd/m/Y') ?></small></td>
                <td><?= e($c['phone']) ?><br><small class="text-muted"><?= e($c['email']) ?></small></td>
                <td class="small">ບັດ: <?= e($c['id_card_no'] ?: '-') ?><br>ໃບຂັບຂີ່: <?= e($c['license_no'] ?: '-') ?>
                    <?php foreach (['id_photo' => 'ຮູບບັດ', 'license_photo' => 'ຮູບໃບຂັບຂີ່'] as $k => $l) if ($c[$k]) echo '<br><a target="_blank" href="file.php?f=' . urlencode($c[$k]) . '">' . $l . '</a>'; ?>
                </td>
                <td class="small"><?= e($c['addr']) ?></td>
                <td><?= $c['source'] === 'web' ? '<span class="badge text-bg-info">ເວັບ</span>' : '<span class="badge text-bg-light border">ໜ້າຮ້ານ</span>' ?>
                </td>
                <td><?= badge($c['status']) ?></td>
                <td><?php if ($c['status'] === 'active'): ?>
                    <button class="btn btn-sm btn-outline-danger js-post" data-confirm="ລະງັບບັນຊີນີ້?"
                        data-post='{"act":"status","status":"blocked","id":<?= $c['customer_id'] ?>}'>ລະງັບ</button>
                    <?php else: ?>
                    <button class="btn btn-sm btn-outline-success js-post" data-confirm="ເປີດໃຊ້ບັນຊີນີ້?"
                        data-post='{"act":"status","status":"active","id":<?= $c['customer_id'] ?>}'>ເປີດໃຊ້</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach;
            if (!$rows) echo '<tr><td colspan="7" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>'; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" class="modal-content">
            <?= csrf_field() ?><input type="hidden" name="act" value="add">
            <div class="modal-header">
                <h5 class="modal-title">ເພີ່ມລູກຄ້າ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-6"><label class="form-label">ຊື່ *</label><input name="first_name"
                        class="form-control" required></div>
                <div class="col-md-6"><label class="form-label">ນາມສະກຸນ</label><input name="last_name"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">ເພດ</label><select name="gender" class="form-select">
                        <option value="">-</option>
                        <option value="M">ຊາຍ</option>
                        <option value="F">ຍິງ</option>
                    </select></div>
                <div class="col-md-8"><label class="form-label">ເບີໂທ *</label><input name="phone" class="form-control"
                        required></div>
                <div class="col-md-6"><label class="form-label">ເລກບັດປະຈຳຕົວ</label><input name="id_card_no"
                        class="form-control"></div>
                <div class="col-md-6"><label class="form-label">ເລກໃບຂັບຂີ່</label><input name="license_no"
                        class="form-control"></div>
                <div class="col-md-4"><label class="form-label">ແຂວງ</label><select id="province" class="form-select">
                        <option value="">--</option>
                        <?php foreach ($provinces as $pv) echo '<option value="' . $pv['province_id'] . '">' . e($pv['province_name']) . '</option>'; ?>
                    </select></div>
                <div class="col-md-4"><label class="form-label">ເມືອງ</label><select id="district" class="form-select">
                        <option value="">--</option>
                    </select></div>
                <div class="col-md-4"><label class="form-label">ບ້ານ</label><select name="village_id" id="village"
                        class="form-select">
                        <option value="">--</option>
                    </select></div>
                <div class="col-12"><label class="form-label">ທີ່ຢູ່ເພີ່ມເຕີມ</label><input name="address_detail"
                        class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">ບັນທຶກ</button></div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>