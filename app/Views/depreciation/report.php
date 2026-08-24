<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-graph-down me-2"></i>รายงานค่าเสื่อมราคาครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/depreciation">ค่าเสื่อมราคา</a></li>
                <li class="breadcrumb-item active">รายงานสรุป</li>
            </ol>
        </nav>
    </div>
    <div class="dropdown">
        <button class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown" type="button">
            <i class="bi bi-download me-1"></i>ส่งออกรายงาน (CSV)
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow">
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/depreciation/export?type=detail"><i class="bi bi-table me-2"></i>รายชิ้นทั้งหมด</a></li>
            <li><a class="dropdown-item" href="<?= SITE_URL ?>/depreciation/export?type=summary"><i class="bi bi-file-earmark-bar-graph me-2"></i>สรุปรายปี</a></li>
        </ul>
    </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ราคาต้นทุนรวม (ทั้งระบบ)</div><h5 class="mb-0"><?= number_format($totals['total_cost'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ค่าเสื่อมรายปีรวม</div><h5 class="mb-0 text-danger"><?= number_format($totals['total_annual'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ค่าเสื่อมสะสมรวม</div><h5 class="mb-0 text-warning"><?= number_format($totals['total_accumulated'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">มูลค่าคงเหลือสุทธิรวม</div><h5 class="mb-0 text-success"><?= number_format($totals['total_nbv'], 2) ?> ฿</h5>
    </div></div></div>
</div>

<!-- Charts -->
<div class="row g-4 mb-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-bar-chart me-2"></i>ค่าเสื่อมรายปี แยกตามปีจัดซื้อ (บาท)</div>
            <div class="card-body"><canvas id="chartAnnual" height="220"></canvas></div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-graph-up me-2"></i>มูลค่าสะสม vs มูลค่าคงเหลือสุทธิ (บาท)</div>
            <div class="card-body"><canvas id="chartNbv" height="220"></canvas></div>
        </div>
    </div>
</div>

<!-- Summary tables -->
<div class="row g-4">
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-calendar3 me-2"></i>สรุปแยกตามปีจัดซื้อ</div>
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>ปี (พ.ศ.)</th><th class="text-center">ชิ้น</th><th class="text-end">ต้นทุน</th><th class="text-end">ค่าเสื่อม/ปี</th><th class="text-end">สะสม</th><th class="text-end">คงเหลือ</th></tr></thead>
                    <tbody>
                    <?php foreach ($byYear as $y): ?>
                        <tr>
                            <td><?= htmlspecialchars($y['year']) ?></td>
                            <td class="text-center"><?= $y['count'] ?></td>
                            <td class="text-end"><?= number_format($y['total_cost'], 2) ?></td>
                            <td class="text-end"><?= number_format($y['total_annual'], 2) ?></td>
                            <td class="text-end"><?= number_format($y['total_accumulated'], 2) ?></td>
                            <td class="text-end fw-bold"><?= number_format($y['total_nbv'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-tags me-2"></i>สรุปแยกตามหมวดหมู่</div>
            <div class="table-responsive">
                <table class="table table-striped table-sm mb-0">
                    <thead><tr><th>หมวดหมู่</th><th class="text-center">ชิ้น</th><th class="text-end">ต้นทุน</th><th class="text-end">สะสม</th><th class="text-end">คงเหลือ</th></tr></thead>
                    <tbody>
                    <?php foreach ($byCategory as $c): ?>
                        <tr>
                            <td><?= htmlspecialchars($c['category_name']) ?></td>
                            <td class="text-center"><?= $c['count'] ?></td>
                            <td class="text-end"><?= number_format($c['total_cost'], 2) ?></td>
                            <td class="text-end"><?= number_format($c['total_accumulated'], 2) ?></td>
                            <td class="text-end fw-bold"><?= number_format($c['total_nbv'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const labels = <?= json_encode(array_map(fn($y) => $y['year'], $byYear), JSON_UNESCAPED_UNICODE) ?>;
    const annual = <?= json_encode(array_map(fn($y) => round((float) $y['total_annual'], 2), $byYear)) ?>;
    const acc = <?= json_encode(array_map(fn($y) => round((float) $y['total_accumulated'], 2), $byYear)) ?>;
    const nbv = <?= json_encode(array_map(fn($y) => round((float) $y['total_nbv'], 2), $byYear)) ?>;

    new Chart(document.getElementById('chartAnnual'), {
        type: 'bar',
        data: { labels: labels, datasets: [{ label: 'ค่าเสื่อมรายปีรวม (บาท)', data: annual, backgroundColor: 'rgba(54,162,235,.7)' }] },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { ticks: { callback: v => v.toLocaleString() } } } }
    });
    new Chart(document.getElementById('chartNbv'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'ค่าเสื่อมสะสม', data: acc, borderColor: 'rgba(255,159,64,1)', backgroundColor: 'rgba(255,159,64,.15)', fill: true, tension: .3 },
                { label: 'มูลค่าคงเหลือสุทธิ', data: nbv, borderColor: 'rgba(75,192,192,1)', backgroundColor: 'rgba(75,192,192,.15)', fill: true, tension: .3 }
            ]
        },
        options: { responsive: true, scales: { y: { ticks: { callback: v => v.toLocaleString() } } } }
    });
})();
</script>
