<?php
/**
 * Teacher Repair Detail
 * ดูรายละเอียดการแจ้งซ่อม (สำหรับอาจารย์)
 */
$pageTitle = 'รายละเอียดการแจ้งซ่อมบำรุง';
require_once '../includes/header.php';
requireRole('teacher');

$pdo = getDB();
$userId = getCurrentUserId();
$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'ไม่พบรายการ');
    redirect('my_repairs.php');
}

// Get repair - verify ownership
$stmt = $pdo->prepare("
    SELECT r.*, e.code, rm.name as room, i.name as item_name, i.brand, i.model,
           u.firstname, u.lastname
    FROM repair r
    JOIN equipment e ON r.equipment_id = e.id
    JOIN items i ON e.item_id = i.id
    LEFT JOIN rooms rm ON e.room_id = rm.id
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.id = ? AND r.user_id = ?
");
$stmt->execute([$id, $userId]);
$repair = $stmt->fetch();

if (!$repair) {
    setFlash('danger', 'ไม่มีสิทธิ์ดูรายการนี้');
    redirect('my_repairs.php');
}

// Get repair images
$stmt = $pdo->prepare("SELECT * FROM repair_img WHERE repair_id = ?");
$stmt->execute([$id]);
$images = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-wrench me-2"></i>รายละเอียดการแจ้งซ่อมบำรุง #<?= $id ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="my_repairs.php">ประวัติการแจ้งซ่อม</a></li>
                <li class="breadcrumb-item active">#<?= $id ?></li>
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
                        <td><code><?= htmlspecialchars($repair['code'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>ห้อง/สถานที่:</th>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($repair['room'] ?? '-') ?></span></td>
                    </tr>
                    <tr>
                        <th>วันที่แจ้ง:</th>
                        <td><?= formatDateTimeThai($repair['created_at']) ?></td>
                    </tr>
                    <tr>
                        <td>
                            <?php if ($repair['firstname']): ?>
                                <?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?>
                            <?php else: ?>
                                <span class="text-muted">ผู้ใช้ถูกลบออกจากระบบ</span>
                            <?php endif; ?>
                        </td>
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
        <!-- Status Timeline -->
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-clock-history me-2"></i>สถานะการดำเนินการ
            </div>
            <div class="card-body">
                <div class="timeline">
                    <div class="timeline-item <?= $repair['status'] ? 'active' : '' ?>">
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
                                <a href="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>" target="_blank">
                                    <img src="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>" class="img-fluid rounded"
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

<?php require_once '../includes/footer.php'; ?>