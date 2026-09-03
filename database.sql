-- ============================================================
-- database.sql — Fresh Database Schema + Starter Data
-- ฐานข้อมูล: equipment_db (utf8mb4_unicode_ci)
-- รองรับภาษาไทยด้วย utf8mb4 ทั้งฐานข้อมูลและตาราง
-- มีข้อมูลเริ่มต้นสำหรับทดสอบระบบ: Admin / Staff / Teacher / Test Student
-- ============================================================
-- วิธี import (ไม่ต้องแก้ไฟล์นี้):
-- 1) XAMPP / CLI (Local):
--    cmd /c "C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 equipment_db < database.sql"
--    ⚠️ ต้องใส่ --default-character-set=utf8mb4 และระบุ DB (equipment_db) ก่อน < ไฟล์ — ไม่ต้องแก้ USE ในไฟล์
--    ⚠️ ต้องใส่ --default-character-set=utf8mb4 เสมอ ไม่งั้นภาษาไทยเพี้ยน (double-encoded)
--
-- 2) phpMyAdmin / InfinityFree (Production):
--    - เลือกฐานข้อมูล if0_40083938_equipment_db ก่อน
--    - Import ไฟล์นี้ได้เลย — ไม่ต้องแก้ไฟล์, ไม่ต้องใส่ USE
--    - phpMyAdmin จัดการ charset utf8mb4 ให้เอง
--    - หลังจากสร้างโครงสร้างแล้ว ให้นำเข้าไฟล์ if0_40083938_equipment_db_import.sql
--      เพื่อเติมข้อมูล Production (ไฟล์นี้เป็น Data Import โดยเฉพาะ ไม่สร้างตารางซ้ำ)
-- 3) นำเข้าข้อมูลจริง if0_40083938_equipment_db.sql โดยไม่ต้องแก้ไฟล์ if0:
--    ไฟล์ if0 เป็น dump แบบไม่มี IF NOT EXISTS และมี sets.name ซ้ำ 1 ชื่อ (19 แถว) ทำให้ import ตรงๆ จะ error #1050 (Table exists) และ #1062 (Duplicate entry for key 'name')
--    ให้รันแบบเติม IF NOT EXISTS + ลบ UNIQUE ของ sets ชั่วคราวโดยไม่ต้องแก้ไฟล์ถาวร:
--    powershell -Command "$c=Get-Content 'if0_40083938_equipment_db.sql' -Raw -Encoding UTF8; $c=$c -replace 'CREATE TABLE `','CREATE TABLE IF NOT EXISTS `'; $c=$c -replace 'INSERT INTO `','INSERT IGNORE INTO `'; $c=$c -replace '\s+ADD UNIQUE KEY `name` \(`name`\),',''; $c | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 --force equipment_db"
--    # คำสั่งนี้ไม่แก้ไฟล์ if0 ถาวร แค่เติม IF NOT EXISTS, INSERT IGNORE และลบ UNIQUE ของ sets (ทุกตารางที่มี ADD UNIQUE KEY `name`) ชั่วคราวก่อน pipe เข้า mysql, --force จะข้าม FK/index duplicate ที่เหลือ (ERROR 1068 Multiple primary key, 121 Duplicate key) แต่ข้อมูล 18-19 sets จะถูกนำเข้า
--    # ใช้ได้ทั้งสองลำดับ:
--    # ลำดับ A: database.sql ก่อน → if0 ทีหลัง (เช่น local มีโครงสร้างแล้ว อยากได้ข้อมูล production)
--    #   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 equipment_db < database.sql
--    #   powershell -Command "...if0..." | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 --force equipment_db
--    # ลำดับ B: if0 ก่อน → database.sql ทีหลัง (fresh production → upgrade) — แนะนำสำหรับ production
--    #   powershell -Command "...if0..." | C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 --force if0_40083938_equipment_db
--    #   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 if0_40083938_equipment_db < database.sql
--    # phpMyAdmin: หาก Import if0 แล้วเจอ #1050/#1062 ให้ติ๊ก "Enable foreign key checks" ออก หรือใช้วิธี CLI ข้างบนแทน
--    หมายเหตุ: database.sql ถูกแก้ให้ sets.name ไม่ UNIQUE ใน CREATE และเพิ่ม UNIQUE แบบ conditional ท้ายไฟล์ (เช็ค duplicate ก่อน) จึงไม่ error #1062 แม้ if0 มีชื่อซ้ำ
-- ============================================================

