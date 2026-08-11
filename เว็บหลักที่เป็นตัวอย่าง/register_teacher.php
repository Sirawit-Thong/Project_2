<?php
/**
 * Teacher Registration
 * หน้าสมัครสมาชิกสำหรับอาจารย์
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// If already logged in, redirect
if (isLoggedIn()) {
    redirect(SITE_URL . '/teacher/index.php');
}

$errors = [];
$success = false;

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validation
    if (empty($email)) {
        $errors[] = 'กรุณากรอกอีเมล';
    } elseif (!isUniversityEmail($email)) {
        $errors[] = 'กรุณาใช้อีเมลมหาวิทยาลัย (@rmutsb.ac.th) เท่านั้น';
    }

    if (empty($firstname)) {
        $errors[] = 'กรุณากรอกชื่อ';
    }

    if (empty($lastname)) {
        $errors[] = 'กรุณากรอกนามสกุล';
    }

    if (empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    } elseif (strlen($password) < 8) {
        $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
    }

    if ($password !== $password_confirm) {
        $errors[] = 'รหัสผ่านไม่ตรงกัน';
    }

    // If no errors, register user
    if (empty($errors)) {
        $result = registerUser([
            'sid' => null,
            'email' => $email,
            'firstname' => $firstname,
            'lastname' => $lastname,
            'password' => $password,
            'role' => 'teacher',
            'class' => null
        ]);

        if ($result['success']) {
            $success = true;
        } else {
            $errors[] = $result['error'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก (อาจารย์) - <?= SITE_NAME ?></title>
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
        <div class="login-card" style="max-width: 500px;">
            <div class="logo">
                <i class="bi bi-person-workspace"></i>
            </div>
            <h2>ลงทะเบียนอาจารย์ใหม่</h2>

            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>สมัครสมาชิกสำเร็จ!</strong><br>
                    กรุณารอการอนุมัติจากผู้ดูแลระบบ
                </div>
                <div class="text-center">
                    <a href="login.php" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-2"></i>กลับสู่หน้าเข้าสู่ระบบ
                    </a>
                </div>
            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-circle me-2"></i>
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="firstname" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstname" name="firstname"
                                placeholder="ระบุชื่อจริง" required
                                value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="lastname" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastname" name="lastname" placeholder="ระบุนามสกุล"
                                required value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมลมหาวิทยาลัย <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="ใส่เป็น @rmutsb.ac.th"
                            required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        <div class="form-text">ใช้อีเมล @rmutsb.ac.th เท่านั้น</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="อย่างน้อย 8 ตัวอักษร" minlength="8" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="password_confirm" class="form-label">ยืนยันรหัสผ่าน <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password_confirm" name="password_confirm"
                                placeholder="อย่างน้อย 8 ตัวอักษร" minlength="8" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                        <i class="bi bi-person-plus me-2"></i>ลงทะเบียน
                    </button>
                </form>

                <hr>

                <div class="text-center">
                    <p>มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="login.php">เข้าสู่ระบบ</a></p>
                    <p>สำหรับนักศึกษา? <a href="register_student.php">ลงทะเบียนนักศึกษา</a></p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>