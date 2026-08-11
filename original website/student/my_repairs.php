<?php
/**
 * My Repairs (Student)
 */
$pageTitle = 'ประวัติการส่งรายการแจ้งซ่อมบำรุง';
require_once '../includes/header.php';
requireRole('student');

$pdo = getDB();
$userId = getCurrentUserId();

$stmt = $pdo->prepare("SELECT r.*, e.code, i.name as item_name FROM repair r 
    JOIN equipment e ON r.equipment_id = e.id JOIN items i ON e.item_id = i.id 
    WHERE r.user_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$userId]);
$repairs = $stmt->fetchAll();
?>

<div class="page-header d-flex justify-content-between">
    <h1><i class="bi bi-list-check me-2"></i>ประวัติการส่งรายการแจ้งซ่อมบำรุง</h1>
    <a href="repair_submit.php" class="btn btn-primary"><i class="bi bi-plus-lg me-1"></i>สร้างรายการแจ้งซ่อมใหม่</a>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>รายการแจ้งซ่อมบำรุง (<?= count($repairs) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($repairs)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i>
                <h5>ยังไม่มีการแจ้งซ่อม</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="hide-mobile">#</th>
                            <th>ครุภัณฑ์</th>
                            <th class="hide-mobile">รายละเอียดความขัดข้อง</th>
                            <th>สถานะ</th>
                            <th class="hide-mobile">วันที่แจ้ง</th>
                            <th style="width: 80px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repairs as $r): ?>
                            <tr>
                                <td class="hide-mobile"><?= $r['id'] ?></td>
                                <td><strong><?= htmlspecialchars($r['code'] ?? '-') ?></strong><br><small><?= htmlspecialchars($r['item_name']) ?></small>
                                </td>
                                <td class="hide-mobile"><?= mb_substr(htmlspecialchars($r['issue']), 0, 50) ?>...</td>
                                <td><span
                                        class="badge bg-<?= getStatusBadgeClass($r['status']) ?>"><?= translateRepairStatus($r['status']) ?></span>
                                </td>
                                <td class="hide-mobile"><?= formatDateTimeThai($r['created_at']) ?></td>
                                <td>
                                    <a href="repair_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-eye me-1"></i>ดู
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>