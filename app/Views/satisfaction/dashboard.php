<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-emoji-smile me-2"></i>สรุปความพึงพอใจงานซ่อมบำรุง</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ความพึงพอใจ</li>
            </ol>
        </nav>
    </div>
    <a href="<?= SITE_URL ?>/satisfaction/export" class="btn btn-success"><i class="bi bi-download me-1"></i>ส่งออก CSV</a>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">คะแนนเฉลี่ยรวม (จาก 5)</div>
        <h5 class="mb-0 text-primary"><?= $overall['avg_rating'] !== null ? number_format($overall['avg_rating'], 2) : '-' ?>
            <?php if ($overall['avg_rating'] !== null): ?>
                <?php for ($s = 1; $s <= 5; $s++): ?><i class="bi bi-star<?= $s <= round((float) $overall['avg_rating']) ? '-fill' : '' ?> text-warning"></i><?php endfor; ?>
            <?php endif; ?>
        </h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">จำนวนแบบประเมินทั้งหมด</div><h5 class="mb-0"><?= $overall['total'] ?> ครั้ง</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">อัตราการตอบแบบประเมิน</div><h5 class="mb-0"><?= number_format($responseRate, 1) ?>% <small class="text-muted fw-normal">(จากซ่อมเสร็จ <?= $completed ?> ใบ)</small></h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ใบแจ้งซ่อมสถานะซ่อมเสร็จ</div><h5 class="mb-0"><?= $completed ?> ใบ</h5>
    </div></div></div>
</div>

<!-- Chart -->
<div class="card mb-4">
    <div class="card-header"><i class="bi bi-graph-up-arrow me-2"></i>คะแนนความพึงพอใจเฉลี่ยรายเดือน (12 เดือนย้อนหลัง)</div>
    <div class="card-body"><canvas id="chartSatisfaction" height="110"></canvas></div>
</div>

<!-- Recent comments -->
<div class="card">
    <div class="card-header"><i class="bi bi-chat-left-text me-2"></i>คำติชม/ข้อเสนอแนะล่าสุด</div>
    <div class="card-body p-0">
        <?php if (empty($recent)): ?>
            <div class="empty-state py-4"><i class="bi bi-inbox"></i><p>ยังไม่มีแบบประเมิน</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th class="hide-mobile">วันที่</th><th>ใบซ่อม</th><th class="hide-mobile">ครุภัณฑ์</th>
                            <th class="hide-mobile">ผู้ประเมิน</th><th>คะแนน</th><th>ความคิดเห็น</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recent as $r): ?>
                        <tr>
                            <td class="hide-mobile"><small><?= formatDateTimeThai($r['created_at']) ?></small></td>
                            <td><a href="<?= SITE_URL ?>/repairs/<?= $r['repair_id'] ?>">#<?= $r['repair_id'] ?></a></td>
                            <td class="hide-mobile"><small><?= htmlspecialchars($r['eq_code'] ?? '-') ?> · <?= htmlspecialchars($r['item_name'] ?? '-') ?></small></td>
                            <td class="hide-mobile"><small><?= htmlspecialchars(trim(($r['firstname'] ?? '') . ' ' . ($r['lastname'] ?? ''))) ?> (<?= translateRole($r['role'] ?? '') ?>)</small></td>
                            <td class="text-nowrap">
                                <?php for ($s = 1; $s <= 5; $s++): ?><i class="bi bi-star<?= $s <= (int) $r['rating'] ? '-fill' : '' ?> text-warning"></i><?php endfor; ?>
                            </td>
                            <td><small><?= $r['comment'] ? nl2br(htmlspecialchars($r['comment'])) : '<span class=\'text-muted\'>-</span>' ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    const stats = <?= json_encode($monthly, JSON_UNESCAPED_UNICODE) ?>;
    new Chart(document.getElementById('chartSatisfaction'), {
        data: {
            labels: stats.map(s => s.label),
            datasets: [
                { type: 'bar', label: 'คะแนนเฉลี่ย', data: stats.map(s => Number(s.avg_rating)), yAxisID: 'y',
                  backgroundColor: stats.map(s => s.avg_rating >= 4 ? 'rgba(75,192,192,.7)' : s.avg_rating >= 3 ? 'rgba(255,193,7,.7)' : 'rgba(255,99,132,.7)'), },
                { type: 'line', label: 'จำนวนแบบประเมิน', data: stats.map(s => Number(s.count)), yAxisID: 'y1',
                  borderColor: 'rgba(54,162,235,1)', backgroundColor: 'rgba(54,162,235,.15)', fill: true, tension: .3 }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y:  { min: 0, max: 5, position: 'left', title: { display: true, text: 'คะแนนเฉลี่ย (1-5)' } },
                y1: { min: 0, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'จำนวน (ครั้ง)' } }
            }
        }
    });
})();
</script>
