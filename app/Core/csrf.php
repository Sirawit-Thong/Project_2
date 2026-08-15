<?php
/**
 * CSRF Protection
 * ระบบป้องกัน Cross-Site Request Forgery
 */

/**
 * สร้างหรือคืนค่า CSRF token
 */
function csrf_token()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * สร้าง hidden input field สำหรับ CSRF token
 */
function csrf_field()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

/**
 * ตรวจสอบ CSRF token
 */
function verify_csrf($token)
{
    if (empty($token) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * ตรวจสอบ CSRF token จาก POST request (ใช้ใน Controller)
 * @return true ถ้าผ่าน, redirect กลับหน้าก่อนถ้าไม่ผ่าน
 */
function require_csrf()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verify_csrf($token)) {
            setFlash('danger', 'คำขอไม่ถูกต้อง กรุณาลองใหม่อีกครั้ง');
            $siteUrl = defined('SITE_URL') ? SITE_URL : '/';
            $referer = $_SERVER['HTTP_REFERER'] ?? $siteUrl;
            // ตรวจว่า referer อยู่ใน host เดียวกัน (เทียบ host ไม่ใช่ prefix — กัน open redirect)
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $siteHost = parse_url($siteUrl, PHP_URL_HOST);
            if ($refererHost !== $siteHost) {
                $referer = $siteUrl;
            }
            redirect($referer);
        }
    }
    return true;
}
