<div class="login-container">
    <div class="login-card" style="max-width: 500px;">
        <div class="logo"><i class="bi bi-person-workspace"></i></div>
        <h2>ลงทะเบียนอาจารย์ใหม่</h2>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <strong>สมัครสมาชิกสำเร็จ!</strong><br>กรุณารอการอนุมัติจากผู้ดูแลระบบ
            </div>
            <div class="text-center">
                <a href="<?= SITE_URL ?>/login" class="btn btn-primary">
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
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="firstname" class="form-label">ชื่อ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="ระบุชื่อจริง" required value="<?= htmlspecialchars($_POST['firstname'] ?? '') ?>">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="lastname" class="form-label">นามสกุล <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="ระบุนามสกุล" required value="<?= htmlspecialchars($_POST['lastname'] ?? '') ?>">
                    </div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">อีเมลมหาวิทยาลัย <span class="text-danger">*</span></label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="ใส่เป็น @rmutsb.ac.th" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    <div class="form-text">ใช้อีเมล @rmutsb.ac.th เท่านั้น</div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="password" class="form-label">รหัสผ่าน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password" name="password" placeholder="อย่างน้อย 8 ตัวอักษร" minlength="8" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label for="password_confirm" class="form-label">ยืนยันรหัสผ่าน <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="อย่างน้อย 8 ตัวอักษร" minlength="8" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                    <i class="bi bi-person-plus me-2"></i>ลงทะเบียน
                </button>
            </form>
            <hr>
            <div class="text-center">
                <p>มีบัญชีผู้ใช้งานอยู่แล้ว? <a href="<?= SITE_URL ?>/login">เข้าสู่ระบบ</a></p>
                <p>สำหรับนักศึกษา? <a href="<?= SITE_URL ?>/register/student">ลงทะเบียนนักศึกษา</a></p>
            </div>
        <?php endif; ?>
    </div>
</div>
