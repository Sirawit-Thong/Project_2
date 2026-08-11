<?php
/**
 * User Form (Add/Edit)
 * ฟอร์มเพิ่ม/แก้ไขผู้ใช้งาน
 */

$pageTitle = 'บริหารจัดการบัญชีผู้ใช้งาน';
require_once '../includes/header.php';
requireRole('admin');

$pdo = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$isEdit = $id > 0;
$user = null;
$errors = [];

// If editing, get existing user
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlash('danger', 'ไม่พบผู้ใช้ที่ต้องการ');
        redirect('users.php');
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sid = trim($_POST['sid'] ?? '') ?: null;
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $status = $_POST['status'] ?? 'pending';
    $class = trim($_POST['class'] ?? '') ?: null;
    
    // Validation
    if (empty($firstname)) $errors[] = 'กรุณากรอกชื่อ';
    if (empty($lastname)) $errors[] = 'กรุณากรอกนามสกุล';
    if (empty($email)) $errors[] = 'กรุณากรอกอีเมล';
    
    if (!$isEdit && empty($password)) {
        $errors[] = 'กรุณากรอกรหัสผ่าน';
    }
    
    // Check email duplicate
    if ($email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
        }
    }
    
    // Check sid duplicate
    if ($sid) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE sid = ? AND id != ?");
        $stmt->execute([$sid, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'รหัสนักศึกษา/พนักงานนี้ถูกใช้งานแล้ว';
        }
    }
    
    if (empty($errors)) {
        if ($isEdit) {
            // Update
            if (!empty($password)) {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("
                    UPDATE users SET sid = ?, firstname = ?, lastname = ?, email = ?, 
                    password = ?, role = ?, status = ?, class = ? WHERE id = ?
                ");
                $stmt->execute([$sid, $firstname, $lastname, $email, $hashedPassword, $role, $status, $class, $id]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE users SET sid = ?, firstname = ?, lastname = ?, email = ?, 
                    role = ?, status = ?, class = ? WHERE id = ?
                ");
                $stmt->execute([$sid, $firstname, $lastname, $email, $role, $status, $class, $id]);
            }
            
            logActivity($pdo, getCurrentUserId(), 'Update User', "แก้ไขผู้ใช้ ID: $id");
            setFlash('success', 'แก้ไขข้อมูลผู้ใช้สำเร็จ');
        } else {
            // Insert
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (sid, firstname, lastname, email, password, role, status, class) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$sid, $firstname, $lastname, $email, $hashedPassword, $role, $status, $class]);
            
            logActivity($pdo, getCurrentUserId(), 'Add User', "เพิ่มผู้ใช้: $email");
            setFlash('success', 'เพิ่มผู้ใช้สำเร็จ');
        }
        
        redirect('users.php');
    }
}

// Form values
$formData = [
    'sid' => $_POST['sid'] ?? ($user['sid'] ?? ''),
    'firstname' => $_POST['firstname'] ?? ($user['firstname'] ?? ''),
    'lastname' => $_POST['lastname'] ?? ($user['lastname'] ?? ''),
    'email' => $_POST['email'] ?? ($user['email'] ?? ''),
    'role' => $_POST['role'] ?? ($user['role'] ?? 'student'),
    'status' => $_POST['status'] ?? ($user['status'] ?? 'pending'),
    'class' => $_POST['class'] ?? ($user['class'] ?? ''),
];
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2"></i><?= $isEdit ? 'แก้ไขข้อมูลบัญชี' : 'ลงทะเบียนบัญชีใหม่' ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="users.php">ผู้ใช้งาน</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'แก้ไข' : 'เพิ่ม' ?></li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>รายละเอียดบัญชีผู้ใช้งาน
            </div>
            <div class="card-body">
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
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname" 
                                   value="<?= htmlspecialchars($formData['firstname']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname" 
                                   value="<?= htmlspecialchars($formData['lastname']) ?>" required>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" 
                                   value="<?= htmlspecialchars($formData['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสประจำตัว (นักศึกษา/บุคลากร)</label>
                            <input type="text" class="form-control" name="sid" 
                                   value="<?= htmlspecialchars($formData['sid']) ?>">
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">รหัสผ่าน <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?></label>
                            <input type="password" class="form-control" name="password" 
                                   placeholder="<?= $isEdit ? 'เว้นว่างถ้าไม่ต้องการเปลี่ยน' : '' ?>"
                                   <?= $isEdit ? '' : 'required' ?>>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ชั้นปี/ห้องเรียน</label>
                            <input type="text" class="form-control" name="class" 
                                   value="<?= htmlspecialchars($formData['class']) ?>"
                                   placeholder="เช่น ITS36641N">
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">บทบาท <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="student" <?= $formData['role'] === 'student' ? 'selected' : '' ?>>นักศึกษา</option>
                                <option value="teacher" <?= $formData['role'] === 'teacher' ? 'selected' : '' ?>>อาจารย์</option>
                                <option value="staff" <?= $formData['role'] === 'staff' ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                                <option value="admin" <?= $formData['role'] === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="approved" <?= $formData['status'] === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                <option value="pending" <?= $formData['status'] === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' ?>
                        </button>
                        <a href="users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-x-lg me-1"></i>ยกเลิก
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>คำแนะนำ
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>บทบาท <strong>ผู้ดูแลระบบ</strong> สามารถเข้าถึงทุกฟังก์ชัน</li>
                    <li>บทบาท <strong>เจ้าหน้าที่</strong> สามารถจัดการครุภัณฑ์และงานซ่อม</li>
                    <li>บทบาท <strong>อาจารย์</strong> และ <strong>นักศึกษา</strong> สามารถแจ้งซ่อมได้</li>
                    <li>สถานะ <strong>รออนุมัติ</strong> จะไม่สามารถเข้าสู่ระบบได้</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
