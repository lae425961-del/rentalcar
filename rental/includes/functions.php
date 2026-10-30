<?php
require_once __DIR__ . '/../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- ພື້ນຖານ ---------- */
function db(): PDO
{
    static $pdo = null;
    if (!$pdo) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        $pdo->exec("SET time_zone = '+07:00'");
    }
    return $pdo;
}
function q(string $sql, array $p = []): PDOStatement
{
    $s = db()->prepare($sql);
    $s->execute($p);
    return $s;
}
function e($v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}
function url(string $p = ''): string
{
    return BASE_URL . '/' . ltrim($p, '/');
}
function redirect(string $p)
{
    header('Location: ' . (preg_match('#^https?://#', $p) ? $p : url($p)));
    exit;
}
function money($n): string
{
    return number_format((float)$n, 0, '.', ',') . ' ' . CURRENCY;
}
function dt($d, $f = 'd/m/Y H:i'): string
{
    return $d ? date($f, strtotime($d)) : '-';
}
function is_ajax(): bool
{
    return ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
}
function json_out(array $d, int $code = 200)
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($d, JSON_UNESCAPED_UNICODE);
    exit;
}
function post(string $k, $default = ''): string
{
    return trim((string)($_POST[$k] ?? $default));
}
function norm_dt(?string $v): string
{
    return str_replace('T', ' ', (string)$v);
}

