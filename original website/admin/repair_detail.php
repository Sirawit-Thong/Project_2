<?php
/**
 * Repair Detail
 * รายละเอียดการซ่อม
 */

$pageTitle = 'รายละเอียดการแจ้งซ่อม';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    setFlash('danger', 'ไม่พบรายการ');
    redirect('repairs.php');
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newStatus = $_POST['status'] ?? '';
    $validStatuses = ['pending', 'in_progress', 'completed', 'cannot_fix'];

    if (in_array($newStatus, $validStatuses)) {
        $stmt = $pdo->prepare("UPDATE repair SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $id]);

        // Update equipment status
        $stmt = $pdo->prepare("SELECT equipment_id FROM repair WHERE id = ?");
        $stmt->execute([$id]);
        $eqId = $stmt->fetchColumn();

        if ($newStatus === 'in_progress') {
            $pdo->prepare("UPDATE equipment SET status = 'repair' WHERE id = ?")->execute([$eqId]);
        } elseif ($newStatus === 'completed') {
            $pdo->prepare("UPDATE equipment SET status = 'available' WHERE id = ?")->execute([$eqId]);
        } elseif ($newStatus === 'cannot_fix') {
            $pdo->prepare("UPDATE equipment SET status = 'broken' WHERE id = ?")->execute([$eqId]);
        }

        logActivity($pdo, getCurrentUserId(), 'Update Repair Status', "อัปเดตสถานะซ่อม ID: $id เป็น $newStatus");
        setFlash('success', 'อัปเดตสถานะสำเร็จ');
    }
    redirect("repair_detail.php?id=$id");
}

$stmt = $pdo->prepare("SELECT r.*, e.code as eq_code, rm.name as room, i.name as item_name, i.brand, i.model, u.firstname, u.lastname, u.email, u.role 
    FROM repair r JOIN equipment e ON r.equipment_id = e.id JOIN items i ON e.item_id = i.id LEFT JOIN rooms rm ON e.room_id = rm.id LEFT JOIN users u ON r.user_id = u.id WHERE r.id = ?");
$stmt->execute([$id]);
$repair = $stmt->fetch();

if (!$repair) {
    setFlash('danger', 'ไม่พบรายการ');
    redirect('repairs.php');
}

$stmt = $pdo->prepare("SELECT * FROM repair_img WHERE repair_id = ?");
$stmt->execute([$id]);
$images = $stmt->fetchAll();
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-wrench me-2"></i>รายละเอียดการแจ้งซ่อม #<?= $id ?></h1>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="history.back()"><i
            class="bi bi-arrow-left me-1"></i>กลับ</button>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-info-circle me-2"></i>รายละเอียดการแจ้งซ่อม</span>
                <span
                    class="badge bg-<?= getStatusBadgeClass($repair['status']) ?> fs-6"><?= translateRepairStatus($repair['status']) ?></span>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <strong>ครุภัณฑ์:</strong><br>
                        <a
                            href="equipment_detail.php?id=<?= $repair['equipment_id'] ?>"><?= htmlspecialchars($repair['eq_code'] ?? '-') ?></a><br>
                        <?= htmlspecialchars($repair['item_name']) ?> (<?= htmlspecialchars($repair['brand'] ?? '') ?>
                        <?= htmlspecialchars($repair['model'] ?? '') ?>)
                    </div>
                    <div class="col-md-6">
                        <strong>ห้อง:</strong> <?= htmlspecialchars($repair['room'] ?? '-') ?><br>
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
                            <a href="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" target="_blank">
                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" alt="Repair Image">
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
                    <strong><?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?></strong>
                    <span class="badge bg-<?= $repair['role'] === 'teacher' ? 'info' : ($repair['role'] === 'admin' ? 'danger' : 'secondary') ?>"><?= translateRole($repair['role']) ?></span><br>
                    <i class="bi bi-envelope me-1"></i><?= htmlspecialchars($repair['email']) ?>
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
                <form method="POST">
                    <div class="mb-3">
                        <select name="status" class="form-select">
                            <option value="pending" <?= $repair['status'] === 'pending' ? 'selected' : '' ?>>รอดำเนินการ
                            </option>
                            <option value="in_progress" <?= $repair['status'] === 'in_progress' ? 'selected' : '' ?>>
                                กำลังซ่อม</option>
                            <option value="completed" <?= $repair['status'] === 'completed' ? 'selected' : '' ?>>
                                ซ่อมเสร็จ
                            </option>
                            <option value="cannot_fix" <?= $repair['status'] === 'cannot_fix' ? 'selected' : '' ?>>
                                ซ่อมไม่ได้</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100"><i
                            class="bi bi-check-lg me-1"></i>บันทึก</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>