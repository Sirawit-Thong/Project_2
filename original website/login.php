<?php
/**
 * Login Page
 * หน้าเข้าสู่ระบบ
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// If already logged in, redirect to dashboard
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
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        $result = loginUser($email, $password);

        if ($result['success']) {
            setFlash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $_SESSION['user_name']);

            $role = $_SESSION['user_role'];
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
            $error = $result['error'];
        }
    }
}

// Get flash message
$flash = getFlash();
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - <?= SITE_NAME ?></title>
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="<?= SITE_URL ?>/assets/Logo.svg">

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Google Fonts - Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="<?= SITE_URL ?>/assets/css/style.css?v=2" rel="stylesheet">
</head>

<body>
    <div class="login-container">
        <div class="login-card">
            <div class="logo">
                <i class="bi bi-tools"></i>
            </div>
            <h2><?= SITE_NAME ?></h2>

            <?php if ($flash): ?>
                <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show" role="alert">
                    <?= $flash['message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="bi bi-person me-1"></i>บัญชีผู้ใช้ / อีเมล
                    </label>
                    <input type="text" class="form-control" id="email" name="email"
                        placeholder="ระบุชื่อบัญชีผู้ใช้ หรืออีเมล" required
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="form-text">นักศึกษาสามารถใช้รหัสนักศึกษาเข้าใช้งานได้</div>
                </div>

                <div class="mb-4">
                    <label for="password" class="form-label">
                        <i class="bi bi-lock me-1"></i>รหัสผ่าน
                    </label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="ระบุรหัสผ่าน"
                        required>
                </div>

                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
                </button>
            </form>

            <hr>

            <div class="text-center">
                <p class="mb-2">หากยังไม่มีบัญชีผู้ใช้งาน</p>
                <a href="register_student.php" class="btn btn-outline-primary me-2">
                    <i class="bi bi-mortarboard me-1"></i>สำหรับนักศึกษา
                </a>
                <a href="register_teacher.php" class="btn btn-outline-secondary">
                    <i class="bi bi-person-workspace me-1"></i>สำหรับอาจารย์
                </a>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>