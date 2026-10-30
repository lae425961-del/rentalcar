# ລາວເຣັນ — ລະບົບເຊົ່າລົດໃຫຍ່ ແລະ ລົດຈັກ (PHP + jQuery + Bootstrap 5 + SweetAlert2 + MySQL)

## ຕິດຕັ້ງ (XAMPP / Laragon / Apache + PHP 8.0+)
1. ວາງໂຟລເດີ `rental` ໄວ້ໃນ `htdocs` (ຫຼື `www`) → ເປີດໄດ້ທີ່ `http://localhost/rental`
2. phpMyAdmin → Import ຕາມລຳດັບ: `database/01_schema.sql` ແລ້ວ `database/02_seed.sql`
3. ແກ້ `config/config.php` (DB_USER / DB_PASS / BASE_URL)
4. ໃຫ້ PHP ຂຽນໄດ້ທີ່ `assets/uploads/` ແລະ `storage/`
5. ເປີດເວັບລູກຄ້າ: `/rental/`   ຫຼັງບ້ານ: `/rental/admin/login.php`
   ຜູ້ດູແລເລີ່ມຕົ້ນ: **admin / admin123** (ປ່ຽນທັນທີ!)

## ຂັ້ນຕອນໃຊ້ງານ
ລູກຄ້າສະໝັກ → ແອັດມີນໄດ້ແຈ້ງເຕືອນ (ກະດິ່ງ ມຸມຂວາເທິງ, ອັບເດດທຸກ 20 ວິ)
→ ລູກຄ້າຈອງ → ແອັດມີນ ຢືນຢັນ/ປະຕິເສດ → ປ່ອຍລົດ (ເປີດເຊົ່າ + ຮັບມັດຈຳ + ພິມສັນຍາ)
→ ຮັບຄືນລົດ (ຄິດຄ່າປັບຄືນຊ້າອັດຕະໂນມັດ, ຄ່າເສຍຫາຍ) → ໃບບິນ (ພິມ/PDF) → ຮັບຊຳລະຍອດຄ້າງ

## ໂຄງສ້າງ
- `index.php vehicle.php register.php login.php my-account.php` — ເວັບລູກຄ້າ
- `api/` — booking, notifications, location (AJAX)
- `admin/` — dashboard, bookings, rentals, return, invoice, customers, vehicles, master, users, reports
- `includes/functions.php` — DB, auth, CSRF, ກົດຈອງຊ້ອນ, ຄິດຄ່າປັບ

## ຄວາມປອດໄພທີ່ມີແລ້ວ
PDO prepared statements · password_hash · CSRF token ທຸກ POST · ຈຳກັດ login 5 ຄັ້ງ/10 ນາທີ · ກວດ MIME ຮູບ ·
ເອກະສານລູກຄ້າເກັບໃນ `storage/` (ເບິ່ງຜ່ານ admin/file.php ເທົ່ານັ້ນ) · ກັນຈອງຊ້ອນດ້ວຍ transaction + FOR UPDATE

## ກ່ອນ deploy ຈິງ
ໃຊ້ HTTPS, ປ່ຽນລະຫັດ admin, ປິດ display_errors, ຕັ້ງ backup ຖານຂໍ້ມູນ, ຕັ້ງ user MySQL ແຍກ (ບໍ່ໃຊ້ root).
