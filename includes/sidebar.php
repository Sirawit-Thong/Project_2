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
    'admin'   => ['/', '/departments', '/sets', '/items', '/rooms', '/equipment', '/equipment/disposal', '/equipment/inspection', '/repairs', '/users/pending', '/users', '/room-managers', '/reports', '/backup', '/logs'],
    'staff'   => ['/', '/departments', '/sets', '/items', '/rooms', '/equipment', '/equipment/disposal', '/equipment/inspection', '/repairs', '/users/pending'],
    'teacher' => ['/', '/repairs/submit', '/repairs/mine', '/equipment', '/teacher/report'],
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
?>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <!-- Admin/Staff Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= isSidebarActive('/') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">จัดการข้อมูลพื้นฐาน</span>
                <a href="<?= SITE_URL ?>/departments" class="nav-link <?= isSidebarActive('/departments') ?>">
                    <i class="bi bi-building"></i>
                    <span>สาขาวิชา</span>
                </a>
                <a href="<?= SITE_URL ?>/sets" class="nav-link <?= isSidebarActive('/sets') ?>">
                    <i class="bi bi-collection"></i>
                    <span>ชุดครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/items" class="nav-link <?= isSidebarActive('/items') ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>รายการครุภัณฑ์ทั้งหมด</span>
                </a>
                <a href="<?= SITE_URL ?>/rooms" class="nav-link <?= isSidebarActive('/rooms') ?>">
                    <i class="bi bi-door-open"></i>
                    <span>ห้อง/สถานที่</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์</span>
                <a href="<?= SITE_URL ?>/equipment" class="nav-link <?= isSidebarActive('/equipment') ?>">
                    <i class="bi bi-pc-display"></i>
                    <span>ทะเบียนครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/disposal" class="nav-link <?= isSidebarActive('/equipment/disposal') ?>">
                    <i class="bi bi-trash3"></i>
                    <span>รายการจำหน่าย/แทงจำหน่าย</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/inspection" class="nav-link <?= isSidebarActive('/equipment/inspection') ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>การตรวจนับครุภัณฑ์ประจำปี</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ซ่อมบำรุง</span>
                <a href="<?= SITE_URL ?>/repairs" class="nav-link <?= isSidebarActive('/repairs') ?>">
                    <i class="bi bi-wrench-adjustable"></i>
                    <span>รายการแจ้งซ่อมทั้งหมด</span>
                    <?php $pendingRepairCount = Repair::pendingCount(); ?>
                    <?php if ($pendingRepairCount > 0): ?>
                        <span class="badge bg-warning text-dark"><?= $pendingRepairCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ผู้ใช้งาน</span>
                <a href="<?= SITE_URL ?>/users/pending" class="nav-link <?= isSidebarActive('/users/pending') ?>">
                    <i class="bi bi-person-check"></i>
                    <span>ผู้ใช้งานรออนุมัติ</span>
                    <?php $pendingUserCount = User::pendingCount(); ?>
                    <?php if ($pendingUserCount > 0): ?>
                        <span class="badge bg-danger"><?= $pendingUserCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if ($role === 'admin'): ?>
                <div class="nav-section">
                    <span class="nav-section-title">จัดการผู้ใช้</span>
                    <a href="<?= SITE_URL ?>/users" class="nav-link <?= isSidebarActive('/users') ?>">
                        <i class="bi bi-people"></i>
                        <span>จัดการบัญชีผู้ใช้งาน</span>
                    </a>
                    <a href="<?= SITE_URL ?>/room-managers" class="nav-link <?= isSidebarActive('/room-managers') ?>">
                        <i class="bi bi-person-badge"></i>
                        <span>กำหนดผู้ดูแลห้อง</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">รายงาน</span>
                    <a href="<?= SITE_URL ?>/reports" class="nav-link <?= isSidebarActive('/reports') ?>">
                        <i class="bi bi-graph-up"></i>
                        <span>รายงานและสถิติ</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">ระบบ</span>
                    <a href="<?= SITE_URL ?>/backup" class="nav-link <?= isSidebarActive('/backup') ?>">
                        <i class="bi bi-database-down"></i>
                        <span>สำรองข้อมูล</span>
                    </a>
                    <a href="<?= SITE_URL ?>/logs" class="nav-link <?= isSidebarActive('/logs') ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>ประวัติการใช้งานระบบ</span>
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif ($role === 'teacher'): ?>
            <!-- Teacher Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= isSidebarActive('/') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/submit" class="nav-link <?= isSidebarActive('/repairs/submit') ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/mine" class="nav-link <?= isSidebarActive('/repairs/mine') ?>">
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์ในการดูแล</span>
                <a href="<?= SITE_URL ?>/equipment/my" class="nav-link <?= isSidebarActive('/equipment') ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>ตรวจสอบครุภัณฑ์ที่รับผิดชอบ</span>
                </a>
                <a href="<?= SITE_URL ?>/teacher/report" class="nav-link <?= isSidebarActive('/teacher/report') ?>">
                    <i class="bi bi-bar-chart"></i>
                    <span>รายงานสรุป</span>
                </a>
            </div>

        <?php elseif ($role === 'student'): ?>
            <!-- Student Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= isSidebarActive('/') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/submit" class="nav-link <?= isSidebarActive('/repairs/submit') ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/mine" class="nav-link <?= isSidebarActive('/repairs/mine') ?>">
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>
