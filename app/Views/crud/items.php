<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-box-seam"></i> จัดการรายการอุปกรณ์</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> เพิ่มรายการ
    </button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/items" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label class="form-label">แผนก</label>
                <select name="dept_id" id="filter_dept" class="form-select">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= (($_GET['dept_id'] ?? '') == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">ชุดอุปกรณ์</label>
                <select name="set_id" class="form-select">
                    <option value="">ทั้งหมด</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> ค้นหา
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th>แผนก</th>
                        <th>ชุดอุปกรณ์</th>
                        <th>ชื่อรายการ</th>
                        <th>ยี่ห้อ</th>
                        <th>รุ่น</th>
                        <th class="text-center">จำนวน</th>
                        <th>หน่วย</th>
                        <th class="text-end">ราคา</th>
                        <th class="text-center">อุปกรณ์</th>
                        <th width="140" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td><?= $pagination['offset'] + $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['dept_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['set_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['brand'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['model'] ?? '') ?></td>
                                <td class="text-center"><?= $row['qty'] ?? 0 ?></td>
                                <td><?= htmlspecialchars($row['unit'] ?? '') ?></td>
                                <td class="text-end"><?= number_format($row['price'] ?? 0, 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $row['equipment_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $row['id'] ?>"
                                        data-dept_id="<?= $row['dept_id'] ?>"
                                        data-set_id="<?= $row['set_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-brand="<?= htmlspecialchars($row['brand'] ?? '') ?>"
                                        data-model="<?= htmlspecialchars($row['model'] ?? '') ?>"
                                        data-qty="<?= $row['qty'] ?? '' ?>"
                                        data-unit="<?= htmlspecialchars($row['unit'] ?? '') ?>"
                                        data-price="<?= $row['price'] ?? '' ?>"
                                        data-price_remark="<?= htmlspecialchars($row['price_remark'] ?? '') ?>"
                                        data-remark="<?= htmlspecialchars($row['remark'] ?? '') ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/items/delete/<?= $row['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ต้องการลบรายการนี้หรือไม่?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="11" class="text-center text-muted py-4">ไม่พบข้อมูลรายการอุปกรณ์</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($pagination && $pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <li class="page-item <?= $p == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= SITE_URL ?>/items?page=<?= $p ?>&dept_id=<?= $_GET['dept_id'] ?? '' ?>&set_id=<?= $_GET['set_id'] ?? '' ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/items">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มรายการอุปกรณ์</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">แผนก <span class="text-danger">*</span></label>
                            <select name="dept_id" id="add_dept_id" class="form-select" required>
                                <option value="">-- เลือกแผนก --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชุดอุปกรณ์ <span class="text-danger">*</span></label>
                            <select name="set_id" id="add_set_id" class="form-select" required>
                                <option value="">-- เลือกชุดอุปกรณ์ --</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชื่อรายการ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required placeholder="กรอกชื่อรายการ">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ยี่ห้อ</label>
                            <input type="text" class="form-control" name="brand">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">รุ่น</label>
                            <input type="text" class="form-control" name="model">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">จำนวน</label>
                            <input type="number" class="form-control" name="qty" value="1">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">หน่วย</label>
                            <input type="text" class="form-control" name="unit" placeholder="ชิ้น">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ราคา</label>
                            <input type="number" step="0.01" class="form-control" name="price" placeholder="0.00">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">หมายเหตุมูลค่า</label>
                            <input type="text" class="form-control" name="price_remark">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="remark" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/items">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> แก้ไขรายการอุปกรณ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">แผนก <span class="text-danger">*</span></label>
                            <select name="dept_id" id="edit_dept_id" class="form-select" required>
                                <option value="">-- เลือกแผนก --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชุดอุปกรณ์ <span class="text-danger">*</span></label>
                            <select name="set_id" id="edit_set_id" class="form-select" required>
                                <option value="">-- เลือกชุดอุปกรณ์ --</option>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ชื่อรายการ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">ยี่ห้อ</label>
                            <input type="text" class="form-control" id="edit_brand" name="brand">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label class="form-label">รุ่น</label>
                            <input type="text" class="form-control" id="edit_model" name="model">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-2 mb-3">
                            <label class="form-label">จำนวน</label>
                            <input type="number" class="form-control" id="edit_qty" name="qty">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label class="form-label">หน่วย</label>
                            <input type="text" class="form-control" id="edit_unit" name="unit">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ราคา</label>
                            <input type="number" step="0.01" class="form-control" id="edit_price" name="price">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">หมายเหตุมูลค่า</label>
                            <input type="text" class="form-control" id="edit_price_remark" name="price_remark">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" id="edit_remark" name="remark" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg"></i> อัปเดต</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var departmentsData = <?= json_encode($departments) ?>;
var setsByDept = {};

departmentsData.forEach(function(d) {
    setsByDept[d.id] = d.sets || [];
});

function loadSets(deptId, selectId, selectedSetId) {
    var sel = document.getElementById(selectId);
    sel.innerHTML = '<option value="">-- เลือกชุดอุปกรณ์ --</option>';
    if (!deptId || !setsByDept[deptId]) return;
    setsByDept[deptId].forEach(function(s) {
        var opt = document.createElement('option');
        opt.value = s.id;
        opt.textContent = s.name;
        if (selectedSetId && s.id == selectedSetId) opt.selected = true;
        sel.appendChild(opt);
    });
}

document.getElementById('add_dept_id')?.addEventListener('change', function() {
    loadSets(this.value, 'add_set_id', null);
});

document.getElementById('edit_dept_id')?.addEventListener('change', function() {
    loadSets(this.value, 'edit_set_id', null);
});

document.getElementById('editModal')?.addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_brand').value = btn.dataset.brand;
    document.getElementById('edit_model').value = btn.dataset.model;
    document.getElementById('edit_qty').value = btn.dataset.qty;
    document.getElementById('edit_unit').value = btn.dataset.unit;
    document.getElementById('edit_price').value = btn.dataset.price;
    document.getElementById('edit_price_remark').value = btn.dataset.price_remark;
    document.getElementById('edit_remark').value = btn.dataset.remark;

    var deptId = btn.dataset.dept_id;
    document.getElementById('edit_dept_id').value = deptId;
    loadSets(deptId, 'edit_set_id', btn.dataset.set_id);
});

document.getElementById('filter_dept')?.addEventListener('change', function() {
    loadSets(this.value, 'filter_set', '<?= $_GET["set_id"] ?? "" ?>');
});

document.getElementById('filter_dept')?.dispatchEvent(new Event('change'));
</script>
