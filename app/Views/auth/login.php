<?php
$lockoutRemaining = $lockoutRemaining ?? 0;
?>
<div class="login-container">
    <div class="login-card">
        <div class="logo"><img src="<?= SITE_URL ?>/assets/Logo.svg" alt="โลโก้ <?= SITE_NAME ?>"></div>
        <h2><?= SITE_NAME ?></h2>

        <?php if (!empty($flash)): ?>
            <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="ปิด"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($lockoutRemaining > 0): ?>
            <div class="alert alert-danger" id="lockoutAlert">
                <i class="bi bi-lock-fill me-2"></i>
                พยายามเข้าสู่ระบบมากเกินไป — ระบบล็อกชั่วคราว กรุณารอ
                <strong><span id="lockoutCountdown">--:--</span></strong> แล้วลองใหม่
            </div>
        <?php endif; ?>

        <form method="POST" action="" id="loginForm">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label for="email" class="form-label"><i class="bi bi-person me-1"></i>บัญชีผู้ใช้ / อีเมล</label>
                <input type="text" class="form-control" id="email" name="email"
                    placeholder="ระบุชื่อบัญชีผู้ใช้ หรืออีเมล" required
                    autocomplete="username" autocapitalize="none" spellcheck="false"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                <div class="form-text">นักศึกษาสามารถใช้รหัสนักศึกษาเข้าใช้งานได้</div>
            </div>
            <div class="mb-4">
                <label for="password" class="form-label"><i class="bi bi-lock me-1"></i>รหัสผ่าน</label>
                <div class="input-group">
                    <input type="password" class="form-control" id="password" name="password"
                        placeholder="ระบุรหัสผ่าน" required autocomplete="current-password">
                    <button type="button" class="btn btn-outline-secondary" id="togglePassword"
                        aria-label="แสดง/ซ่อนรหัสผ่าน" tabindex="-1">
                        <i class="bi bi-eye" id="togglePasswordIcon"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary w-100 py-2 mb-3" id="loginSubmitBtn"
                <?= $lockoutRemaining > 0 ? 'disabled' : '' ?>>
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // แสดง/ซ่อนรหัสผ่าน
        const toggle = document.getElementById('togglePassword');
        const pwd = document.getElementById('password');
        if (toggle && pwd) {
            toggle.addEventListener('click', function () {
                const show = pwd.type === 'password';
                pwd.type = show ? 'text' : 'password';
                document.getElementById('togglePasswordIcon').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
            });
        }

        // นับถอยหลังเมื่อโดนล็อก — หมดเวลาแล้วรีโหลดหน้าให้ฟอร์มกลับมาใช้งานได้
        <?php if ($lockoutRemaining > 0): ?>
            let remaining = <?= (int) $lockoutRemaining ?>;
            const countdownEl = document.getElementById('lockoutCountdown');
            function tick() {
                if (remaining <= 0) {
                    window.location.reload();
                    return;
                }
                const m = Math.floor(remaining / 60);
                const s = remaining % 60;
                countdownEl.textContent = String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
                remaining--;
            }
            tick();
            setInterval(tick, 1000);
        <?php endif; ?>
    });
</script>
