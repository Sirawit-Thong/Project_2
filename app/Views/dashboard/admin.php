<?php
/**
 * Admin Dashboard View
 * แดชบอร์ดผู้ดูแลระบบ — ภาพรวมระบบ
 *
 * Variables from controller:
 *   $totalEquipment, $availableCount, $repairCount, $totalValue,
 *   $totalRepairs, $inProgressRepairs, $totalUsers, $statusCounts,
 *   $monthlyStats, $deptStats, $recentRepairs, $pendingUsers
 */
?>

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
                            <?= number_format($totalEquipment) ?> รายการ
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
                            <?= number_format($availableCount) ?> รายการ
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
                            <?= number_format($repairCount) ?> รายการ
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
                            <?= formatCurrency($totalValue) ?>
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
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-tools me-2 text-primary"></i>สรุปสถานะการแจ้งซ่อม</h6>
            </div>
            <div class="card-body">
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small font-weight-bold text-warning">รอดำเนินการ</span>
                        <span class="small font-weight-bold"><?= $repairCount ?></span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-warning" role="progressbar"
                            style="width: <?= ($totalRepairs > 0 ? ($repairCount / $totalRepairs * 100) : 0) ?>%">
                        </div>
                    </div>
                </div>
                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small font-weight-bold text-primary">กำลังซ่อม</span>
                        <span class="small font-weight-bold"><?= $inProgressRepairs ?></span>
                    </div>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-primary" role="progressbar"
                            style="width: <?= ($totalRepairs > 0 ? ($inProgressRepairs / $totalRepairs * 100) : 0) ?>%">
                        </div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small font-weight-bold text-secondary">รวมทั้งหมด</span>
                        <span class="small font-weight-bold"><?= $totalRepairs ?> ครั้ง</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Status Breakdown -->
    <div class="col-lg-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-box-seam me-2 text-success"></i>สถานะครุภัณฑ์</h6>
            </div>
            <div class="card-body">
                <ul class="list-group list-group-flush">
                    <?php
                    $eqStatusLabels = [
                        'available' => 'พร้อมใช้งาน',
                        'repair' => 'ส่งซ่อม',
                        'broken' => 'ซ่อมไม่ได้',
                        'pending_disposal' => 'รอจำหน่ายออก',
                    ];
                    $eqStatusCounts = array_column($statusCounts, 'count', 'status');
                    ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 pb-2">
                        <span><span class="badge rounded-circle bg-success me-2 p-1"><span class="visually-hidden">s</span></span>พร้อมใช้งาน</span>
                        <span class="badge bg-light text-dark rounded-pill"><?= $eqStatusCounts['available'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                        <span><span class="badge rounded-circle bg-warning me-2 p-1"><span class="visually-hidden">s</span></span>ส่งซ่อม</span>
                        <span class="badge bg-light text-dark rounded-pill"><?= $eqStatusCounts['repair'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                        <span><span class="badge rounded-circle bg-danger me-2 p-1"><span class="visually-hidden">s</span></span>ซ่อมไม่ได้</span>
                        <span class="badge bg-light text-dark rounded-pill"><?= $eqStatusCounts['broken'] ?? 0 ?></span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 pt-2">
                        <span><span class="badge rounded-circle bg-info me-2 p-1"><span class="visually-hidden">s</span></span>รอจำหน่ายออก</span>
                        <span class="badge bg-light text-dark rounded-pill"><?= $eqStatusCounts['pending_disposal'] ?? 0 ?></span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- User Stats -->
    <div class="col-lg-4 col-md-12">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-people me-2 text-info"></i>สมาชิกในระบบ</h6>
            </div>
            <div class="card-body d-flex flex-column justify-content-center align-items-center">
                <div class="position-relative mb-3">
                    <i class="bi bi-person-circle text-gray-300" style="font-size: 4rem;"></i>
                    <?php if ($pendingUsers > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?= $pendingUsers ?>
                            <span class="visually-hidden">pending users</span>
                        </span>
                    <?php endif; ?>
                </div>
                <h3 class="font-weight-bold text-dark"><?= number_format($totalUsers) ?></h3>
                <p class="text-muted mb-4">ผู้ใช้งานทั้งหมด</p>

                <?php if ($pendingUsers > 0): ?>
                    <a href="<?= SITE_URL ?>/users/pending" class="btn btn-warning w-100 rounded-pill">
                        <i class="bi bi-person-check me-2"></i>อนุมัติสมาชิก (<?= $pendingUsers ?>)
                    </a>
                <?php else: ?>
                    <a href="<?= SITE_URL ?>/users" class="btn btn-outline-primary w-100 rounded-pill">
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
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-graph-up me-2"></i>สถิติการแจ้งซ่อมรายเดือน</h6>
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
        <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-clock-history me-2 text-secondary"></i>การแจ้งซ่อมล่าสุด</h6>
        <a href="<?= SITE_URL ?>/repairs" class="btn btn-sm btn-outline-primary rounded-pill px-3">
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
                                <td class="ps-4">
                                    <a href="<?= SITE_URL ?>/repairs/<?= $repair['id'] ?>" class="fw-bold text-decoration-none">
                                        #<?= $repair['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($repair['item_name']) ?></span>
                                        <span class="small text-muted"><?= htmlspecialchars($repair['equipment_code'] ?? $repair['code'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td class="hide-mobile">
                                    <?php if (!empty($repair['firstname'])): ?>
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
                                <td><span class="text-muted"><?= mb_substr(htmlspecialchars($repair['issue']), 0, 40) ?>...</span></td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= getStatusBadgeClass($repair['status']) ?>">
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

<script>
Chart.defaults.font.family = "'Sarabun', 'Nunito', sans-serif";
Chart.defaults.color = '#858796';

<?php if (!empty($monthlyStats)): ?>
new Chart(document.getElementById('repairChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($monthlyStats, 'label')) ?>,
        datasets: [{
            label: 'จำนวนแจ้งซ่อม',
            data: <?= json_encode(array_map('intval', array_column($monthlyStats, 'count'))) ?>,
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
                    label: function(ctx) {
                        return 'แจ้งซ่อม: ' + ctx.parsed.y + ' รายการ';
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
                grid: { display: false, drawBorder: false }
            }
        }
    }
});
<?php endif; ?>

<?php if (!empty($deptStats)): ?>
new Chart(document.getElementById('deptChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_column($deptStats, 'name')) ?>,
        datasets: [{
            data: <?= json_encode(array_map('intval', array_column($deptStats, 'count'))) ?>,
            backgroundColor: ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#858796'],
            hoverBorderColor: "rgba(234, 236, 244, 1)"
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
                padding: 12
            }
        },
        cutout: '70%'
    }
});
<?php endif; ?>
</script>
