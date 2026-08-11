<?php
/**
 * Annual Inspection
 * ระบบตรวจนับครุภัณฑ์ประจำปี
 */
$pageTitle = 'ระบบตรวจนับครุภัณฑ์';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();
$roomId = $_GET['room_id'] ?? null;
$currentYear = date('Y');

// Handle Saving
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_inspection'])) {
    $inspectedIds = $_POST['inspected'] ?? [];
    $statuses = $_POST['status'] ?? [];
    $remarks = $_POST['remark'] ?? [];
    $allIds = $_POST['item_ids'] ?? [];

    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare("UPDATE equipment SET status = ?, remark = ?, check_date = ? WHERE id = ?");
        $stmtNoCheck = $pdo->prepare("UPDATE equipment SET status = ?, remark = ? WHERE id = ?");

        foreach ($allIds as $id) {
            $status = $statuses[$id] ?? 'available';
            $remark = $remarks[$id] ?? '';
            // Check if this item is marked as inspected
            $isInspected = in_array($id, $inspectedIds);

            if ($isInspected) {
                // Update check_date to today
                $stmt->execute([$status, $remark, date('Y-m-d'), $id]);
            } else {
                // Don't update check_date, just status/remark
                // (In case they changed status but didn't mark as "Annual Check" for some reason, 
                // or simply un-checked mistakenly but we still want to save the status/remark changes)
                $stmtNoCheck->execute([$status, $remark, $id]);
            }
        }
        $pdo->commit();
        setFlash('success', 'บันทึกข้อมูลการตรวจนับเรียบร้อยแล้ว');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
    }
    // Redirect to prevent resubmission
    redirect("inspection.php?room_id=" . $roomId);
}

// Fetch Rooms for dropdown
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY name")->fetchAll();

// Fetch Equipment if room selected
$equipment = [];
$stats = [
    'total' => 0,
    'inspected' => 0,
    'pending' => 0,
    'status_counts' => ['available' => 0, 'repair' => 0, 'broken' => 0, 'disposed' => 0, 'pending_disposal' => 0]
];

