<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'cashier']);
$pageTitle = 'ລາຍງານ';
$tab = $_GET['tab'] ?? 'revenue';
$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
include __DIR__ . '/../includes/admin_header.php';
$tabs = ['revenue' => 'ລາຍຮັບ', 'vehicles' => 'ລົດທີ່ເຊົ່າຫຼາຍ', 'overdue' => 'ລົດເກີນກຳນົດ', 'users' => 'ການໃຊ້ງານຂອງຜູ້ນຳໃຊ້'];
if ($u['role_name'] === 'cashier') unset($tabs['users']);
$typeLbl = ['deposit' => 'ເງິນມັດຈຳ', 'rent' => 'ຄ່າເຊົ່າ/ຄ່າປັບ', 'late_fee' => 'ຄ່າປັບ', 'damage' => 'ຄ່າເສຍຫາຍ', 'refund' => 'ຄືນມັດຈຳ'];
?>
<ul class="nav nav-pills mb-3 no-print">
    <?php foreach ($tabs as $k => $l) echo '<li class="nav-item"><a class="nav-link ' . ($tab === $k ? 'active' : '') . '" href="?tab=' . $k . '">' . $l . '</a></li>'; ?>
</ul>
<?php if (in_array($tab, ['revenue', 'vehicles', 'users'], true)): ?>
<form class="row g-2 mb-3 no-print"><input type="hidden" name="tab" value="<?= e($tab) ?>">
    <div class="col-auto"><input type="date" name="from" class="form-control" value="<?= e($from) ?>"></div>
    <div class="col-auto"><input type="date" name="to" class="form-control" value="<?= e($to) ?>"></div>
    <?php if ($tab === 'users'): $us = q("SELECT user_id, full_name FROM users ORDER BY full_name")->fetchAll(); ?>
    <div class="col-auto"><select name="uid" class="form-select">
            <option value="">ທຸກຄົນ</option>
            <?php foreach ($us as $x) echo '<option value="' . $x['user_id'] . '"' . (($_GET['uid'] ?? '') == $x['user_id'] ? ' selected' : '') . '>' . e($x['full_name']) . '</option>'; ?>
        </select></div>
    <?php endif; ?>
    <div class="col-auto"><button class="btn btn-primary">ສະແດງ</button> <button type="button"
            class="btn btn-outline-secondary" onclick="print()"><i class="bi bi-printer"></i></button></div>
