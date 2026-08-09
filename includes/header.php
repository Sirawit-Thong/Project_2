<?php
/**
 * Header Template
 * ส่วนหัวของหน้าเว็บ
 */

// Start output buffering to allow redirects after HTML output
ob_start();

require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/auth.php';

// Get current user info
$currentUser = getCurrentUser();
$currentRole = getCurrentRole();

// Set page title - append SITE_NAME if title is set
if (isset($pageTitle) && !empty($pageTitle)) {
    $pageTitle = $pageTitle . ' - ' . SITE_NAME;
} else {
    $pageTitle = SITE_NAME;
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/Logo.svg">
    <!-- PWA -->
    <link rel="manifest" href="<?= SITE_URL ?>/manifest.json">
    <meta name="theme-color" content="#0d6efd">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <link rel="apple-touch-icon" href="<?= SITE_URL ?>/assets/Logo.svg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php if (isLoggedIn()): ?>
        <!-- Navbar -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
            <div class="container-fluid">
                <button class="btn btn-link text-white me-2 d-none d-lg-block" id="sidebarToggle">
                    <i class="bi bi-list fs-4"></i>
                </button>
                <a class="navbar-brand" href="<?= SITE_URL ?>">
                    <i class="bi bi-tools me-2"></i><?= SITE_NAME ?>
                </a>

                <!-- Mobile toggle for sidebar -->
                <button class="btn btn-link text-white d-lg-none" id="sidebarToggleMobile">
                    <i class="bi bi-list fs-4"></i>
                </button>

                <!-- User dropdown - always visible, aligned right -->
                <div class="dropdown ms-auto">
                    <a class="nav-link dropdown-toggle text-white d-flex align-items-center" href="#"
                        data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle me-1"></i>
                        <span class="d-none d-sm-inline"><?= e($_SESSION['user_name'] ?? 'ผู้ใช้') ?></span>
                        <span
                            class="badge bg-light text-primary ms-1 d-none d-sm-inline"><?= translateRole($currentRole) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li class="d-sm-none px-3 py-2 text-muted small"><?= e($_SESSION['user_name'] ?? 'ผู้ใช้') ?>
                            (<?= translateRole($currentRole) ?>)</li>
                        <li class="d-sm-none">
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile"><i
                                    class="bi bi-person me-2"></i>ข้อมูลส่วนตัว</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <form method="POST" action="<?= SITE_URL ?>/logout" class="d-inline">
                                <?= csrf_field() ?>
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>

        <div class="wrapper">
            <?php include __DIR__ . '/sidebar.php'; ?>

            <main class="main-content">
                <div class="container-fluid py-4">
                    <?php flashMessage(); ?>
                <?php else: ?>
                    <div class="container-fluid">
                        <?php flashMessage(); ?>
                    <?php endif; ?>