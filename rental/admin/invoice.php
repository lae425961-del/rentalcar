<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff', 'cashier']);
$id = (int)($_GET['rental_id'] ?? 0);

/* ຮັບຊຳລະເພີ່ມ (ໃບບິນທີ່ຍັງຄ້າງ) */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'pay') {
  csrf_check();
  $inv0 = q("SELECT * FROM invoices WHERE rental_id=? ORDER BY invoice_id DESC LIMIT 1", [$id])->fetch();
  $due = $inv0 ? (float)$inv0['grand_total'] - (float)$inv0['paid_amount'] : 0;
  $amt = min($due, max(0, (float)post('amount')));
  if ($inv0 && $amt > 0) {
    $m = in_array(post('method'), ['cash', 'transfer', 'qr'], true) ? post('method') : 'cash';
    q("INSERT INTO payments (invoice_id,rental_id,payment_type,method,amount,received_by) VALUES (?,?, 'rent', ?, ?, ?)", [$inv0['invoice_id'], $id, $m, $amt, $u['user_id']]);
    $paid = (float)$inv0['paid_amount'] + $amt;
    q("UPDATE invoices SET paid_amount=?, status=? WHERE invoice_id=?", [$paid, $paid + 0.01 >= (float)$inv0['grand_total'] ? 'paid' : 'partial', $inv0['invoice_id']]);
    log_activity('payment', 'invoices', (int)$inv0['invoice_id'], (string)$amt);
    flash('success', 'ບັນທຶກການຊຳລະແລ້ວ');
  } else flash('error', 'ຈຳນວນເງິນບໍ່ຖືກຕ້ອງ');
  redirect('admin/invoice.php?rental_id=' . $id);
}
$r = q("SELECT r.*, v.brand, v.model, v.plate_no, v.color, t.type_name,
               CONCAT(c.first_name,' ',c.last_name) cname, c.phone, c.id_card_no, c.license_no, c.address_detail,
               CONCAT_WS(', ', vl.village_name, d.district_name, p.province_name) addr, us.full_name staff
        FROM rentals r JOIN vehicles v ON v.vehicle_id=r.vehicle_id JOIN vehicle_types t ON t.type_id=v.type_id
        JOIN customers c ON c.customer_id=r.customer_id JOIN users us ON us.user_id=r.user_id
        LEFT JOIN villages vl ON vl.village_id=c.village_id LEFT JOIN districts d ON d.district_id=vl.district_id
        LEFT JOIN provinces p ON p.province_id=d.province_id WHERE r.rental_id=?", [$id])->fetch();
if (!$r) {
  http_response_code(404);
  exit('ບໍ່ພົບຂໍ້ມູນ');
}
$inv = q("SELECT * FROM invoices WHERE rental_id=? ORDER BY invoice_id DESC LIMIT 1", [$id])->fetch();
$ret = q("SELECT * FROM returns WHERE rental_id=?", [$id])->fetch();
$pays = q("SELECT * FROM payments WHERE rental_id=? ORDER BY payment_id", [$id])->fetchAll();
$typeLbl = ['deposit' => 'ເງິນມັດຈຳ', 'rent' => 'ຄ່າເຊົ່າ/ຄ່າປັບ', 'late_fee' => 'ຄ່າປັບ', 'damage' => 'ຄ່າເສຍຫາຍ', 'refund' => 'ຄືນເງິນມັດຈຳ'];
$title = $inv ? 'ໃບບິນ ' . $inv['invoice_no'] : 'ສັນຍາເຊົ່າ ' . $r['rental_code'];
?>
<!DOCTYPE html>
<html lang="lo">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <link
        href="https://fonts.googleapis.com/css2?family=Noto+Sans+Lao:wght@400;500;700&family=Sora:wght@600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>

<body style="background:#e5eaee">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <div class="no-print text-center py-3">
        <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> ພິມ / ບັນທຶກເປັນ
            PDF</button>
        <a class="btn btn-light" href="rentals.php">ກັບຄືນ</a>
    </div>
    <?php if ($inv && in_array($inv['status'], ['unpaid', 'partial'], true)): ?>
    <form method="post" class="no-print d-flex gap-2 justify-content-center pb-3"><?= csrf_field() ?><input
            type="hidden" name="act" value="pay">
        <input type="number" name="amount" class="form-control w-auto"
            value="<?= (int)($inv['grand_total'] - $inv['paid_amount']) ?>" min="1">
        <select name="method" class="form-select w-auto">
            <option value="cash">ເງິນສົດ</option>
            <option value="transfer">ໂອນ</option>
            <option value="qr">QR</option>
        </select>
        <button class="btn btn-warning">ຮັບຊຳລະຍອດຄ້າງ</button>
    </form>
    <?php endif; ?>
    <div class="invoice mb-5">
        <div class="d-flex justify-content-between border-bottom pb-3 mb-3">
            <div>
                <div class="brand fs-3" style="color:var(--mekong)!important"><?= APP_NAME ?></div>
                <div class="text-muted small">ບໍລິການເຊົ່າລົດໃຫຍ່ ແລະ ລົດຈັກ</div>
            </div>
            <div class="text-end">
                <div class="h5 mb-1"><?= $inv ? 'ໃບບິນ / ໃບຮັບເງິນ' : 'ສັນຍາເຊົ່າລົດ' ?></div>
                <div><?= e($inv['invoice_no'] ?? $r['rental_code']) ?></div>
                <div class="small text-muted">ວັນທີ <?= dt($inv['issue_date'] ?? $r['created_at']) ?></div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-6"><strong>ຜູ້ເຊົ່າ</strong><br><?= e($r['cname']) ?><br>ໂທ: <?= e($r['phone']) ?><br>
                <?php if ($r['id_card_no']): ?>ບັດ: <?= e($r['id_card_no']) ?><br><?php endif; ?>
                <?php if ($r['license_no']): ?>ໃບຂັບຂີ່: <?= e($r['license_no']) ?><br><?php endif; ?>
                <small class="text-muted"><?= e(trim($r['addr'] . ' ' . $r['address_detail'])) ?></small>
            </div>
            <div class="col-6"><strong>ລົດ</strong><br><?= e($r['brand'] . ' ' . $r['model']) ?>
                (<?= e($r['type_name']) ?>)<br>
                ປ້າຍ: <?= e($r['plate_no']) ?> · ສີ: <?= e($r['color']) ?><br>
                ເລີ່ມ: <?= dt($r['start_datetime']) ?><br>ກຳນົດຄືນ: <?= dt($r['due_datetime']) ?>
                <?php if ($ret): ?><br>ຄືນຈິງ: <?= dt($ret['return_datetime']) ?><?php endif; ?></div>
        </div>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>ລາຍການ</th>
                    <th class="text-end">ຈຳນວນ</th>
                    <th class="text-end">ລາຄາ</th>
                    <th class="text-end">ລວມ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>ຄ່າເຊົ່າລົດ</td>
                    <td class="text-end"><?= (int)$r['rent_days'] ?> ວັນ</td>
                    <td class="text-end"><?= number_format($r['daily_rate']) ?></td>
                    <td class="text-end"><?= number_format($r['rent_days'] * $r['daily_rate']) ?></td>
                </tr>
                <?php if ($r['discount'] > 0): ?><tr>
                    <td>ສ່ວນຫຼຸດ</td>
                    <td></td>
                    <td></td>
                    <td class="text-end">- <?= number_format($r['discount']) ?></td>
                </tr><?php endif; ?>
                <?php if ($inv && $inv['late_fee'] > 0): ?><tr>
                    <td>ຄ່າປັບຄືນຊ້າ (<?= late_text((int)$ret['late_minutes']) ?>)</td>
                    <td></td>
                    <td></td>
                    <td class="text-end"><?= number_format($inv['late_fee']) ?></td>
                </tr><?php endif; ?>
                <?php if ($inv && $inv['damage_fee'] > 0): ?><tr>
                    <td>ຄ່າເສຍຫາຍ</td>
                    <td></td>
                    <td></td>
                    <td class="text-end"><?= number_format($inv['damage_fee']) ?></td>
                </tr><?php endif; ?>
            </tbody>
            <tfoot>
                <?php if ($inv): ?>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">ລວມທັງໝົດ</td>
                    <td class="text-end"><?= money($inv['grand_total']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">ຊຳລະແລ້ວ (ລວມມັດຈຳ)</td>
                    <td class="text-end"><?= money($inv['paid_amount']) ?></td>
                </tr>
                <?php if ($ret && $ret['deposit_refund'] > 0): ?><tr>
                    <td colspan="3" class="text-end">ຄືນເງິນມັດຈຳເກີນ</td>
                    <td class="text-end"><?= money($ret['deposit_refund']) ?></td>
                </tr><?php endif; ?>
                <tr>
                    <td colspan="3" class="text-end">ສະຖານະ</td>
                    <td class="text-end"><?= badge($inv['status']) ?></td>
                </tr>
                <?php else: ?>
                <tr class="fw-bold">
                    <td colspan="3" class="text-end">ຄ່າເຊົ່າຄາດໝາຍ</td>
                    <td class="text-end"><?= money($r['total_amount']) ?></td>
                </tr>
                <tr>
                    <td colspan="3" class="text-end">ເງິນມັດຈຳທີ່ຮັບ</td>
                    <td class="text-end"><?= money($r['deposit']) ?></td>
                </tr>
                <?php endif; ?>
            </tfoot>
        </table>
        <?php if ($pays): ?><div class="small mb-3"><strong>ປະຫວັດການຊຳລະ</strong>
            <?php foreach ($pays as $p) echo '<div>' . dt($p['paid_at']) . ' · ' . e($typeLbl[$p['payment_type']] ?? $p['payment_type']) . ' · ' . money($p['amount']) . ' (' . e($p['method']) . ')</div>'; ?>
        </div><?php endif; ?>
        <?php if (!$inv): ?><p class="small text-muted">ຜູ້ເຊົ່າຕົກລົງຮັບຜິດຊອບລົດຕະຫຼອດໄລຍະເຊົ່າ ແລະ ຄືນຕາມກຳນົດ.
            ຄືນຊ້າຈະຖືກປັບຕາມອັດຕາຂອງຮ້ານ.</p><?php endif; ?>
        <div class="row text-center mt-5 pt-4">
            <div class="col-6">ຜູ້ເຊົ່າ<br><br><br>................................</div>
            <div class="col-6">ພະນັກງານ: <?= e($r['staff']) ?><br><br><br>................................</div>
        </div>
    </div>
    <?= flash_script() ?>
</body>

</html>