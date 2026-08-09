<?php
$monthlyStats = $monthlyStats ?? [];
$statusCounts = $statusCounts ?? [];
$topBroken = $topBroken ?? [];
$deptStats = $deptStats ?? [];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-graph-up"></i> รายงานสรุป</h4>
    <div class="dropdown">
        <button class="btn btn-outline-primary dropdown-toggle" data-bs-toggle="dropdown">
            <i class="bi bi-download"></i> ส่งออกรายงาน
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li>
                <a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/equipment">
                    <i class="bi bi-box-seam"></i> รายงานอุปกรณ์
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/repairs">
                    <i class="bi bi-tools"></i> รายงานซ่อมแซม
                </a>
            </li>
            <li>
                <a class="dropdown-item" href="<?= SITE_URL ?>/reports/export/users">
                    <i class="bi bi-people"></i> รายงานผู้ใช้
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Stats Cards -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card bg-primary text-white shadow">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-box-seam" style="font-size: 2.5rem;"></i>
                </div>
                <div>
                    <div class="opacity-75">อุปกรณ์ทั้งหมด</div>
                    <div class="fs-3 fw-bold"><?= number_format($totalEquipment ?? 0) ?> ชิ้น</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card bg-success text-white shadow">
            <div class="card-body d-flex align-items-center">
                <div class="me-3">
                    <i class="bi bi-cash-stack" style="font-size: 2.5rem;"></i>
                </div>
                <div>
                    <div class="opacity-75">มูลค่ารวม</div>
                    <div class="fs-3 fw-bold">฿<?= number_format($totalValue ?? 0, 2) ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-bar-chart-line"></i> สถิติการซ่อมรายเดือน</h6>
            </div>
            <div class="card-body">
                <canvas id="monthlyChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-pie-chart"></i> สถานะอุปกรณ์</h6>
            </div>
            <div class="card-body d-flex justify-content-center">
                <canvas id="statusChart" width="280" height="280"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-exclamation-triangle text-warning"></i> อุปกรณ์ที่เสียบ่อย</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>ชื่ออุปกรณ์</th>
                                <th width="100" class="text-center">ครั้งที่เสีย</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($topBroken)): ?>
                                <?php foreach ($topBroken as $i => $item): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-danger"><?= $item['break_count'] ?? $item['count'] ?? 0 ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-3">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-light">
                <h6 class="mb-0"><i class="bi bi-building"></i> มูลค่าอุปกรณ์ตามแผนก</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th width="50">#</th>
                                <th>แผนก</th>
                                <th class="text-center">จำนวน</th>
                                <th class="text-end">มูลค่า</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($deptStats)): ?>
                                <?php foreach ($deptStats as $i => $dept): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= htmlspecialchars($dept['name'] ?? $dept['dept_name'] ?? '') ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-info"><?= $dept['equipment_count'] ?? $dept['count'] ?? 0 ?></span>
                                        </td>
                                        <td class="text-end">฿<?= number_format($dept['total_value'] ?? $dept['value'] ?? 0, 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">ไม่มีข้อมูล</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
<script>
var monthlyData = <?= json_encode($monthlyStats) ?>;
var statusData = <?= json_encode($statusCounts) ?>;

var monthLabels = monthlyData.map(function(d) { return d.month || d.label || ''; });
var monthRepairs = monthlyData.map(function(d) { return d.repair_count || d.count || 0; });
var monthCosts = monthlyData.map(function(d) { return d.total_cost || d.cost || 0; });

var statusLabels = statusData.map(function(d) { return d.status || d.label || ''; });
var statusValues = statusData.map(function(d) { return d.count || 0; });
var statusColors = ['#28a745', '#ffc107', '#dc3545', '#17a2b8', '#6c757d', '#fd7e14', '#6f42c1'];

new Chart(document.getElementById('monthlyChart'), {
    type: 'bar',
    data: {
        labels: monthLabels,
        datasets: [
            {
                label: 'จำนวนครั้งที่ซ่อม',
                data: monthRepairs,
                backgroundColor: 'rgba(13, 110, 253, 0.7)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                label: 'ค่าใช้จ่าย (฿)',
                data: monthCosts,
                type: 'line',
                borderColor: '#dc3545',
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                fill: true,
                tension: 0.3,
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { position: 'top' }
        },
        scales: {
            y: {
                beginAtZero: true,
                position: 'left',
                title: { display: true, text: 'จำนวนครั้ง' }
            },
            y1: {
                beginAtZero: true,
                position: 'right',
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'ค่าใช้จ่าย (฿)' }
            }
        }
    }
});

var statusCtx = document.getElementById('statusChart').getContext('2d');
new Chart(statusCtx, {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: statusColors.slice(0, statusLabels.length)
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom', labels: { padding: 15 } }
        }
    }
});
</script>
