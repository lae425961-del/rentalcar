<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff', 'cashier']);
mark_overdue();

/* ---------- ບັນທຶກການເຊົ່າໃໝ່ ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && post('act') === 'create') {
  require_admin(['admin', 'staff']);
  csrf_check();
  $bid = (int)post('booking_id');
  $cid = (int)post('customer_id');
  $vid = (int)post('vehicle_id');
  $start = norm_dt(post('start'));
  $due = norm_dt(post('due'));
  $deposit = max(0, (float)post('deposit'));
  $discount = max(0, (float)post('discount'));
  $method = in_array(post('method'), ['cash', 'transfer', 'qr'], true) ? post('method') : 'cash';
  $pdo = db();
  try {
    if (!strtotime($start) || !strtotime($due) || strtotime($due) <= strtotime($start)) throw new Exception('ວັນເລີ່ມ ແລະ ວັນກຳນົດຄືນບໍ່ຖືກຕ້ອງ');
    $pdo->beginTransaction();
    $v = q("SELECT * FROM vehicles WHERE vehicle_id=? FOR UPDATE", [$vid])->fetch();
    $c = q("SELECT * FROM customers WHERE customer_id=? AND status='active'", [$cid])->fetch();
    if (!$v || !$c) throw new Exception('ກະລຸນາເລືອກລູກຄ້າ ແລະ ລົດ');
    if ($v['status'] !== 'available') throw new Exception('ລົດຄັນນີ້ບໍ່ຢູ່ໃນສະຖານະວ່າງ');
    if ($bid) {
      $bk = q("SELECT * FROM bookings WHERE booking_id=? AND status='confirmed'", [$bid])->fetch();
      if (!$bk || (int)$bk['customer_id'] !== $cid || (int)$bk['vehicle_id'] !== $vid) throw new Exception('ຂໍ້ມູນການຈອງບໍ່ກົງກັນ');
    }
    if (!is_vehicle_available($vid, $start, $due, $bid)) throw new Exception('ລົດມີການຈອງ/ເຊົ່າຊ້ອນໃນຊ່ວງເວລານີ້');
    $days = rental_days($start, $due);
    $total = max(0, $days * (float)$v['daily_rate'] - $discount);
    $code = gen_code('RT');
    q(
      "INSERT INTO rentals (rental_code,booking_id,customer_id,vehicle_id,user_id,start_datetime,due_datetime,daily_rate,rent_days,
               deposit,discount,total_amount,mileage_out,condition_out,status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?, 'active')",
      [
        $code,
        $bid ?: null,
        $cid,
        $vid,
        $u['user_id'],
        $start,
        $due,
        $v['daily_rate'],
        $days,
        $deposit,
        $discount,
        $total,
        post('mileage_out') !== '' ? (int)post('mileage_out') : $v['mileage'],
        post('condition_out')
      ]
    );
    $rid = (int)$pdo->lastInsertId();
    q("UPDATE vehicles SET status='rented' WHERE vehicle_id=?", [$vid]);
    if ($bid) q("UPDATE bookings SET status='converted' WHERE booking_id=?", [$bid]);
    if ($deposit > 0)
      q("INSERT INTO payments (rental_id,payment_type,method,amount,received_by) VALUES (?, 'deposit', ?, ?, ?)", [$rid, $method, $deposit, $u['user_id']]);
    log_activity('create_rental', 'rentals', $rid, $code);
    $pdo->commit();
    flash('success', "ເປີດການເຊົ່າສຳເລັດ ລະຫັດ $code");
    redirect('admin/invoice.php?rental_id=' . $rid);
  } catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $ex->getMessage());
    redirect('admin/rentals.php?new=1' . ($bid ? "&booking_id=$bid" : ''));
  }
}

$pageTitle = isset($_GET['new']) ? 'ເປີດການເຊົ່າໃໝ່' : 'ການເຊົ່າ / ຄືນລົດ';
include __DIR__ . '/../includes/admin_header.php';

/* ---------- ຟອມເປີດເຊົ່າ ---------- */
if (isset($_GET['new'])):
  $bk = null;
  if (!empty($_GET['booking_id']))
    $bk = q("SELECT * FROM bookings WHERE booking_id=? AND status='confirmed'", [(int)$_GET['booking_id']])->fetch() ?: null;
  $customers = q("SELECT customer_id, CONCAT(first_name,' ',last_name,' · ',phone) label FROM customers WHERE status='active' ORDER BY first_name")->fetchAll();
  $vehicles = q("SELECT vehicle_id, CONCAT(brand,' ',model,' · ',plate_no,' · ',FORMAT(daily_rate,0),'/ວັນ') label, deposit_amount, mileage
                   FROM vehicles WHERE status='available' OR vehicle_id=? ORDER BY brand", [$bk['vehicle_id'] ?? 0])->fetchAll();
  $startVal = str_replace(' ', 'T', substr($bk['pickup_datetime'] ?? date('Y-m-d H:i'), 0, 16));
  $dueVal = str_replace(' ', 'T', substr($bk['return_datetime'] ?? date('Y-m-d H:i', strtotime('+1 day')), 0, 16));
?>
<div class="panel" style="max-width:860px">
    <?php if ($bk): ?><div class="alert alert-info py-2">ເປີດເຊົ່າຈາກການຈອງ
        <strong><?= e($bk['booking_code']) ?></strong></div><?php endif; ?>
    <form method="post" class="row g-3">
        <?= csrf_field() ?><input type="hidden" name="act" value="create"><input type="hidden" name="booking_id"
            value="<?= (int)($bk['booking_id'] ?? 0) ?>">
        <div class="col-md-6"><label class="form-label">ລູກຄ້າ *</label>
            <select name="customer_id" class="form-select" required
                <?= $bk ? 'style="pointer-events:none;background:#eee"' : '' ?>>
                <option value="">-- ເລືອກ --</option>
                <?php foreach ($customers as $c) echo '<option value="' . $c['customer_id'] . '"' . (($bk['customer_id'] ?? 0) == $c['customer_id'] ? ' selected' : '') . '>' . e($c['label']) . '</option>'; ?>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">ລົດ *</label>
            <select name="vehicle_id" id="vsel" class="form-select" required
                <?= $bk ? 'style="pointer-events:none;background:#eee"' : '' ?>>
                <option value="">-- ເລືອກ --</option>
                <?php foreach ($vehicles as $v) echo '<option value="' . $v['vehicle_id'] . '" data-dep="' . (float)$v['deposit_amount'] . '" data-km="' . (int)$v['mileage'] . '"' . (($bk['vehicle_id'] ?? 0) == $v['vehicle_id'] ? ' selected' : '') . '>' . e($v['label']) . '</option>'; ?>
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">ເລີ່ມເຊົ່າ *</label><input type="datetime-local" name="start"
                class="form-control" value="<?= $startVal ?>" required></div>
        <div class="col-md-6"><label class="form-label">ກຳນົດຄືນ *</label><input type="datetime-local" name="due"
                class="form-control" value="<?= $dueVal ?>" required></div>
        <div class="col-md-4"><label class="form-label">ເງິນມັດຈຳ (₭)</label><input type="number" min="0" step="1000"
                name="deposit" id="dep" class="form-control" value="0"></div>
        <div class="col-md-4"><label class="form-label">ວິທີຮັບມັດຈຳ</label>
            <select name="method" class="form-select">
                <option value="cash">ເງິນສົດ</option>
                <option value="transfer">ໂອນ</option>
                <option value="qr">QR</option>
            </select>
        </div>
        <div class="col-md-4"><label class="form-label">ສ່ວນຫຼຸດ (₭)</label><input type="number" min="0" step="1000"
                name="discount" class="form-control" value="0"></div>
        <div class="col-md-4"><label class="form-label">ເລກໄມລ໌ຕອນອອກ</label><input type="number" min="0"
                name="mileage_out" id="km" class="form-control"></div>
        <div class="col-md-8"><label class="form-label">ສະພາບລົດຕອນອອກ</label><input name="condition_out"
                class="form-control" placeholder="ເຊັ່ນ ຮອຍຂີດຂ່ວນດ້ານຂວາ, ນ້ຳມັນເຕັມ"></div>
        <div class="col-12 d-flex gap-2"><button class="btn btn-primary">ບັນທຶກການເຊົ່າ</button><a class="btn btn-light"
                href="rentals.php">ຍົກເລີກ</a></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var s = document.getElementById('vsel');

    function fill() {
        var o = s.options[s.selectedIndex];
        if (!o || !o.dataset.dep) return;
        document.getElementById('dep').value = o.dataset.dep;
        document.getElementById('km').value = o.dataset.km;
    }
    s.addEventListener('change', fill);
    fill();
});
</script>
<?php
else:
  $st = $_GET['status'] ?? '';
  $kw = trim($_GET['kw'] ?? '');
  $sql = "SELECT r.*, CONCAT(c.first_name,' ',c.last_name) cname, c.phone, v.brand, v.model, v.plate_no,
                   (SELECT status FROM invoices i WHERE i.rental_id=r.rental_id ORDER BY invoice_id DESC LIMIT 1) inv_status
            FROM rentals r JOIN customers c ON c.customer_id=r.customer_id JOIN vehicles v ON v.vehicle_id=r.vehicle_id WHERE 1=1";
  $p = [];
  if ($st) {
    $sql .= " AND r.status=?";
    $p[] = $st;
  }
  if ($kw) {
    $sql .= " AND (r.rental_code LIKE ? OR c.first_name LIKE ? OR c.phone LIKE ? OR v.plate_no LIKE ?)";
    array_push($p, "%$kw%", "%$kw%", "%$kw%", "%$kw%");
  }
  $rows = q($sql . " ORDER BY r.rental_id DESC LIMIT 200", $p)->fetchAll();
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <a class="btn btn-warning" href="?new=1"><i class="bi bi-plus-lg"></i> ເປີດເຊົ່າໃໝ່ (ລູກຄ້າມາເອງ)</a>
    <?php foreach (['' => 'ທັງໝົດ', 'active' => 'ກຳລັງເຊົ່າ', 'overdue' => 'ເກີນກຳນົດ', 'returned' => 'ຄືນແລ້ວ'] as $k => $l): ?>
    <a class="btn btn-sm align-self-center <?= $st === $k ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="?status=<?= $k ?>"><?= $l ?></a>
    <?php endforeach; ?>
    <form class="ms-auto"><input type="hidden" name="status" value="<?= e($st) ?>"><input name="kw" class="form-control"
            placeholder="ຄົ້ນຫາ ລະຫັດ/ຊື່/ເບີ/ປ້າຍ" value="<?= e($kw) ?>"></form>
