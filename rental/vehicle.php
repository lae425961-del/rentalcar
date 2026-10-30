<?php
require_once __DIR__ . '/includes/functions.php';
$id = (int)($_GET['id'] ?? 0);
$v = q("SELECT v.*, t.type_name, t.category FROM vehicles v JOIN vehicle_types t ON t.type_id=v.type_id
        WHERE v.vehicle_id=? AND v.status NOT IN ('inactive')", [$id])->fetch();
if (!$v) {
  http_response_code(404);
  exit('ບໍ່ພົບລົດ');
}
$me = customer();
$pageTitle = $v['brand'] . ' ' . $v['model'];
$from = str_replace(' ', 'T', norm_dt($_GET['from'] ?? ''));
$to = str_replace(' ', 'T', norm_dt($_GET['to'] ?? ''));
$fuel = ['petrol' => 'ແອັດຊັງ', 'diesel' => 'ກາຊວນ', 'hybrid' => 'ໄຮບຣິດ', 'electric' => 'ໄຟຟ້າ'];
include __DIR__ . '/includes/header.php';
?>
<div class="container pt-4">
    <a href="<?= url() ?>" class="small"><i class="bi bi-arrow-left"></i> ກັບໄປລາຍການລົດ</a>
    <div class="row g-4 mt-1">
        <div class="col-lg-7">
            <div class="vcard">
                <div class="pic" style="font-size:6rem"><?php if ($v['image']): ?><img src="<?= url($v['image']) ?>"
                        alt=""><?php else: ?><i
                        class="bi <?= $v['category'] === 'car' ? 'bi-car-front' : 'bi-bicycle' ?>"></i><?php endif; ?>
                </div>
            </div>
            <div class="panel mt-3">
                <h1 class="h4"><?= e($v['brand'] . ' ' . $v['model']) ?> <?= badge($v['status']) ?></h1>
                <span class="plate"><?= e($v['plate_no']) ?></span>
                <dl class="row mt-3 mb-0">
                    <dt class="col-5 col-md-4">ປະເພດ</dt>
                    <dd class="col-7 col-md-8"><?= e($v['type_name']) ?></dd>
                    <dt class="col-5 col-md-4">ປີ / ສີ</dt>
                    <dd class="col-7 col-md-8"><?= e($v['year']) ?> / <?= e($v['color']) ?></dd>
                    <?php if ($v['seats']): ?><dt class="col-5 col-md-4">ຈຳນວນບ່ອນນັ່ງ</dt>
                    <dd class="col-7 col-md-8"><?= (int)$v['seats'] ?></dd><?php endif; ?>
                    <?php if ($v['transmission']): ?><dt class="col-5 col-md-4">ເກຍ</dt>
                    <dd class="col-7 col-md-8"><?= $v['transmission'] === 'auto' ? 'ອໍໂຕ້' : 'ທຳມະດາ' ?></dd>
                    <?php endif; ?>
                    <?php if ($v['fuel_type']): ?><dt class="col-5 col-md-4">ເຊື້ອໄຟ</dt>
                    <dd class="col-7 col-md-8"><?= $fuel[$v['fuel_type']] ?></dd><?php endif; ?>
                    <dt class="col-5 col-md-4">ເງິນມັດຈຳ</dt>
                    <dd class="col-7 col-md-8"><?= money($v['deposit_amount']) ?></dd>
                </dl>
            </div>
        </div>
        <div class="col-lg-5">
            <div class="panel">
                <div class="price fs-4 mb-3"><?= number_format($v['daily_rate']) ?> ₭ <small>/ ວັນ</small></div>
                <?php if (!$me): ?>
                <p>ເຂົ້າສູ່ລະບົບ ຫຼື ສະໝັກສະມາຊິກ ເພື່ອຈອງລົດຄັນນີ້.</p>
                <div class="d-grid gap-2">
                    <a class="btn btn-primary" href="<?= url('register.php') ?>">ສະໝັກສະມາຊິກ</a>
                    <a class="btn btn-outline-primary" href="<?= url('login.php') ?>">ມີບັນຊີແລ້ວ? ເຂົ້າສູ່ລະບົບ</a>
                </div>
                <?php else: ?>
                <form id="bookForm" data-rate="<?= (float)$v['daily_rate'] ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="vehicle_id" value="<?= $v['vehicle_id'] ?>">
                    <div class="mb-2"><label class="form-label">ວັນ-ເວລາຮັບລົດ</label>
                        <input type="datetime-local" name="pickup" class="form-control" value="<?= e($from) ?>"
                            required>
                    </div>
                    <div class="mb-2"><label class="form-label">ວັນ-ເວລາຄືນລົດ</label>
                        <input type="datetime-local" name="return" class="form-control" value="<?= e($to) ?>" required>
                    </div>
                    <div class="mb-2"><label class="form-label">ສະຖານທີ່ຮັບລົດ <span
                                class="text-muted">(ບໍ່ບັງຄັບ)</span></label>
                        <input type="text" name="place" class="form-control" maxlength="250"
                            placeholder="ເຊັ່ນ ຮ້ານ ຫຼື ສະໜາມບິນ">
                    </div>
                    <div class="p-2 mb-3 rounded" style="background:#FFF8E5">ລາຄາປະມານ: <strong id="estimate">-</strong>
                    </div>
                    <button class="btn btn-warning w-100" type="submit"><i class="bi bi-calendar-check"></i>
                        ສົ່ງຄຳຂໍຈອງ</button>
                    <p class="small text-muted mt-2 mb-0">ພະນັກງານຈະກວດ ແລະ ຢືນຢັນການຈອງ.
                        ທ່ານຈະເຫັນສະຖານະໃນໜ້າບັນຊີຂອງຂ້ອຍ.</p>
                </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php
$extraJs = <<<'JS'
<script>
calcEstimate();
$('#bookForm').on('submit', function (e) {
  e.preventDefault();
  const $btn = $(this).find('button').prop('disabled', true);
  $.post(BASE_URL + '/api/booking.php', $(this).serialize())
    .done(r => r.ok
      ? Swal.fire({ icon: 'success', title: 'ສົ່ງຄຳຂໍຈອງແລ້ວ', text: r.msg }).then(() => location.href = BASE_URL + '/my-account.php')
      : Swal.fire({ icon: 'warning', text: r.msg }))
    .fail(x => Swal.fire({ icon: 'error', text: (x.responseJSON && x.responseJSON.msg) || 'ເຊື່ອມຕໍ່ບໍ່ໄດ້' }))
    .always(() => $btn.prop('disabled', false));
});
</script>
JS;
include __DIR__ . '/includes/footer.php'; ?>