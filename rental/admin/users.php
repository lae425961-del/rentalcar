<?php
require_once __DIR__ . '/../includes/functions.php';
$me = require_admin(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = post('act');
  $id = (int)post('id');
  if ($act === 'add') {
    try {
      if (post('username') === '' || post('full_name') === '' || strlen((string)$_POST['password']) < 6) throw new Exception('ກະລຸນາປ້ອນຂໍ້ມູນໃຫ້ຄົບ (ລະຫັດຜ່ານຢ່າງໜ້ອຍ 6 ຕົວ)');
      q(
        "INSERT INTO users (role_id,username,password_hash,full_name,phone) VALUES (?,?,?,?,?)",
        [(int)post('role_id'), post('username'), password_hash((string)$_POST['password'], PASSWORD_DEFAULT), post('full_name'), post('phone')]
      );
      log_activity('add_user', 'users', (int)db()->lastInsertId(), post('username'));
      flash('success', 'ເພີ່ມຜູ້ນຳໃຊ້ແລ້ວ');
    } catch (PDOException $ex) {
      flash('error', 'ຊື່ຜູ້ໃຊ້ນີ້ມີແລ້ວ');
    } catch (Exception $ex) {
      flash('error', $ex->getMessage());
    }
    redirect('admin/users.php');
  }
  if ($id === (int)$me['user_id'] && $act === 'toggle') json_out(['ok' => false, 'msg' => 'ບໍ່ສາມາດປິດບັນຊີຂອງຕົນເອງ']);
  if ($act === 'toggle') {
    q("UPDATE users SET status=IF(status='active','inactive','active') WHERE user_id=?", [$id]);
    log_activity('toggle_user', 'users', $id);
    json_out(['ok' => true, 'msg' => 'ອັບເດດແລ້ວ']);
  }
  if ($act === 'reset') {
    $pw = post('note');
    if (strlen($pw) < 6) json_out(['ok' => false, 'msg' => 'ລະຫັດຜ່ານຕ້ອງມີຢ່າງໜ້ອຍ 6 ຕົວ']);
    q("UPDATE users SET password_hash=? WHERE user_id=?", [password_hash($pw, PASSWORD_DEFAULT), $id]);
    log_activity('reset_password', 'users', $id);
    json_out(['ok' => true, 'msg' => 'ປ່ຽນລະຫັດຜ່ານແລ້ວ']);
  }
  json_out(['ok' => false, 'msg' => 'bad action'], 400);
}
$pageTitle = 'ຜູ້ນຳໃຊ້ລະບົບ';
$roleList = q("SELECT * FROM roles")->fetchAll();
$rows = q("SELECT u.*, r.role_name FROM users u JOIN roles r USING(role_id) ORDER BY u.user_id")->fetchAll();
include __DIR__ . '/../includes/admin_header.php';
?>
<div class="row g-3">
    <div class="col-lg-8">
        <div class="panel p-0 table-responsive">
            <table class="table align-middle mb-0">
                <thead>
                    <tr>
                        <th>ຊື່ຜູ້ໃຊ້</th>
                        <th>ຊື່ເຕັມ</th>
                        <th>ສິດ</th>
                        <th>ເຂົ້າລະບົບຫຼ້າສຸດ</th>
                        <th>ສະຖານະ</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?><tr>
                        <td><?= e($r['username']) ?></td>
                        <td><?= e($r['full_name']) ?><br><small class="text-muted"><?= e($r['phone']) ?></small></td>
                        <td><?= e($r['role_name']) ?></td>
                        <td><?= dt($r['last_login']) ?></td>
                        <td><?= $r['status'] === 'active' ? '<span class="badge text-bg-success">ໃຊ້ງານ</span>' : '<span class="badge text-bg-secondary">ປິດ</span>' ?>
                        </td>
                        <td class="text-nowrap">
                            <button class="btn btn-sm btn-outline-primary js-post" data-confirm="ຕັ້ງລະຫັດຜ່ານໃໝ່"
                                data-input="ລະຫັດຜ່ານໃໝ່ (6 ຕົວຂຶ້ນໄປ)"
                                data-post='{"act":"reset","id":<?= $r['user_id'] ?>}'>ປ່ຽນລະຫັດ</button>
                            <?php if ($r['user_id'] != $me['user_id']): ?><button
                                class="btn btn-sm btn-outline-secondary js-post" data-confirm="ປ່ຽນສະຖານະບັນຊີນີ້?"
                                data-post='{"act":"toggle","id":<?= $r['user_id'] ?>}'>ເປີດ/ປິດ</button><?php endif; ?>
                        </td>
                    </tr><?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="panel">
            <h2 class="h6">ເພີ່ມຜູ້ນຳໃຊ້</h2>
            <form method="post" class="row g-2"><?= csrf_field() ?><input type="hidden" name="act" value="add">
                <div class="col-12"><input name="username" class="form-control" placeholder="ຊື່ຜູ້ໃຊ້" required></div>
                <div class="col-12"><input name="full_name" class="form-control" placeholder="ຊື່ເຕັມ" required></div>
                <div class="col-12"><input name="phone" class="form-control" placeholder="ເບີໂທ"></div>
                <div class="col-12"><select name="role_id"
                        class="form-select"><?php foreach ($roleList as $rl) echo '<option value="' . $rl['role_id'] . '">' . e($rl['role_name']) . '</option>'; ?></select>
                </div>
                <div class="col-12"><input type="password" name="password" class="form-control" placeholder="ລະຫັດຜ່ານ"
                        required minlength="6"></div>
                <div class="col-12"><button class="btn btn-primary w-100">ເພີ່ມ</button></div>
            </form>
            <p class="small text-muted mt-3 mb-0">admin = ທຸກຢ່າງ · staff = ຈອງ/ເຊົ່າ/ລູກຄ້າ/ລົດ · cashier = ເຊົ່າ ແລະ
                ລາຍງານການເງິນ</p>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>