</div>
<div class="panel p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>ລະຫັດ</th>
                <th>ລູກຄ້າ</th>
                <th>ລົດ</th>
                <th>ເລີ່ມ</th>
                <th>ກຳນົດຄືນ</th>
                <th class="text-end">ຄ່າເຊົ່າ</th>
                <th>ສະຖານະ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r): ?>
            <tr>
                <td><?= e($r['rental_code']) ?></td>
                <td><?= e($r['cname']) ?><br><small class="text-muted"><?= e($r['phone']) ?></small></td>
                <td><?= e($r['brand'] . ' ' . $r['model']) ?><br><span class="plate"><?= e($r['plate_no']) ?></span>
                </td>
                <td><?= dt($r['start_datetime']) ?></td>
                <td class="<?= $r['status'] === 'overdue' ? 'text-danger fw-bold' : '' ?>"><?= dt($r['due_datetime']) ?>
                </td>
                <td class="text-end"><?= money($r['total_amount']) ?></td>
                <td><?= badge($r['status']) ?><?= $r['inv_status'] ? '<br>' . badge($r['inv_status']) : '' ?></td>
                <td class="text-nowrap">
                    <?php if (in_array($r['status'], ['active', 'overdue'], true)): ?><a class="btn btn-sm btn-success"
                        href="return.php?id=<?= $r['rental_id'] ?>">ຮັບຄືນລົດ</a><?php endif; ?>
                    <a class="btn btn-sm btn-outline-secondary" href="invoice.php?rental_id=<?= $r['rental_id'] ?>"
                        target="_blank"><i class="bi bi-printer"></i> ເອກະສານ</a>
                </td>
            </tr>
            <?php endforeach;
        if (!$rows) echo '<tr><td colspan="8" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>'; ?>
        </tbody>
    </table>
</div>
<?php endif;
include __DIR__ . '/../includes/admin_footer.php'; ?>