/* ---------- CSRF + Flash ---------- */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function csrf_check(): void
{
    $t = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals(csrf_token(), (string)$t)) {
        if (is_ajax()) json_out(['ok' => false, 'msg' => 'ເຊດຊັນໝົດອາຍຸ ກະລຸນາ refresh ໜ້າ'], 419);
        http_response_code(419);
        exit('Session expired');
    }
}
function flash(string $type, string $msg): void
{
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}
function flash_script(): string
{
    if (empty($_SESSION['flash'])) return '';
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<script>$(function(){Swal.fire({icon:' . json_encode($f['type']) . ',text:'
        . json_encode($f['msg'], JSON_UNESCAPED_UNICODE) . ',confirmButtonColor:"#0B3C5D",confirmButtonText:"ຕົກລົງ"});});</script>';
}

/* ---------- ການຢືນຢັນຕົວຕົນ ---------- */
function customer(): ?array
{
    static $c = false;
    if ($c === false) {
        $c = null;
        if (!empty($_SESSION['customer_id'])) {
            $c = q("SELECT * FROM customers WHERE customer_id=? AND status='active'", [$_SESSION['customer_id']])->fetch() ?: null;
            if (!$c) unset($_SESSION['customer_id']);
        }
    }
    return $c;
}
function require_customer(bool $json = false): array
{
    $c = customer();
    if (!$c) {
        if ($json) json_out(['ok' => false, 'msg' => 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ', 'login' => true], 401);
        flash('info', 'ກະລຸນາເຂົ້າສູ່ລະບົບກ່ອນ');
        $_SESSION['back'] = $_SERVER['REQUEST_URI'];
        redirect('login.php');
    }
    return $c;
}
function admin(): ?array
{
    static $u = false;
    if ($u === false) {
        $u = null;
        if (!empty($_SESSION['user_id'])) {
            $u = q("SELECT u.*, r.role_name FROM users u JOIN roles r ON r.role_id=u.role_id
                    WHERE u.user_id=? AND u.status='active'", [$_SESSION['user_id']])->fetch() ?: null;
            if (!$u) unset($_SESSION['user_id']);
        }
    }
    return $u;
}
function require_admin(array $roles = [], bool $json = false): array
{
    $u = admin();
    if (!$u) {
        if ($json) json_out(['ok' => false, 'msg' => 'ກະລຸນາເຂົ້າສູ່ລະບົບ'], 401);
        redirect('admin/login.php');
    }
    if ($roles && !in_array($u['role_name'], $roles, true)) {
        if ($json) json_out(['ok' => false, 'msg' => 'ບໍ່ມີສິດ'], 403);
        http_response_code(403);
        exit('ທ່ານບໍ່ມີສິດເຂົ້າໜ້ານີ້');
    }
    return $u;
}

/* ---------- ແຈ້ງເຕືອນ / Log / ລະຫັດ ---------- */
function notify(string $type, string $title, string $msg = '', ?string $refTable = null, ?int $refId = null): void
{
    q(
        "INSERT INTO notifications (type,title,message,ref_table,ref_id) VALUES (?,?,?,?,?)",
        [$type, $title, $msg, $refTable, $refId]
    );
}
function log_activity(string $action, ?string $table = null, ?int $id = null, string $detail = ''): void
{
    if (empty($_SESSION['user_id'])) return;
    q(
        "INSERT INTO activity_logs (user_id,action,table_name,record_id,detail,ip_address) VALUES (?,?,?,?,?,?)",
        [$_SESSION['user_id'], $action, $table, $id, $detail, $_SERVER['REMOTE_ADDR'] ?? '']
    );
}
function gen_code(string $prefix): string
{
    return $prefix . date('ymd') . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

/* ---------- ອັບໂຫຼດຮູບ ---------- */
function save_upload(array $f, string $sub, bool $private = false): ?string
{
    if (empty($f['name']) || ($f['error'] ?? 0) === UPLOAD_ERR_NO_FILE) return null;
    if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('ອັບໂຫຼດໄຟລ໌ບໍ່ສຳເລັດ');
    if ($f['size'] > 3 * 1024 * 1024) throw new Exception('ໄຟລ໌ໃຫຍ່ເກີນ 3MB');
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
    $ext = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
    if (!$ext) throw new Exception('ອະນຸຍາດສະເພາະຮູບ JPG / PNG / WEBP');
    $base = $private ? STORAGE_DIR : UPLOAD_DIR;
    $dir = $base . $sub . '/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $name = bin2hex(random_bytes(10)) . '.' . $ext;
    move_uploaded_file($f['tmp_name'], $dir . $name);
    return $private ? "$sub/$name" : "assets/uploads/$sub/$name";
}

/* ---------- ກົດທຸລະກິດ ---------- */
function rental_days(string $from, string $to): int
{
    return max(1, (int)ceil((strtotime($to) - strtotime($from)) / 86400));
}
function is_vehicle_available(int $vid, string $from, string $to, int $ignoreBooking = 0): bool
{
    $v = q("SELECT status FROM vehicles WHERE vehicle_id=?", [$vid])->fetch();
    if (!$v || in_array($v['status'], ['maintenance', 'inactive'], true)) return false;
    $n = q(
        "SELECT
        (SELECT COUNT(*) FROM bookings WHERE vehicle_id=? AND status IN ('pending','confirmed')
            AND pickup_datetime < ? AND return_datetime > ? AND booking_id <> ?)
      + (SELECT COUNT(*) FROM rentals WHERE vehicle_id=? AND status IN ('active','overdue')
            AND start_datetime < ? AND (due_datetime > ? OR due_datetime < NOW()))",
        [$vid, $to, $from, $ignoreBooking, $vid, $to, $from]
    )->fetchColumn();
    return (int)$n === 0;
}
function mark_overdue(): void
{
    q("UPDATE rentals SET status='overdue' WHERE status='active' AND due_datetime < NOW()");
}
/** ຄິດຄ່າປັບຄືນຊ້າ -> [ນາທີທີ່ຊ້າ, ຄ່າປັບ, rule_id] */
function calc_late(array $r, string $returnAt): array
{
    $late = max(0, (int)floor((strtotime($returnAt) - strtotime($r['due_datetime'])) / 60));
    $rules = q("SELECT * FROM penalty_rules WHERE is_active=1 AND category IN ('all',?) ORDER BY unit", [$r['category'] ?? 'all'])->fetchAll();
    $hr = $dy = null;
    foreach ($rules as $x) {
        if ($x['unit'] === 'hour' && !$hr) $hr = $x;
        if ($x['unit'] === 'day' && !$dy) $dy = $x;
    }
    $grace = (int)($hr['grace_minutes'] ?? 0);
    if ($late <= $grace) return [$late, 0.0, null];
    $unitFee = fn($rule) => $rule['fee_per_unit'] !== null
        ? (float)$rule['fee_per_unit'] : (float)$r['daily_rate'] * (float)$rule['rate_percent'] / 100;
    $dayFee = $dy ? $unitFee($dy) : (float)$r['daily_rate'];
    $days = intdiv($late, 1440);
    $rem = $late % 1440;
    $fee = $days * $dayFee;
    if ($rem > 0) $fee += min($hr ? ceil($rem / 60) * $unitFee($hr) : $dayFee, $dayFee);
    return [$late, round($fee), $hr['rule_id'] ?? ($dy['rule_id'] ?? null)];
}
function late_text(int $min): string
{
    if ($min <= 0) return '-';
    $d = intdiv($min, 1440);
    $h = intdiv($min % 1440, 60);
    $m = $min % 60;
    return trim(($d ? "$d ວັນ " : '') . ($h ? "$h ຊມ. " : '') . ($m ? "$m ນາທີ" : ''));
}
const STATUS_LABEL = [
    'available' => ['ວ່າງ', 'success'],
    'reserved' => ['ຖືກຈອງ', 'warning'],
    'rented' => ['ກຳລັງເຊົ່າ', 'primary'],
    'maintenance' => ['ສ້ອມແປງ', 'secondary'],
    'inactive' => ['ປິດໃຊ້ງານ', 'dark'],
    'pending' => ['ລໍຖ້າ', 'warning'],
    'confirmed' => ['ຢືນຢັນແລ້ວ', 'success'],
    'rejected' => ['ປະຕິເສດ', 'danger'],
    'cancelled' => ['ຍົກເລີກ', 'secondary'],
    'converted' => ['ເປີດເຊົ່າແລ້ວ', 'info'],
    'active' => ['ກຳລັງເຊົ່າ', 'primary'],
    'returned' => ['ຄືນແລ້ວ', 'success'],
    'overdue' => ['ເກີນກຳນົດ', 'danger'],
    'unpaid' => ['ຍັງບໍ່ຈ່າຍ', 'danger'],
    'partial' => ['ຈ່າຍບາງສ່ວນ', 'warning'],
    'paid' => ['ຈ່າຍແລ້ວ', 'success'],
    'void' => ['ຍົກເລີກ', 'secondary'],
    'blocked' => ['ຖືກລະງັບ', 'danger'],
];
function badge(string $s): string
{
    [$t, $c] = STATUS_LABEL[$s] ?? [$s, 'secondary'];
    return '<span class="badge text-bg-' . $c . '">' . e($t) . '</span>';
}