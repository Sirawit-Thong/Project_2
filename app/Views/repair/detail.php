<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-wrench me-2"></i>รายละเอียดการแจ้งซ่อมบำรุง #<?= $repair['id'] ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/repairs/mine">ประวัติการแจ้งซ่อม</a></li>
                <li class="breadcrumb-item active">#<?= $repair['id'] ?></li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </button>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Repair Info -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-info-circle me-2"></i>ข้อมูลการแจ้งซ่อมบำรุง</span>
                <span class="badge bg-<?= getStatusBadgeClass($repair['status']) ?> fs-6">
                    <?= translateRepairStatus($repair['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th style="width: 150px;">ครุภัณฑ์:</th>
                        <td>
                            <strong><?= htmlspecialchars($repair['item_name']) ?></strong><br>
                            <small class="text-muted"><?= htmlspecialchars($repair['brand'] ?? '') ?>
                                <?= htmlspecialchars($repair['model'] ?? '') ?></small>
                        </td>
                    </tr>
                    <tr>
                        <th>รหัสครุภัณฑ์:</th>
                        <td><code><?= htmlspecialchars($repair['eq_code'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>ห้อง/สถานที่:</th>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($repair['room'] ?? '-') ?></span></td>
                    </tr>
                    <tr>
                        <th>วันที่แจ้ง:</th>
                        <td><?= formatDateTimeThai($repair['created_at']) ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Issue Description -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-exclamation-circle me-2"></i>รายละเอียดความขัดข้อง/ปัญหาที่พบ
            </div>
            <div class="card-body">
                <p class="mb-0"><?= nl2br(htmlspecialchars($repair['issue'])) ?></p>
            </div>
        </div>

        <!-- Resolution (if any) -->
        <?php if (!empty($repair['resolution'] ?? '')): ?>
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <i class="bi bi-check-circle me-2"></i>ผลการดำเนินการซ่อมบำรุง
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($repair['resolution'])) ?></p>
                    <?php if (!empty($repair['completed_at'] ?? '')): ?>
                        <hr>
                        <small class="text-muted">
                            <i class="bi bi-calendar-check me-1"></i>ดำเนินการเสร็จสิ้นเมื่อ:
                            <?= formatDateTimeThai($repair['completed_at']) ?>
                        </small>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <?php if ($role === 'admin' || $role === 'staff'): ?>
            <!-- Status Update (admin/staff) -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="bi bi-gear me-2"></i>อัปเดตสถานะ
                </div>
                <div class="card-body">
                    <form method="POST" action="<?= SITE_URL ?>/repairs/<?= $repair['id'] ?>">
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

        <!-- Status Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>สถานะการดำเนินการ
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item active">
                        <div class="timeline-marker bg-success"></div>
                        <div class="timeline-content">
                            <p class="mb-0 fw-bold">แจ้งซ่อม</p>
                            <small class="text-muted"><?= formatDateTimeThai($repair['created_at']) ?></small>
                        </div>
                    </div>
                    <?php if ($repair['status'] !== 'pending'): ?>
                        <div
                            class="timeline-item <?= in_array($repair['status'], ['in_progress', 'completed']) ? 'active' : '' ?>">
                            <div
                                class="timeline-marker bg-<?= in_array($repair['status'], ['in_progress', 'completed']) ? 'warning' : 'secondary' ?>">
                            </div>
                            <div class="timeline-content">
                                <p class="mb-0 fw-bold">กำลังดำเนินการ</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    <?php if ($repair['status'] === 'completed'): ?>
                        <div class="timeline-item active">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <p class="mb-0 fw-bold">ซ่อมเสร็จ</p>
                                <?php if (!empty($repair['completed_at'] ?? '')): ?>
                                    <small class="text-muted"><?= formatDateTimeThai($repair['completed_at']) ?></small>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Images -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-images me-2"></i>รูปภาพประกอบ
            </div>
            <div class="card-body">
                <?php if (empty($images)): ?>
                    <div class="empty-state py-3">
                        <i class="bi bi-image"></i>
                        <p>ไม่มีรูปภาพ</p>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($images as $img): ?>
                            <div class="col-6">
                                <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" target="_blank">
                                    <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" class="img-fluid rounded"
                                        alt="Repair Image">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
        border-left: 2px solid #dee2e6;
        padding-left: 20px;
        margin-left: 6px;
    }

    .timeline-item:last-child {
        border-left: none;
        padding-bottom: 0;
    }

    .timeline-item.active .timeline-marker {
        transform: scale(1.2);
    }

    .timeline-marker {
        position: absolute;
        left: -8px;
        top: 0;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #6c757d;
    }
</style>
