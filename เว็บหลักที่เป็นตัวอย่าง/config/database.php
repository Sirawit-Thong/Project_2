<?php
/**
 * Database Configuration
 * การตั้งค่าเชื่อมต่อฐานข้อมูล
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'equipment_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

/**
 * สร้างการเชื่อมต่อฐานข้อมูล PDO
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
        }
    }
    
    return $pdo;
}

// Application settings
define('SITE_NAME', 'ระบบแจ้งซ่อมครุภัณฑ์');
define('SITE_URL', 'http://localhost/P1');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
