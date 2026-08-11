<?php
/**
 * Authentication Helper
 * ฟังก์ชันช่วยเหลือด้านการยืนยันตัวตน
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

// ============================================
// ตั้งค่า Session Timeout (30 นาที)
// ============================================
$timeout_duration = 1800; // 30 นาที = 1800 วินาที

if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // ถ้าหมดเวลา
    session_unset();
    session_destroy();
    session_start();     // เริ่ม session ใหม่เพื่อเก็บ flash message

    if (function_exists('setFlash')) {
        setFlash('warning', 'หมดเวลาการใช้งาน กรุณาเข้าสู่ระบบใหม่');
    }

    header("Location: " . SITE_URL . "/login.php");
    exit();
}

$_SESSION['last_activity'] = time(); // อัพเดทเวลาล่าสุด

/**
 * ตรวจสอบว่า login แล้วหรือยัง
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

/**
 * บังคับให้ login ก่อนเข้าหน้านี้
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        setFlash('warning', 'กรุณาเข้าสู่ระบบก่อน');
        redirect(SITE_URL . '/login.php');
    }

    // Verify user still exists in database
    $user = getCurrentUser();
    if (!$user) {
        setFlash('danger', 'บัญชีผู้ใช้ของคุณถูกลบออกจากระบบ');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * ตรวจสอบ Role ของผู้ใช้
 */
function hasRole($roles)
{
    if (!isLoggedIn())
        return false;

    if (!is_array($roles)) {
        $roles = [$roles];
    }

    return in_array($_SESSION['user_role'], $roles);
}

/**
 * บังคับให้ต้องมี Role ที่กำหนด
 */
function requireRole($roles)
{
    requireLogin();

    if (!hasRole($roles)) {
        setFlash('danger', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
        redirect(SITE_URL . '/login.php');
    }
}

/**
 * ดึงข้อมูลผู้ใช้ปัจจุบัน
 */
function getCurrentUser()
{
    if (!isLoggedIn())
        return null;

    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // User was deleted from DB while logged in
        session_unset();
        session_destroy();
        return null;
    }

    return $user;
}

/**
 * ดึง user_id ปัจจุบัน
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * ดึง role ปัจจุบัน
 */
function getCurrentRole()
{
    return $_SESSION['user_role'] ?? null;
}

/**
 * Login ผู้ใช้
 * รองรับการ login แบบย่อ:
 * - admin, staff, teacher สามารถใส่แค่ชื่อ role เช่น "admin" 
 * - นักศึกษาสามารถใส่แค่รหัสนักศึกษา เช่น "366408241011"
 * - หรือใส่อีเมลเต็มได้ก็ได้
 */
function loginUser($email, $password)
{
    $pdo = getDB();

    // Normalize input - trim and lowercase
    $input = strtolower(trim($email));

    // Check if input is short form (no @)
    if (strpos($input, '@') === false) {
        // Check if it's a role name (admin/staff/teacher)
        if (in_array($input, ['admin', 'staff', 'teacher'])) {
            // Try to find user with role@rmutsb.ac.th email pattern
            $email = $input . '@rmutsb.ac.th';
        }
        // Check if it looks like a student ID (numeric or contains numbers)
        elseif (preg_match('/^\d+$/', $input) || preg_match('/^\d+-\d+$/', $input)) {
            // Try student ID format: {studentid}-st@rmutsb.ac.th
            $email = $input . '-st@rmutsb.ac.th';
        }
        // Otherwise, try appending @rmutsb.ac.th
        else {
            $email = $input . '@rmutsb.ac.th';
        }
    }

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    // If not found by email, try to find by student ID (sid field)
    if (!$user && strpos($input, '@') === false) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE sid = ?");
        $stmt->execute([$input]);
        $user = $stmt->fetch();
    }

    if (!$user) {
        return ['success' => false, 'error' => 'ไม่พบอีเมลหรือรหัสนักศึกษานี้ในระบบ'];
    }

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง'];
    }

    if ($user['status'] === 'pending') {
        return ['success' => false, 'error' => 'บัญชีของคุณยังรออนุมัติ กรุณารอการอนุมัติจากผู้ดูแลระบบ'];
    }

    if ($user['status'] === 'rejected') {
        return ['success' => false, 'error' => 'บัญชีของคุณถูกปฏิเสธ กรุณาติดต่อผู้ดูแลระบบ'];
    }

    // Set session
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['user_role'] = $user['role'];
    $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
    $_SESSION['user_email'] = $user['email'];

    // Log activity
    logActivity($pdo, $user['id'], 'Login', 'เข้าสู่ระบบสำเร็จ');

    return ['success' => true, 'user' => $user];
}

/**
 * Logout ผู้ใช้
 */
function logoutUser()
{
    $pdo = getDB();

    if (isLoggedIn()) {
        logActivity($pdo, getCurrentUserId(), 'Logout', 'ออกจากระบบ');
    }

    session_destroy();
    session_start();
}

/**
 * ลงทะเบียนผู้ใช้ใหม่
 */
function registerUser($data)
{
    $pdo = getDB();

    // Check email exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$data['email']]);
    if ($stmt->fetch()) {
        return ['success' => false, 'error' => 'อีเมลนี้ถูกใช้งานแล้ว'];
    }

    // Check student ID exists (if student)
    if ($data['role'] === 'student' && !empty($data['sid'])) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE sid = ?");
        $stmt->execute([$data['sid']]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'รหัสนักศึกษานี้ถูกใช้งานแล้ว'];
        }
    }

    // Hash password
    $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);

    // Insert user
    $stmt = $pdo->prepare("
        INSERT INTO users (sid, firstname, lastname, email, password, role, status, class) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)
    ");

    $stmt->execute([
        $data['sid'] ?? null,
        $data['firstname'],
        $data['lastname'],
        $data['email'],
        $hashedPassword,
        $data['role'],
        $data['class'] ?? null
    ]);

    logActivity($pdo, null, 'Register', 'สมัครสมาชิกใหม่: ' . $data['email']);

    return ['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ'];
}

/**
 * ตรวจสอบอีเมลมหาวิทยาลัย
 */
function isUniversityEmail($email)
{
    return str_ends_with(strtolower($email), '@rmutsb.ac.th');
}
