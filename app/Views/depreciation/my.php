<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-calculator me-2"></i>ค่าเสื่อมราคาครุภัณฑ์ในความดูแล</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ค่าเสื่อมราคา</li>
            </ol>
        </nav>
    </div>
    <a href="<?= SITE_URL ?>/depreciation/my/export" class="btn btn-success"><i class="bi bi-download me-1"></i>ส่งออก CSV</a>
</div>

<div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    ใช้ประกอบการประเมินสภาพครุภัณฑ์และวางแผนขอจัดงบประมาณทดแทนล่วงหน้า — ครุภัณฑ์ที่มูลค่าคงเหลือใกล้ 0 หรือหมดอายุการใช้งาน ควรพิจารณาเสนอขอจัดซื้อทดแทน
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">จำนวนครุภัณฑ์ที่ดูแล</div><h5 class="mb-0"><?= $totals['count_total'] ?> ชิ้น</h5>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ค่าเสื่อมสะสมรวม</div><h5 class="mb-0 text-warning"><?= number_format($totals['total_accumulated'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-4"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">มูลค่าคงเหลือสุทธิรวม</div><h5 class="mb-0 text-success"><?= number_format($totals['total_nbv'], 2) ?> ฿</h5>
    </div></div></div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>รายการครุภัณฑ์ (<?= $totals['count_total'] ?> รายการ)</div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="empty-state py-4"><i class="bi bi-inbox"></i><p>ยังไม่มีครุภัณฑ์ในความดูแลของท่าน</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>ครุภัณฑ์</th><th class="hide-mobile">ห้อง</th><th>ปีจัดซื้อ</th>
                            <th class="text-end">ราคาต้นทุน</th><th class="text-end hide-mobile">ค่าเสื่อม/ปี</th>
                            <th class="text-end hide-mobile">สะสม</th><th class="text-end">มูลค่าคงเหลือ</th><th>สถานะ</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($row['code'] ?? '-') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($row['item_name']) ?></small>
                            </td>
                            <td class="hide-mobile"><?= htmlspecialchars($row['room_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['set_year']) ?></td>
                            <td class="text-end"><?= number_format((float) $row['price'], 2) ?></td>
                            <?php if ($row['dep_ok']): ?>
                                <td class="text-end hide-mobile"><?= number_format($row['annual_dep'], 2) ?></td>
                                <td class="text-end hide-mobile text-warning"><?= number_format($row['accumulated'], 2) ?></td>
                                <td class="text-end fw-bold <?= (float) $row['nbv'] <= 0 ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format($row['nbv'], 2) ?><?= (float) $row['nbv'] <= 0 ? ' <small class="fw-normal text-muted">(หมดอายุการใช้งาน)</small>' : '' ?>
                                </td>
                            <?php else: ?>
                                <td colspan="3" class="text-muted"><span class="badge bg-secondary">-</span> <?= translateDepReason($row['dep_reason']) ?></td>
                            <?php endif; ?>
                            <td><span class="badge bg-<?= getStatusBadgeClass($row['status']) ?>"><?= translateEquipmentStatus($row['status']) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
