<?php
/**
 * Teacher Dashboard
 */
$pageTitle = 'ระบบบริหารจัดการครุภัณฑ์สำหรับอาจารย์';
require_once '../includes/header.php';
requireRole('teacher');

$pdo = getDB();
$userId = getCurrentUserId();
$user = getCurrentUser();

// Get statistics
$myRepairs = $pdo->prepare("SELECT COUNT(*) FROM repair WHERE user_id = ?");
$myRepairs->execute([$userId]);
$totalRepairs = $myRepairs->fetchColumn();

$pendingRepairs = $pdo->prepare("SELECT COUNT(*) FROM repair WHERE user_id = ? AND status = 'pending'");
$pendingRepairs->execute([$userId]);
$pendingCount = $pendingRepairs->fetchColumn();

// Recent repairs
$stmt = $pdo->prepare("SELECT r.*, e.code, i.name as item_name FROM repair r 
    JOIN equipment e ON r.equipment_id = e.id JOIN items i ON e.item_id = i.id 
    WHERE r.user_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$userId]);
$recentRepairs = $stmt->fetchAll();

// Managed rooms
$stmt = $pdo->prepare("SELECT r.name, COUNT(e.id) as eq_count FROM room_managers rm 
    JOIN rooms r ON rm.room_id = r.id LEFT JOIN equipment e ON r.id = e.room_id 
    WHERE rm.user_id = ? GROUP BY r.id");
$stmt->execute([$userId]);
$managedRooms = $stmt->fetchAll();
?>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800"><i class="bi bi-speedometer2 me-2 text-primary"></i>แดชบอร์ดอาจารย์</h1>
            <p class="text-muted mb-0">สวัสดี, อาจารย์ <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></p>
        </div>
        <div>
            <div class="bg-white px-3 py-2 rounded shadow-sm border">
                <i class="bi bi-calendar3 me-2 text-primary"></i>
                <span class="text-dark fw-medium"><?= formatDateThai(date('Y-m-d')) ?></span>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row g-4 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">รายการแจ้งซ่อมทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($totalRepairs) ?> รายการ</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-tools fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">รอดำเนินการ</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= number_format($pendingCount) ?> รายการ</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-hourglass-split fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">ห้องที่ดูแลรับผิดชอบ</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($managedRooms) ?> ห้อง</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-door-open fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Recent Repairs -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>รายการแจ้งซ่อมล่าสุด</h6>
                    <a href="my_repairs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                        ดูทั้งหมด <i class="bi bi-arrow-right ms-1"></i>
                    </a>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recentRepairs)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-inbox display-4 mb-3 d-block text-gray-300"></i>
                            <p>ยังไม่มีการแจ้งซ่อม</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">ครุภัณฑ์</th>
                                        <th>อาการ</th>
                                        <th>สถานะ</th>
                                        <th class="hide-mobile pe-4 text-end">วันที่แจ้ง</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentRepairs as $r): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="d-flex flex-column">
                                                    <span class="fw-medium text-dark"><?= htmlspecialchars($r['item_name']) ?></span>
                                                    <span class="small text-muted"><?= htmlspecialchars($r['code']) ?></span>
                                                </div>
                                            </td>
                                            <td><span class="text-muted"><?= mb_substr(htmlspecialchars($r['issue']), 0, 40) ?>...</span></td>
                                            <td>
                                                <span class="badge rounded-pill bg-<?= getStatusBadgeClass($r['status']) ?>">
                                                    <?= translateRepairStatus($r['status']) ?>
                                                </span>
                                            </td>
                                            <td class="hide-mobile pe-4 text-end text-muted small"><?= formatDateThai($r['created_at']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Sidebar / Actions -->
        <div class="col-lg-4">
            <!-- Quick Action -->
            <div class="card border-0 shadow-sm mb-4 bg-primary text-white overflow-hidden position-relative">
                <div class="card-body text-center p-4 position-relative" style="z-index: 1;">
                    <h5 class="fw-bold mb-3">พบปัญหาครุภัณฑ์?</h5>
                    <p class="mb-4 text-white-50">แจ้งซ่อมได้ทันที รวดเร็ว และติดตามสถานะได้ตลอดเวลา</p>
                    <a href="repair_submit.php" class="btn btn-light text-primary fw-bold rounded-pill px-4 shadow-sm">
                        <i class="bi bi-wrench me-2"></i>แจ้งซ่อมใหม่
                    </a>
                </div>
                <!-- Decorative Icon -->
                <i class="bi bi-tools position-absolute" style="font-size: 8rem; opacity: 0.1; right: -20px; bottom: -20px; color: white;"></i>
            </div>

            <!-- Managed Rooms -->
            <?php if (!empty($managedRooms)): ?>
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white py-3">
                        <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-door-open me-2 text-success"></i>ห้องปฏิบัติการที่ดูแล</h6>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($managedRooms as $room): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-3 px-4 border-0 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-light rounded p-2 me-3 text-success">
                                            <i class="bi bi-display"></i>
                                        </div>
                                        <span class="fw-medium">ห้อง <?= htmlspecialchars($room['name']) ?></span>
                                    </div>
                                    <span class="badge bg-light text-dark rounded-pill border"><?= $room['eq_count'] ?> ครุภัณฑ์</span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>