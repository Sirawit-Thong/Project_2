<div class="login-container">
    <div class="login-card">
        <div class="logo"><i class="bi bi-tools"></i></div>
        <h2><?= SITE_NAME ?></h2>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-person me-1"></i>บัญชีผู้ใช้ / อีเมล</label>
                <input type="text" class="form-control" id="email" name="email"
                    placeholder="ระบุชื่อบัญชีผู้ใช้ หรืออีเมล" required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <div class="form-text">นักศึกษาสามารถใช้รหัสนักศึกษาเข้าใช้งานได้</div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>รหัสผ่าน</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="ระบุรหัสผ่าน" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                <i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ
            </button>
        </form>

        <hr>
        <div class="text-center">
            <p class="mb-2">หากยังไม่มีบัญชีผู้ใช้งาน</p>
            <a href="<?= SITE_URL ?>/register/student" class="btn btn-outline-primary me-2">
                <i class="bi bi-mortarboard me-1"></i>สำหรับนักศึกษา
            </a>
            <a href="<?= SITE_URL ?>/register/teacher" class="btn btn-outline-secondary">
                <i class="bi bi-person-workspace me-1"></i>สำหรับอาจารย์
            </a>
        </div>
    </div>
</div>
