<?php
// ===== ຕັ້ງຄ່າລະບົບ =====
define('DB_HOST', 'localhost');
define('DB_NAME', 'rental_db');
define('DB_USER', 'root');
define('DB_PASS', '');

define('BASE_URL', '/rental');          // ໂຟລເດີທີ່ວາງໂປຣເຈັກ ເຊັ່ນ http://localhost/rental
define('APP_NAME', 'ລາວເຣັນ');
define('CURRENCY', '₭');
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');   // ຮູບລົດ (ສາທາລະນະ)
define('STORAGE_DIR', __DIR__ . '/../storage/');          // ເອກະສານລູກຄ້າ (ສ່ວນຕົວ)

date_default_timezone_set('Asia/Vientiane');
