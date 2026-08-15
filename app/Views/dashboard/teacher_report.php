<?php
/**
 * Teacher Room Report View
 * รายงานสรุปครุภัณฑ์ในห้องที่ดูแล — ตามแบบเว็บออริจินอล
 *
 * Variables from controller:
 *   $rooms, $totals
 */
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i class="bi bi-bar-chart me-2"></i>รายงานสรุปสถานะครุภัณฑ์ในสังกัด</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">รายงานสรุป</li>
        </ol>
    </nav>
</div>

<?php if (empty($rooms)): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle me-2"></i>
        คุณยังไม่ได้รับมอบหมายให้ดูแลห้องปฏิบัติการใด กรุณาติดต่อเจ้าหน้าที่ดูแลระบบ
    </div>
<?php else: ?>

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stats-card bg-gradient-primary">
                <div class="stats-icon"><i class="bi bi-pc-display"></i></div>
                <div class="stats-number"><?= number_format($totals['total_equipment']) ?></div>
                <div class="stats-label">ครุภัณฑ์ทั้งหมด</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-gradient-success">
                <div class="stats-icon"><i class="bi bi-check-circle"></i></div>
                <div class="stats-number"><?= number_format($totals['available_count']) ?></div>
                <div class="stats-label">พร้อมใช้งาน</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-gradient-warning">
                <div class="stats-icon"><i class="bi bi-tools"></i></div>
                <div class="stats-number"><?= number_format($totals['repair_count'] + $totals['broken_count']) ?></div>
                <div class="stats-label">ชำรุด / ส่งซ่อม</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card bg-gradient-info">
                <div class="stats-icon"><i class="bi bi-currency-dollar"></i></div>
                <div class="stats-number"><?= number_format($totals['total_value'], 0) ?></div>
                <div class="stats-label">มูลค่ารวม (บาท)</div>
            </div>
        </div>
    </div>

    <!-- Rooms Table -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="bi bi-list-check me-2 text-primary"></i>สรุปข้อมูลแยกตามห้องปฏิบัติการ</span>
            <a href="<?= SITE_URL ?>/teacher/export" class="btn btn-success btn-sm rounded-pill px-3">
                <i class="bi bi-file-earmark-excel me-1"></i>ส่งออก Excel
            </a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ห้อง/สถานที่</th>
                            <th class="text-center">ทั้งหมด</th>
                            <th class="text-center">สภาพดี</th>
                            <th class="text-center">ส่งซ่อม</th>
                            <th class="text-center">ชำรุด</th>
                            <th class="text-center text-danger">รอตรวจ</th>
                            <th class="text-end">มูลค่า</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td>
                                    <?php if ($room['room_id'] === 'other'): ?>
                                        <span class="text-primary fw-bold"><i
                                                class="bi bi-asterisk me-2"></i><?= htmlspecialchars($room['room_name']) ?></span>
                                    <?php else: ?>
                                        <strong><?= htmlspecialchars($room['room_name']) ?></strong>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><span
                                        class="badge bg-secondary rounded-pill"><?= $room['total_equipment'] ?></span>
                                </td>
                                <td class="text-center"><span
                                        class="badge bg-success rounded-pill"><?= $room['available_count'] ?></span>
                                </td>
                                <td class="text-center"><span
                                        class="badge bg-warning text-dark rounded-pill"><?= $room['repair_count'] ?></span></td>
                                <td class="text-center"><span
                                        class="badge bg-danger rounded-pill"><?= $room['broken_count'] ?></span></td>
                                <td class="text-center">
                                    <?php if ($room['need_check_count'] > 0): ?>
                                        <span class="badge bg-danger rounded-pill"><?= $room['need_check_count'] ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted rounded-pill">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= number_format($room['total_value'], 0) ?></td>
                                <td>
                                    <a href="<?= SITE_URL ?>/equipment/my?room=<?= urlencode($room['room_id']) ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                        <i class="bi bi-eye me-1"></i>ดู
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th>รวม <?= count($rooms) ?> รายการ</th>
                            <th class="text-center"><?= $totals['total_equipment'] ?></th>
                            <th class="text-center"><?= $totals['available_count'] ?></th>
                            <th class="text-center"><?= $totals['repair_count'] ?></th>
                            <th class="text-center"><?= $totals['broken_count'] ?></th>
                            <th class="text-center"><?= $totals['need_check_count'] ?></th>
                            <th class="text-end"><?= number_format($totals['total_value'], 0) ?></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts -->
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-pie-chart me-2"></i>สถานะครุภัณฑ์
                </div>
                <div class="card-body">
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-bar-chart me-2"></i>จำนวนครุภัณฑ์ตามห้องปฏิบัติการ
                </div>
                <div class="card-body">
                    <canvas id="roomChart" height="250"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Status Pie Chart
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: ['พร้อมใช้งาน', 'ส่งซ่อม', 'ซ่อมไม่ได้', 'จำหน่ายออก'],
                datasets: [{
                    data: [<?= $totals['available_count'] ?>, <?= $totals['repair_count'] ?>, <?= $totals['broken_count'] ?>, <?= $totals['disposed_count'] ?>],
                    backgroundColor: ['#198754', '#ffc107', '#dc3545', '#6c757d']
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // Room Bar Chart
        new Chart(document.getElementById('roomChart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($rooms, 'room_name'), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>,
                datasets: [{
                    label: 'จำนวนครุภัณฑ์',
                    data: [<?php foreach ($rooms as $r)
                        echo $r['total_equipment'] . ','; ?>],
                    backgroundColor: '#0d6efd'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    </script>

<?php endif; ?>