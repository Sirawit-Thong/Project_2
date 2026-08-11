-- Create Database
-- สร้างฐานข้อมูล equipment_db และกำหนดชุดตัวอักษรเป็น UTF-8
CREATE DATABASE IF NOT EXISTS equipment_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE equipment_db;

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

-- Table: sets (Level 1: ชุดครุภัณฑ์)
-- ตาราง: ชุดครุภัณฑ์ (Grouping ของครุภัณฑ์ตามปี/ชุดจัดซื้อ)
CREATE TABLE IF NOT EXISTS sets (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสชุด
    dept_id INT, -- อ้างอิงสาขา (Foreign Key -> dept.id)
    name VARCHAR(255) NOT NULL UNIQUE, -- ชื่อชุดครุภัณฑ์
    year VARCHAR(50) NOT NULL, -- ปีงบประมาณ (เช่น 2567)
    price DECIMAL(10, 2) DEFAULT 0.00, -- ราคารวมทั้งชุด
    price_remark TEXT, -- หมายเหตุราคา (เช่น ราคาเหมา 5 ล้านบาท)
    remark TEXT, -- หมายเหตุ
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (dept_id) REFERENCES dept(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: items (Level 2: รายการครุภัณฑ์)
-- ตาราง: รายการแม่แบบครุภัณฑ์ (Master Items) กำหนดสเปค รุ่น ยี่ห้อ
CREATE TABLE IF NOT EXISTS items (
    id INT AUTO_INCREMENT PRIMARY KEY, -- รหัสรายการ
    set_id INT NOT NULL, -- อ้างอิงชุด (Foreign Key -> sets.id)
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
    FOREIGN KEY (set_id) REFERENCES sets(id) ON DELETE CASCADE
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
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE(room_id, user_id) -- ห้ามซ้ำคู่เดิม
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: equipment (Level 3: รายละเอียดครุภัณฑ์/ตัวครุภัณฑ์)
-- ตาราง: ทะเบียนครุภัณฑ์รายชิ้น (Asset Inventory)
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_id INT NOT NULL, -- อ้างอิงรายการแม่แบบ (items.id)
    code VARCHAR(50) UNIQUE DEFAULT NULL, -- รหัสครุภัณฑ์ (Asset Code)
    
    -- Location & Status
    room_id INT DEFAULT NULL, -- สถานที่ตั้งปัจจุบัน (Foreign Key -> rooms.id)
    status ENUM('available', 'repair', 'broken', 'disposed', 'pending_disposal') DEFAULT 'available', 
    -- สถานะ: ปกติ, ส่งซ่อม, ชำรุด, จำหน่ายออก, รอจำหน่าย
    
    -- Specific Details
    purchase_date DATE, -- วันที่จัดซื้อ
    check_date DATE, -- วันที่ตรวจเช็คเมื่อ
    price DECIMAL(10, 2) DEFAULT 0.00, -- ราคาจริงของชิ้นนี้
    price_remark TEXT, -- หมายเหตุราคา (กรณีพิเศษรายชิ้น)
    
    remark TEXT, -- หมายเหตุเพิ่มเติม
    
    holder_id INT, -- ผู้ถือครอง/ผู้รับผิดชอบปัจจุบัน (Foreign Key -> users.id)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (item_id) REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (holder_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (room_id) REFERENCES rooms(id) ON DELETE SET NULL
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
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
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
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: repair_img (Repair Photos)
-- ตาราง: รูปประกอบการแจ้งซ่อม/การซ่อม
CREATE TABLE IF NOT EXISTS repair_img (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL, -- อ้างอิงใบแจ้งซ่อม
    path VARCHAR(255) NOT NULL, -- path ไฟล์รูป
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repair(id) ON DELETE CASCADE
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
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ข้อมูลผู้ใช้เริ่มต้น (รหัสผ่านคือ 123456)
INSERT INTO users (sid, email, password, firstname, lastname, role, status, class) VALUES
(NULL, 'admin@rmutsb.ac.th', '$2y$10$O6hMD4KspNa0nAofyrSiuOo31Jd2L2UuUoDuI/CPxHLkDDsJ.Zaze', 'ผู้ดูแลระบบ', 'ทดสอบ', 'admin', 'approved', NULL),
(NULL, 'staff@rmutsb.ac.th', '$2y$10$O6hMD4KspNa0nAofyrSiuOo31Jd2L2UuUoDuI/CPxHLkDDsJ.Zaze', 'พนักงาน', 'ทดสอบ', 'staff', 'approved', NULL),
(NULL, 'teacher@rmutsb.ac.th', '$2y$10$O6hMD4KspNa0nAofyrSiuOo31Jd2L2UuUoDuI/CPxHLkDDsJ.Zaze', 'อาจารย์', 'ทดสอบ', 'teacher', 'approved', NULL),

-- ข้อมูลห้องเริ่มต้น
INSERT INTO rooms (name) VALUES
('6101'), ('6102'), ('6103'), ('6104'), ('6201'),
('6301'), ('6302'), ('6303'), ('6304'), ('6305'), ('6306'),
('6401'), ('6402'), ('6403'), ('6404'), ('6405'), ('6406'),
('6501'), ('6502'), ('6503'), ('6504'), ('6505'), ('6506'),
('6601'), ('6602'), ('6603'), ('6604'), ('6605'), ('6606');

