<?php
/**
 * Admin Dashboard
 * แดชบอร์ดผู้ดูแลระบบ
 */

$pageTitle = 'ภาพรวมระบบ';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Get statistics
$stats = [];

// Total equipment
$stats['total_equipment'] = $pdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();

// Equipment by status
$stats['available'] = $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'available'")->fetchColumn();
$stats['repair'] = $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'repair'")->fetchColumn();
$stats['broken'] = $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'broken'")->fetchColumn();
$stats['pending_disposal'] = $pdo->query("SELECT COUNT(*) FROM equipment WHERE status = 'pending_disposal'")->fetchColumn();

// Total repairs
$stats['total_repairs'] = $pdo->query("SELECT COUNT(*) FROM repair")->fetchColumn();
$stats['pending_repairs'] = $pdo->query("SELECT COUNT(*) FROM repair WHERE status = 'pending'")->fetchColumn();
$stats['in_progress_repairs'] = $pdo->query("SELECT COUNT(*) FROM repair WHERE status = 'in_progress'")->fetchColumn();

// Total users
$stats['total_users'] = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$stats['pending_users'] = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();

// Total value
$sqlTotalValue = "
SELECT 
    (SELECT COALESCE(SUM(price), 0) FROM equipment WHERE status != 'disposed') as eq_val,
    (SELECT COALESCE(SUM(i.price * (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id AND e.status != 'disposed')), 0) FROM items i) as item_val,
    (SELECT COALESCE(SUM(s.price), 0) FROM sets s WHERE s.price > 0 AND EXISTS (SELECT 1 FROM items i JOIN equipment e ON i.id = e.item_id WHERE i.set_id = s.id AND e.status != 'disposed')) as set_val
";
$val_row = $pdo->query($sqlTotalValue)->fetch();
$stats['total_value'] = $val_row['eq_val'] + $val_row['item_val'] + $val_row['set_val'];

