<?php
/**
 * Teacher Equipment Check
 * ตรวจสอบครุภัณฑ์ในห้องที่ดูแล
 */
$pageTitle = 'ตรวจสอบและยืนยันสภาพครุภัณฑ์';
require_once '../includes/header.php';
requireRole('teacher');

$pdo = getDB();
$userId = getCurrentUserId();

// Get managed rooms for this teacher
$stmt = $pdo->prepare("
    SELECT r.id, r.name 
    FROM room_managers rm 
    JOIN rooms r ON rm.room_id = r.id 
    WHERE rm.user_id = ?
    ORDER BY r.name
");
$stmt->execute([$userId]);
$managedRooms = $stmt->fetchAll();

// Check if teacher has any equipment assigned directly that are not in managed rooms
$stmtOther = $pdo->prepare("
    SELECT 1 FROM equipment e 
    WHERE e.holder_id = ? 
    AND (e.room_id IS NULL OR e.room_id NOT IN (SELECT room_id FROM room_managers WHERE user_id = ?))
    LIMIT 1
");
$stmtOther->execute([$userId, $userId]);
$hasOtherEquipment = $stmtOther->fetchColumn() !== false;

if ($hasOtherEquipment) {
    // Inject the pseudo-room into the dropdown array
    $managedRooms[] = [
        'id' => 'other',
        'name' => 'อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)'
    ];
}

// Get selected room (now by id)
$selectedRoom = $_GET['room'] ?? '';

// Handle check update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['equipment_id'])) {
    $eqId = (int) $_POST['equipment_id'];
    $checkStatus = $_POST['check_status'] ?? '';
    $remark = trim($_POST['remark'] ?? '');

    // Verify teacher has access to this equipment
    if ($selectedRoom === 'other') {
        // For "other", verify by holder_id
        $stmt = $pdo->prepare("
            SELECT id FROM equipment 
            WHERE id = ? AND holder_id = ?
        ");
        $stmt->execute([$eqId, $userId]);
    } else {
        // For managed rooms, verify by room_managers
        $stmt = $pdo->prepare("
            SELECT e.id FROM equipment e 
            JOIN rooms r ON e.room_id = r.id 
            JOIN room_managers rm ON rm.room_id = r.id 
            WHERE e.id = ? AND rm.user_id = ?
        ");
        $stmt->execute([$eqId, $userId]);
    }

    if ($stmt->fetch()) {
        // Update check date and remark
        $stmt = $pdo->prepare("UPDATE equipment SET check_date = CURDATE(), remark = ? WHERE id = ?");
        $stmt->execute([$remark, $eqId]);

        // If broken, update status
        if ($checkStatus === 'broken') {
            $stmt = $pdo->prepare("UPDATE equipment SET status = 'broken' WHERE id = ?");
            $stmt->execute([$eqId]);
        }

        logActivity($pdo, $userId, 'Teacher Equipment Check', "ตรวจสอบครุภัณฑ์ ID: $eqId");
        setFlash('success', 'บันทึกการตรวจสอบสำเร็จ');
    }

    redirect("my_equipment.php?room=" . urlencode($selectedRoom));
}

// Get equipment in selected room
$equipment = [];
$selectedRoomName = '';
$eqStats = ['total' => 0, 'available' => 0, 'broken' => 0, 'inspected' => 0];
$currentYear = date('Y');

if ($selectedRoom) {
    if ($selectedRoom === 'other') {
        // Handle "อื่นๆ" room
        $selectedRoomName = "อื่นๆ (ไม่ได้อยู่ในห้องที่รับผิดชอบ)";
        $stmt = $pdo->prepare("
            SELECT e.*, i.name as item_name, i.brand, i.model
            FROM equipment e
            JOIN items i ON e.item_id = i.id
            WHERE e.holder_id = ? 
            AND (e.room_id IS NULL OR e.room_id NOT IN (SELECT room_id FROM room_managers WHERE user_id = ?))
            ORDER BY e.code
        ");
        $stmt->execute([$userId, $userId]);
        $equipment = $stmt->fetchAll();
    } else {
        // Verify teacher manages this room and get room name
        $stmt = $pdo->prepare("
            SELECT r.id, r.name FROM rooms r 
            JOIN room_managers rm ON rm.room_id = r.id 
            WHERE r.id = ? AND rm.user_id = ?
        ");
        $stmt->execute([$selectedRoom, $userId]);
        $roomInfo = $stmt->fetch();

        if ($roomInfo) {
            $selectedRoomName = $roomInfo['name'];
            $stmt = $pdo->prepare("
                SELECT e.*, i.name as item_name, i.brand, i.model
                FROM equipment e
                JOIN items i ON e.item_id = i.id
                WHERE e.room_id = ?
                ORDER BY e.code
            ");
            $stmt->execute([$selectedRoom]);
            $equipment = $stmt->fetchAll();
        }
    }

    if (!empty($equipment) || $selectedRoomName) {
        // Calculate stats
        foreach ($equipment as $item) {
            $eqStats['total']++;
            if ($item['status'] === 'available')
                $eqStats['available']++;
            if ($item['status'] === 'broken')
                $eqStats['broken']++;
            if ($item['check_date'] && date('Y', strtotime($item['check_date'])) == $currentYear) {
                $eqStats['inspected']++;
            }
        }
    }
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-clipboard-check me-2"></i>ตรวจสอบและยืนยันสภาพครุภัณฑ์</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">ตรวจสอบครุภัณฑ์</li>
        </ol>
    </nav>
</div>

<?php if (empty($managedRooms)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        คุณยังไม่ได้รับมอบหมายให้ดูแลห้องปฏิบัติการใด กรุณาติดต่อเจ้าหน้าที่ดูแลระบบ
    </div>
<?php else: ?>

    <!-- Room Selection -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">เลือกห้องปฏิบัติการที่ต้องการตรวจสอบ</label>
                    <select name="room" class="form-select" onchange="this.form.submit()">
                        <option value="">-- กรุณาเลือกห้อง --</option>
                        <?php foreach ($managedRooms as $room): ?>
                            <option value="<?= htmlspecialchars($room['id']) ?>" <?= $selectedRoom === (string) $room['id'] ? 'selected' : '' ?>>
                                <?php if ($room['id'] === 'other'): ?>
                                    *** <?= htmlspecialchars($room['name']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($room['name']) ?>
                                <?php endif; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <?php if ($selectedRoom): ?>
                        <a href="my_equipment.php" class="btn btn-outline-secondary">ล้างค่า</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <?php if ($selectedRoom && !empty($equipment)): ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3 col-6">
                <div class="card bg-primary text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-pc-display me-1"></i>ครุภัณฑ์ทั้งหมด</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['total'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-success text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-check-circle me-1"></i>พร้อมใช้งาน</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['available'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-danger text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-x-circle me-1"></i>ชำรุด</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['broken'] ?></h2>
                        <p class="mb-0">รายการ</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="card bg-info text-white h-100">
                    <div class="card-body text-center">
                        <h6 class="card-title"><i class="bi bi-calendar-check me-1"></i>ตรวจสอบแล้ว</h6>
                        <h2 class="display-4 fw-bold"><?= $eqStats['inspected'] ?></h2>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-white"
                                style="width: <?= $eqStats['total'] > 0 ? ($eqStats['inspected'] / $eqStats['total']) * 100 : 0 ?>%">
                            </div>
                        </div>
                        <p class="mb-0 mt-1">
                            <?= $eqStats['total'] > 0 ? number_format(($eqStats['inspected'] / $eqStats['total']) * 100, 1) : 0 ?>%
                            ของทั้งหมด
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>รายการครุภัณฑ์ — ห้อง
                    <?= htmlspecialchars($selectedRoomName) ?>
                </h5>
                <a href="export_excel.php?room=<?= urlencode($selectedRoom) ?>" class="btn btn-success btn-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i>ส่งออก Excel
                </a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 50px;" class="hide-mobile">#</th>
                                <th>รหัสครุภัณฑ์</th>
                                <th>รายการ</th>
                                <th>สถานะ</th>
                                <th class="hide-mobile">ตรวจสอบล่าสุด</th>
                                <th style="width: 200px;">ดำเนินการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $n = 1;
                            foreach ($equipment as $eq):
                                $isInspectedThisYear = ($eq['check_date'] && date('Y', strtotime($eq['check_date'])) == $currentYear);
                                ?>
                                <tr>
                                    <td class="hide-mobile"><?= $n++ ?></td>
                                    <td>
                                        <span
                                            class="badge bg-secondary font-monospace"><?= htmlspecialchars($eq['code'] ?? '-') ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($eq['item_name']) ?></strong>
                                        <?php if ($eq['brand'] || $eq['model']): ?>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($eq['brand'] ?? '') ?>
                                                <?= htmlspecialchars($eq['model'] ?? '') ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>">
                                            <?= translateEquipmentStatus($eq['status']) ?>
                                        </span>
                                    </td>
                                    <td class="hide-mobile">
                                        <?php if ($eq['check_date']): ?>
                                            <span class="<?= $isInspectedThisYear ? 'text-success fw-bold' : 'text-muted' ?>">
                                                <i class="bi bi-check-circle me-1"></i><?= formatDateThai($eq['check_date']) ?>
                                            </span>
                                            <?php if (!$isInspectedThisYear): ?>
                                                <i class="bi bi-exclamation-circle text-warning" title="ยังไม่ได้ตรวจปีนี้"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-danger">- ไม่เคยตรวจ -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="equipment_detail.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary"
                                            title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal"
                                            data-bs-target="#checkModal" data-id="<?= $eq['id'] ?>"
                                            data-code="<?= htmlspecialchars($eq['code'] ?? '-') ?>"
                                            data-name="<?= htmlspecialchars($eq['item_name']) ?>"
                                            data-remark="<?= htmlspecialchars($eq['remark'] ?? '') ?>">
                                            <i class="bi bi-check2-circle me-1"></i>ตรวจสอบ
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Check Modal -->
        <div class="modal fade" id="checkModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form method="POST">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title"><i class="bi bi-clipboard-check me-2"></i>ยืนยันผลการตรวจสอบสภาพครุภัณฑ์
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="equipment_id" id="modalEquipmentId">

                            <div class="mb-3">
                                <label class="form-label fw-bold">ครุภัณฑ์</label>
                                <div id="modalEquipmentInfo" class="form-control-plaintext">
                                    <span class="badge bg-secondary font-monospace" id="modalCodeBadge"></span>
                                    <span class="fw-bold ms-2" id="modalNameText"></span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">ผลการตรวจสอบ</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="check_status" value="ok" id="statusOk"
                                            checked>
                                        <label class="btn btn-outline-success w-100 py-3" for="statusOk">
                                            <i class="bi bi-check-circle fs-4 d-block mb-1"></i>
                                            <span class="fw-bold">พร้อมใช้งาน</span>
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="check_status" value="broken"
                                            id="statusBroken">
                                        <label class="btn btn-outline-danger w-100 py-3" for="statusBroken">
                                            <i class="bi bi-x-circle fs-4 d-block mb-1"></i>
                                            <span class="fw-bold">ชำรุด / เสียหาย</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">หมายเหตุ</label>
                                <textarea class="form-control" name="remark" id="modalRemark" rows="3"
                                    placeholder="บันทึกหมายเหตุเพิ่มเติม..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                            <button type="submit" class="btn btn-success">
                                <i class="bi bi-check-lg me-1"></i>บันทึกผลการตรวจสอบ
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('checkModal').addEventListener('show.bs.modal', function (e) {
                const btn = e.relatedTarget;
                document.getElementById('modalEquipmentId').value = btn.dataset.id;
                document.getElementById('modalCodeBadge').textContent = btn.dataset.code;
                document.getElementById('modalNameText').textContent = btn.dataset.name;
                document.getElementById('modalRemark').value = btn.dataset.remark;
                document.getElementById('statusOk').checked = true;
            });
        </script>

    <?php elseif ($selectedRoom): ?>
        <div class="alert alert-info text-center py-5">
            <i class="bi bi-search display-1 text-muted"></i>
            <h4 class="mt-3">ไม่พบครุภัณฑ์ในห้อง <?= htmlspecialchars($selectedRoomName ?: $selectedRoom) ?></h4>
            <p>กรุณาเลือกห้องอื่น หรือติดต่อเจ้าหน้าที่เพื่อเพิ่มครุภัณฑ์</p>
        </div>
    <?php else: ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-arrow-up-circle display-1"></i>
            <h4 class="mt-3">กรุณาเลือกห้องเพื่อเริ่มการตรวจสอบ</h4>
        </div>
    <?php endif; ?>

<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>