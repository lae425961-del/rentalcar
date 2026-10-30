SET NAMES utf8mb4;
USE rental_db;
-- ຜູ້ດູແລລະບົບເລີ່ມຕົ້ນ: admin / admin123  (ກະລຸນາປ່ຽນລະຫັດທັນທີຫຼັງເຂົ້າໃຊ້ຄັ້ງທຳອິດ)
INSERT INTO users (role_id, username, password_hash, full_name, phone)
VALUES (1, 'admin', '$2y$10$vONxMw5MJL3I61J633L.YeFvCsCCLKz9JKaPcjTklWepMZuj8usC.', 'ຜູ້ດູແລລະບົບ', '020 00000000');

INSERT INTO provinces (province_name) VALUES ('ນະຄອນຫຼວງວຽງຈັນ'),('ຫຼວງພະບາງ'),('ວຽງຈັນ'),('ສະຫວັນນະເຂດ'),('ຈຳປາສັກ');
INSERT INTO districts (province_id, district_name) VALUES
 (1,'ຈັນທະບູລີ'),(1,'ສີໂຄດຕະບອງ'),(1,'ໄຊເສດຖາ'),(1,'ສີສັດຕະນາກ'),(1,'ນາຊາຍທອງ'),(1,'ໄຊທານີ'),(1,'ຫາດຊາຍຟອງ'),(1,'ສັງທອງ'),(1,'ປາກງື່ມ'),
 (2,'ຫຼວງພະບາງ'),(2,'ຈອມເພັດ'),(3,'ໂພນໂຮງ'),(4,'ໄກສອນ ພົມວິຫານ'),(5,'ປາກເຊ');
INSERT INTO villages (district_id, village_name) VALUES
 (1,'ບ້ານ ໂພນທັນ'),(1,'ບ້ານ ຈອມເພັດ'),(1,'ບ້ານ ສີເມືອງ'),(2,'ບ້ານ ໜອງບອນ'),(2,'ບ້ານ ວັດຈັນ'),(3,'ບ້ານ ຊຽງຍືນ'),(10,'ບ້ານ ຊຽງທອງ');

-- ລົດຕົວຢ່າງ (ລຶບໄດ້)
INSERT INTO vehicles (type_id, plate_no, brand, model, year, color, transmission, fuel_type, seats, daily_rate, deposit_amount, mileage) VALUES
 (1,'ກກ 1234','Toyota','Camry',2020,'ຂາວ','auto','petrol',5,600000,2000000,45000),
 (2,'ຂຂ 5678','Toyota','Fortuner',2021,'ດຳ','auto','diesel',7,900000,3000000,32000),
 (3,'ຄຄ 9012','Toyota','Hilux Revo',2019,'ເງິນ','manual','diesel',5,700000,2500000,70000),
 (5,'ມມ 3456','Honda','Click 125',2022,'ແດງ','auto','petrol',2,120000,500000,8000),
 (6,'ນນ 7890','Honda','Wave 110',2021,'ຟ້າ','manual','petrol',2,80000,300000,15000);
