<?php
/**
 * Sidebar Navigation
 * เมนูนำทางด้านข้าง — แยกตาม role (ตรงกับโครงสร้างเว็บเดิม)
 */
$role = getCurrentRole();
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// ตัด base path (เช่น /P) ออกเพื่อให้ path ตรงกับ route ที่ router ใช้
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
$requestUri = rtrim($requestUri, '/') ?: '/';

// path ทั้งหมดที่เมนูของแต่ละ role ใช้ตรวจ active
$roleLinks = [
    'admin'   => ['/', '/departments', '/sets', '/items', '/rooms', '/equipment', '/equipment/disposal', '/equipment/inspection', '/repairs', '/depreciation', '/depreciation/report', '/users/pending', '/users', '/room-managers', '/reports', '/backup', '/logs'],
    'staff'   => ['/', '/departments', '/sets', '/items', '/rooms', '/equipment', '/equipment/disposal', '/equipment/inspection', '/repairs', '/depreciation', '/depreciation/report', '/users/pending'],
    'teacher' => ['/', '/repairs/submit', '/repairs/mine', '/equipment', '/teacher/report', '/depreciation/my'],
    'student' => ['/', '/repairs/submit', '/repairs/mine'],
];
$roleLinks = $roleLinks[$role] ?? [];

// เลือก path ที่แมท URL ปัจจุบันแล้วยาวที่สุด (เจาะจงสุด) เป็น active
// แมทแบบ segment เท่านั้น เช่น /equipment แมท /equipment/5 แต่ไม่แมท /equipmentx
// เก็บใน $GLOBALS เพราะ sidebar ถูก include ใน function scope (controller method)
$activePath = null;
$activeLen = -1;
foreach ($roleLinks as $link) {
    $isMatch = $link === '/'
        ? $requestUri === '/'
        : ($requestUri === $link || strpos($requestUri, $link . '/') === 0);
    if ($isMatch && strlen($link) > $activeLen) {
        $activePath = $link;
        $activeLen = strlen($link);
    }
}
$GLOBALS['sidebar_active_path'] = $activePath;

function isSidebarActive($path)
{
    return $path === ($GLOBALS['sidebar_active_path'] ?? null) ? 'active' : '';
}

/**
 * คืนค่า attribute ของลิงก์เมนู (class + aria-current) — ใส่ aria-current="page"
 * ให้ screen reader ทราบว่ากำลังอยู่หน้าไหน
 */
function sidebarNavLink($path)
{
    return isSidebarActive($path)
        ? 'class="nav-link active" aria-current="page"'
        : 'class="nav-link"';
}