</form>
<?php endif; ?>
<div class="panel p-0 table-responsive">
    <?php if ($tab === 'revenue'):
    $rows = q("SELECT DATE(paid_at) d, payment_type t, SUM(amount) s, COUNT(*) n FROM payments
               WHERE DATE(paid_at) BETWEEN ? AND ? GROUP BY DATE(paid_at), payment_type ORDER BY d DESC", [$from, $to])->fetchAll();
    $sum = ['in' => 0, 'out' => 0]; ?>
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ວັນທີ</th>
                <th>ປະເພດ</th>
                <th class="text-end">ຈຳນວນລາຍການ</th>
                <th class="text-end">ຍອດ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r) {
          $r['t'] === 'refund' ? $sum['out'] += $r['s'] : $sum['in'] += $r['s'];
          echo '<tr><td>' . dt($r['d'], 'd/m/Y') . '</td><td>' . e($typeLbl[$r['t']] ?? $r['t']) . '</td><td class="text-end">' . $r['n'] . '</td><td class="text-end ' . ($r['t'] === 'refund' ? 'text-danger' : '') . '">' . ($r['t'] === 'refund' ? '-' : '') . number_format($r['s']) . '</td></tr>';
        }
        if (!$rows) echo '<tr><td colspan="4" class="text-center text-muted py-4">ບໍ່ມີຂໍ້ມູນ</td></tr>'; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold">
                <td colspan="3" class="text-end">ຮັບທັງໝົດ - ຄືນມັດຈຳ</td>
                <td class="text-end"><?= money($sum['in'] - $sum['out']) ?></td>
            </tr>
            <tr>
                <td colspan="4" class="small text-muted">ໝາຍເຫດ: ເງິນມັດຈຳເປັນເງິນຮັບຊົ່ວຄາວ, ລວມຢູ່ໃນຍອດຮັບ ແລະ
                    ຄືນໃນລາຍການ "ຄືນມັດຈຳ" ເມື່ອລູກຄ້າຈ່າຍເກີນ.</td>
            </tr>
        </tfoot>
    </table>
    <?php elseif ($tab === 'vehicles'):
    $rows = q("SELECT v.plate_no, v.brand, v.model, COUNT(r.rental_id) n, COALESCE(SUM(r.total_amount),0) s FROM vehicles v
               LEFT JOIN rentals r ON r.vehicle_id=v.vehicle_id AND DATE(r.start_datetime) BETWEEN ? AND ? AND r.status<>'cancelled'
               GROUP BY v.vehicle_id ORDER BY n DESC, s DESC LIMIT 50", [$from, $to])->fetchAll(); ?>
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ລົດ</th>
                <th>ປ້າຍ</th>
                <th class="text-end">ເຊົ່າ (ຄັ້ງ)</th>
                <th class="text-end">ຄ່າເຊົ່າລວມ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r) echo '<tr><td>' . e($r['brand'] . ' ' . $r['model']) . '</td><td><span class="plate">' . e($r['plate_no']) . '</span></td><td class="text-end">' . $r['n'] . '</td><td class="text-end">' . money($r['s']) . '</td></tr>'; ?>
        </tbody>
    </table>
    <?php elseif ($tab === 'overdue'): mark_overdue();
    $rows = q("SELECT * FROM v_overdue_rentals ORDER BY late_minutes DESC")->fetchAll(); ?>
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ການເຊົ່າ</th>
                <th>ລູກຄ້າ</th>
                <th>ເບີໂທ</th>
                <th>ປ້າຍ</th>
                <th>ກຳນົດຄືນ</th>
                <th>ຊ້າ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r) echo '<tr><td>' . e($r['rental_code']) . '</td><td>' . e($r['customer']) . '</td><td>' . e($r['phone']) . '</td><td><span class="plate">' . e($r['plate_no']) . '</span></td><td>' . dt($r['due_datetime']) . '</td><td class="text-danger">' . late_text((int)$r['late_minutes']) . '</td></tr>';
        if (!$rows) echo '<tr><td colspan="6" class="text-center text-muted py-4">ບໍ່ມີລົດເກີນກຳນົດ</td></tr>'; ?>
        </tbody>
    </table>
    <?php elseif ($tab === 'users' && $u['role_name'] === 'admin'):
    $uid = (int)($_GET['uid'] ?? 0);
    $w = "DATE(l.created_at) BETWEEN ? AND ?" . ($uid ? " AND l.user_id=?" : '');
    $p = $uid ? [$from, $to, $uid] : [$from, $to];
    $sum = q("SELECT us.full_name, COUNT(*) n, MAX(l.created_at) last_at, SUM(l.action='login') logins FROM activity_logs l JOIN users us USING(user_id) WHERE $w GROUP BY l.user_id ORDER BY n DESC", $p)->fetchAll();
    $rows = q("SELECT l.*, us.full_name FROM activity_logs l JOIN users us USING(user_id) WHERE $w ORDER BY l.log_id DESC LIMIT 300", $p)->fetchAll(); ?>
    <table class="table mb-0">
        <thead>
            <tr>
                <th>ຜູ້ນຳໃຊ້</th>
                <th class="text-end">ຈຳນວນການກະທຳ</th>
                <th class="text-end">ເຂົ້າລະບົບ (ຄັ້ງ)</th>
                <th>ລ່າສຸດ</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sum as $r) echo '<tr><td>' . e($r['full_name']) . '</td><td class="text-end">' . $r['n'] . '</td><td class="text-end">' . $r['logins'] . '</td><td>' . dt($r['last_at']) . '</td></tr>'; ?>
        </tbody>
    </table>
    <table class="table table-sm mb-0 border-top">
        <thead>
            <tr>
                <th>ເວລາ</th>
                <th>ຜູ້ນຳໃຊ້</th>
                <th>ການກະທຳ</th>
                <th>ຂໍ້ມູນ</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $r) echo '<tr><td>' . dt($r['created_at']) . '</td><td>' . e($r['full_name']) . '</td><td>' . e($r['action']) . '</td><td class="small">' . e($r['table_name'] . ' #' . $r['record_id'] . ' ' . $r['detail']) . '</td><td class="small">' . e($r['ip_address']) . '</td></tr>'; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>