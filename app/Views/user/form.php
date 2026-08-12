<?php
$pageTitle = 'บริหารจัดการบัญชีผู้ใช้งาน';
$isEdit = !empty($user);
$formAction = $isEdit ? SITE_URL . '/users/edit/' . $user['id'] : SITE_URL . '/users/add';
?>

<div class="page-header">
    <h1><i class="bi bi-person-<?= $isEdit ? 'gear' : 'plus' ?> me-2"></i><?= $isEdit ? 'แก้ไขข้อมูลบัญชี' : 'ลงทะเบียนบัญชีใหม่' ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/users">ผู้ใช้งาน</a></li>
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
                        <li><?= htmlspecialchars($error) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" action="<?= $formAction ?>">
                    <?= csrf_field() ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="firstname"
                                   value="<?= htmlspecialchars($user['firstname'] ?? ($_POST['firstname'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="lastname"
                                   value="<?= htmlspecialchars($user['lastname'] ?? ($_POST['lastname'] ?? '')) ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email"
                                   value="<?= htmlspecialchars($user['email'] ?? ($_POST['email'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">รหัสประจำตัว (นักศึกษา/บุคลากร)</label>
                            <input type="text" class="form-control" name="sid"
                                   value="<?= htmlspecialchars($user['sid'] ?? ($_POST['sid'] ?? '')) ?>">
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
                                   value="<?= htmlspecialchars($user['class'] ?? ($_POST['class'] ?? '')) ?>"
                                   placeholder="เช่น ITS36641N">
                        </div>
                    </div>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <label class="form-label">บทบาท <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="student" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>นักศึกษา</option>
                                <option value="teacher" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>อาจารย์</option>
                                <option value="staff" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'staff') ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                                <option value="admin" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="approved" <?= (($user['status'] ?? $_POST['status'] ?? '') === 'approved') ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                <option value="pending" <?= (($user['status'] ?? $_POST['status'] ?? '') === 'pending') ? 'selected' : '' ?>>รออนุมัติ</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' ?>
                        </button>
                        <a href="<?= SITE_URL ?>/users" class="btn btn-outline-secondary">
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