// ตัวเลข badge แสดงบนเมนู — แคชใน session 60 วินาที (กัน query DB ทุกครั้งที่โหลดหน้า)
$pendingRepairCount = 0;
$pendingUserCount = 0;
if (in_array($role, ['admin', 'staff'], true)) {
    $badgeCache = $_SESSION['badge_counts'] ?? [];
    if (empty($badgeCache) || time() - (int) ($badgeCache['time'] ?? 0) > 60) {
        $badgeCache = [
            'pending_repairs' => Repair::pendingCount(),
            'pending_users'   => User::pendingCount(),
            'time'            => time(),
        ];
        $_SESSION['badge_counts'] = $badgeCache;
    }
    $pendingRepairCount = (int) ($badgeCache['pending_repairs'] ?? 0);
    $pendingUserCount = (int) ($badgeCache['pending_users'] ?? 0);
}
?>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <!-- Admin/Staff Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" <?= sidebarNavLink('/') ?>>
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">จัดการข้อมูลพื้นฐาน</span>
                <a href="<?= SITE_URL ?>/departments" <?= sidebarNavLink('/departments') ?>>
                    <i class="bi bi-building"></i>
                    <span>สาขาวิชา</span>
                </a>
                <a href="<?= SITE_URL ?>/sets" <?= sidebarNavLink('/sets') ?>>
                    <i class="bi bi-collection"></i>
                    <span>ชุดครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/items" <?= sidebarNavLink('/items') ?>>
                    <i class="bi bi-box-seam"></i>
                    <span>รายการครุภัณฑ์ทั้งหมด</span>
                </a>
                <a href="<?= SITE_URL ?>/rooms" <?= sidebarNavLink('/rooms') ?>>
                    <i class="bi bi-door-open"></i>
                    <span>ห้อง/สถานที่</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์</span>
                <a href="<?= SITE_URL ?>/equipment" <?= sidebarNavLink('/equipment') ?>>
                    <i class="bi bi-pc-display"></i>
                    <span>ทะเบียนครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/disposal" <?= sidebarNavLink('/equipment/disposal') ?>>
                    <i class="bi bi-trash3"></i>
                    <span>รายการจำหน่าย/แทงจำหน่าย</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/inspection" <?= sidebarNavLink('/equipment/inspection') ?>>
                    <i class="bi bi-clipboard-check"></i>
                    <span>การตรวจนับครุภัณฑ์ประจำปี</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ซ่อมบำรุง</span>
                <a href="<?= SITE_URL ?>/repairs" <?= sidebarNavLink('/repairs') ?>>
                    <i class="bi bi-wrench-adjustable"></i>
                    <span>รายการแจ้งซ่อมทั้งหมด</span>
                    <?php if ($pendingRepairCount > 0): ?>
                        <span class="badge bg-warning text-dark"><?= $pendingRepairCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ค่าเสื่อมราคา</span>
                <a href="<?= SITE_URL ?>/depreciation" <?= sidebarNavLink('/depreciation') ?>>
                    <i class="bi bi-calculator"></i>
                    <span>คำนวณค่าเสื่อมราคา</span>
                </a>
                <a href="<?= SITE_URL ?>/depreciation/report" <?= sidebarNavLink('/depreciation/report') ?>>
                    <i class="bi bi-graph-down"></i>
                    <span>รายงานค่าเสื่อมราคา</span>
                </a>
                <?php if ($role === 'admin'): ?>
                    <a href="<?= SITE_URL ?>/depreciation/settings" <?= sidebarNavLink('/depreciation/settings') ?>>
                        <i class="bi bi-sliders"></i>
                        <span>ตั้งค่าเกณฑ์ค่าเสื่อมราคา</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ผู้ใช้งาน</span>
                <a href="<?= SITE_URL ?>/users/pending" <?= sidebarNavLink('/users/pending') ?>>
                    <i class="bi bi-person-check"></i>
                    <span>ผู้ใช้งานรออนุมัติ</span>
                    <?php if ($pendingUserCount > 0): ?>
                        <span class="badge bg-danger"><?= $pendingUserCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if ($role === 'admin'): ?>
                <div class="nav-section">
                    <span class="nav-section-title">จัดการผู้ใช้</span>
                    <a href="<?= SITE_URL ?>/users" <?= sidebarNavLink('/users') ?>>
                        <i class="bi bi-people"></i>
                        <span>จัดการบัญชีผู้ใช้งาน</span>
                    </a>
                    <a href="<?= SITE_URL ?>/room-managers" <?= sidebarNavLink('/room-managers') ?>>
                        <i class="bi bi-person-badge"></i>
                        <span>กำหนดผู้ดูแลห้อง</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">รายงาน</span>
                    <a href="<?= SITE_URL ?>/reports" <?= sidebarNavLink('/reports') ?>>
                        <i class="bi bi-graph-up"></i>
                        <span>รายงานและสถิติ</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">ระบบ</span>
                    <a href="<?= SITE_URL ?>/backup" <?= sidebarNavLink('/backup') ?>>
                        <i class="bi bi-database-down"></i>
                        <span>สำรองข้อมูล</span>
                    </a>
                    <a href="<?= SITE_URL ?>/logs" <?= sidebarNavLink('/logs') ?>>
                        <i class="bi bi-journal-text"></i>
                        <span>ประวัติการใช้งานระบบ</span>
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($role === 'teacher'): ?>
            <!-- Teacher Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" <?= sidebarNavLink('/') ?>>
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/submit" <?= sidebarNavLink('/repairs/submit') ?>>
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/mine" <?= sidebarNavLink('/repairs/mine') ?>>
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์ในการดูแล</span>
                <a href="<?= SITE_URL ?>/equipment/my" <?= sidebarNavLink('/equipment') ?>>
                    <i class="bi bi-clipboard-check"></i>
                    <span>ตรวจสอบครุภัณฑ์ที่รับผิดชอบ</span>
                </a>
                <a href="<?= SITE_URL ?>/teacher/report" <?= sidebarNavLink('/teacher/report') ?>>
                    <i class="bi bi-bar-chart"></i>
                    <span>รายงานสรุป</span>
                </a>
                <a href="<?= SITE_URL ?>/depreciation/my" <?= sidebarNavLink('/depreciation/my') ?>>
                    <i class="bi bi-calculator"></i>
                    <span>ค่าเสื่อมราคาครุภัณฑ์ที่ดูแล</span>
                </a>
            </div>

        <?php elseif ($role === 'student'): ?>
            <!-- Student Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" <?= sidebarNavLink('/') ?>>
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/submit" <?= sidebarNavLink('/repairs/submit') ?>>
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/mine" <?= sidebarNavLink('/repairs/mine') ?>>
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>
