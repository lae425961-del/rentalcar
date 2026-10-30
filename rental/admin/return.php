<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff']);
$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$r = q("SELECT r.*, v.brand, v.model, v.plate_no, v.mileage, t.category, CONCAT(c.first_name,' ',c.last_name) cname, c.phone
        FROM rentals r JOIN vehicles v ON v.vehicle_id=r.vehicle_id JOIN vehicle_types t ON t.type_id=v.type_id
        JOIN customers c ON c.customer_id=r.customer_id WHERE r.rental_id=?", [$id])->fetch();
if (!$r || !in_array($r['status'], ['active', 'overdue'], true)) {
  flash('error', 'ບໍ່ພົບການເຊົ່າ ຫຼື ຄືນລົດແລ້ວ');
  redirect('admin/rentals.php');
}

/* ພຣີວິວຄ່າປັບ (AJAX) */
if (isset($_GET['preview'])) {
  $at = norm_dt($_GET['at'] ?? '');
  if (!strtotime($at)) json_out(['ok' => false]);
  [$min, $fee] = calc_late($r, $at);
  json_out(['ok' => true, 'late_min' => $min, 'late_text' => late_text($min), 'late_fee' => $fee]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $pdo = db();
  try {
    $at = norm_dt(post('return_at'));
    if (!strtotime($at) || strtotime($at) < strtotime($r['start_datetime'])) throw new Exception('ເວລາຄືນບໍ່ຖືກຕ້ອງ');
    $pdo->beginTransaction();
    $lock = q("SELECT status FROM rentals WHERE rental_id=? FOR UPDATE", [$id])->fetchColumn();
    if (!in_array($lock, ['active', 'overdue'], true)) throw new Exception('ຄືນລົດແລ້ວ');
    [$min, $lateFee, $ruleId] = calc_late($r, $at);
    $damage = max(0, (float)post('damage_fee'));
    $grand = (float)$r['total_amount'] + $lateFee + $damage;
    $balance = $grand - (float)$r['deposit'];
    $method = in_array(post('method'), ['cash', 'transfer', 'qr'], true) ? post('method') : 'cash';
    $received = $balance > 0 ? min($balance, max(0, (float)post('received', $balance))) : 0;
    $refund = $balance < 0 ? -$balance : 0;
    $paid = (float)$r['deposit'] + $received;
    $invStatus = $paid + 0.01 >= $grand ? 'paid' : ($received > 0 || $r['deposit'] > 0 ? 'partial' : 'unpaid');
    $km = post('mileage_in') !== '' ? (int)post('mileage_in') : null;

    q("INSERT INTO returns (rental_id,return_datetime,mileage_in,condition_in,late_minutes,rule_id,late_fee,damage_fee,deposit_refund,received_by,note)
           VALUES (?,?,?,?,?,?,?,?,?,?,?)", [$id, $at, $km, post('condition_in'), $min, $ruleId, $lateFee, $damage, $refund, $u['user_id'], post('note')]);
    $retId = (int)$pdo->lastInsertId();
    $invNo = gen_code('INV');
    q("INSERT INTO invoices (invoice_no,rental_id,return_id,rent_amount,late_fee,damage_fee,discount,grand_total,paid_amount,status,created_by)
           VALUES (?,?,?,?,?,?,0,?,?,?,?)", [$invNo, $id, $retId, $r['total_amount'], $lateFee, $damage, $grand, $paid, $invStatus, $u['user_id']]);
    $invId = (int)$pdo->lastInsertId();
    q("UPDATE payments SET invoice_id=? WHERE rental_id=? AND payment_type='deposit'", [$invId, $id]);
    if ($received > 0)
      q("INSERT INTO payments (invoice_id,rental_id,payment_type,method,amount,received_by) VALUES (?,?, 'rent', ?, ?, ?)", [$invId, $id, $method, $received, $u['user_id']]);
    if ($refund > 0)
      q("INSERT INTO payments (invoice_id,rental_id,payment_type,method,amount,received_by) VALUES (?,?, 'refund', ?, ?, ?)", [$invId, $id, $method, $refund, $u['user_id']]);
    q("UPDATE rentals SET status='returned' WHERE rental_id=?", [$id]);
    q("UPDATE vehicles SET status=?, mileage=COALESCE(?, mileage) WHERE vehicle_id=?", [post('need_repair') ? 'maintenance' : 'available', $km, $r['vehicle_id']]);
    log_activity('return_vehicle', 'rentals', $id, "late_fee=$lateFee damage=$damage");
    $pdo->commit();
    flash('success', 'ບັນທຶກການຄືນລົດ ແລະ ອອກໃບບິນແລ້ວ');
    redirect('admin/invoice.php?rental_id=' . $id);
  } catch (Exception $ex) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    flash('error', $ex->getMessage());
    redirect('admin/return.php?id=' . $id);
  }
}

$pageTitle = 'ຮັບຄືນລົດ ' . $r['rental_code'];
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <form method="post" id="retForm" class="row g-3">
                <?= csrf_field() ?><input type="hidden" name="id" value="<?= $id ?>">
                <div class="col-md-6"><label class="form-label">ວັນ-ເວລາຄືນຈິງ</label>
                    <input type="datetime-local" name="return_at" id="at" class="form-control"
                        value="<?= date('Y-m-d\TH:i') ?>" required>
                </div>
                <div class="col-md-6"><label class="form-label">ເລກໄມລ໌ຕອນຄືນ <small class="text-muted">(ອອກ:
                            <?= (int)$r['mileage_out'] ?>)</small></label>
                    <input type="number" name="mileage_in" min="<?= (int)$r['mileage_out'] ?>" class="form-control">
                </div>
                <div class="col-12"><label class="form-label">ສະພາບລົດຕອນຄືນ</label><input name="condition_in"
                        class="form-control" placeholder="ເຊັ່ນ ສະພາບປົກກະຕິ"></div>
                <div class="col-md-6"><label class="form-label">ຄ່າເສຍຫາຍ (₭)</label><input type="number" min="0"
                        step="1000" name="damage_fee" id="dmg" class="form-control" value="0"></div>
                <div class="col-md-6"><label class="form-label">ວິທີຊຳລະ</label>
                    <select name="method" class="form-select">
                        <option value="cash">ເງິນສົດ</option>
                        <option value="transfer">ໂອນ</option>
                        <option value="qr">QR</option>
                    </select>
                </div>
                <div class="col-md-6"><label class="form-label">ຮັບເງິນຈາກລູກຄ້າ (₭)</label><input type="number" min="0"
                        name="received" id="rec" class="form-control"></div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="form-check"><input class="form-check-input" type="checkbox" name="need_repair" id="rp"
                            value="1"><label class="form-check-label" for="rp">ລົດຕ້ອງສ້ອມແປງ (ຍັງບໍ່ໃຫ້ເຊົ່າ)</label>
                    </div>
                </div>
                <div class="col-12"><label class="form-label">ໝາຍເຫດ</label><input name="note" class="form-control">
                </div>
                <div class="col-12"><button class="btn btn-success">ບັນທຶກການຄືນ ແລະ ອອກໃບບິນ</button> <a
                        class="btn btn-light" href="rentals.php">ຍົກເລີກ</a></div>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="panel">
            <h2 class="h6">ລາຍລະອຽດການເຊົ່າ</h2>
            <div><?= e($r['cname']) ?> · <?= e($r['phone']) ?></div>
            <div><?= e($r['brand'] . ' ' . $r['model']) ?> <span class="plate"><?= e($r['plate_no']) ?></span></div>
            <div class="text-muted small mb-3">ກຳນົດຄືນ <?= dt($r['due_datetime']) ?></div>
            <table class="table table-sm mb-0">
                <tr>
                    <td>ຄ່າເຊົ່າ (<?= (int)$r['rent_days'] ?> ວັນ, ຫຼັງຫຼຸດ)</td>
                    <td class="text-end"><?= money($r['total_amount']) ?></td>
                </tr>
                <tr>
                    <td>ຄືນຊ້າ <small id="lateTxt" class="text-danger"></small></td>
                    <td class="text-end" id="lateFee">0</td>
                </tr>
                <tr>
                    <td>ຄ່າເສຍຫາຍ</td>
                    <td class="text-end" id="dmgTxt">0</td>
                </tr>
                <tr class="fw-bold">
                    <td>ລວມ</td>
                    <td class="text-end" id="grand">-</td>
                </tr>
                <tr>
                    <td>ຫັກເງິນມັດຈຳ</td>
                    <td class="text-end">- <?= money($r['deposit']) ?></td>
                </tr>
                <tr class="table-warning fw-bold">
                    <td id="balLabel">ຕ້ອງຮັບເພີ່ມ</td>
                    <td class="text-end" id="bal">-</td>
                </tr>
            </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var base = <?= (float)$r['total_amount'] ?>,
        dep = <?= (float)$r['deposit'] ?>,
        late = 0;
    var f = function(n) {
        return Math.round(n).toLocaleString() + ' ₭';
    };

    function recalc() {
        var dmg = parseFloat($('#dmg').val()) || 0,
            grand = base + late + dmg,
            bal = grand - dep;
        $('#lateFee').text(f(late));
        $('#dmgTxt').text(f(dmg));
        $('#grand').text(f(grand));
        $('#balLabel').text(bal >= 0 ? 'ຕ້ອງຮັບເພີ່ມ' : 'ຕ້ອງຄືນເງິນມັດຈຳ');
        $('#bal').text(f(Math.abs(bal)));
        $('#rec').val(Math.max(0, Math.round(bal)));
    }

    function preview() {
        $.getJSON('return.php', {
            id: <?= $id ?>,
            preview: 1,
            at: $('#at').val()
        }, function(r) {
            if (!r.ok) return;
            late = r.late_fee;
            $('#lateTxt').text(r.late_min > 0 ? '(' + r.late_text + ')' : '');
            recalc();
        });
    }
    $('#at').on('change', preview);
    $('#dmg').on('input', recalc);
    preview();
});
</script>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>