SET NAMES utf8mb4;
-- ລະບົບເຊົ່າລົດໃຫຍ່ ແລະ ລົດຈັກ | MySQL 8 / MariaDB 10.4+
CREATE DATABASE IF NOT EXISTS rental_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rental_db;

-- ========== 1. ທີ່ຢູ່ (ແຂວງ > ເມືອງ > ບ້ານ) ==========
CREATE TABLE provinces (
  province_id INT AUTO_INCREMENT PRIMARY KEY,
  province_name VARCHAR(100) NOT NULL
);
CREATE TABLE districts (
  district_id INT AUTO_INCREMENT PRIMARY KEY,
  province_id INT NOT NULL,
  district_name VARCHAR(100) NOT NULL,
  FOREIGN KEY (province_id) REFERENCES provinces(province_id)
);
CREATE TABLE villages (
  village_id INT AUTO_INCREMENT PRIMARY KEY,
  district_id INT NOT NULL,
  village_name VARCHAR(100) NOT NULL,
  FOREIGN KEY (district_id) REFERENCES districts(district_id)
);

-- ========== 2. ຜູ້ນຳໃຊ້ລະບົບ (ພະນັກງານ/ແອັດມີນ) ==========
CREATE TABLE roles (
  role_id INT AUTO_INCREMENT PRIMARY KEY,
  role_name VARCHAR(50) NOT NULL  -- admin, staff, cashier
);
CREATE TABLE users (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  role_id INT NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,   -- password_hash() ຂອງ PHP
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  status ENUM('active','inactive') DEFAULT 'active',
  last_login DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (role_id) REFERENCES roles(role_id)
);

-- ========== 3. ລູກຄ້າ / ສະມາຊິກເວັບ ==========
CREATE TABLE customers (
  customer_id INT AUTO_INCREMENT PRIMARY KEY,
  first_name VARCHAR(100) NOT NULL,
  last_name VARCHAR(100) NOT NULL,
  gender ENUM('M','F','O') NULL,
  birth_date DATE NULL,
  phone VARCHAR(20) NOT NULL,
  email VARCHAR(150) UNIQUE,
  password_hash VARCHAR(255) NULL,         -- ໃຊ້ເມື່ອສະໝັກຜ່ານເວັບ
  id_card_no VARCHAR(50),
  license_no VARCHAR(50),
  license_expiry DATE NULL,
  village_id INT NULL,
  address_detail VARCHAR(255),
  id_photo VARCHAR(255),
  license_photo VARCHAR(255),
  source ENUM('web','counter') DEFAULT 'counter',
  status ENUM('pending','active','blocked') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (village_id) REFERENCES villages(village_id)
);

-- ========== 4. ລົດ ==========
CREATE TABLE vehicle_types (
  type_id INT AUTO_INCREMENT PRIMARY KEY,
  type_name VARCHAR(100) NOT NULL,                 -- SUV, Sedan, Pickup, Scooter...
  category ENUM('car','motorcycle') NOT NULL
);
CREATE TABLE vehicles (
  vehicle_id INT AUTO_INCREMENT PRIMARY KEY,
  type_id INT NOT NULL,
  plate_no VARCHAR(30) NOT NULL UNIQUE,
  brand VARCHAR(80) NOT NULL,
  model VARCHAR(80) NOT NULL,
  year YEAR,
  color VARCHAR(40),
  transmission ENUM('manual','auto') NULL,
  fuel_type ENUM('petrol','diesel','hybrid','electric') NULL,
  seats TINYINT NULL,
  daily_rate DECIMAL(12,2) NOT NULL,
  deposit_amount DECIMAL(12,2) DEFAULT 0,
  mileage INT DEFAULT 0,
  image VARCHAR(255),
  status ENUM('available','reserved','rented','maintenance','inactive') DEFAULT 'available',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (type_id) REFERENCES vehicle_types(type_id)
);

