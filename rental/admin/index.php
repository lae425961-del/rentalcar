<?php
$pageTitle = 'ແຜງຄວບຄຸມ';
require_once __DIR__ . '/../includes/admin_header.php';
mark_overdue();
$n = fn($sql) => (int)q($sql)->fetchColumn();
$s = [
  'available' => $n("SELECT COUNT(*) FROM vehicles WHERE status='available'"),
  'rented'    => $n("SELECT COUNT(*) FROM rentals WHERE status IN ('active','overdue')"),
  'overdue'   => $n("SELECT COUNT(*) FROM rentals WHERE status='overdue'"),
  'pending'   => $n("SELECT COUNT(*) FROM bookings WHERE status='pending'"),
];
$today = (float)q("SELECT COALESCE(SUM(CASE WHEN payment_type='refund' THEN -amount ELSE amount END),0) FROM payments WHERE DATE(paid_at)=CURDATE()")->fetchColumn();
$month = (float)q("SELECT COALESCE(SUM(CASE WHEN payment_type='refund' THEN -amount ELSE amount END),0) FROM payments WHERE DATE_FORMAT(paid_at,'%Y-%m')=DATE_FORMAT(CURDATE(),'%Y-%m')")->fetchColumn();
$pend = q("SELECT b.*, CONCAT(c.first_name,' ',c.last_name) cname, v.brand, v.model FROM bookings b
           JOIN customers c ON c.customer_id=b.customer_id JOIN vehicles v ON v.vehicle_id=b.vehicle_id
           WHERE b.status='pending' ORDER BY b.booking_id DESC LIMIT 6")->fetchAll();
$over = q("SELECT * FROM v_overdue_rentals ORDER BY late_minutes DESC LIMIT 6")->fetchAll();
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="stat ok">
            <div class="n"><?= $s['available'] ?></div>ລົດວ່າງ
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat">
            <div class="n"><?= $s['rented'] ?></div>ກຳລັງເຊົ່າ
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat bad">
            <div class="n"><?= $s['overdue'] ?></div>ເກີນກຳນົດຄືນ
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="stat warn">
            <div class="n"><?= $s['pending'] ?></div>ການຈອງລໍຖ້າຢືນຢັນ
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat ok">
            <div class="n"><?= money($today) ?></div>ລາຍຮັບມື້ນີ້
        </div>
    </div>
    <div class="col-md-6">
        <div class="stat ok">
            <div class="n"><?= money($month) ?></div>ລາຍຮັບເດືອນນີ້
        </div>
    </div>
</div>
<div class="row g-3">
    <div class="col-xl-6">
        <div class="panel p-0">
            <div class="p-3 border-bottom d-flex justify-content-between"><strong>ການຈອງລໍຖ້າຢືນຢັນ</strong><a
                    href="bookings.php?status=pending">ເບິ່ງທັງໝົດ</a></div>
            <table class="table mb-0">
                <tbody>
                    <?php foreach ($pend as $b): ?><tr>
                        <td><?= e($b['booking_code']) ?></td>
                        <td><?= e($b['cname']) ?></td>
                        <td><?= e($b['brand'] . ' ' . $b['model']) ?></td>
                        <td><?= dt($b['pickup_datetime'], 'd/m H:i') ?></td>
                    </tr>
                    <?php endforeach;
          if (!$pend) echo '<tr><td class="text-muted text-center py-4">ບໍ່ມີລາຍການຄ້າງ</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-xl-6">
        <div class="panel p-0">
            <div class="p-3 border-bottom d-flex justify-content-between"><strong
                    class="text-danger">ລົດເກີນກຳນົດຄືນ</strong><a href="rentals.php?status=overdue">ເບິ່ງທັງໝົດ</a>
            </div>
            <table class="table mb-0">
                <tbody>
                    <?php foreach ($over as $o): ?><tr>
                        <td><?= e($o['rental_code']) ?></td>
                        <td><?= e($o['customer']) ?><br><small class="text-muted"><?= e($o['phone']) ?></small></td>
                        <td><span class="plate"><?= e($o['plate_no']) ?></span></td>
                        <td class="text-danger"><?= late_text((int)$o['late_minutes']) ?></td>
                    </tr>
                    <?php endforeach;
          if (!$over) echo '<tr><td class="text-muted text-center py-4">ບໍ່ມີລົດເກີນກຳນົດ</td></tr>'; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>