<div class="page-header">
    <h1><i class="bi bi-person-circle me-2"></i>ข้อมูลส่วนตัว</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
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
    <div class="col-lg-4 mb-4">
        <div class="card">
            <div class="card-body text-center">
                <div class="mb-3">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center" style="width: 100px; height: 100px;">
                        <i class="bi bi-person-fill text-white" style="font-size: 3rem;"></i>
                    </div>
                </div>
                <h4 class="mb-1"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></h4>
                <p class="text-muted mb-2"><?= htmlspecialchars($user['email']) ?></p>
                <span class="badge bg-<?= getStatusBadgeClass($user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : 'primary')) ?> fs-6">
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

        <div class="card mt-4">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>สถิติการใช้งานระบบ</div>
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

    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-2"></i>รายละเอียดบัญชีผู้ใช้</div>
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
                        <td><span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'warning' : ($user['role'] === 'teacher' ? 'info' : 'secondary')) ?>"><?= translateRole($user['role']) ?></span></td>
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

        <div class="card">
            <div class="card-header"><i class="bi bi-key me-2"></i>เปลี่ยนรหัสผ่านบัญชี</div>
            <div class="card-body">
                <form method="POST" action="">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="change_password">

                    <div class="mb-3">
                        <label class="form-label">รหัสผ่านเดิม <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" name="current_password" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รหัสผ่านใหม่ <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="new_password" required minlength="6" placeholder="อย่างน้อย 6 ตัวอักษร">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ยืนยันรหัสผ่านใหม่อีกครั้ง <span class="text-danger">*</span></label>
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