if ($roomId) {
    $stmt = $pdo->prepare("
        SELECT e.*, i.name as item_name, i.model, i.brand, i.unit 
        FROM equipment e 
        JOIN items i ON e.item_id = i.id 
        WHERE e.room_id = ? 
        ORDER BY i.name, e.code
    ");
    $stmt->execute([$roomId]);
    $equipment = $stmt->fetchAll();

    foreach ($equipment as $item) {
        $stats['total']++;

        // Check if inspected this year (check_date is in current year)
        $isInspected = false;
        if ($item['check_date'] && date('Y', strtotime($item['check_date'])) == $currentYear) {
            $stats['inspected']++;
            $isInspected = true;
        } else {
            $stats['pending']++;
        }

        // Count statuses
        if (isset($stats['status_counts'][$item['status']])) {
            $stats['status_counts'][$item['status']]++;
        }
    }
}
?>

<div class="page-header d-flex justify-content-between align-items-center">
    <h1><i class="bi bi-clipboard-check me-2"></i>ระบบตรวจนับครุภัณฑ์ประจำปี</h1>
</div>

<!-- Room Selection -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">เลือกห้องเพื่อทำการตรวจนับ</label>
                <select name="room_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- กรุณาเลือกห้อง --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $roomId == $r['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <?php if ($roomId): ?>
                    <a href="inspection.php" class="btn btn-outline-secondary">ล้างค่า</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($roomId): ?>
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-primary text-white h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">จำนวนครุภัณฑ์ทั้งหมด</h6>
                    <h2 class="display-4 fw-bold"><?= $stats['total'] ?></h2>
                    <p class="mb-0">รายการ</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">ตรวจนับแล้ว (ปี <?= $currentYear ?>)</h6>
                    <h2 class="display-4 fw-bold"><?= $stats['inspected'] ?></h2>
                    <div class="progress mt-2" style="height: 5px;">
                        <div class="progress-bar bg-white"
                            style="width: <?= $stats['total'] > 0 ? ($stats['inspected'] / $stats['total']) * 100 : 0 ?>%">
                        </div>
                    </div>
                    <p class="mb-0 mt-1">
                        <?= $stats['total'] > 0 ? number_format(($stats['inspected'] / $stats['total']) * 100, 1) : 0 ?>%
                        เรียบร้อย
                    </p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-warning text-dark h-100">
                <div class="card-body text-center">
                    <h6 class="card-title">คงเหลือยังไม่ตรวจนับ</h6>
                    <h2 class="display-4 fw-bold"><?= $stats['pending'] ?></h2>
                    <p class="mb-0">รายการ</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Inspection Form -->
    <form method="POST" id="inspectionForm">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center sticky-top bg-white border-bottom">
                <h5 class="mb-0"><i class="bi bi-list-check me-2"></i>รายการตรวจนับ</h5>
                <div>
                    <button type="button" class="btn btn-outline-primary btn-sm me-2" onclick="checkAll()">
                        <i class="bi bi-check-all me-1"></i>เลือกทั้งหมด
                    </button>
                    <button type="submit" name="save_inspection" class="btn btn-success">
                        <i class="bi bi-save me-1"></i>บันทึกผลการตรวจนับ
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
                            <tr>
                                <th width="50" class="text-center">พบ</th>
                                <th>รหัสครุภัณฑ์</th>
                                <th>รายการ</th>
                                <th>สถานะปัจจุบัน</th>
                                <th>วันที่ตรวจล่าสุด</th>
                                <th>เปลี่ยนสถานะ</th>
                                <th>หมายเหตุ (ความผิดปกติ)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($equipment as $item):
                                $isInspectedThisYear = ($item['check_date'] && date('Y', strtotime($item['check_date'])) == $currentYear);
                                $rowClass = $isInspectedThisYear ? 'table-success' : ''; // Highlight inspected rows lightly? Maybe too strong. Let's strictly use icons/dates.
                                // Actually, let's use a border indicator or just relies on the checkbox.
                                // Pre-check if inspected this year
                                ?>
                                <tr class="<?= $isInspectedThisYear ? 'bg-light-success' : '' ?>">
                                    <td class="text-center">
                                        <input type="hidden" name="item_ids[]" value="<?= $item['id'] ?>">
                                        <input type="checkbox" name="inspected[]" value="<?= $item['id'] ?>"
                                            class="form-check-input form-check-lg" <?= $isInspectedThisYear ? 'checked' : '' ?>>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-secondary font-monospace"><?= htmlspecialchars($item['code']) ?></span>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($item['item_name']) ?></strong>
                                        <?php if ($item['brand'] || $item['model']): ?>
                                            <div class="text-muted small">
                                                <?= htmlspecialchars($item['brand']) ?>             <?= htmlspecialchars($item['model']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= getStatusBadgeClass($item['status']) ?>">
                                            <?= translateEquipmentStatus($item['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($item['check_date']): ?>
                                            <span class="<?= $isInspectedThisYear ? 'text-success fw-bold' : 'text-muted' ?>">
                                                <?= date('d/m/Y', strtotime($item['check_date'])) ?>
                                            </span>
                                            <?php if (!$isInspectedThisYear): ?>
                                                <i class="bi bi-exclamation-circle text-warning" title="ยังไม่ได้ตรวจปีนี้"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-danger">- ไม่เคยตรวจ -</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select name="status[<?= $item['id'] ?>]"
                                            class="form-select form-select-sm status-select"
                                            data-original="<?= $item['status'] ?>">
                                            <option value="available" <?= $item['status'] == 'available' ? 'selected' : '' ?>>
                                                พร้อมใช้งาน</option>
                                            <option value="repair" <?= $item['status'] == 'repair' ? 'selected' : '' ?>>ส่งซ่อม
                                            </option>
                                            <option value="broken" <?= $item['status'] == 'broken' ? 'selected' : '' ?>>ซ่อมไม่ได้
                                            </option>
                                            <option value="pending_disposal" <?= $item['status'] == 'pending_disposal' ? 'selected' : '' ?>>รอจำหน่ายออก</option>
                                            <option value="disposed" <?= $item['status'] == 'disposed' ? 'selected' : '' ?>>
                                                จำหน่ายออก</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="remark[<?= $item['id'] ?>]"
                                            class="form-control form-control-sm"
                                            value="<?= htmlspecialchars($item['remark'] ?? '') ?>"
                                            placeholder="ระบุอาการ/หมายเหตุ">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white py-3">
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <button type="submit" name="save_inspection" class="btn btn-success btn-lg px-5">
                        <i class="bi bi-save me-2"></i>ยืนยันการบันทึกข้อมูล
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        // Feature: Auto-check checkbox when status or remark changes
        document.addEventListener('DOMContentLoaded', function () {
            const inputs = document.querySelectorAll('.status-select, input[name^="remark"]');
            inputs.forEach(input => {
                input.addEventListener('change', function () {
                    // Find the parent row
                    const row = this.closest('tr');
                    // Find the checkbox in this row
                    const checkbox = row.querySelector('input[name="inspected[]"]');
                    // Check it
                    if (checkbox) checkbox.checked = true;
                });
            });
        });

        function checkAll() {
            const checkboxes = document.querySelectorAll('input[name="inspected[]"]');
            const allChecked = Array.from(checkboxes).every(c => c.checked);
            checkboxes.forEach(c => c.checked = !allChecked);
        }
    </script>

<?php elseif (isset($_GET['room_id'])): ?>
    <div class="alert alert-info text-center py-5">
        <i class="bi bi-search display-1 text-muted"></i>
        <h4 class="mt-3">ไม่พบข้อมูลครุภัณฑ์ในห้องนี้</h4>
        <p>กรุณาเลือกห้องอื่น หรือเพิ่มครุภัณฑ์ลงในห้องนี้ก่อน</p>
    </div>
<?php else: ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-arrow-up-circle display-1"></i>
        <h4 class="mt-3">กรุณาเลือกห้องเพื่อเริ่มการตรวจนับ</h4>
    </div>
<?php endif; ?>

<?php require_once '../includes/footer.php'; ?>