-- ========== 5. ການຈອງ (ຈາກເວັບ) ==========
CREATE TABLE bookings (
  booking_id INT AUTO_INCREMENT PRIMARY KEY,
  booking_code VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  pickup_datetime DATETIME NOT NULL,
  return_datetime DATETIME NOT NULL,
  pickup_place VARCHAR(255),
  est_days INT NOT NULL,
  est_amount DECIMAL(12,2) NOT NULL,
  status ENUM('pending','confirmed','rejected','cancelled','converted') DEFAULT 'pending',
  admin_note VARCHAR(255),
  handled_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
  FOREIGN KEY (handled_by) REFERENCES users(user_id),
  INDEX idx_vehicle_dates (vehicle_id, pickup_datetime, return_datetime)
);

-- ========== 6. ການເຊົ່າ ==========
CREATE TABLE rentals (
  rental_id INT AUTO_INCREMENT PRIMARY KEY,
  rental_code VARCHAR(20) NOT NULL UNIQUE,
  booking_id INT NULL,
  customer_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  user_id INT NOT NULL,                       -- ພະນັກງານທີ່ປ່ອຍລົດ
  start_datetime DATETIME NOT NULL,
  due_datetime DATETIME NOT NULL,             -- ກຳນົດຄືນ
  daily_rate DECIMAL(12,2) NOT NULL,          -- ເກັບລາຄາ ນະ ວັນເຊົ່າ
  rent_days INT NOT NULL,
  deposit DECIMAL(12,2) DEFAULT 0,
  discount DECIMAL(12,2) DEFAULT 0,
  total_amount DECIMAL(12,2) NOT NULL,
  mileage_out INT NULL,
  condition_out TEXT,
  status ENUM('active','returned','overdue','cancelled') DEFAULT 'active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (booking_id) REFERENCES bookings(booking_id),
  FOREIGN KEY (customer_id) REFERENCES customers(customer_id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(vehicle_id),
  FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- ========== 7. ກົດປັບໄໝ ==========
CREATE TABLE penalty_rules (
  rule_id INT AUTO_INCREMENT PRIMARY KEY,
  category ENUM('car','motorcycle','all') DEFAULT 'all',
  grace_minutes INT DEFAULT 60,                -- ຜ່ອນຜັນກ່ອນເລີ່ມປັບ
  unit ENUM('hour','day') NOT NULL,
  fee_per_unit DECIMAL(12,2) NULL,             -- ຄ່າປັບຄົງທີ່ ຕໍ່ຊົ່ວໂມງ/ວັນ
  rate_percent DECIMAL(5,2) NULL,              -- ຫຼື % ຂອງຄ່າເຊົ່າ/ວັນ
  is_active TINYINT(1) DEFAULT 1
);

-- ========== 8. ການຄືນລົດ ==========
CREATE TABLE returns (
  return_id INT AUTO_INCREMENT PRIMARY KEY,
  rental_id INT NOT NULL UNIQUE,
  return_datetime DATETIME NOT NULL,
  mileage_in INT NULL,
  condition_in TEXT,
  late_minutes INT DEFAULT 0,
  rule_id INT NULL,
  late_fee DECIMAL(12,2) DEFAULT 0,
  damage_fee DECIMAL(12,2) DEFAULT 0,
  deposit_refund DECIMAL(12,2) DEFAULT 0,
  received_by INT NOT NULL,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (rental_id) REFERENCES rentals(rental_id),
  FOREIGN KEY (rule_id) REFERENCES penalty_rules(rule_id),
  FOREIGN KEY (received_by) REFERENCES users(user_id)
);

-- ========== 9. ໃບບິນ ==========
CREATE TABLE invoices (
  invoice_id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_no VARCHAR(30) NOT NULL UNIQUE,
  rental_id INT NOT NULL,
  return_id INT NULL,
  issue_date DATETIME DEFAULT CURRENT_TIMESTAMP,
  rent_amount DECIMAL(12,2) NOT NULL,
  late_fee DECIMAL(12,2) DEFAULT 0,
  damage_fee DECIMAL(12,2) DEFAULT 0,
  discount DECIMAL(12,2) DEFAULT 0,
  grand_total DECIMAL(12,2) NOT NULL,
  paid_amount DECIMAL(12,2) DEFAULT 0,
  status ENUM('unpaid','partial','paid','void') DEFAULT 'unpaid',
  created_by INT NOT NULL,
  FOREIGN KEY (rental_id) REFERENCES rentals(rental_id),
  FOREIGN KEY (return_id) REFERENCES returns(return_id),
  FOREIGN KEY (created_by) REFERENCES users(user_id)
);

-- ========== 10. ການຈ່າຍເງິນ ==========
CREATE TABLE payments (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  invoice_id INT NULL,
  rental_id INT NOT NULL,
  payment_type ENUM('deposit','rent','late_fee','damage','refund') NOT NULL,
  method ENUM('cash','transfer','qr') DEFAULT 'cash',
  amount DECIMAL(12,2) NOT NULL,
  currency ENUM('LAK','THB','USD') DEFAULT 'LAK',
  ref_no VARCHAR(80),
  slip_image VARCHAR(255),
  paid_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  received_by INT NOT NULL,
  FOREIGN KEY (invoice_id) REFERENCES invoices(invoice_id),
  FOREIGN KEY (rental_id) REFERENCES rentals(rental_id),
  FOREIGN KEY (received_by) REFERENCES users(user_id)
);

-- ========== 11. ແຈ້ງເຕືອນ ແອັດມີນ ==========
CREATE TABLE notifications (
  notification_id INT AUTO_INCREMENT PRIMARY KEY,
  type ENUM('new_member','new_booking','booking_cancel','overdue','payment') NOT NULL,
  title VARCHAR(150) NOT NULL,
  message VARCHAR(255),
  ref_table VARCHAR(30),
  ref_id INT,
  is_read TINYINT(1) DEFAULT 0,
  read_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (read_by) REFERENCES users(user_id),
  INDEX idx_unread (is_read, created_at)
);

-- ========== 12. Log ການໃຊ້ງານ (ສຳລັບລາຍງານຜູ້ນຳໃຊ້) ==========
CREATE TABLE activity_logs (
  log_id BIGINT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  action VARCHAR(50) NOT NULL,           -- login, create_rental, return, payment, edit...
  table_name VARCHAR(50),
  record_id INT,
  detail TEXT,
  ip_address VARCHAR(45),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(user_id),
  INDEX idx_user_date (user_id, created_at)
);

-- ========== ຂໍ້ມູນເບື້ອງຕົ້ນ ==========
INSERT INTO roles (role_name) VALUES ('admin'),('staff'),('cashier');
INSERT INTO vehicle_types (type_name, category) VALUES
 ('Sedan','car'),('SUV','car'),('Pickup','car'),('Van','car'),
 ('Scooter','motorcycle'),('Manual','motorcycle'),('Big bike','motorcycle');
INSERT INTO penalty_rules (category, grace_minutes, unit, rate_percent) VALUES
 ('all', 60, 'hour', 10.00),   -- ຊ້າ: 10% ຂອງຄ່າເຊົ່າ/ວັນ ຕໍ່ຊົ່ວໂມງ
 ('all', 0,  'day',  150.00);  -- ເກີນ 1 ວັນເຕັມ: ຄິດ 150% ຂອງຄ່າເຊົ່າ/ວັນ

-- ========== View ຕົວຢ່າງ: ລາຍການເຊົ່າທີ່ເກີນກຳນົດ ==========
CREATE VIEW v_overdue_rentals AS
SELECT r.rental_id, r.rental_code, CONCAT(c.first_name,' ',c.last_name) AS customer,
       c.phone, v.plate_no, r.due_datetime,
       TIMESTAMPDIFF(MINUTE, r.due_datetime, NOW()) AS late_minutes
FROM rentals r
JOIN customers c ON c.customer_id = r.customer_id
JOIN vehicles v ON v.vehicle_id = r.vehicle_id
WHERE r.status IN ('active','overdue') AND r.due_datetime < NOW();
