<?php
/**
 * Authentication Helpers
 * ฟังก์ชันช่วยเหลือด้านการยืนยันตัวตน
 *
 * หมายเหตุ: loginUser, registerUser, logoutUser ย้ายไป AuthController แล้ว
 *          session timeout ย้ายไป app/init.php แล้ว
 *          database.php, functions.php โหลดผ่าน app/init.php แล้ว
 */

if (!function_exists('isLoggedIn')) {
    function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }
}

if (!function_exists('requireLogin')) {
    function requireLogin()
    {
        if (!isLoggedIn()) {
            setFlash('warning', 'กรุณาเข้าสู่ระบบก่อน');
            redirect(SITE_URL . '/login');
        }

        $user = getCurrentUser();
        if (!$user) {
            setFlash('danger', 'บัญชีผู้ใช้ของคุณถูกลบออกจากระบบ');
            redirect(SITE_URL . '/login');
        }
    }
}

if (!function_exists('hasRole')) {
    function hasRole($roles)
    {
        if (!isLoggedIn()) return false;
        if (!is_array($roles)) $roles = [$roles];
        return in_array($_SESSION['user_role'], $roles);
    }
}

if (!function_exists('requireRole')) {
    function requireRole($roles)
    {
        requireLogin();
        if (!hasRole($roles)) {
            setFlash('danger', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            redirect(SITE_URL . '/login');
        }
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        if (!isLoggedIn()) return null;
        $user = User::find('users', $_SESSION['user_id']);
        if (!$user) {
            session_unset();
            session_destroy();
            return null;
        }
        return $user;
    }
}

if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }
}

if (!function_exists('getCurrentRole')) {
    function getCurrentRole()
    {
        return $_SESSION['user_role'] ?? null;
    }
}

if (!function_exists('isUniversityEmail')) {
    function isUniversityEmail($email)
    {
        return str_ends_with(strtolower($email), '@rmutsb.ac.th');
    }
}
