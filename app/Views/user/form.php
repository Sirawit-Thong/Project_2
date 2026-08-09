<?php
$pageTitle = $pageTitle ?? ($user ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่');
$isEdit = !empty($user);
$formAction = $isEdit ? SITE_URL . '/users/edit/' . $user['id'] : SITE_URL . '/users/add';
?>

<div class="page-header">
    <h1><i class="bi bi-<?= $isEdit ? 'pencil-square' : 'person-plus' ?> me-2"></i><?= $isEdit ? 'แก้ไขผู้ใช้' : 'เพิ่มผู้ใช้ใหม่' ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/users">จัดการผู้ใช้</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'แก้ไข' : 'เพิ่มใหม่' ?></li>
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

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-form me-2"></i>ข้อมูลผู้ใช้
            </div>
            <div class="card-body">
                <form method="POST" action="<?= $formAction ?>">
                    <?= csrf_field() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="sid" class="form-label">รหัสประจำตัว <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="sid" name="sid"
                                value="<?= htmlspecialchars($user['sid'] ?? ($_POST['sid'] ?? '')) ?>" required
                                placeholder="รหัสนักศึกษาหรือบัตรพนักงาน">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label">อีเมล <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= htmlspecialchars($user['email'] ?? ($_POST['email'] ?? '')) ?>" required
                                placeholder="example@rmutsb.ac.th">
                        </div>

                        <div class="col-md-6">
                            <label for="firstname" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="firstname" name="firstname"
                                value="<?= htmlspecialchars($user['firstname'] ?? ($_POST['firstname'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="lastname" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="lastname" name="lastname"
                                value="<?= htmlspecialchars($user['lastname'] ?? ($_POST['lastname'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label">
                                รหัสผ่าน <?= $isEdit ? '' : '<span class="text-danger">*</span>' ?>
                            </label>
                            <input type="password" class="form-control" id="password" name="password"
                                <?= $isEdit ? '' : 'required' ?> minlength="6"
                                placeholder="<?= $isEdit ? 'ปล่อยว่างหากไม่ต้องการเปลี่ยน' : 'อย่างน้อย 6 ตัวอักษร' ?>">
                            <?php if ($isEdit): ?>
                                <div class="form-text">ปล่อยว่างหากไม่ต้องการเปลี่ยนรหัสผ่าน</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="class" class="form-label">ห้อง / ชั้นปี</label>
                            <input type="text" class="form-control" id="class" name="class"
                                value="<?= htmlspecialchars($user['class'] ?? ($_POST['class'] ?? '')) ?>"
                                placeholder="เช่น ปวส.2/1">
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label">บทบาท <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" required>
                                <option value="">-- เลือกบทบาท --</option>
                                <option value="admin" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                                <option value="staff" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'staff') ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                                <option value="teacher" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'teacher') ? 'selected' : '' ?>>อาจารย์</option>
                                <option value="student" <?= (($user['role'] ?? $_POST['role'] ?? '') === 'student') ? 'selected' : '' ?>>นักศึกษา</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="approved" <?= (($user['status'] ?? $_POST['status'] ?? '') === 'approved') ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                                <option value="pending" <?= (($user['status'] ?? $_POST['status'] ?? '') === 'pending') ? 'selected' : '' ?>>รออนุมัติ</option>
                                <option value="rejected" <?= (($user['status'] ?? $_POST['status'] ?? '') === 'rejected') ? 'selected' : '' ?>>ถูกปฏิเสธ</option>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="<?= SITE_URL ?>/users" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>ย้อนกลับ
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-<?= $isEdit ? 'save' : 'plus-circle' ?> me-1"></i>
                            <?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มผู้ใช้' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>


