<?php
require_once __DIR__ . '/../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_out(['ok' => false, 'msg' => 'POST only'], 405);
csrf_check();
$c = require_customer(true);
$action = post('action');

if ($action === 'create') {
    $vid = (int)post('vehicle_id');
    $from = norm_dt(post('pickup'));
    $to = norm_dt(post('return'));
    $place = mb_substr(post('place'), 0, 250);

    $a = strtotime($from); $b = strtotime($to);
    if (!$a || !$b) json_out(['ok' => false, 'msg' => 'ກະລຸນາເລືອກວັນ-ເວລາຮັບ ແລະ ຄືນ']);
    if ($a < time() - 300) json_out(['ok' => false, 'msg' => 'ວັນຮັບລົດຕ້ອງບໍ່ຢູ່ໃນອະດີດ']);
    if ($b <= $a) json_out(['ok' => false, 'msg' => 'ວັນຄືນຕ້ອງຫຼັງຈາກວັນຮັບ']);

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $v = q("SELECT * FROM vehicles WHERE vehicle_id=? FOR UPDATE", [$vid])->fetch();
        if (!$v) throw new Exception('ບໍ່ພົບລົດ');
        if (!is_vehicle_available($vid, $from, $to)) throw new Exception('ລົດຄັນນີ້ບໍ່ວ່າງໃນຊ່ວງເວລາທີ່ເລືອກ');
        $days = rental_days($from, $to);
        $amount = $days * (float)$v['daily_rate'];
        $code = gen_code('BK');
        q("INSERT INTO bookings (booking_code,customer_id,vehicle_id,pickup_datetime,return_datetime,pickup_place,est_days,est_amount)
           VALUES (?,?,?,?,?,?,?,?)", [$code, $c['customer_id'], $vid, $from, $to, $place, $days, $amount]);
        $id = (int)$pdo->lastInsertId();
        notify('new_booking', 'ການຈອງໃໝ່ ' . $code,
            $c['first_name'] . ' ' . $c['last_name'] . ' ຈອງ ' . $v['brand'] . ' ' . $v['model'] . ' (' . $v['plate_no'] . ')', 'bookings', $id);
        $pdo->commit();
        json_out(['ok' => true, 'msg' => "ສົ່ງຄຳຂໍຈອງແລ້ວ ລະຫັດ $code ພະນັກງານຈະຕິດຕໍ່ຢືນຢັນ", 'code' => $code]);
    } catch (Exception $ex) {
        $pdo->rollBack();
        json_out(['ok' => false, 'msg' => $ex->getMessage()]);
    }
}

if ($action === 'cancel') {
    $id = (int)post('id');
    $bk = q("SELECT * FROM bookings WHERE booking_id=? AND customer_id=?", [$id, $c['customer_id']])->fetch();
    if (!$bk || !in_array($bk['status'], ['pending', 'confirmed'], true)) json_out(['ok' => false, 'msg' => 'ບໍ່ສາມາດຍົກເລີກໄດ້']);
    q("UPDATE bookings SET status='cancelled' WHERE booking_id=?", [$id]);
    notify('booking_cancel', 'ລູກຄ້າຍົກເລີກການຈອງ ' . $bk['booking_code'], $c['first_name'] . ' ' . $c['last_name'], 'bookings', $id);
    json_out(['ok' => true, 'msg' => 'ຍົກເລີກການຈອງແລ້ວ']);
}
json_out(['ok' => false, 'msg' => 'bad action'], 400);
