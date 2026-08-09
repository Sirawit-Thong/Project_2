<?php
/**
 * Sidebar Navigation
 * เมนูนำทางด้านซ้าย — แยกตาม role
 */
$role = getCurrentRole();
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestUri = rtrim($requestUri, '/') ?: '/';
?>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <?php if ($role === 'admin'): ?>
            <div class="nav-section">
                <span class="nav-section-title">ภาพรวม</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= $requestUri === '/' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>แดชบอร์ด</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์</span>
                <a href="<?= SITE_URL ?>/equipment" class="nav-link <?= $requestUri === '/equipment' ? 'active' : '' ?>">
                    <i class="bi bi-pc-display"></i><span>รายการครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/add" class="nav-link <?= $requestUri === '/equipment/add' ? 'active' : '' ?>">
                    <i class="bi bi-plus-circle"></i><span>เพิ่ม/แก้ไขครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/bulk-add" class="nav-link <?= $requestUri === '/equipment/bulk-add' ? 'active' : '' ?>">
                    <i class="bi bi-plus-square"></i><span>เพิ่มครุภัณฑ์จำนวนมาก</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/inspection" class="nav-link <?= $requestUri === '/equipment/inspection' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i><span>ตรวจนับประจำปี</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/disposal" class="nav-link <?= $requestUri === '/equipment/disposal' ? 'active' : '' ?>">
                    <i class="bi bi-trash3"></i><span>จำหน่ายครุภัณฑ์</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ฝ่ายซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs" class="nav-link <?= $requestUri === '/repairs' ? 'active' : '' ?>">
                    <i class="bi bi-tools"></i><span>รายการซ่อม</span>
                </a>
                <a href="<?= SITE_URL ?>/users/pending" class="nav-link <?= $requestUri === '/users/pending' ? 'active' : '' ?>">
                    <i class="bi bi-person-check"></i><span>รออนุมัติผู้ใช้</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">จัดการข้อมูล</span>
                <a href="<?= SITE_URL ?>/departments" class="nav-link <?= $requestUri === '/departments' ? 'active' : '' ?>">
                    <i class="bi bi-building"></i><span>สาขาวิชา</span>
                </a>
                <a href="<?= SITE_URL ?>/sets" class="nav-link <?= $requestUri === '/sets' ? 'active' : '' ?>">
                    <i class="bi bi-collection"></i><span>ชุดครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/items" class="nav-link <?= $requestUri === '/items' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i><span>รายการครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/rooms" class="nav-link <?= $requestUri === '/rooms' ? 'active' : '' ?>">
                    <i class="bi bi-door-open"></i><span>ห้อง/สถานที่</span>
                </a>
                <a href="<?= SITE_URL ?>/room-managers" class="nav-link <?= $requestUri === '/room-managers' ? 'active' : '' ?>">
                    <i class="bi bi-person-badge"></i><span>ผู้รับผิดชอบห้อง</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ระบบ</span>
                <a href="<?= SITE_URL ?>/users" class="nav-link <?= in_array($requestUri, ['/users', '/users/add']) ? 'active' : '' ?>">
                    <i class="bi bi-people"></i><span>จัดการผู้ใช้</span>
                </a>
                <a href="<?= SITE_URL ?>/logs" class="nav-link <?= $requestUri === '/logs' ? 'active' : '' ?>">
                    <i class="bi bi-journal-text"></i><span>บันทึกระบบ</span>
                </a>
                <a href="<?= SITE_URL ?>/backup" class="nav-link <?= $requestUri === '/backup' ? 'active' : '' ?>">
                    <i class="bi bi-download"></i><span>สำรองข้อมูล</span>
                </a>
            </div>

        <?php elseif ($role === 'staff'): ?>
            <div class="nav-section">
                <span class="nav-section-title">ภาพรวม</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= $requestUri === '/' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>แดชบอร์ด</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์</span>
                <a href="<?= SITE_URL ?>/equipment" class="nav-link <?= $requestUri === '/equipment' ? 'active' : '' ?>">
                    <i class="bi bi-pc-display"></i><span>รายการครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/add" class="nav-link <?= $requestUri === '/equipment/add' ? 'active' : '' ?>">
                    <i class="bi bi-plus-circle"></i><span>เพิ่ม/แก้ไขครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/bulk-add" class="nav-link <?= $requestUri === '/equipment/bulk-add' ? 'active' : '' ?>">
                    <i class="bi bi-plus-square"></i><span>เพิ่มครุภัณฑ์จำนวนมาก</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/inspection" class="nav-link <?= $requestUri === '/equipment/inspection' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i><span>ตรวจนับประจำปี</span>
                </a>
                <a href="<?= SITE_URL ?>/equipment/disposal" class="nav-link <?= $requestUri === '/equipment/disposal' ? 'active' : '' ?>">
                    <i class="bi bi-trash3"></i><span>จำหน่ายครุภัณฑ์</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ฝ่ายซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs" class="nav-link <?= $requestUri === '/repairs' ? 'active' : '' ?>">
                    <i class="bi bi-tools"></i><span>รายการซ่อม</span>
                </a>
                <a href="<?= SITE_URL ?>/users/pending" class="nav-link <?= $requestUri === '/users/pending' ? 'active' : '' ?>">
                    <i class="bi bi-person-check"></i><span>รออนุมัติผู้ใช้</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">จัดการข้อมูล</span>
                <a href="<?= SITE_URL ?>/departments" class="nav-link <?= $requestUri === '/departments' ? 'active' : '' ?>">
                    <i class="bi bi-building"></i><span>สาขาวิชา</span>
                </a>
                <a href="<?= SITE_URL ?>/sets" class="nav-link <?= $requestUri === '/sets' ? 'active' : '' ?>">
                    <i class="bi bi-collection"></i><span>ชุดครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/items" class="nav-link <?= $requestUri === '/items' ? 'active' : '' ?>">
                    <i class="bi bi-box-seam"></i><span>รายการครุภัณฑ์</span>
                </a>
                <a href="<?= SITE_URL ?>/rooms" class="nav-link <?= $requestUri === '/rooms' ? 'active' : '' ?>">
                    <i class="bi bi-door-open"></i><span>ห้อง/สถานที่</span>
                </a>
                <a href="<?= SITE_URL ?>/room-managers" class="nav-link <?= $requestUri === '/room-managers' ? 'active' : '' ?>">
                    <i class="bi bi-person-badge"></i><span>ผู้รับผิดชอบห้อง</span>
                </a>
            </div>

        <?php elseif ($role === 'teacher'): ?>
            <div class="nav-section">
                <span class="nav-section-title">ภาพรวม</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= $requestUri === '/' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>แดชบอร์ด</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">ครุภัณฑ์ของฉัน</span>
                <a href="<?= SITE_URL ?>/equipment" class="nav-link <?= $requestUri === '/equipment' ? 'active' : '' ?>">
                    <i class="bi bi-clipboard-check"></i><span>ตรวจสอบครุภัณฑ์</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/mine" class="nav-link <?= $requestUri === '/repairs/mine' ? 'active' : '' ?>">
                    <i class="bi bi-list-check"></i><span>รายการซ่อมของฉัน</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/submit" class="nav-link <?= $requestUri === '/repairs/submit' ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-circle"></i><span>แจ้งซ่อมใหม่</span>
                </a>
            </div>

        <?php elseif ($role === 'student'): ?>
            <div class="nav-section">
                <span class="nav-section-title">ภาพรวม</span>
                <a href="<?= SITE_URL ?>/" class="nav-link <?= $requestUri === '/' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span>แดชบอร์ด</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">แจ้งซ่อม</span>
                <a href="<?= SITE_URL ?>/repairs/mine" class="nav-link <?= $requestUri === '/repairs/mine' ? 'active' : '' ?>">
                    <i class="bi bi-list-check"></i><span>รายการซ่อมของฉัน</span>
                </a>
                <a href="<?= SITE_URL ?>/repairs/submit" class="nav-link <?= $requestUri === '/repairs/submit' ? 'active' : '' ?>">
                    <i class="bi bi-exclamation-circle"></i><span>แจ้งซ่อมใหม่</span>
                </a>
            </div>
        <?php endif; ?>
    </nav>
</aside>