-- Auto-create DB (ไม่ต้องแก้ไฟล์): สร้าง equipment_db ไว้ถ้ายังไม่มี (ไม่เปลี่ยน DB ปัจจุบัน)
CREATE DATABASE IF NOT EXISTS equipment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE equipment_db;
-- ไฟล์นี้เลือกฐานข้อมูล equipment_db ให้เอง จึง import ผ่าน phpMyAdmin ได้โดยไม่ต้องเลือกฐานข้อมูลล่วงหน้า:
-- CLI (แนะนำ): mysql -u root --default-character-set=utf8mb4 equipment_db < database.sql
-- phpMyAdmin: สามารถ Import ไฟล์นี้ได้เลย เพราะไฟล์เลือก equipment_db ให้เอง

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLE DEFINITIONS (เรียงตามลำดับ Foreign Key)
-- ทุกตารางใช้ IF NOT EXISTS เพื่อรันซ้ำได้ (idempotent)
-- ============================================================

-- Table: users (Use Email for Login)
-- ตาราง: ผู้ใช้งาน (ใช้อีเมลในการเข้าสู่ระบบ)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสผู้ใช้ (Auto ID)
    sid VARCHAR(50) DEFAULT NULL, -- รหัสนักศึกษา หรือ รหัสพนักงาน
    firstname VARCHAR(100) NOT NULL, -- ชื่อจริง
    lastname VARCHAR(100) NOT NULL, -- นามสกุล
    email VARCHAR(100) NOT NULL UNIQUE, -- อีเมล (ใช้สำหรับ Login ห้ามซ้ำ)
    password VARCHAR(255) NOT NULL, -- รหัสผ่าน (เข้ารหัส Hash)
    role ENUM('admin', 'staff', 'teacher', 'student') DEFAULT 'student', -- บทบาท: ผู้ดูแล, เจ้าหน้าที่, อาจารย์, นักศึกษา
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending', -- สถานะบัญชี: รออนุมัติ, อนุมัติแล้ว, ถูกปฏิเสธ
    class VARCHAR(50) DEFAULT NULL, -- ชั้นปี/ห้องเรียน (สำหรับนักศึกษา) เช่น ITS36641N
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- วันที่สร้าง
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP -- วันที่แก้ไขล่าสุด
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: dept (Level 0: สาขา)
-- ตาราง: สาขาวิชา (ระดับสูงสุดของโครงสร้างครุภัณฑ์)
CREATE TABLE IF NOT EXISTS dept (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสสาขา
    name VARCHAR(255) NOT NULL UNIQUE, -- ชื่อสาขา (เช่น เทคโนโลยีสารสนเทศ)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: asset_categories (หมวดหมู่ครุภัณฑ์สำหรับเกณฑ์ค่าเสื่อมราคา)
CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: depreciation_settings (เกณฑ์ค่าเสื่อมราคาต่อหมวดหมู่ — 1 หมวด = 1 เกณฑ์)
CREATE TABLE IF NOT EXISTS depreciation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL, -- หมวดหมู่ (FK -> asset_categories.id) UNIQUE
    useful_life_years INT NOT NULL, -- อายุการใช้งาน (ปี)
    dep_rate DECIMAL(5, 2) NOT NULL, -- เปอร์เซ็นต์ค่าเสื่อมต่อปี (เช่น 20.00 = 20%)
    method ENUM('straight_line', 'declining_balance') NOT NULL DEFAULT 'straight_line', -- วิธีคิด: เส้นตรง / ลดยอดคงเหลือ
    updated_by INT DEFAULT NULL, -- ผู้แก้ไขล่าสุด (FK -> users.id)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_dep_settings_category FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE CASCADE,
    CONSTRAINT fk_dep_settings_user FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_dep_setting_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: sets (Level 1: ชุดครุภัณฑ์)
-- ตาราง: ชุดครุภัณฑ์ (Grouping ของครุภัณฑ์ตามปี/ชุดจัดซื้อ)
-- หมายเหตุ: name ไม่ใส่ UNIQUE ใน CREATE เพื่อรองรับข้อมูลจริงจาก if0 ที่มีชื่อซ้ำ (19 แถว, duplicate 1 ชื่อ)
-- UNIQUE จะถูกเพิ่มแบบ conditional ท้ายไฟล์ (UPGRADE GUARDS) เฉพาะเมื่อไม่มี duplicate
CREATE TABLE IF NOT EXISTS sets (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสชุด
    dept_id INT, -- อ้างอิงสาขา (Foreign Key -> dept.id)
    name VARCHAR(255) NOT NULL, -- ชื่อชุดครุภัณฑ์
    year VARCHAR(50) NOT NULL, -- ปีงบประมาณ (เช่น 2567)
    price DECIMAL(10, 2) DEFAULT 0.00, -- ราคารวมทั้งชุด
    price_remark TEXT, -- หมายเหตุราคา (เช่น ราคาเหมา 5 ล้านบาท)
    remark TEXT, -- หมายเหตุ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_sets_dept FOREIGN KEY (dept_id) REFERENCES dept(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: items (Level 2: รายการครุภัณฑ์)
-- ตาราง: รายการแม่แบบครุภัณฑ์ (Master Items) กำหนดสเปค รุ่น ยี่ห้อ
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสรายการ
    set_id INT NOT NULL, -- อ้างอิงชุด (Foreign Key -> sets.id)
    category_id INT DEFAULT NULL, -- หมวดหมู่ครุภัณฑ์ (FK -> asset_categories.id) ใช้อ้างเกณฑ์ค่าเสื่อมราคา
    name VARCHAR(255) NOT NULL, -- ชื่อรายการครุภัณฑ์ (เช่น เครื่องคอมพิวเตอร์)
    brand VARCHAR(100), -- ยี่ห้อ
    model VARCHAR(100), -- รุ่น
    qty INT DEFAULT 0, -- จำนวนที่มีในชุด
    unit VARCHAR(50), -- หน่วยนับ (เครื่อง, ชุด, อัน)
    price DECIMAL(10, 2) DEFAULT 0.00, -- ราคาต่อหน่วย
    price_remark TEXT, -- หมายเหตุราคา (เช่น ราคาต่อหน่วย 75,500 บาท)
    remark TEXT, -- หมายเหตุ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_items_set FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_category2 FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: rooms (Master Data: ห้อง)
-- ตาราง: ข้อมูลห้องเรียน/สถานที่
CREATE TABLE IF NOT EXISTS rooms (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสห้อง
    name VARCHAR(255) NOT NULL UNIQUE, -- ชื่อห้อง (เช่น 6101)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: room_managers (Teachers who manage rooms)
-- ตาราง: ผู้รับผิดชอบห้อง (อาจารย์ผู้ดูแล)
CREATE TABLE IF NOT EXISTS room_managers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    room_id INT NOT NULL, -- อ้างอิงห้อง
    user_id INT NOT NULL, -- อ้างอิงอาจารย์ (users.id)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_room_mgr_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    CONSTRAINT fk_room_mgr_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(room_id, user_id) -- ห้ามซ้ำคู่เดิม
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: equipment (Level 3: รายละเอียดครุภัณฑ์/ตัวครุภัณฑ์)
-- ตาราง: ทะเบียนครุภัณฑ์รายชิ้น (Asset Inventory)
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL, -- อ้างอิงรายการแม่แบบ (items.id)
    code VARCHAR(50) UNIQUE DEFAULT NULL, -- รหัสครุภัณฑ์ (Asset Code)
    room_id INT DEFAULT NULL, -- สถานที่ตั้งปัจจุบัน (Foreign Key -> rooms.id)
    status ENUM('available', 'repair', 'broken', 'disposed', 'pending_disposal') DEFAULT 'available',
    -- สถานะ: ปกติ, ส่งซ่อม, ชำรุด, จำหน่ายออก, รอจำหน่าย
    purchase_date DATE, -- วันที่จัดซื้อ
    check_date DATE, -- วันที่ตรวจเช็คเมื่อ
    price DECIMAL(10, 2) DEFAULT 0.00, -- ราคาจริงของชิ้นนี้
    price_remark TEXT, -- หมายเหตุราคา (กรณีพิเศษรายชิ้น)
    remark TEXT, -- หมายเหตุเพิ่มเติม
    holder_id INT, -- ผู้ถือครอง/ผู้รับผิดชอบปัจจุบัน (Foreign Key -> users.id)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equipment_item FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    CONSTRAINT fk_equipment_holder FOREIGN KEY (holder_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_equipment_room FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: equipment_img (Multiple Photos)
-- ตาราง: รูปภาพครุภัณฑ์ (รองรับหลายรูป)
CREATE TABLE IF NOT EXISTS equipment_img (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL, -- อ้างอิงครุภัณฑ์
    path VARCHAR(255) NOT NULL, -- path ไฟล์รูป
    type ENUM('purchase', 'current_condition') NOT NULL DEFAULT 'current_condition', -- ประเภทรูป: รูปตอนซื้อ, รูปสภาพปัจจุบัน
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_equip_img_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: repair (Maintenance Requests)
-- ตาราง: การแจ้งซ่อม
CREATE TABLE IF NOT EXISTS repair (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL, -- ครุภัณฑ์ที่แจ้งซ่อม
    user_id INT NULL, -- ผู้แจ้ง (Link กับ users table)
    issue TEXT NOT NULL, -- อาการที่พบ/ปัญหา
    status ENUM('pending', 'in_progress', 'completed', 'cannot_fix') DEFAULT 'pending',
    -- สถานะซ่อม: รอดำเนินการ, กำลังซ่อม, เสร็จสิ้น, ซ่อมไม่ได้
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_repair_equipment FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    CONSTRAINT fk_repair_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: repair_img (Repair Photos)
-- ตาราง: รูปประกอบการแจ้งซ่อม/การซ่อม
CREATE TABLE IF NOT EXISTS repair_img (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL, -- อ้างอิงใบแจ้งซ่อม
    path VARCHAR(255) NOT NULL, -- path ไฟล์รูป
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_repair_img_repair FOREIGN KEY (repair_id) REFERENCES repair(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: satisfaction_surveys (แบบประเมินความพึงพอใจหลังซ่อมเสร็จ — 1 ใบซ่อมประเมินได้ 1 ครั้ง)
CREATE TABLE IF NOT EXISTS satisfaction_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL, -- ใบแจ้งซ่อม (FK -> repair.id) UNIQUE — ป้องกันประเมินซ้ำ
    user_id INT DEFAULT NULL, -- ผู้ประเมิน (FK -> users.id)
    rating TINYINT UNSIGNED NOT NULL, -- คะแนนความพึงพอใจ 1-5
    comment TEXT, -- คำติชม/ข้อเสนอแนะ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_satisfaction_repair FOREIGN KEY (repair_id) REFERENCES repair(id) ON DELETE CASCADE,
    CONSTRAINT fk_satisfaction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_survey_repair (repair_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: system_logs (Audit Trail / Activity Logs)
-- ตาราง: บันทึกการใช้งานระบบ (Audit Log)
CREATE TABLE IF NOT EXISTS system_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL, -- ผู้กระทำ (ถ้ามี)
    action VARCHAR(255) NOT NULL, -- ชื่อการกระทำ (เช่น Login, Add User)
    details TEXT, -- รายละเอียดเพิ่มเติม
    ip_address VARCHAR(45), -- IP Address ผู้ใช้งาน
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- STARTER DATA — ข้อมูลเริ่มต้นสำหรับทดสอบระบบ
-- รองรับภาษาไทย: UTF-8 / utf8mb4
-- รหัสผ่านสำหรับทุกบัญชีเริ่มต้น: 123456
-- ============================================================

INSERT IGNORE INTO users
    (sid, firstname, lastname, email, password, role, status, class)
VALUES
    (NULL, 'ผู้ดูแลระบบ', 'ทดสอบ', 'admin@rmutsb.ac.th',
     '$2y$10$0oRIyPbbbKpeHSoXfnmiB.Vp9TIxynqZygTVJ3yJwIAFSbGSm0.mG',
     'admin', 'approved', NULL),
    (NULL, 'พนักงาน', 'ทดสอบ', 'staff@rmutsb.ac.th',
     '$2y$10$0oRIyPbbbKpeHSoXfnmiB.Vp9TIxynqZygTVJ3yJwIAFSbGSm0.mG',
     'staff', 'approved', NULL),
    (NULL, 'อาจารย์', 'ทดสอบ', 'teacher@rmutsb.ac.th',
     '$2y$10$0oRIyPbbbKpeHSoXfnmiB.Vp9TIxynqZygTVJ3yJwIAFSbGSm0.mG',
     'teacher', 'approved', NULL),
    (NULL, 'บัญชี', 'ทดสอบ', 'test@rmutsb.ac.th',
     '$2y$10$0oRIyPbbbKpeHSoXfnmiB.Vp9TIxynqZygTVJ3yJwIAFSbGSm0.mG',
     'student', 'approved', NULL);

-- ข้อมูลห้องเริ่มต้นสำหรับทดลองระบบ
INSERT IGNORE INTO rooms (name) VALUES
    ('6101'), ('6102'), ('6103'), ('6104'), ('6201'),
('6301'), ('6302'), ('6303'), ('6304'), ('6305'), ('6306'),
('6401'), ('6402'), ('6403'), ('6404'), ('6405'), ('6406'),
('6501'), ('6502'), ('6503'), ('6504'), ('6505'), ('6506'),
('6601'), ('6602'), ('6603'), ('6604'), ('6605'), ('6606');

-- หมวดหมู่เริ่มต้นสำหรับทดลองระบบ
INSERT IGNORE INTO asset_categories (id, name, remark) VALUES
    (1, 'เครื่องคอมพิวเตอร์และอุปกรณ์ไอที',
        'คอมพิวเตอร์ โน้ตบุ๊ก เซิร์ฟเวอร์ และอุปกรณ์เครือข่าย'),
    (2, 'เฟอร์นิเจอร์และของใช้สำนักงาน',
        'โต๊ะ เก้าอี้ ตู้ และอุปกรณ์สำนักงาน');

-- เกณฑ์ค่าเสื่อมราคาเริ่มต้น
INSERT IGNORE INTO depreciation_settings
    (category_id, useful_life_years, dep_rate, method)
VALUES
    (1, 5, 20.00, 'straight_line'),
    (2, 10, 10.00, 'straight_line');

-- ============================================================
-- END OF SCHEMA
-- ============================================================
