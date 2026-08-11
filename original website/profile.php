<?php
/**
 * User Profile
 * หน้าโปรไฟล์ผู้ใช้ - ดูและแก้ไขข้อมูลส่วนตัว
 */

$pageTitle = 'ข้อมูลส่วนตัว';
require_once 'includes/header.php';

// ต้อง login ก่อน
if (!isLoggedIn()) {
    setFlash('danger', 'กรุณาเข้าสู่ระบบก่อน');
    redirect('login.php');
}

$pdo = getDB();
$userId = getCurrentUserId();
$errors = [];
$success = false;

// ดึงข้อมูลผู้ใช้
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Only allow password change
    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($currentPassword)) {
            $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
        } elseif (!password_verify($currentPassword, $user['password'])) {
            $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
        }

        if (empty($newPassword)) {
            $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 6 ตัวอักษร';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
        }

        if (empty($errors)) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);

            logActivity($pdo, $userId, 'Change Password', 'เปลี่ยนรหัสผ่าน');
            setFlash('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
            redirect('profile.php');
        }
    }

    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
}

// Get user stats
$stats = [];

if ($user['role'] === 'admin' || $user['role'] === 'staff') {
    $stats['total_equipment'] = $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
    $stats['total_repairs'] = $pdo->query("SELECT COUNT(*) FROM repair")->fetchColumn();
} else {
    // My repairs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repair WHERE user_id = ?");
    $stmt->execute([$userId]);
    $stats['my_repairs'] = $stmt->fetchColumn();

    // My equipment (as holder)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipment WHERE holder_id = ?");
    $stmt->execute([$userId]);
    $stats['my_equipment'] = $stmt->fetchColumn();
}

// Get managed rooms (for teachers)
$managedRooms = [];
if ($user['role'] === 'teacher') {
    $stmt = $pdo->prepare("SELECT r.name FROM room_managers rm JOIN rooms r ON rm.room_id = r.id WHERE rm.user_id = ?");
    $stmt->execute([$userId]);
    $managedRooms = $stmt->fetchAll(PDO::FETCH_COLUMN);
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-person-circle me-2"></i>ข้อมูลส่วนตัว</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a
                    href="<?= $user['role'] === 'admin' || $user['role'] === 'staff' ? 'admin/' : ($user['role'] === 'teacher' ? 'teacher/' : 'student/') ?>index.php">แดชบอร์ด</a>
            </li>
            <li class="breadcrumb-item active">ข้อมูลส่วนตัว</li>
        </ol>
    </nav>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <i class="bi bi-exclamation-circle me-2"></i>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= htmlspecialchars($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Profile Info -->
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center"
                        style="width: 100px; height: 100px;">
                        <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h4>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <span
                    class="badge bg-<?= getStatusBadgeClass($user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary')) ?> fs-6">
                    <?= translateRole($user['role']) ?>
                </span>

                <?php if ($user['sid']): ?>
                    <p class="mt-3 mb-1"><strong>รหัส:</strong> <?= htmlspecialchars($user['sid']) ?></p>
                <?php endif; ?>

                <?php if ($user['class']): ?>
                    <p class="mb-1"><strong>ห้อง:</strong> <?= htmlspecialchars($user['class']) ?></p>
                <?php endif; ?>

                <hr>

                <div class="text-start">
                    <p class="mb-2">
                        <i class="bi bi-calendar me-2"></i>
                        <strong>สมัครเมื่อ:</strong> <?= formatDateThai($user['created_at']) ?>
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-check-circle me-2 text-success"></i>
                        <strong>สถานะ:</strong>
                        <span class="badge bg-<?= $user['status'] === 'approved' ? 'success' : 'warning' ?>">
                            <?= translateUserStatus($user['status']) ?>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Stats Card -->
        <div class="card mt-4">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2"></i>สถิติการใช้งานระบบ
            </div>
            <div class="card-body">
                <?php if ($user['role'] === 'admin' || $user['role'] === 'staff'): ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>ครุภัณฑ์ทั้งหมด:</span>
                        <strong><?= number_format($stats['total_equipment']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>การแจ้งซ่อมทั้งหมด:</span>
                        <strong><?= number_format($stats['total_repairs']) ?></strong>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span>รายการที่แจ้งซ่อมแล้ว:</span>
                        <strong><?= number_format($stats['my_repairs']) ?></strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>ครุภัณฑ์ในความดูแล:</span>
                        <strong><?= number_format($stats['my_equipment']) ?></strong>
                    </div>
                <?php endif; ?>

                <?php if (!empty($managedRooms)): ?>
                    <hr>
                    <p class="mb-2"><strong>ห้องที่รับผิดชอบดูแล:</strong></p>
                    <div class="d-flex flex-wrap gap-1">
                        <?php foreach ($managedRooms as $room): ?>
                            <span class="badge bg-secondary"><?= htmlspecialchars($room) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- User Info (Read-only) -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>รายละเอียดบัญชีผู้ใช้
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-3">
                    <i class="bi bi-info-circle me-2"></i>
                    หากต้องการแก้ไขข้อมูลบัญชี กรุณาติดต่อผู้ดูแลระบบ
                </div>

                <table class="table table-borderless">
                    <tr>
                        <th style="width: 150px;">ชื่อ-นามสกุล:</th>
                        <td><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></td>
                    </tr>
                    <tr>
                        <th>อีเมล:</th>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                    </tr>
                    <tr>
                        <th>บทบาท:</th>
                        <td><span
                                class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : ($user['role'] === 'teacher' ? 'info' : 'secondary')) ?>"><?= translateRole($user['role']) ?></span>
                        </td>
                    </tr>
                    <?php if ($user['sid']): ?>
                        <tr>
                            <th>รหัสประจำตัว:</th>
                            <td><?= htmlspecialchars($user['sid']) ?></td>
                        </tr>
                    <?php endif; ?>
                    <?php if ($user['class']): ?>
                        <tr>
                            <th>ห้อง/ชั้นปี:</th>
                            <td><?= htmlspecialchars($user['class']) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Change Password -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-key me-2"></i>เปลี่ยนรหัสผ่านบัญชี
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านเดิม <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="new_password" required minlength="6"
                                placeholder="อย่างน้อย 6 ตัวอักษร">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่อีกครั้ง <span
                                    class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-key me-1"></i>เปลี่ยนรหัสผ่าน
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>