-- ============================================================
-- Fix: ข้อมูลหมวดหมู่ครุภัณฑ์เพี้ยน (double-encoded) จากการ import
-- ด้วย mysql CLI ที่ไม่ได้ระบุ charset
--
-- วิธีรัน (ต้องมี --default-character-set=utf8mb4 เสมอ):
-- cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 equipment_db < database_fix_charset_seed.sql"
-- ============================================================
USE equipment_db;
SET NAMES utf8mb4;

-- ลบข้อมูลเดิมที่เพี้ยน (settings cascade ตาม category, ยังไม่มี items ผูก)
DELETE FROM depreciation_settings;
DELETE FROM asset_categories;

-- ใส่ใหม่ด้วย id เดิม 1-5
INSERT INTO asset_categories (id, name, remark) VALUES
(1, 'เครื่องคอมพิวเตอร์และอุปกรณ์ไอที', 'คอมพิวเตอร์ โน้ตบุ๊ก เซิร์ฟเวอร์ อุปกรณ์เครือข่าย'),
(2, 'อุปกรณ์ทดลองวิทยาศาสตร์', 'เครื่องมือวิทยาศาสตร์/อุปกรณ์ห้องปฏิบัติการ'),
(3, 'เครื่องจักรและเครื่องมือช่าง', 'เครื่องจักรกล เครื่องมือช่าง'),
(4, 'เฟอร์นิเจอร์และของใช้สำนักงาน', 'โต๊ะ เก้าอี้ ตู้ เคาน์เตอร์'),
(5, 'ยานพาหนะ', 'รถยนต์ รถตู้ รถจักรยานยนต์');

INSERT INTO depreciation_settings (category_id, useful_life_years, dep_rate, method) VALUES
(1, 5, 20.00, 'straight_line'),
(2, 10, 10.00, 'straight_line'),
(3, 10, 10.00, 'straight_line'),
(4, 10, 10.00, 'straight_line'),
(5, 8, 12.50, 'straight_line');
