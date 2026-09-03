<?php
// ตั้งเวลาประเทศไทยเป็นค่าเริ่มต้นทั้งระบบ กัน InfinityFree (UTC) ช้า 7-14 ชม.
date_default_timezone_set('Asia/Bangkok');

/**
 * Database Configuration
 * การตั้งค่าเชื่อมต่อฐานข้อมูล
 *
 * ค่าด้านล่างเป็นค่าเริ่มต้นของโปรเจคนี้ (auto-detect ระหว่าง local กับ production)
 * ถ้าต้องการ override ให้ตั้ง environment variables: DB_HOST, DB_NAME, DB_USER, DB_PASS, SITE_URL
 * (เช่น ผ่าน SetEnv ใน .htaccess, export ใน shell, หรือ .env)
 *
 * ⚠️ หมายเหตุ: ค่าเชื่อมต่อ production อยู่ในไฟล์นี้เพื่อให้ deploy/setup ง่าย
 *    ถ้า repo นี้จะถูกทำให้เป็น public หรือแชร์ให้คนอื่น ควรย้ายไป env vars แทน
 */

// Helper: อ่าน env จาก getenv() หรือ $_SERVER (SetEnv ของ Apache ไปอยู่ใน $_SERVER ได้ทั้งคู่)
if (!function_exists('env')) {
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_SERVER[$key] ?? null;
        }
        return ($value !== null && $value !== '') ? $value : $default;
    }
}

// Auto-detect: local (XAMPP) หรือ production (InfinityFree)
// เพิ่ม CLI detection เพื่อให้ cron / scripts ทำงานบน local ได้โดยไม่ต้อง spoof $_SERVER
$isLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'])
    || strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false
    || php_sapi_name() === 'cli'
    || empty($_SERVER['HTTP_HOST'] ?? '') && !empty($_SERVER['argv'] ?? null);

// ค่าเริ่มต้นของโปรเจค — ล็อก Host หลักอย่างเดียว (free.nf)
$__host = $_SERVER['HTTP_HOST'] ?? '';
if ($isLocal) {
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_NAME', env('DB_NAME', 'equipment_db'));
    define('DB_USER', env('DB_USER', 'root'));
    define('DB_PASS', env('DB_PASS', ''));
} else {
    // Production: Host หลัก khuruphan-rus.free.nf อย่างเดียว
    define('DB_HOST', env('DB_HOST', 'sql103.infinityfree.com'));
    define('DB_NAME', env('DB_NAME', 'if0_40083938_invent_db'));
    define('DB_USER', env('DB_USER', 'if0_40083938'));
    define('DB_PASS', env('DB_PASS', 'tnRWdRx6inu7F'));
    // ถ้าเผลอเข้า Host รอง (free.je) ให้ 307 ไป Host หลักทันที กันล็อค/เด้ง (307 คง POST)
    if (strpos($__host, 'free.je') !== false || strpos($__host, 'khuruphan-rus.free.je') !== false) {
        $redirectUrl = 'https://khuruphan-rus.free.nf' . ($_SERVER['REQUEST_URI'] ?? '/');
        header('Location: ' . $redirectUrl, true, 307);
        exit;
    }
}
define('DB_CHARSET', 'utf8mb4');

// ตรวจว่าเป็น HTTPS หรือไม่ (ใช้กำหนด cookie_secure และ SITE_URL)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['SERVER_PORT'] ?? '') == 443)
    || strpos($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '', 'https') !== false;

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
            // บังคับ MySQL session ให้เป็น +07:00 (Bangkok) กัน TIMESTAMP DEFAULT ช้า
            try {
                $pdo->exec("SET time_zone = '+07:00'");
            } catch (PDOException $e) {
                // InfinityFree บางแผนอาจไม่อนุญาต SET time_zone ให้ fallback เป็น UTC+7 แบบ manual
                // ไม่ throw เพื่อไม่ให้เว็บล่ม
            }
        } catch (PDOException $e) {
            die("การเชื่อมต่อฐานข้อมูลล้มเหลว: " . $e->getMessage());
        }
    }

    return $pdo;
}

// Application settings
define('SITE_NAME', 'ระบบแจ้งซ่อมครุภัณฑ์');
// Auto-detect base path from SCRIPT_NAME (e.g. /Project_2/index.php -> /Project_2)
$__basePath = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
if ($__basePath === '/' || $__basePath === '\\' || $__basePath === '.') $__basePath = '';
if ($isLocal) {
    define('SITE_URL', env('SITE_URL', 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $__basePath));
} else {
    // Production: ล็อก Host หลักอย่างเดียว
    define('SITE_URL', env('SITE_URL', 'https://khuruphan-rus.free.nf'));
}
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// Start session with secure settings
if (session_status() === PHP_SESSION_NONE) {
    // Set secure session params before starting
    ini_set('session.cookie_httponly', 1);
    // ส่ง session cookie ผ่าน HTTPS เท่านั้น (ถ้าเว็บใช้ HTTPS) — กัน session hijacking
    ini_set('session.cookie_secure', $isHttps ? 1 : 0);
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
