<?php
/**
 * Index - Redirect to Login
 * หน้าหลัก - เปลี่ยนเส้นทางไปหน้าเข้าสู่ระบบ
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// If logged in, redirect to appropriate dashboard
if (isLoggedIn()) {
    $role = getCurrentRole();
    switch ($role) {
        case 'admin':
            redirect(SITE_URL . '/admin/index.php');
            break;
        case 'staff':
            redirect(SITE_URL . '/staff/index.php');
            break;
        case 'teacher':
            redirect(SITE_URL . '/teacher/index.php');
            break;
        case 'student':
            redirect(SITE_URL . '/student/index.php');
            break;
    }
} else {
    redirect(SITE_URL . '/login.php');
}
