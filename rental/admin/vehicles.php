<?php
require_once __DIR__ . '/../includes/functions.php';
$u = require_admin(['admin', 'staff']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = post('act');
  if ($act === 'status') {
    $s = post('status');
    if (!in_array($s, ['available', 'maintenance', 'inactive'], true)) json_out(['ok' => false, 'msg' => 'ສະຖານະບໍ່ຖືກຕ້ອງ']);
    $vid = (int)post('id');
    if (q("SELECT COUNT(*) FROM rentals WHERE vehicle_id=? AND status IN ('active','overdue')", [$vid])->fetchColumn() > 0)
      json_out(['ok' => false, 'msg' => 'ລົດກຳລັງຖືກເຊົ່າ ບໍ່ສາມາດປ່ຽນສະຖານະ']);
    q("UPDATE vehicles SET status=? WHERE vehicle_id=?", [$s, $vid]);
    log_activity('vehicle_status', 'vehicles', $vid, $s);
    json_out(['ok' => true, 'msg' => 'ອັບເດດສະຖານະແລ້ວ']);
  }
  if ($act === 'save') {
    try {
      $id = (int)post('vehicle_id');
      foreach (['type_id', 'plate_no', 'brand', 'model', 'daily_rate'] as $k) if (post($k) === '') throw new Exception('ກະລຸນາປ້ອນຂໍ້ມູນທີ່ມີ * ໃຫ້ຄົບ');
      $img = save_upload($_FILES['image'] ?? [], 'vehicles');
      $f = [
        (int)post('type_id'),
        post('plate_no'),
        post('brand'),
        post('model'),
        post('year') ?: null,
        post('color'),
        post('transmission') ?: null,
        post('fuel_type') ?: null,
        post('seats') ?: null,
        (float)post('daily_rate'),
        (float)post('deposit_amount'),
        (int)post('mileage')
      ];
      if ($id) {
        q("UPDATE vehicles SET type_id=?,plate_no=?,brand=?,model=?,year=?,color=?,transmission=?,fuel_type=?,seats=?,daily_rate=?,deposit_amount=?,mileage=?
                   WHERE vehicle_id=?", [...$f, $id]);
        if ($img) q("UPDATE vehicles SET image=? WHERE vehicle_id=?", [$img, $id]);
      } else {
        q("INSERT INTO vehicles (type_id,plate_no,brand,model,year,color,transmission,fuel_type,seats,daily_rate,deposit_amount,mileage,image)
                   VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)", [...$f, $img]);
        $id = (int)db()->lastInsertId();
      }
      log_activity('save_vehicle', 'vehicles', $id, post('plate_no'));
      flash('success', 'ບັນທຶກຂໍ້ມູນລົດແລ້ວ');
    } catch (PDOException $ex) {
      flash('error', $ex->getCode() == 23000 ? 'ປ້າຍທະບຽນນີ້ມີໃນລະບົບແລ້ວ' : 'ບັນທຶກບໍ່ສຳເລັດ');
    } catch (Exception $ex) {
      flash('error', $ex->getMessage());
    }
    redirect('admin/vehicles.php');
  }
  json_out(['ok' => false, 'msg' => 'bad action'], 400);
}

$pageTitle = 'ຂໍ້ມູນລົດ';
$types = q("SELECT * FROM vehicle_types ORDER BY category, type_name")->fetchAll();
$cat = $_GET['category'] ?? '';
$sql = "SELECT v.*, t.type_name, t.category FROM vehicles v JOIN vehicle_types t ON t.type_id=v.type_id";
$p = [];
if (in_array($cat, ['car', 'motorcycle'], true)) {
  $sql .= " WHERE t.category=?";
  $p[] = $cat;
}
$rows = q($sql . " ORDER BY v.vehicle_id DESC", $p)->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <button class="btn btn-warning" id="btnNew"><i class="bi bi-plus-lg"></i> ເພີ່ມລົດ</button>
    <?php foreach (['' => 'ທັງໝົດ', 'car' => 'ລົດໃຫຍ່', 'motorcycle' => 'ລົດຈັກ'] as $k => $l): ?>
    <a class="btn btn-sm align-self-center <?= $cat === $k ? 'btn-primary' : 'btn-outline-primary' ?>"
        href="?category=<?= $k ?>"><?= $l ?></a>
    <?php endforeach; ?>
</div>
<div class="panel p-0 table-responsive">
    <table class="table table-hover align-middle mb-0">
        <thead>
            <tr>
                <th>ຮູບ</th>
                <th>ລົດ</th>
                <th>ປະເພດ</th>
                <th class="text-end">ລາຄາ/ວັນ</th>
                <th class="text-end">ມັດຈຳ</th>
                <th>ສະຖານະ</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $v): ?>
            <tr>
                <td style="width:70px"><?php if ($v['image']): ?><img src="<?= url($v['image']) ?>" alt=""
                        style="width:60px;height:40px;object-fit:cover;border-radius:4px"><?php endif; ?></td>
                <td><?= e($v['brand'] . ' ' . $v['model']) ?> <small
                        class="text-muted"><?= e($v['year']) ?></small><br><span
                        class="plate"><?= e($v['plate_no']) ?></span></td>
                <td><?= e($v['type_name']) ?></td>
                <td class="text-end"><?= money($v['daily_rate']) ?></td>
                <td class="text-end"><?= money($v['deposit_amount']) ?></td>
                <td><?= badge($v['status']) ?></td>
                <td class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary btn-edit"
                        data-v="<?= e(json_encode($v, JSON_UNESCAPED_UNICODE)) ?>"><i class="bi bi-pencil"></i></button>
                    <?php if ($v['status'] === 'available'): ?>
                    <button class="btn btn-sm btn-outline-secondary js-post" data-confirm="ສົ່ງລົດເຂົ້າສ້ອມແປງ?"
                        data-post='{"act":"status","status":"maintenance","id":<?= $v['vehicle_id'] ?>}'>ສ້ອມແປງ</button>
                    <?php elseif (in_array($v['status'], ['maintenance', 'inactive'], true)): ?>
                    <button class="btn btn-sm btn-outline-success js-post" data-confirm="ເປີດໃຫ້ເຊົ່າໄດ້?"
                        data-post='{"act":"status","status":"available","id":<?= $v['vehicle_id'] ?>}'>ເປີດໃຫ້ເຊົ່າ</button>
                    <?php endif; ?>
                    <?php if ($v['status'] !== 'inactive' && $v['status'] !== 'rented'): ?>
                    <button class="btn btn-sm btn-outline-danger js-post" data-confirm="ປິດໃຊ້ງານລົດຄັນນີ້?"
                        data-post='{"act":"status","status":"inactive","id":<?= $v['vehicle_id'] ?>}'><i
                            class="bi bi-x-lg"></i></button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<div class="modal fade" id="vModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="post" enctype="multipart/form-data" class="modal-content" id="vForm">
            <?= csrf_field() ?><input type="hidden" name="act" value="save"><input type="hidden" name="vehicle_id"
                value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="vTitle">ເພີ່ມລົດ</h5><button type="button" class="btn-close"
                    data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body row g-3">
                <div class="col-md-4"><label class="form-label">ປະເພດ *</label><select name="type_id"
                        class="form-select" required>
                        <?php foreach ($types as $t) echo '<option value="' . $t['type_id'] . '">' . ($t['category'] === 'car' ? 'ລົດໃຫຍ່' : 'ລົດຈັກ') . ' · ' . e($t['type_name']) . '</option>'; ?></select>
                </div>
                <div class="col-md-4"><label class="form-label">ປ້າຍທະບຽນ *</label><input name="plate_no"
                        class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">ປີ</label><input type="number" name="year" min="1990"
                        max="2100" class="form-control"></div>
                <div class="col-md-4"><label class="form-label">ຍີ່ຫໍ້ *</label><input name="brand" class="form-control"
                        required></div>
                <div class="col-md-4"><label class="form-label">ລຸ້ນ *</label><input name="model" class="form-control"
                        required></div>
                <div class="col-md-4"><label class="form-label">ສີ</label><input name="color" class="form-control">
                </div>
                <div class="col-md-3"><label class="form-label">ເກຍ</label><select name="transmission"
                        class="form-select">
                        <option value="">-</option>
                        <option value="auto">ອໍໂຕ້</option>
                        <option value="manual">ທຳມະດາ</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">ເຊື້ອໄຟ</label><select name="fuel_type"
                        class="form-select">
                        <option value="">-</option>
                        <option value="petrol">ແອັດຊັງ</option>
                        <option value="diesel">ກາຊວນ</option>
                        <option value="hybrid">ໄຮບຣິດ</option>
                        <option value="electric">ໄຟຟ້າ</option>
                    </select></div>
                <div class="col-md-3"><label class="form-label">ບ່ອນນັ່ງ</label><input type="number" name="seats"
                        min="1" max="60" class="form-control"></div>
                <div class="col-md-3"><label class="form-label">ໄມລ໌ປັດຈຸບັນ</label><input type="number" name="mileage"
                        min="0" class="form-control" value="0"></div>
                <div class="col-md-4"><label class="form-label">ລາຄາ/ວັນ (₭) *</label><input type="number"
                        name="daily_rate" min="0" step="1000" class="form-control" required></div>
                <div class="col-md-4"><label class="form-label">ເງິນມັດຈຳ (₭)</label><input type="number"
                        name="deposit_amount" min="0" step="1000" class="form-control" value="0"></div>
                <div class="col-md-4"><label class="form-label">ຮູບລົດ</label><input type="file" name="image"
                        accept="image/*" class="form-control"></div>
            </div>
            <div class="modal-footer"><button class="btn btn-primary">ບັນທຶກ</button></div>
        </form>
    </div>
</div>
<?php
$extraJs = <<<'JS'
<script>
const vm = new bootstrap.Modal('#vModal');
$('#btnNew').on('click', function () {
  $('#vForm')[0].reset(); $('[name=vehicle_id]').val(0); $('#vTitle').text('ເພີ່ມລົດ'); vm.show();
});
$('.btn-edit').on('click', function () {
  const v = $(this).data('v'); $('#vForm')[0].reset(); $('#vTitle').text('ແກ້ໄຂລົດ ' + v.plate_no);
  ['vehicle_id','type_id','plate_no','brand','model','year','color','transmission','fuel_type','seats','daily_rate','deposit_amount','mileage']
    .forEach(k => $('#vForm [name=' + k + ']').val(v[k] ?? '')); vm.show();
});
</script>
JS;
include __DIR__ . '/../includes/admin_footer.php'; ?>