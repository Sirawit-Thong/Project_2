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

        <!-- Satisfaction Survey (1.3.2.11) -->
        <?php if ($repair['status'] === 'completed'): ?>
            <div class="card mb-4 border-warning">
                <div class="card-header"><i class="bi bi-star me-2"></i>ประเมินความพึงพอใจงานซ่อม</div>
                <div class="card-body">
                    <?php if ($survey): ?>
                        <p class="mb-1">
                            ท่านให้คะแนนไว้:
                            <?php for ($s = 1; $s <= 5; $s++): ?>
                                <i class="bi bi-star<?= $s <= (int) $survey['rating'] ? '-fill' : '' ?> text-warning"></i>
                            <?php endfor; ?>
                            (<?= (int) $survey['rating'] ?>/5)
                        </p>
                        <?php if (!empty($survey['comment'])): ?>
                            <p class="mb-0 text-muted"><i class="bi bi-chat-left-text me-1"></i><?= nl2br(htmlspecialchars($survey['comment'])) ?></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p class="text-muted">กรุณาให้คะแนนความพึงพอใจและคำติชม เพื่อนำไปปรับปรุงคุณภาพการบริการบำรุงรักษาครุภัณฑ์</p>
                        <form method="POST" action="<?= SITE_URL ?>/satisfaction/submit/<?= (int) $repair['id'] ?>">
                            <?= csrf_field() ?>
                            <div class="mb-3">
                                <label class="form-label d-block">ระดับความพึงพอใจ <span class="text-danger">*</span></label>
                                <?php foreach ([5 => 'ดีมาก', 4 => 'ดี', 3 => 'ปานกลาง', 2 => 'พอใช้', 1 => 'ควรปรับปรุง'] as $val => $label): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="rating" value="<?= $val ?>" id="rate<?= $val ?>" required>
                                        <label class="form-check-label" for="rate<?= $val ?>"><?= $val ?> - <?= $label ?></label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="surveyComment">คำติชม / ข้อเสนอแนะ (ถ้ามี)</label>
                                <textarea class="form-control" id="surveyComment" name="comment" rows="3" maxlength="1000"
                                    placeholder="แสดงความคิดเห็นเกี่ยวกับการซ่อมบำรุงครั้งนี้..."></textarea>
                            </div>
                            <button type="submit" class="btn btn-warning"><i class="bi bi-send me-1"></i>ส่งแบบประเมิน</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

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