// Recent repairs
$recentRepairs = $pdo->query("
    SELECT r.*, e.code as equipment_code, i.name as item_name,
           u.firstname, u.lastname
    FROM repair r
    JOIN equipment e ON r.equipment_id = e.id
    JOIN items i ON e.item_id = i.id
    LEFT JOIN users u ON r.user_id = u.id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

// Monthly repair stats (last 6 months)
$monthlyRepairs = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month,
           DATE_FORMAT(created_at, '%m/%Y') as label,
           COUNT(*) as count
    FROM repair
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
")->fetchAll();

// Equipment by department
$deptStats = $pdo->query("
    SELECT d.name, COUNT(e.id) as count
    FROM dept d
    LEFT JOIN sets s ON d.id = s.dept_id
    LEFT JOIN items i ON s.id = i.set_id
    LEFT JOIN equipment e ON i.id = e.item_id
    GROUP BY d.id, d.name
")->fetchAll();
?>

<div class="container-fluid px-0">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800"><i class="bi bi-speedometer2 me-2 text-primary"></i>ภาพรวมระบบ</h1>
            <p class="text-muted mb-0">ยินดีต้อนรับสู่ระบบจัดการครุภัณฑ์และแจ้งซ่อม</p>
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
        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ครุภัณฑ์ทั้งหมด</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_equipment']) ?> รายการ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-pc-display fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-success">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">พร้อมใช้งาน</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['available']) ?> รายการ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-check-circle fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">แจ้งซ่อม
                                (รอดำเนินการ)</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['pending_repairs']) ?> รายการ
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-wrench fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-info">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">มูลค่าทรัพย์สินรวม</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($stats['total_value']) ?> บาท
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-currency-exchange fa-2x text-gray-300 fs-1 opacity-25"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Secondary Stats & Charts Row -->
    <div class="row g-4 mb-4">
        <!-- Repair Status Breakdown -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i
                            class="bi bi-tools me-2 text-primary"></i>สรุปสถานะการแจ้งซ่อม</h6>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small font-weight-bold text-warning">รอดำเนินการ</span>
                            <span class="small font-weight-bold"><?= $stats['pending_repairs'] ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-warning" role="progressbar"
                                style="width: <?= ($stats['total_repairs'] > 0 ? ($stats['pending_repairs'] / $stats['total_repairs'] * 100) : 0) ?>%">
                            </div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small font-weight-bold text-primary">กำลังซ่อม</span>
                            <span class="small font-weight-bold"><?= $stats['in_progress_repairs'] ?></span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-primary" role="progressbar"
                                style="width: <?= ($stats['total_repairs'] > 0 ? ($stats['in_progress_repairs'] / $stats['total_repairs'] * 100) : 0) ?>%">
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="small font-weight-bold text-secondary">รวมทั้งหมด</span>
                            <span class="small font-weight-bold"><?= $stats['total_repairs'] ?> ครั้ง</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Equipment Status Breakdown -->
        <div class="col-lg-4 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i
                            class="bi bi-box-seam me-2 text-success"></i>สถานะครุภัณฑ์</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 pb-2">
                            <span><span class="badge rounded-circle bg-success me-2 p-1"><span
                                        class="visually-hidden">s</span></span>พร้อมใช้งาน</span>
                            <span class="badge bg-light text-dark rounded-pill"><?= $stats['available'] ?></span>
                        </li>
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                            <span><span class="badge rounded-circle bg-warning me-2 p-1"><span
                                        class="visually-hidden">s</span></span>ส่งซ่อม</span>
                            <span class="badge bg-light text-dark rounded-pill"><?= $stats['repair'] ?></span>
                        </li>
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                            <span><span class="badge rounded-circle bg-danger me-2 p-1"><span
                                        class="visually-hidden">s</span></span>ซ่อมไม่ได้</span>
                            <span class="badge bg-light text-dark rounded-pill"><?= $stats['broken'] ?></span>
                        </li>
                        <li
                            class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 pt-2">
                            <span><span class="badge rounded-circle bg-info me-2 p-1"><span
                                        class="visually-hidden">s</span></span>รอจำหน่ายออก</span>
                            <span class="badge bg-light text-dark rounded-pill"><?= $stats['pending_disposal'] ?></span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- User Stats -->
        <div class="col-lg-4 col-md-12">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-people me-2 text-info"></i>สมาชิกในระบบ
                    </h6>
                </div>
                <div class="card-body d-flex flex-column justify-content-center align-items-center">
                    <div class="position-relative mb-3">
                        <i class="bi bi-person-circle text-gray-300" style="font-size: 4rem;"></i>
                        <?php if ($stats['pending_users'] > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?= $stats['pending_users'] ?>
                                <span class="visually-hidden">pending users</span>
                            </span>
                        <?php endif; ?>
                    </div>
                    <h3 class="font-weight-bold text-dark"><?= $stats['total_users'] ?></h3>
                    <p class="text-muted mb-4">ผู้ใช้งานทั้งหมด</p>

                    <?php if ($stats['pending_users'] > 0): ?>
                        <a href="pending_users.php" class="btn btn-warning w-100 rounded-pill">
                            <i class="bi bi-person-check me-2"></i>อนุมัติสมาชิก (<?= $stats['pending_users'] ?>)
                        </a>
                    <?php else: ?>
                        <a href="users.php" class="btn btn-outline-primary w-100 rounded-pill">
                            <i class="bi bi-people me-2"></i>จัดการสมาชิก
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row g-4 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i
                            class="bi bi-graph-up me-2"></i>สถิติการแจ้งซ่อมรายเดือน</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="repairChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h6 class="m-0 font-weight-bold text-info"><i class="bi bi-pie-chart me-2"></i>ครุภัณฑ์ตามสาขา</h6>
                </div>
                <div class="card-body">
                    <div class="chart-container" style="height: 300px;">
                        <canvas id="deptChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Repairs -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-dark"><i
                    class="bi bi-clock-history me-2 text-secondary"></i>การแจ้งซ่อมล่าสุด</h6>
            <a href="repairs.php" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                ดูทั้งหมด <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
        <div class="card-body p-0">
            <?php if (empty($recentRepairs)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox display-4 mb-3 d-block text-gray-300"></i>
                    <p>ไม่พบข้อมูลการแจ้งซ่อมในช่วงนี้</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">รหัส</th>
                                <th>ครุภัณฑ์</th>
                                <th class="hide-mobile">ผู้แจ้ง</th>
                                <th>อาการ</th>
                                <th>สถานะ</th>
                                <th class="hide-mobile pe-4 text-end">วันที่แจ้ง</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRepairs as $repair): ?>
                                <tr>
                                    <td class="ps-4"><a href="repair_detail.php?id=<?= $repair['id'] ?>"
                                            class="fw-bold text-decoration-none">#<?= $repair['id'] ?></a></td>
                                    <td>
                                        <div class="d-flex flex-column">
                                            <span
                                                class="fw-medium text-dark"><?= htmlspecialchars($repair['item_name']) ?></span>
                                            <span
                                                class="small text-muted"><?= htmlspecialchars($repair['equipment_code']) ?></span>
                                        </div>
                                    </td>
                                    <td class="hide-mobile">
                                        <?php if ($repair['firstname']): ?>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-primary text-white me-2"
                                                    style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size: 12px;">
                                                    <?= mb_substr($repair['firstname'], 0, 1) . mb_substr($repair['lastname'], 0, 1) ?>
                                                </div>
                                                <span><?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="d-flex align-items-center">
                                                <div class="avatar-circle bg-secondary text-white me-2"
                                                    style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size: 12px;">
                                                    ?
                                                </div>
                                                <span class="text-muted">ผู้ใช้ถูกลบ</span>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><span
                                            class="text-muted"><?= mb_substr(htmlspecialchars($repair['issue']), 0, 40) ?>...</span>
                                    </td>
                                    <td>
                                        <?php
                                        // Get correct class logic
                                        $statusClass = 'secondary';
                                        if ($repair['status'] == 'pending')
                                            $statusClass = 'warning';
                                        elseif ($repair['status'] == 'in_progress')
                                            $statusClass = 'primary';
                                        elseif ($repair['status'] == 'completed')
                                            $statusClass = 'success';
                                        elseif ($repair['status'] == 'cannot_fix')
                                            $statusClass = 'danger';
                                        ?>
                                        <span class="badge rounded-pill bg-<?= $statusClass ?>">
                                            <?= translateRepairStatus($repair['status']) ?>
                                        </span>
                                    </td>
                                    <td class="hide-mobile pe-4 text-end text-muted small">
                                        <?= formatDateTimeThai($repair['created_at']) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Styling logic for Charts
    Chart.defaults.font.family = "'Sarabun', 'Nunito', sans-serif";
    Chart.defaults.color = '#858796';

    // Monthly Repair Chart
    const repairCtx = document.getElementById('repairChart').getContext('2d');
    new Chart(repairCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($monthlyRepairs, 'label')) ?>,
            datasets: [{
                label: 'จำนวนแจ้งซ่อม',
                data: <?= json_encode(array_column($monthlyRepairs, 'count')) ?>,
                backgroundColor: 'rgba(78, 115, 223, 0.8)',
                hoverBackgroundColor: 'rgba(78, 115, 223, 1)',
                borderColor: 'rgba(78, 115, 223, 1)',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    titleColor: '#6e707e',
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function (context) {
                            return 'แจ้งซ่อม: ' + context.parsed.y + ' รายการ';
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [2], drawBorder: false, color: "rgb(234, 236, 244)" },
                    ticks: { padding: 10, stepSize: 1 }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                }
            }
        }
    });

    // Department Chart
    const deptCtx = document.getElementById('deptChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode(array_column($deptStats, 'name')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($deptStats, 'count')) ?>,
                backgroundColor: [
                    '#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'
                ],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { boxWidth: 12, usePointStyle: true } },
                tooltip: {
                    backgroundColor: "rgb(255,255,255)",
                    bodyColor: "#858796",
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 12,
                }
            },
            cutout: '70%',
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>