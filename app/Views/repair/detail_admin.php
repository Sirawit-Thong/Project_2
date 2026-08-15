<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-wrench me-2"></i>รายละเอียดการแจ้งซ่อม #<?= (int) $repair['id'] ?></h1>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </button>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-info-circle me-2"></i>รายละเอียดการแจ้งซ่อม</span>
                <span class="badge bg-<?= getStatusBadgeClass($repair['status']) ?> fs-6">
                    <?= translateRepairStatus($repair['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ครุภัณฑ์:</strong><br>
                        <a href="<?= SITE_URL ?>/equipment/<?= (int) $repair['equipment_id'] ?>">
                            <?= sanitize($repair['eq_code'] ?? '-') ?>
                        </a><br>
                        <?= sanitize($repair['item_name']) ?>
                        (<?= sanitize($repair['brand'] ?? '') ?> <?= sanitize($repair['model'] ?? '') ?>)
                    </div>
                    <div class="col-md-6">
                        <strong>ห้อง:</strong> <?= sanitize($repair['room'] ?? '-') ?><br>
                        <strong>วันที่แจ้ง:</strong> <?= formatDateTimeThai($repair['created_at']) ?>
                    </div>
                </div>
                <hr>
                <strong>อาการเสีย:</strong>
                <p class="mt-2 p-3 bg-light rounded"><?= nl2br(htmlspecialchars($repair['issue'])) ?></p>

                <?php if (!empty($images)): ?>
                    <strong>รูปประกอบ:</strong>
                    <div class="image-gallery mt-2">
                        <?php foreach ($images as $img): ?>
                            <a href="<?= SITE_URL ?>/uploads/<?= sanitize($img['path']) ?>" target="_blank">
                                <img src="<?= SITE_URL ?>/uploads/<?= sanitize($img['path']) ?>" alt="Repair Image">
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><i class="bi bi-person me-2"></i>ข้อมูลผู้แจ้ง</div>
            <div class="card-body">
                <?php if ($repair['firstname']): ?>
                    <strong><?= sanitize($repair['firstname'] . ' ' . $repair['lastname']) ?></strong>
                    <span class="badge bg-<?= $repair['role'] === 'teacher' ? 'info' : ($repair['role'] === 'admin' ? 'danger' : 'secondary') ?>">
                        <?= translateRole($repair['role']) ?>
                    </span><br>
                    <i class="bi bi-envelope me-1"></i><?= sanitize($repair['email']) ?>
                <?php else: ?>
                    <strong class="text-muted">ผู้ใช้งานถูกลบออกจากระบบ</strong>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="bi bi-gear me-2"></i>บันทึกผลการดำเนินงาน</div>
            <div class="card-body">
                <form method="POST" action="<?= SITE_URL ?>/repairs/<?= (int) $repair['id'] ?>">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="pending" <?= $repair['status'] === 'pending' ? 'selected' : '' ?>>รอดำเนินการ</option>
                            <option value="in_progress" <?= $repair['status'] === 'in_progress' ? 'selected' : '' ?>>กำลังซ่อม</option>
                            <option value="completed" <?= $repair['status'] === 'completed' ? 'selected' : '' ?>>ซ่อมเสร็จ</option>
                            <option value="cannot_fix" <?= $repair['status'] === 'cannot_fix' ? 'selected' : '' ?>>ซ่อมไม่ได้</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg me-1"></i>บันทึก
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
