<?php
/**
 * Helper Functions
 * ฟังก์ชันช่วยเหลือทั่วไปสำหรับระบบ
 */

// ============================================
// Redirect & Flash Messages
// ============================================

function redirect($url)
{
    header("Location: {$url}");
    exit;
}

function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e($text)
{
    return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
}

function flashMessage()
{
    $flash = getFlash();
    if (!$flash) return;

    $type = e($flash['type'] ?? 'info');
    // Allow <br> tags only — escape everything else
    $message = nl2br(e($flash['message'] ?? ''));
    $message = str_replace('&lt;br&gt;', '<br>', $message);
    $message = str_replace('&lt;br /&gt;', '<br />', $message);
    $message = str_replace('&lt;br/&gt;', '<br/>', $message);

    echo '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">';
    echo $message;
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>';
    echo '</div>';
}

// ============================================
// Input Sanitization
// ============================================

function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// ============================================
// Translation Functions
// ============================================

function translateRole($role)
{
    $roles = [
        'admin' => 'ผู้ดูแลระบบ',
        'staff' => 'เจ้าหน้าที่',
        'teacher' => 'อาจารย์',
        'student' => 'นักศึกษา'
    ];
    return $roles[$role] ?? $role;
}

function translateUserStatus($status)
{
    $statuses = [
        'pending' => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ถูกปฏิเสธ'
    ];
    return $statuses[$status] ?? $status;
}

function translateEquipmentStatus($status)
{
    $statuses = [
        'available' => 'พร้อมใช้งาน',
        'repair' => 'ส่งซ่อม',
        'broken' => 'ซ่อมไม่ได้',
        'disposed' => 'จำหน่ายออก',
        'pending_disposal' => 'รอจำหน่ายออก'
    ];
    return $statuses[$status] ?? $status;
}

function translateRepairStatus($status)
{
    $statuses = [
        'pending' => 'รอดำเนินการ',
        'in_progress' => 'กำลังซ่อม',
        'completed' => 'ซ่อมเสร็จ',
        'cannot_fix' => 'ซ่อมไม่ได้'
    ];
    return $statuses[$status] ?? $status;
}

// ============================================
// Badge / Status Classes
// ============================================

function getStatusBadgeClass($status)
{
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'available' => 'success',
        'repair' => 'warning',
        'broken' => 'danger',
        'disposed' => 'secondary',
        'pending_disposal' => 'info',
        'in_progress' => 'primary',
        'completed' => 'success',
        'cannot_fix' => 'danger'
    ];
    return $classes[$status] ?? 'secondary';
}

// ============================================
// Date Formatting (Thai)
// ============================================

function formatDateThai($date)
{
    if (!$date) return '-';

    $timestamp = strtotime($date);
    $thaiMonths = [
        1 => 'ม.ค.', 2 => 'ก.พ.', 3 => 'มี.ค.', 4 => 'เม.ย.',
        5 => 'พ.ค.', 6 => 'มิ.ย.', 7 => 'ก.ค.', 8 => 'ส.ค.',
        9 => 'ก.ย.', 10 => 'ต.ค.', 11 => 'พ.ย.', 12 => 'ธ.ค.'
    ];

    $day = date('j', $timestamp);
    $month = $thaiMonths[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;

    return "{$day} {$month} {$year}";
}

function formatDateTimeThai($datetime)
{
    if (!$datetime) return '-';

    $date = formatDateThai($datetime);
    $time = date('H:i', strtotime($datetime));

    return "{$date} {$time} น.";
}

// ============================================
// Currency Formatting
// ============================================

function formatCurrency($amount)
{
    return number_format($amount, 2) . ' บาท';
}

// ============================================
// System Log
// ============================================

function logActivity($userId, $action, $details = null)
{
    SystemLog::log($userId, $action, $details);
}

// ============================================
// Pagination
// ============================================

function paginate($totalItems, $currentPage, $perPage = 10)
{
    return Model::paginate($totalItems, $currentPage, $perPage);
}

function paginationLinks($pagination, $baseUrl)
{
    if ($pagination['total_pages'] <= 1) return '';

    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    $range = 2;

    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';

    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1" title="หน้าแรก"><i class="bi bi-chevron-double-left"></i></a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($current - 1) . '">ก่อนหน้า</a></li>';
    }

    $start = max(1, $current - $range);
    $end = min($total, $current + $range);

    if ($current <= $range) {
        $end = min($total, $range * 2 + 1);
    }
    if ($current > $total - $range) {
        $start = max(1, $total - $range * 2);
    }

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $total . '">' . $total . '</a></li>';
    }

    if ($current < $total) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($current + 1) . '">ถัดไป</a></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $total . '" title="หน้าสุดท้าย"><i class="bi bi-chevron-double-right"></i></a></li>';
    }

    $html .= '</ul></nav>';
    $html .= '<div class="text-center text-muted small mt-2">หน้า ' . $current . ' จาก ' . $total . ' (ทั้งหมด ' . number_format($pagination['total_items']) . ' รายการ)</div>';

    return $html;
}

// ============================================
// File Upload (Secure)
// ============================================

function uploadImage($file, $folder = 'equipment')
{
    $maxSize = 5 * 1024 * 1024; // 5MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'เกิดข้อผิดพลาดในการอัปโหลด (code: ' . $file['error'] . ')'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'ขนาดไฟล์เกิน 5MB'];
    }

    // Verify real content via finfo (not client-sent MIME)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);

    $allowedMimes = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!isset($allowedMimes[$realMime])) {
        return ['success' => false, 'error' => 'รูปแบบไฟล์ไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP)'];
    }

    // Verify image is actually valid
    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'error' => 'ไฟล์ไม่ใช่รูปภาพที่ถูกต้อง'];
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // Random filename with correct extension (no original filename)
    $ext = $allowedMimes[$realMime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $folder . '/' . $filename];
    }

    return ['success' => false, 'error' => 'ไม่สามารถอัปโหลดไฟล์ได้'];
}

// ============================================
// Error Pages
// ============================================

function page404() { ErrorHandler::page404(); }
function page403() { ErrorHandler::page403(); }
