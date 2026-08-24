<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-sliders me-2"></i>ตั้งค่าเกณฑ์ค่าเสื่อมราคา</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/depreciation">ค่าเสื่อมราคา</a></li>
                <li class="breadcrumb-item active">ตั้งค่าเกณฑ์</li>
            </ol>
        </nav>
    </div>
    <a href="<?= SITE_URL ?>/depreciation" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-1"></i>กลับหน้าคำนวณ</a>
</div>

<div class="row g-4">
    <!-- Card A: หมวดหมู่ -->
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-tags me-2"></i>หมวดหมู่ครุภัณฑ์</div>
            <div class="card-body">
                <form method="POST" action="<?= SITE_URL ?>/depreciation/settings" class="row g-2 mb-3">
                    <input type="hidden" name="action" value="save_category">
                    <?= csrf_field() ?>
                    <div class="col-12">
                        <input type="text" class="form-control" name="name" placeholder="ชื่อหมวดหมู่ใหม่ *" required>
                    </div>
                    <div class="col-12">
                        <input type="text" class="form-control" name="remark" placeholder="หมายเหตุ (ถ้ามี)">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-plus-lg me-1"></i>เพิ่มหมวดหมู่</button>
                    </div>
                </form>

                <table class="table table-sm align-middle">
                    <thead><tr><th>หมวดหมู่</th><th class="hide-mobile">ผูกแล้ว</th><th style="width:110px;"></th></tr></thead>
                    <tbody>
                    <?php foreach ($categories as $c): ?>
                        <tr>
                            <td>
                                <form method="POST" action="<?= SITE_URL ?>/depreciation/settings" id="catForm<?= $c['id'] ?>">
                                    <input type="hidden" name="action" value="save_category">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <?= csrf_field() ?>
                                    <input type="text" class="form-control form-control-sm mb-1" name="name" value="<?= htmlspecialchars($c['name']) ?>" required>
                                    <input type="text" class="form-control form-control-sm" name="remark" value="<?= htmlspecialchars($c['remark'] ?? '') ?>" placeholder="หมายเหตุ">
                                </form>
                            </td>
                            <td class="hide-mobile"><span class="badge bg-secondary"><?= $itemCounts[$c['id']] ?? 0 ?></span></td>
                            <td>
                                <button type="submit" form="catForm<?= $c['id'] ?>" class="btn btn-sm btn-outline-primary" title="บันทึก"><i class="bi bi-check-lg"></i></button>
                                <form method="POST" action="<?= SITE_URL ?>/depreciation/settings" class="d-inline"
                                      onsubmit="return confirm('ลบหมวดหมู่นี้? รายการที่ผูกจะไม่ระบุหมวด และเกณฑ์ค่าเสื่อมจะถูกลบ');">
                                    <input type="hidden" name="action" value="delete_category">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Card B: เกณฑ์ต่อหมวด -->
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-percent me-2"></i>เกณฑ์อายุการใช้งานและ % ค่าเสื่อมต่อหมวดหมู่</div>
            <div class="card-body">
                <?php if (empty($categories)): ?>
                    <div class="empty-state py-3"><i class="bi bi-tags"></i><p>เพิ่มหมวดหมู่ก่อนกำหนดเกณฑ์</p></div>
                <?php else: ?>
                    <form method="POST" action="<?= SITE_URL ?>/depreciation/settings">
                        <input type="hidden" name="action" value="save_setting">
                        <?= csrf_field() ?>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr><th>หมวดหมู่</th><th style="width:120px;">อายุ (ปี)</th><th style="width:130px;">%/ปี</th><th style="width:190px;">วิธีคิด</th></tr>
                                </thead>
                                <tbody>
                                <?php foreach ($categories as $c):
                                    $cid = (int) $c['id'];
                                    $s = $settingsByCat[$cid] ?? null;
                                ?>
                                    <tr class="<?= $s ? '' : 'table-light' ?>">
                                        <td>
                                            <?= htmlspecialchars($c['name']) ?>
                                            <?php if (!$s): ?><br><small class="text-danger">ยังไม่ได้กำหนดเกณฑ์</small><?php endif; ?>
                                        </td>
                                        <td><input type="number" class="form-control form-control-sm" min="1"
                                                   name="setting[<?= $cid ?>][useful_life_years]"
                                                   value="<?= $s ? (int) $s['useful_life_years'] : '' ?>" required></td>
                                        <td><input type="number" class="form-control form-control-sm" step="0.01" min="0" max="100"
                                                   name="setting[<?= $cid ?>][dep_rate]"
                                                   value="<?= $s ? (float) $s['dep_rate'] : '' ?>"></td>
                                        <td>
                                            <select class="form-select form-select-sm" name="setting[<?= $cid ?>][method]">
                                                <option value="straight_line" <?= $s && $s['method'] === 'straight_line' ? 'selected' : '' ?>>เส้นตรง</option>
                                                <option value="declining_balance" <?= $s && $s['method'] === 'declining_balance' ? 'selected' : '' ?>>ลดยอดคงเหลือ</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary"><i class="bi bi-save me-1"></i>บันทึกเกณฑ์ทั้งหมด</button>
                            <small class="text-muted align-self-center">* การเปลี่ยนเกณฑ์มีผลทันทีกับการคำนวณทุกหน้า</small>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
