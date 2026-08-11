<?php
/**
 * Sidebar Navigation
 * เมนูด้านข้างตาม Role
 */

$currentPath = $_SERVER['REQUEST_URI'];

function isActive($path)
{
    global $currentPath;
    return strpos($currentPath, $path) !== false ? 'active' : '';
}
?>

<aside class="sidebar" id="sidebar">

    <nav class="sidebar-nav">
        <?php if (hasRole(['admin', 'staff'])): ?>
            <!-- Admin/Staff Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <?php if (hasRole('admin')): ?>
                    <a href="<?= SITE_URL ?>/admin/index.php" class="nav-link <?= isActive('/admin/index.php') ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>ภาพรวมระบบ</span>
                    </a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/staff/index.php" class="nav-link <?= isActive('/staff/index.php') ?>">
                        <i class="bi bi-speedometer2"></i>
                        <span>ภาพรวมระบบ</span>
                    </a>
                <?php endif; ?>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">จัดการข้อมูลพื้นฐาน</span>
                <a href="<?= SITE_URL ?>/admin/departments.php" class="nav-link <?= isActive('/admin/departments.php') ?>">
                    <i class="bi bi-building"></i>
                    <span>สาขาวิชา</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/sets.php" class="nav-link <?= isActive('/admin/sets.php') ?>">
                    <i class="bi bi-collection"></i>
                    <span>ชุดครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/items.php" class="nav-link <?= isActive('/admin/items.php') ?>">
                    <i class="bi bi-box-seam"></i>
                    <span>รายการครุภัณฑ์ทั้งหมด</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/rooms.php" class="nav-link <?= isActive('/admin/rooms.php') ?>">
                    <i class="bi bi-door-open"></i>
                    <span>ห้อง/สถานที่</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์</span>
                <a href="<?= SITE_URL ?>/admin/equipment.php" class="nav-link <?= isActive('/admin/equipment.php') ?>">
                    <i class="bi bi-pc-display"></i>
                    <span>ทะเบียนครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/equipment_disposal.php"
                    class="nav-link <?= isActive('/admin/equipment_disposal.php') ?>">
                    <i class="bi bi-trash3"></i>
                    <span>รายการจำหน่าย/แทงจำหน่าย</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/inspection.php" class="nav-link <?= isActive('/admin/inspection.php') ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>การตรวจนับครุภัณฑ์ประจำปี</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ซ่อมบำรุง</span>
                <a href="<?= SITE_URL ?>/admin/repairs.php" class="nav-link <?= isActive('/admin/repairs.php') ?>">
                    <i class="bi bi-wrench-adjustable"></i>
                    <span>รายการแจ้งซ่อมทั้งหมด</span>
                    <?php
                    $pdo = getDB();
                    $pendingRepairCount = $pdo->query("SELECT COUNT(*) FROM repair WHERE status = 'pending'")->fetchColumn();
                    if ($pendingRepairCount > 0):
                        ?>
                        <span class="badge bg-warning text-dark"><?= $pendingRepairCount ?></span>
                    <?php endif; ?>
                </a>
            </div>

            <?php if (hasRole(['admin', 'staff'])): ?>
                <div class="nav-section">
                    <span class="nav-section-title">ผู้ใช้งาน</span>
                    <a href="<?= SITE_URL ?>/admin/pending_users.php"
                        class="nav-link <?= isActive('/admin/pending_users.php') ?>">
                        <i class="bi bi-person-check"></i>
                        <span>ผู้ใช้งานรออนุมัติ</span>
                        <?php
                        $pdo = getDB();
                        $pendingCount = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
                        if ($pendingCount > 0):
                            ?>
                            <span class="badge bg-danger"><?= $pendingCount ?></span>
                        <?php endif; ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (hasRole('admin')): ?>
                <div class="nav-section">
                    <span class="nav-section-title">จัดการผู้ใช้</span>
                    <a href="<?= SITE_URL ?>/admin/users.php" class="nav-link <?= isActive('/admin/users.php') ?>">
                        <i class="bi bi-people"></i>
                        <span>จัดการบัญชีผู้ใช้งาน</span>
                    </a>
                    <a href="<?= SITE_URL ?>/admin/room_managers.php"
                        class="nav-link <?= isActive('/admin/room_managers.php') ?>">
                        <i class="bi bi-person-badge"></i>
                        <span>กำหนดผู้ดูแลห้อง</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">รายงาน</span>
                    <a href="<?= SITE_URL ?>/admin/reports/index.php" class="nav-link <?= isActive('/admin/reports') ?>">
                        <i class="bi bi-graph-up"></i>
                        <span>รายงานและสถิติ</span>
                    </a>
                </div>

                <div class="nav-section">
                    <span class="nav-section-title">ระบบ</span>
                    <a href="<?= SITE_URL ?>/admin/backup.php" class="nav-link <?= isActive('/admin/backup.php') ?>">
                        <i class="bi bi-database-down"></i>
                        <span>สำรองข้อมูล</span>
                    </a>
                    <a href="<?= SITE_URL ?>/admin/logs.php" class="nav-link <?= isActive('/admin/logs.php') ?>">
                        <i class="bi bi-journal-text"></i>
                        <span>ประวัติการใช้งานระบบ</span>
                    </a>
                </div>
            <?php endif; ?>

        <?php elseif (hasRole('teacher')): ?>
            <!-- Teacher Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/teacher/index.php" class="nav-link <?= isActive('/teacher/index.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/teacher/repair_submit.php"
                    class="nav-link <?= isActive('/teacher/repair_submit.php') ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/teacher/my_repairs.php"
                    class="nav-link <?= isActive('/teacher/my_repairs.php') ?>">
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์ในการดูแล</span>
                <a href="<?= SITE_URL ?>/teacher/my_equipment.php"
                    class="nav-link <?= isActive('/teacher/my_equipment.php') ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>ตรวจสอบครุภัณฑ์ที่รับผิดชอบ</span>
                </a>
                <a href="<?= SITE_URL ?>/teacher/report.php" class="nav-link <?= isActive('/teacher/report.php') ?>">
                    <i class="bi bi-bar-chart"></i>
                    <span>รายงานสรุปตามห้อง</span>
                </a>
            </div>

        <?php elseif (hasRole('student')): ?>
            <!-- Student Menu -->
            <div class="nav-section">
                <span class="nav-section-title">แดชบอร์ด</span>
                <a href="<?= SITE_URL ?>/student/index.php" class="nav-link <?= isActive('/student/index.php') ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>ภาพรวมระบบ</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/student/repair_submit.php"
                    class="nav-link <?= isActive('/student/repair_submit.php') ?>">
                    <i class="bi bi-plus-circle"></i>
                    <span>แจ้งซ่อมครุภัณฑ์ใหม่</span>
                </a>
                <a href="<?= SITE_URL ?>/student/my_repairs.php"
                    class="nav-link <?= isActive('/student/my_repairs.php') ?>">
                    <i class="bi bi-list-check"></i>
                    <span>ติดตามสถานะการแจ้งซ่อม</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>