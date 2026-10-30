<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_customer();
$pageTitle = 'ບັນຊີຂອງຂ້ອຍ';
$bookings = q("SELECT b.*, v.brand, v.model, v.plate_no FROM bookings b JOIN vehicles v ON v.vehicle_id=b.vehicle_id
               WHERE b.customer_id=? ORDER BY b.booking_id DESC", [$me['customer_id']])->fetchAll();
$rentals = q("SELECT r.*, v.brand, v.model, v.plate_no FROM rentals r JOIN vehicles v ON v.vehicle_id=r.vehicle_id
              WHERE r.customer_id=? ORDER BY r.rental_id DESC", [$me['customer_id']])->fetchAll();
include __DIR__ . '/includes/header.php';
?>
<div class="container py-4">
    <div class="panel mb-3">
        <h1 class="h5 mb-1"><?= e($me['first_name'] . ' ' . $me['last_name']) ?></h1>
        <div class="text-muted"><?= e($me['phone']) ?> · <?= e($me['email']) ?></div>
    </div>

    <h2 class="h6">ການຈອງຂອງຂ້ອຍ</h2>
    <div class="panel p-0 table-responsive mb-4">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ລົດ</th>
                    <th>ຮັບ</th>
                    <th>ຄືນ</th>
                    <th class="text-end">ປະມານ</th>
                    <th>ສະຖານະ</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($bookings as $b): ?>
                <tr>
                    <td><?= e($b['booking_code']) ?></td>
                    <td><?= e($b['brand'] . ' ' . $b['model']) ?><br><span class="plate"><?= e($b['plate_no']) ?></span>
                    </td>
                    <td><?= dt($b['pickup_datetime']) ?></td>
                    <td><?= dt($b['return_datetime']) ?></td>
                    <td class="text-end"><?= money($b['est_amount']) ?></td>
                    <td><?= badge($b['status']) ?><?php if ($b['status'] === 'rejected' && $b['admin_note']): ?><div
                            class="small text-muted"><?= e($b['admin_note']) ?></div><?php endif; ?></td>
                    <td><?php if (in_array($b['status'], ['pending', 'confirmed'], true)): ?>
                        <button class="btn btn-sm btn-outline-danger js-post" data-url="<?= url('api/booking.php') ?>"
                            data-confirm="ຍົກເລີກການຈອງນີ້?"
                            data-post='{"action":"cancel","id":<?= (int)$b['booking_id'] ?>}'>ຍົກເລີກ</button><?php endif; ?>
                    </td>
                </tr>
                <?php endforeach;
        if (!$bookings) echo '<tr><td colspan="7" class="text-center text-muted py-4">ຍັງບໍ່ມີການຈອງ</td></tr>'; ?>
            </tbody>
        </table>
    </div>

    <h2 class="h6">ປະຫວັດການເຊົ່າ</h2>
    <div class="panel p-0 table-responsive">
        <table class="table mb-0 align-middle">
            <thead>
                <tr>
                    <th>ລະຫັດ</th>
                    <th>ລົດ</th>
                    <th>ເລີ່ມ</th>
                    <th>ກຳນົດຄືນ</th>
                    <th class="text-end">ຄ່າເຊົ່າ</th>
                    <th>ສະຖານະ</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rentals as $r): ?>
                <tr>
                    <td><?= e($r['rental_code']) ?></td>
                    <td><?= e($r['brand'] . ' ' . $r['model']) ?></td>
                    <td><?= dt($r['start_datetime']) ?></td>
                    <td><?= dt($r['due_datetime']) ?></td>
                    <td class="text-end"><?= money($r['total_amount']) ?></td>
                    <td><?= badge($r['status']) ?></td>
                </tr>
                <?php endforeach;
        if (!$rentals) echo '<tr><td colspan="6" class="text-center text-muted py-4">ຍັງບໍ່ມີປະຫວັດການເຊົ່າ</td></tr>'; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>