<?php
/**
 * Common Functions
 * ฟังก์ชันทั่วไปสำหรับใช้งานในระบบ
 */

/**
 * Redirect ไปยัง URL ที่กำหนด
 */
function redirect($url)
{
    header("Location: $url");
    exit;
}

/**
 * ตั้งค่า Flash Message
 */
function setFlash($type, $message)
{
    $_SESSION['flash'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * แสดง Flash Message
 */
function getFlash()
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Sanitize ข้อมูล input
 */
function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * แปลง Role เป็นภาษาไทย
 */
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

/**
 * แปลงสถานะผู้ใช้เป็นภาษาไทย
 */
function translateUserStatus($status)
{
    $statuses = [
        'pending' => 'รออนุมัติ',
        'approved' => 'อนุมัติแล้ว',
        'rejected' => 'ถูกปฏิเสธ'
    ];
    return $statuses[$status] ?? $status;
}

/**
 * แปลงสถานะครุภัณฑ์เป็นภาษาไทย
 */
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

/**
 * แปลงสถานะการซ่อมเป็นภาษาไทย
 */
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

/**
 * รับ CSS class สำหรับ badge สถานะ
 */
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

/**
 * Format วันที่เป็นภาษาไทย
 */
function formatDateThai($date)
{
    if (!$date)
        return '-';
    $timestamp = strtotime($date);
    $thaiMonths = [
        1 => 'ม.ค.',
        2 => 'ก.พ.',
        3 => 'มี.ค.',
        4 => 'เม.ย.',
        5 => 'พ.ค.',
        6 => 'มิ.ย.',
        7 => 'ก.ค.',
        8 => 'ส.ค.',
        9 => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.'
    ];
    $day = date('j', $timestamp);
    $month = $thaiMonths[(int) date('n', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    return "$day $month $year";
}

/**
 * Format วันที่และเวลาเป็นภาษาไทย
 */
function formatDateTimeThai($datetime)
{
    if (!$datetime)
        return '-';
    $date = formatDateThai($datetime);
    $time = date('H:i', strtotime($datetime));
    return "$date $time น.";
}

/**
 * Format ตัวเลขเป็นสกุลเงินบาท
 */
function formatCurrency($amount)
{
    return number_format($amount, 2) . ' บาท';
}

/**
 * บันทึก System Log
 */
function logActivity($pdo, $userId, $action, $details = null)
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
    $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $action, $details, $ip]);
}

/**
 * สร้าง pagination
 */
function paginate($totalItems, $currentPage, $perPage = 10)
{
    $totalPages = ceil($totalItems / $perPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $perPage;

    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'per_page' => $perPage,
        'offset' => $offset
    ];
}

/**
 * แสดง pagination links
 */
function paginationLinks($pagination, $baseUrl)
{
    if ($pagination['total_pages'] <= 1)
        return '';

    $current = $pagination['current_page'];
    $total = $pagination['total_pages'];
    $range = 2; // แสดงกี่หน้าข้างๆ หน้าปัจจุบัน

    $html = '<nav><ul class="pagination justify-content-center flex-wrap">';

    // First page
    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1" title="หน้าแรก"><i class="bi bi-chevron-double-left"></i></a></li>';
    }

    // Previous
    if ($current > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($current - 1) . '">ก่อนหน้า</a></li>';
    }

    // Calculate start and end page
    $start = max(1, $current - $range);
    $end = min($total, $current + $range);

    // Adjust if at the beginning or end
    if ($current <= $range) {
        $end = min($total, $range * 2 + 1);
    }
    if ($current > $total - $range) {
        $start = max(1, $total - $range * 2);
    }

    // Ellipsis at start
    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=1">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Page numbers
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '"><a class="page-link" href="' . $baseUrl . '&page=' . $i . '">' . $i . '</a></li>';
    }

    // Ellipsis at end
    if ($end < $total) {
        if ($end < $total - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $total . '">' . $total . '</a></li>';
    }

    // Next
    if ($current < $total) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . ($current + 1) . '">ถัดไป</a></li>';
    }

    // Last page
    if ($current < $total) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '&page=' . $total . '" title="หน้าสุดท้าย"><i class="bi bi-chevron-double-right"></i></a></li>';
    }

    $html .= '</ul></nav>';

    // Page info
    $html .= '<div class="text-center text-muted small mt-2">หน้า ' . $current . ' จาก ' . $total . ' (ทั้งหมด ' . number_format($pagination['total_items']) . ' รายการ)</div>';

    return $html;
}

/**
 * อัปโหลดไฟล์รูปภาพ
 */
function uploadImage($file, $folder = 'equipment')
{
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024; // 5MB

    if (!in_array($file['type'], $allowedTypes)) {
        return ['success' => false, 'error' => 'รูปแบบไฟล์ไม่ถูกต้อง (รองรับ JPG, PNG, GIF, WEBP)'];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'error' => 'ขนาดไฟล์เกิน 5MB'];
    }

    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename, 'path' => $folder . '/' . $filename];
    }

    return ['success' => false, 'error' => 'ไม่สามารถอัปโหลดไฟล์ได้'];
}
