<?php
/**
 * Database Configuration
 * การตั้งค่าเชื่อมต่อฐานข้อมูล
 *
 * IMPORTANT: In production, use environment variables or .env file
 * for sensitive credentials. Never commit passwords to version control.
 */

// Auto-detect: local (XAMPP) or production (InfinityFree)
// On InfinityFree, env vars are set via .htaccess SetEnv
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'equipment_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
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
define('SITE_URL', getenv('SITE_URL') ?: 'http://localhost/P');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session params before starting
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 in production with HTTPS
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_trans_sid', 0);
    ini_set('session.gc_maxlifetime', 1800); // 30 minutes

    session_start();

    // Regenerate session ID periodically to prevent fixation
    if (!isset($_SESSION['_created'])) {
        $_SESSION['_created'] = time();
    } elseif (time() - $_SESSION['_created'] > 300) { // 5 minutes
        session_regenerate_id(true);
        $_SESSION['_created'] = time();
    }
}
