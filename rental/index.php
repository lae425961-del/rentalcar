<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'ເຊົ່າລົດໃຫຍ່ ແລະ ລົດຈັກ';
$cat  = in_array($_GET['category'] ?? '', ['car', 'motorcycle'], true) ? $_GET['category'] : '';
$from = norm_dt($_GET['from'] ?? '');
$to   = norm_dt($_GET['to'] ?? '');
$kw   = trim($_GET['kw'] ?? '');
$byDate = $from && $to && strtotime($to) > strtotime($from);

$sql = "SELECT v.*, t.type_name, t.category FROM vehicles v JOIN vehicle_types t ON t.type_id=v.type_id
        WHERE v.status IN ('available','reserved','rented')";
$p = [];
if ($cat) { $sql .= " AND t.category=?"; $p[] = $cat; }
if ($kw)  { $sql .= " AND (v.brand LIKE ? OR v.model LIKE ?)"; $p[] = "%$kw%"; $p[] = "%$kw%"; }
$sql .= " ORDER BY v.status='available' DESC, v.daily_rate";
$list = q($sql, $p)->fetchAll();
if ($byDate) $list = array_values(array_filter($list, fn($v) => is_vehicle_available((int)$v['vehicle_id'], $from, $to)));

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <div class="container">
    <div class="row align-items-end g-4">
      <div class="col-lg-5 pb-lg-5">
        <h1>ຈອງລົດ ຮັບລົດ ອອກເດີນທາງ</h1>
        <p class="mt-3">ເລືອກລົດໃຫຍ່ ຫຼື ລົດຈັກ, ເລືອກວັນຮັບ-ຄືນ ແລ້ວພະນັກງານຈະຢືນຢັນໃຫ້ທ່ານ.</p>
      </div>
      <div class="col-lg-7">
        <form class="ticket" method="get">
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small">ປະເພດ</label>
              <select name="category" class="form-select">
                <option value="">ທັງໝົດ</option>
                <option value="car" <?= $cat === 'car' ? 'selected' : '' ?>>ລົດໃຫຍ່</option>
                <option value="motorcycle" <?= $cat === 'motorcycle' ? 'selected' : '' ?>>ລົດຈັກ</option>
              </select>
            </div>
            <div class="col-md-4"><label class="form-label small">ວັນຮັບລົດ</label>
              <input type="datetime-local" name="from" class="form-control" value="<?= e(str_replace(' ', 'T', $from)) ?>"></div>
            <div class="col-md-4"><label class="form-label small">ວັນຄືນລົດ</label>
              <input type="datetime-local" name="to" class="form-control" value="<?= e(str_replace(' ', 'T', $to)) ?>"></div>
            <div class="col-md-8"><input type="search" name="kw" class="form-control" placeholder="ຊື່ຍີ່ຫໍ້ ຫຼື ລຸ້ນ ເຊັ່ນ Toyota, Wave" value="<?= e($kw) ?>"></div>
            <div class="col-md-4 d-grid"><button class="btn btn-primary"><i class="bi bi-search"></i> ຄົ້ນຫາລົດວ່າງ</button></div>
          </div>
        </form>
      </div>
    </div>
  </div>
</section>

<div class="container pt-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 m-0"><?= $byDate ? 'ລົດວ່າງໃນຊ່ວງທີ່ເລືອກ' : 'ລົດທັງໝົດ' ?> (<?= count($list) ?>)</h2>
  </div>
  <?php if (!$list): ?>
    <div class="panel text-center text-muted py-5">ບໍ່ພົບລົດທີ່ກົງກັບເງື່ອນໄຂ ລອງປ່ຽນວັນ ຫຼື ປະເພດ</div>
  <?php endif; ?>
  <div class="row g-3">
    <?php foreach ($list as $v):
      $q = $byDate ? '&from=' . urlencode($from) . '&to=' . urlencode($to) : ''; ?>
    <div class="col-sm-6 col-lg-4 col-xl-3">
      <div class="vcard">
        <div class="pic"><?php if ($v['image']): ?><img src="<?= url($v['image']) ?>" alt="<?= e($v['brand'] . ' ' . $v['model']) ?>"><?php else: ?><i class="bi <?= $v['category'] === 'car' ? 'bi-car-front' : 'bi-bicycle' ?>"></i><?php endif; ?></div>
        <div class="body">
          <div class="d-flex justify-content-between align-items-start">
            <div class="fw-bold"><?= e($v['brand'] . ' ' . $v['model']) ?></div>
            <?= $byDate ? '<span class="badge text-bg-success">ວ່າງ</span>' : badge($v['status']) ?>
          </div>
          <span class="plate"><?= e($v['plate_no']) ?></span>
          <div class="specs">
            <span><?= e($v['type_name']) ?></span>
            <?php if ($v['seats']): ?><span><i class="bi bi-people"></i> <?= (int)$v['seats'] ?></span><?php endif; ?>
            <?php if ($v['transmission']): ?><span><?= $v['transmission'] === 'auto' ? 'ອໍໂຕ້' : 'ທຳມະດາ' ?></span><?php endif; ?>
          </div>
          <div class="mt-auto d-flex justify-content-between align-items-end pt-2">
            <div class="price"><?= number_format($v['daily_rate']) ?> ₭ <small>/ ວັນ</small></div>
            <a class="btn btn-sm btn-primary" href="<?= url('vehicle.php?id=' . $v['vehicle_id'] . $q) ?>">ເບິ່ງ / ຈອງ</a>
          </div>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
