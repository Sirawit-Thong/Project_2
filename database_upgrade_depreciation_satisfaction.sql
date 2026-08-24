-- ============================================================
-- Upgrade Script: ระบบค่าเสื่อมราคา + ระบบความพึงพอใจ
-- รันครั้งเดียวบนฐานข้อมูล equipment_db ที่มีข้อมูลอยู่แล้ว
-- ใช้: cmd /c "C:\xampp\mysql\bin\mysql.exe -u root equipment_db < database_upgrade_depreciation_satisfaction.sql"
-- ============================================================
USE equipment_db;

CREATE TABLE IF NOT EXISTS asset_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE,
    remark TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS depreciation_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    useful_life_years INT NOT NULL,
    dep_rate DECIMAL(5, 2) NOT NULL,
    method ENUM('straight_line', 'declining_balance') NOT NULL DEFAULT 'straight_line',
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_dep_setting_category (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE items ADD COLUMN IF NOT EXISTS category_id INT DEFAULT NULL AFTER set_id;
ALTER TABLE items ADD CONSTRAINT fk_items_category
    FOREIGN KEY (category_id) REFERENCES asset_categories(id) ON DELETE SET NULL;

CREATE TABLE IF NOT EXISTS satisfaction_surveys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    repair_id INT NOT NULL,
    user_id INT DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL,
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (repair_id) REFERENCES repair(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY uq_survey_repair (repair_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO asset_categories (id, name, remark) VALUES
(1, 'เครื่องคอมพิวเตอร์และอุปกรณ์ไอที', 'คอมพิวเตอร์ โน้ตบุ๊ก เซิร์ฟเวอร์ อุปกรณ์เครือข่าย'),
(2, 'อุปกรณ์ทดลองวิทยาศาสตร์', 'เครื่องมือวิทยาศาสตร์/อุปกรณ์ห้องปฏิบัติการ'),
(3, 'เครื่องจักรและเครื่องมือช่าง', 'เครื่องจักรกล เครื่องมือช่าง'),
(4, 'เฟอร์นิเจอร์และของใช้สำนักงาน', 'โต๊ะ เก้าอี้ ตู้ เคาน์เตอร์'),
(5, 'ยานพาหนะ', 'รถยนต์ รถตู้ รถจักรยานยนต์');

INSERT IGNORE INTO depreciation_settings (category_id, useful_life_years, dep_rate, method) VALUES
(1, 5, 20.00, 'straight_line'),
(2, 10, 10.00, 'straight_line'),
(3, 10, 10.00, 'straight_line'),
(4, 10, 10.00, 'straight_line'),
(5, 8, 12.50, 'straight_line');
