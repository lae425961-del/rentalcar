<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)post('id');
    $act = post('act');
    $b = q("SELECT * FROM bookings WHERE booking_id=?", [$id])->fetch();
    if (!$b || $b['status'] !== 'pending') json_out(['ok' => false, 'msg' => 'ລາຍການນີ້ຖືກດຳເນີນການແລ້ວ']);
    if ($act === 'confirm') {
        if (!is_vehicle_available((int)$b['vehicle_id'], $b['pickup_datetime'], $b['return_datetime'], $id))
            json_out(['ok' => false, 'msg' => 'ລົດບໍ່ວ່າງໃນຊ່ວງເວລານີ້ ກະລຸນາປະຕິເສດ ຫຼື ຕິດຕໍ່ລູກຄ້າ']);
        q("UPDATE bookings SET status='confirmed', handled_by=? WHERE booking_id=?", [$u['user_id'], $id]);
        log_activity('confirm_booking', 'bookings', $id, $b['booking_code']);
        json_out(['ok' => true, 'msg' => 'ຢືນຢັນການຈອງແລ້ວ']);
    }
    if ($act === 'reject') {
        q("UPDATE bookings SET status='rejected', admin_note=?, handled_by=? WHERE booking_id=?", [post('note'), $u['user_id'], $id]);
        log_activity('reject_booking', 'bookings', $id, post('note'));
        json_out(['ok' => true, 'msg' => 'ປະຕິເສດການຈອງແລ້ວ']);
    }
    json_out(['ok' => false, 'msg' => 'bad action'], 400);
}

$pageTitle = 'ການຈອງ';
$st = $_GET['status'] ?? '';
$sql = "SELECT b.*, CONCAT(c.first_name,' ',c.last_name) cname, c.phone, v.brand, v.model, v.plate_no
        FROM bookings b JOIN customers c ON c.customer_id=b.customer_id JOIN vehicles v ON v.vehicle_id=b.vehicle_id";
$p = [];
if ($st) {
    $sql .= " WHERE b.status=?";
    $p[] = $st;
}
$rows = q($sql . " ORDER BY b.booking_id DESC LIMIT 200", $p)->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="mb-3 d-flex gap-2 flex-wrap">
    <?php foreach (['' => 'ທັງໝົດ', 'pending' => 'ລໍຖ້າ', 'confirmed' => 'ຢືນຢັນແລ້ວ', 'converted' => 'ເປີດເຊົ່າແລ້ວ', 'rejected' => 'ປະຕິເສດ', 'cancelled' => 'ຍົກເລີກ'] as $k => $l): ?>
    <a class="btn btn-sm <?= $st === $k ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="?status=<?= $k ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>
<div class="panel p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>ລະຫັດ</th>
                <th>ລູກຄ້າ</th>
                <th>ລົດ</th>
                <th>ຮັບ</th>
                <th>ຄືນ</th>
                <th class="text-end">ປະມານ</th>
                <th>ສະຖານະ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $b): ?>
            <tr>
                <td><?= e($b['booking_code']) ?><br><small
                        class="text-muted"><?= dt($b['created_at'], 'd/m H:i') ?></small></td>
                <td><?= e($b['cname']) ?><br><small class="text-muted"><?= e($b['phone']) ?></small></td>
                <td><?= e($b['brand'] . ' ' . $b['model']) ?><br><span class="plate"><?= e($b['plate_no']) ?></span>
                </td>
                <td><?= dt($b['pickup_datetime']) ?></td>
                <td><?= dt($b['return_datetime']) ?></td>
                <td class="text-end"><?= money($b['est_amount']) ?></td>
                <td><?= badge($b['status']) ?></td>
                <td class="text-nowrap">
                    <?php if ($b['status'] === 'pending'): ?>
                    <button class="btn btn-sm btn-success js-post" data-confirm="ຢືນຢັນການຈອງນີ້?"
                        data-post='{"act":"confirm","id":<?= $b['booking_id'] ?>}'>ຢືນຢັນ</button>
                    <button class="btn btn-sm btn-outline-danger js-post" data-confirm="ປະຕິເສດການຈອງ"
                        data-input="ເຫດຜົນ (ລູກຄ້າຈະເຫັນ)"
                        data-post='{"act":"reject","id":<?= $b['booking_id'] ?>}'>ປະຕິເສດ</button>
                    <?php elseif ($b['status'] === 'confirmed'): ?>
                    <a class="btn btn-sm btn-primary"
                        href="rentals.php?new=1&booking_id=<?= $b['booking_id'] ?>">ປ່ອຍລົດ / ເປີດເຊົ່າ</a>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach;
            if (!$rows) echo '<tr><td colspan="8" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>'; ?>
        </tbody>
    </table>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>