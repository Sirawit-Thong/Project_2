<?php
$eqStats = $eqStats ?? [];
$totalValue = $totalValue ?? 0;
$monthlyData = $monthlyData ?? [];
$topBroken = $topBroken ?? [];
$deptStats = $deptStats ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-dark"><i class="bi bi-graph-up me-2 text-primary"></i>รายงานและสถิติ</h1>
        <p class="text-muted mb-0">ภาพรวมข้อมูลครุภัณฑ์และการแจ้งซ่อมบำรุง</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-primary shadow-sm dropdown-toggle" type="button" id="exportDropdown"
            data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-file-earmark-arrow-down me-2"></i>ส่งออกรายงาน
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="exportDropdown">
            <li>
                <h6 class="dropdown-header">เลือกประเภทข้อมูล</h6>
            </li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/equipment"><i
                        class="bi bi-pc-display me-2 text-primary"></i>รายการครุภัณฑ์ทั้งหมด</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/repairs"><i
                        class="bi bi-tools me-2 text-warning"></i>ประวัติการแจ้งซ่อม</a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/users"><i
                        class="bi bi-people me-2 text-info"></i>รายชื่อผู้ใช้งาน</a></li>
        </ul>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-primary">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col">
                        <div class="small text-primary text-uppercase mb-1">ครุภัณฑ์ทั้งหมด</div>
                        <div class="h5 mb-0 fw-bold text-dark">
                            <?= number_format(array_sum($eqStats)) ?> รายการ
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-box-seam fs-1 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-success">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col">
                        <div class="small text-success text-uppercase mb-1">พร้อมใช้งาน</div>
                        <div class="h5 mb-0 fw-bold text-dark">
                            <?= number_format($eqStats['available'] ?? 0) ?> รายการ
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-check-circle fs-1 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-warning">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col">
                        <div class="small text-warning text-uppercase mb-1">สถานะไม่ปกติ</div>
                        <div class="h5 mb-0 fw-bold text-dark">
                            <?= number_format(($eqStats['repair'] ?? 0) + ($eqStats['broken'] ?? 0)) ?> รายการ
                        </div>
                        <small class="text-muted">(ส่งซ่อม/ซ่อมไม่ได้)</small>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-exclamation-triangle fs-1 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-info">
            <div class="card-body">
                <div class="row g-0 align-items-center">
                    <div class="col">
                        <div class="small text-info text-uppercase mb-1">มูลค่าทรัพย์สินรวม</div>
                        <div class="h5 mb-0 fw-bold text-dark">
                            <?= number_format($totalValue / 1000000, 2) ?> ล้านบาท
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-currency-dollar fs-1 text-muted opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Content -->
<div class="row g-4 mb-4">
    <!-- Monthly Repairs Chart -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i
                        class="bi bi-bar-chart-line me-2"></i>สถิติการแจ้งซ่อมย้อนหลัง 12 เดือน</h6>
            </div>
            <div class="card-body">
                <div class="chart-area" style="height: 350px;">
                    <canvas id="monthlyChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Equipment Status Chart -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-primary"><i
                        class="bi bi-pie-chart me-2"></i>สัดส่วนสถานะครุภัณฑ์</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie pt-4 pb-2" style="height: 280px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div class="mt-4 text-center small">
                    <span class="me-2"><i class="bi bi-circle-fill text-success"></i> พร้อมใช้งาน</span>
                    <span class="me-2"><i class="bi bi-circle-fill text-warning"></i> ส่งซ่อม</span>
                    <span class="me-2"><i class="bi bi-circle-fill text-danger"></i> ซ่อมไม่ได้</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Stats Rows -->
<div class="row g-4">
    <!-- Top Broken Items -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-danger"><i class="bi bi-tools me-2"></i>5
                    อันดับครุภัณฑ์แจ้งซ่อมบ่อย</h6>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($topBroken as $i => $item): ?>
                        <li class="list-group-item px-4 py-3 border-bottom">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-bold text-dark"><?= ($i + 1) ?>.
                                    <?= htmlspecialchars($item['name']) ?></span>
                                <span class="badge bg-danger rounded-pill"><?= $item['cnt'] ?> ครั้ง</span>
                            </div>
                            <div class="progress" style="height: 6px;">
                                <div class="progress-bar bg-danger" role="progressbar"
                                    style="width: <?= min(100, $item['cnt'] * 5) ?>%"></div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                    <?php if (empty($topBroken)): ?>
                        <li class="list-group-item text-center text-muted py-4">ยังไม่มีข้อมูลการแจ้งซ่อม</li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- Department Stats -->
    <div class="col-lg-6">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h6 class="m-0 fw-bold text-info"><i
                        class="bi bi-building me-2"></i>มูลค่าครุภัณฑ์ตามสาขาวิชา</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">สาขาวิชา</th>
                                <th class="text-center">จำนวน (ชิ้น)</th>
                                <th class="text-end pe-4">มูลค่ารวม</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($deptStats as $d): ?>
                                <tr>
                                    <td class="ps-4 fw-medium"><?= htmlspecialchars($d['name']) ?></td>
                                    <td class="text-center"><span
                                            class="badge bg-secondary"><?= number_format($d['c']) ?></span></td>
                                    <td class="text-end pe-4 text-success fw-bold"><?= number_format($d['v']) ?> ฿</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Styling logic for Charts
    Chart.defaults.font.family = "'Prompt', sans-serif";
    Chart.defaults.color = '#858796';

    // Monthly Chart
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($monthlyData, 'month')) ?>,
            datasets: [{
                label: 'จำนวนแจ้งซ่อม',
                data: <?= json_encode(array_column($monthlyData, 'count')) ?>,
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
                    ticks: { padding: 10 }
                },
                x: {
                    grid: { display: false, drawBorder: false },
                    ticks: { maxTicksLimit: 12 }
                }
            }
        }
    });

    // Status Chart
    new Chart(document.getElementById('statusChart'), {
        type: 'doughnut',
        data: {
            labels: ['พร้อมใช้งาน', 'ส่งซ่อม', 'ซ่อมไม่ได้', 'รอจำหน่ายออก'],
            datasets: [{
                data: [<?= $eqStats['available'] ?? 0 ?>, <?= $eqStats['repair'] ?? 0 ?>, <?= $eqStats['broken'] ?? 0 ?>, <?= $eqStats['pending_disposal'] ?? 0 ?>],
                backgroundColor: ['#1cc88a', '#f6c23e', '#e74a3b', '#36b9cc'],
                hoverBorderColor: "rgba(234, 236, 244, 1)",
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
                    borderColor: '#dddfeb',
                    borderWidth: 1,
                    padding: 12,
                }
            },
            cutout: '70%',
        }
    });
</script>
