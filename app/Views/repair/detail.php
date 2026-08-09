<?php
$pageTitle = 'รายละเอียดการแจ้งซ่อม #' . $repair['id'];
?>

<div class="page-header">
    <h1><i class="bi bi-tools me-2"></i>รายละเอียดการแจ้งซ่อม</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/repairs">รายการแจ้งซ่อม</a></li>
            <li class="breadcrumb-item active">#<?= $repair['id'] ?></li>
        </ol>
    </nav>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-info-circle me-2"></i>ข้อมูลครุภัณฑ์</span>
                <span class="badge bg-<?= getStatusBadgeClass($repair['status']) ?> fs-6">
                    <?= translateRepairStatus($repair['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">รหัสครุภัณฑ์</div>
                        <div class="fw-semibold"><?= htmlspecialchars($repair['eq_code']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">ชื่อรายการ</div>
                        <div class="fw-semibold"><?= htmlspecialchars($repair['item_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">ห้อง/สถานที่</div>
                        <div class="fw-semibold"><?= htmlspecialchars($repair['room'] ?? '-') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small mb-1">ยี่ห้อ / รุ่น</div>
                        <div class="fw-semibold">
                            <?= htmlspecialchars($repair['brand'] ?? '-') ?>
                            <?= !empty($repair['model']) ? '/ ' . htmlspecialchars($repair['model']) : '' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-chat-left-text me-2"></i>ปัญหาที่แจ้ง
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($repair['issue']) ?></p>
            </div>
        </div>

        <?php if (!empty($images)): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-images me-2"></i>รูปภาพประกอบ (<?= count($images) ?> รูป)
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach ($images as $img): ?>
                            <div class="col-6 col-md-4">
                                <a href="<?= SITE_URL ?>/uploads/repairs/<?= htmlspecialchars($img['filename']) ?>" target="_blank">
                                    <img src="<?= SITE_URL ?>/uploads/repairs/<?= htmlspecialchars($img['filename']) ?>"
                                        class="img-fluid rounded" alt="รูปประกอบ"
                                        style="width:100%; height:150px; object-fit:cover;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($repair['admin_note'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-sticky me-2"></i>บันทึกจากเจ้าหน้าที่
                </div>
                <div class="card-body">
                    <p class="mb-0" style="white-space: pre-wrap;"><?= htmlspecialchars($repair['admin_note']) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-person me-2"></i>ข้อมูลผู้แจ้ง
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3">
                    <div class="rounded-circle bg-primary d-inline-flex align-items-center justify-content-center me-3" style="width:50px;height:50px;min-width:50px;">
                        <i class="bi bi-person-fill text-white fs-5"></i>
                    </div>
                    <div>
                        <div class="fw-semibold"><?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($repair['email']) ?></small><br>
                        <span class="badge bg-<?= $repair['role'] === 'teacher' ? 'info' : 'secondary' ?>">
                            <?= translateRole($repair['role']) ?>
                        </span>
                    </div>
                </div>
                <hr>
                <div class="small">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">วันที่แจ้ง:</span>
                        <span><?= formatDateTimeThai($repair['created_at']) ?></span>
                    </div>
                    <?php if (!empty($repair['updated_at']) && $repair['updated_at'] !== $repair['created_at']): ?>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">อัปเดตล่าสุด:</span>
                            <span><?= formatDateTimeThai($repair['updated_at']) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i>อัปเดตสถานะ
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>/repairs/<?= $repair['id'] ?>/status">
                        <?= csrf_field() ?>
                        <div class="d-grid gap-2">
                            <?php if ($repair['status'] !== 'in_progress'): ?>
                                <button type="submit" name="status" value="in_progress" class="btn btn-info text-white">
                                    <i class="bi bi-gear me-1"></i>กำลังซ่อม
                                </button>
                            <?php endif; ?>
                            <?php if ($repair['status'] !== 'completed'): ?>
                                <button type="submit" name="status" value="completed" class="btn btn-success">
                                    <i class="bi bi-check-circle me-1"></i>ซ่อมเสร็จ
                                </button>
                            <?php endif; ?>
                            <?php if ($repair['status'] !== 'cannot_fix'): ?>
                                <button type="submit" name="status" value="cannot_fix" class="btn btn-danger"
                                    onclick="return confirm('ยืนยันว่าซ่อมไม่ได้?');">
                                    <i class="bi bi-x-circle me-1"></i>ซ่อมไม่ได้
                                </button>
                            <?php endif; ?>
                        </div>
                        <?php if ($repair['status'] === 'in_progress' || $repair['status'] === 'completed' || $repair['status'] === 'cannot_fix'): ?>
                            <div class="text-center mt-3">
                                <small class="text-muted">สถานะปัจจุบัน: <strong><?= translateRepairStatus($repair['status']) ?></strong></small>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>ประวัติสถานะ
            </div>
            <div class="card-body">
                <?php if (!empty($repair['timeline'])): ?>
                    <div class="timeline">
                        <?php foreach ($repair['timeline'] as $event): ?>
                            <div class="d-flex mb-3">
                                <div class="me-3">
                                    <div class="rounded-circle bg-<?= getStatusBadgeClass($event['status']) ?> d-flex align-items-center justify-content-center"
                                        style="width:32px;height:32px;min-width:32px;">
                                        <i class="bi bi-<?= $event['status'] === 'pending' ? 'hourglass' : ($event['status'] === 'in_progress' ? 'gear' : ($event['status'] === 'completed' ? 'check-lg' : 'x-lg')) ?> text-white small"></i>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="fw-semibold small"><?= translateRepairStatus($event['status']) ?></div>
                                    <div class="text-muted small"><?= formatDateTimeThai($event['created_at']) ?></div>
                                    <?php if (!empty($event['note'])): ?>
                                        <div class="small mt-1"><?= htmlspecialchars($event['note']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-clock d-block mb-1"></i>
                        <small>ยังไม่มีประวัติการเปลี่ยนสถานะ</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


