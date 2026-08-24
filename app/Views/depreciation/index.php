<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-calculator me-2"></i>คำนวณค่าเสื่อมราคาครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ค่าเสื่อมราคา</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= SITE_URL ?>/depreciation/report" class="btn btn-outline-primary">
            <i class="bi bi-graph-down me-1"></i>รายงานสรุปและกราฟ
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="<?= SITE_URL ?>/depreciation" class="row g-2 align-items-center">
            <div class="col-md-3">
                <label class="form-label mb-0 small text-muted">สาขาวิชา</label>
                <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $filters['dept_id'] == $d['id'] ? 'selected' : '' ?>><?= htmlspecialchars($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label mb-0 small text-muted">หมวดหมู่</label>
                <select name="category_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกหมวดหมู่ --</option>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $filters['category_id'] == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">ปีจัดซื้อ</label>
                <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกปี --</option>
                    <?php foreach ($years as $y): ?>
                        <option value="<?= htmlspecialchars($y) ?>" <?= $filters['year'] == $y ? 'selected' : '' ?>><?= htmlspecialchars($y) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0 small text-muted">สถานะ</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกสถานะ --</option>
                    <?php foreach (['available', 'repair', 'broken'] as $st): ?>
                        <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= translateEquipmentStatus($st) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <a href="<?= SITE_URL ?>/depreciation" class="btn btn-sm btn-outline-secondary mt-3"><i class="bi bi-x-lg"></i> ล้าง</a>
            </div>
        </form>
    </div>
</div>

<!-- KPI -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">รวมราคาต้นทุน</div><h5 class="mb-0"><?= number_format($totals['total_cost'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">ค่าเสื่อมสะสมรวม</div><h5 class="mb-0 text-warning"><?= number_format($totals['total_accumulated'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">มูลค่าคงเหลือสุทธิรวม</div><h5 class="mb-0 text-success"><?= number_format($totals['total_nbv'], 2) ?> ฿</h5>
    </div></div></div>
    <div class="col-md-3"><div class="card h-100"><div class="card-body py-3">
        <div class="text-muted small">คำนวณได้ / ข้าม (รวม <?= $totals['count_total'] ?> ชิ้น)</div>
        <h5 class="mb-0"><?= $totals['count_ok'] ?> / <?= $totals['count_skip'] ?></h5>
    </div></div></div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>ค่าเสื่อมราคารายชิ้น (<?= $totals['count_total'] ?> รายการ)</div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="empty-state py-4"><i class="bi bi-inbox"></i><p>ไม่พบข้อมูลครุภัณฑ์</p></div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr>
                            <th>#</th><th>ครุภัณฑ์</th><th class="hide-mobile">หมวดหมู่</th><th>ปีจัดซื้อ</th>
                            <th class="text-end">ราคาต้นทุน</th><th class="hide-mobile">เกณฑ์</th>
                            <th class="text-end">ค่าเสื่อม/ปี</th><th class="hide-mobile">ผ่านมา</th>
                            <th class="text-end">สะสม</th><th class="text-end">มูลค่าคงเหลือ</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($rows as $i => $row): ?>
                        <tr>
                            <td><?= $pagination['offset'] + $i + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($row['code'] ?? '-') ?></strong><br>
                                <small class="text-muted"><?= htmlspecialchars($row['item_name']) ?></small>
                            </td>
                            <td class="hide-mobile"><?= htmlspecialchars($row['category_name'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['set_year']) ?></td>
                            <td class="text-end"><?= number_format((float) $row['price'], 2) ?></td>
                            <?php if ($row['dep_ok']): ?>
                                <td class="hide-mobile"><small><?= (int) $row['useful_life_years'] ?> ปี · <?= rtrim(rtrim(number_format((float) $row['dep_rate'], 2), '0'), '.') ?>% <?= $row['method'] === 'declining_balance' ? '(ลดยอด)' : '(เส้นตรง)' ?></small></td>
                                <td class="text-end"><?= number_format($row['annual_dep'], 2) ?></td>
                                <td class="hide-mobile"><?= (int) $row['years_elapsed'] ?> ปี</td>
                                <td class="text-end text-warning"><?= number_format($row['accumulated'], 2) ?></td>
                                <td class="text-end fw-bold text-success"><?= number_format($row['nbv'], 2) ?></td>
                                <td>
                                    <?php if (!empty($row['schedule'])): ?>
                                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse"
                                            data-bs-target="#sched-<?= $row['id'] ?>" aria-expanded="false" title="ตารางรายปี"><i class="bi bi-clock-history"></i></button>
                                    <?php endif; ?>
                                </td>
                            <?php else: ?>
                                <td colspan="5" class="text-muted"><span class="badge bg-secondary">-</span> <?= translateDepReason($row['dep_reason']) ?></td>
                                <td></td>
                            <?php endif; ?>
                        </tr>
                        <?php if ($row['dep_ok'] && !empty($row['schedule'])): ?>
                        <tr class="collapse" id="sched-<?= $row['id'] ?>">
                            <td colspan="11" class="bg-light">
                                <div class="p-2">
                                    <strong><i class="bi bi-clock-history me-1"></i>ตารางค่าเสื่อมรายปี — <?= htmlspecialchars($row['code'] ?? $row['item_name']) ?></strong>
                                    <table class="table table-sm table-bordered bg-white mt-2 mb-0 w-auto ms-auto">
                                        <thead><tr><th>ปี (พ.ศ.)</th><th class="text-end">ค่าเสื่อมประจำปี</th><th class="text-end">ค่าเสื่อมสะสม</th><th class="text-end">มูลค่าคงเหลือ</th></tr></thead>
                                        <tbody>
                                        <?php foreach ($row['schedule'] as $s): ?>
                                            <tr class="<?= $s['year'] <= DepreciationCalculator::currentBuddhistYear() ? 'fw-bold' : 'text-muted' ?>">
                                                <td><?= $s['year'] ?></td>
                                                <td class="text-end"><?= number_format($s['annual'], 2) ?></td>
                                                <td class="text-end"><?= number_format($s['accumulated'], 2) ?></td>
                                                <td class="text-end"><?= number_format($s['nbv'], 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination && $pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, SITE_URL . '/depreciation?' . http_build_query(array_filter($filters, fn($v) => $v !== '')) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>
