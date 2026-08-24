<?php
/**
 * Application Bootstrap
 * ไฟล์เริ่มต้นระบบที่โหลดทุกสิ่งที่จำเป็น
 *
 * ใช้: require_once __DIR__ . '/app/init.php';
 *      หรือ require_once dirname(__DIR__) . '/app/init.php'; (จาก subfolder)
 */

// ============================================
// Config & Database
// ============================================
require_once __DIR__ . '/../config/database.php';

// ============================================
// Helpers
// ============================================
require_once __DIR__ . '/Helpers/functions.php';
require_once __DIR__ . '/Core/csrf.php';

// ============================================
// Autoloader — โหลด class อัตโนมัติตามชื่อ
// ============================================
spl_autoload_register(function ($class) {
    $map = [
        // Core
        'Model'       => __DIR__ . '/Core/Model.php',
        'Controller'  => __DIR__ . '/Core/Controller.php',
        'Router'      => __DIR__ . '/Core/Router.php',
        'ErrorHandler' => __DIR__ . '/Core/ErrorHandler.php',
        // Models
        'User'        => __DIR__ . '/Models/User.php',
        'Equipment'   => __DIR__ . '/Models/Equipment.php',
        'Repair'      => __DIR__ . '/Models/Repair.php',
        'Department'  => __DIR__ . '/Models/Department.php',
        'SetModel'    => __DIR__ . '/Models/SetModel.php',
        'Item'        => __DIR__ . '/Models/Item.php',
        'Room'        => __DIR__ . '/Models/Room.php',
        'RoomManager' => __DIR__ . '/Models/RoomManager.php',
        'SystemLog'   => __DIR__ . '/Models/SystemLog.php',
        'EquipmentImage' => __DIR__ . '/Models/EquipmentImage.php',
        'EquipmentStats' => __DIR__ . '/Models/EquipmentStats.php',
        'AssetCategory'       => __DIR__ . '/Models/AssetCategory.php',
        'DepreciationSetting' => __DIR__ . '/Models/DepreciationSetting.php',
        'DepreciationReport'  => __DIR__ . '/Models/DepreciationReport.php',
        'Satisfaction'        => __DIR__ . '/Models/Satisfaction.php',
        'DepreciationCalculator' => __DIR__ . '/Core/DepreciationCalculator.php',
        'RateLimiter'    => __DIR__ . '/Core/RateLimiter.php',
        // Controllers
        'AuthController'      => __DIR__ . '/Controllers/AuthController.php',
        'DashboardController' => __DIR__ . '/Controllers/DashboardController.php',
        'EquipmentController' => __DIR__ . '/Controllers/EquipmentController.php',
        'RepairController'    => __DIR__ . '/Controllers/RepairController.php',
        'UserController'      => __DIR__ . '/Controllers/UserController.php',
        'DepartmentController' => __DIR__ . '/Controllers/DepartmentController.php',
        'SetController'       => __DIR__ . '/Controllers/SetController.php',
        'ItemController'      => __DIR__ . '/Controllers/ItemController.php',
        'RoomController'      => __DIR__ . '/Controllers/RoomController.php',
        'RoomManagerController' => __DIR__ . '/Controllers/RoomManagerController.php',
        'AdminController'     => __DIR__ . '/Controllers/AdminController.php',
        'DepreciationController' => __DIR__ . '/Controllers/DepreciationController.php',
        'SatisfactionController' => __DIR__ . '/Controllers/SatisfactionController.php',
    ];

    if (isset($map[$class])) {
        require_once $map[$class];
        return;
    }

    // Fallback: ค้นหาใน app/ directory tree
    $dirs = [__DIR__ . '/Core', __DIR__ . '/Models', __DIR__ . '/Controllers', __DIR__ . '/Helpers'];
    foreach ($dirs as $dir) {
        $file = $dir . '/' . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

ErrorHandler::register();

// ============================================
// Auth Helpers (isLoggedIn, requireLogin, etc.)
// ============================================
require_once __DIR__ . '/../includes/auth.php';

// ============================================
// Session Timeout (30 นาที)
// ============================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$timeout_duration = 1800;

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    session_start();

    setFlash('warning', 'หมดเวลาการใช้งาน กรุณาเข้าสู่ระบบใหม่');
    header("Location: " . SITE_URL . "/login");
    exit();
}

$_SESSION['last_activity'] = time();

// ============================================
// Security Headers
// ============================================
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
