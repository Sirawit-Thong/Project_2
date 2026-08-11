<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-collection"></i> จัดการชุดอุปกรณ์</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> เพิ่มชุดอุปกรณ์
    </button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/sets" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label">แผนก</label>
                <select name="dept_id" class="form-select">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= (($_GET['dept_id'] ?? '') == $dept['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
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
                        <th>ชื่อชุดอุปกรณ์</th>
                        <th>ปี</th>
                        <th class="text-end">มูลค่า</th>
                        <th width="100" class="text-center">รายการ</th>
                        <th width="100" class="text-center">อุปกรณ์</th>
                        <th width="160" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td><?= $pagination['offset'] + $i + 1 ?></td>
                                <td><?= htmlspecialchars($row['dept_name'] ?? '') ?></td>
                                <td><?= htmlspecialchars($row['name']) ?></td>
                                <td><?= htmlspecialchars($row['year'] ?? '') ?></td>
                                <td class="text-end"><?= number_format($row['price'] ?? 0, 2) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $row['item_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $row['equipment_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $row['id'] ?>"
                                        data-dept_id="<?= $row['dept_id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-year="<?= htmlspecialchars($row['year'] ?? '') ?>"
                                        data-price="<?= $row['price'] ?? '' ?>"
                                        data-price_remark="<?= htmlspecialchars($row['price_remark'] ?? '') ?>"
                                        data-remark="<?= htmlspecialchars($row['remark'] ?? '') ?>">
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/sets/delete/<?= $row['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ต้องการลบชุดอุปกรณ์นี้หรือไม่?');">
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
                            <td colspan="8" class="text-center text-muted py-4">ไม่พบข้อมูลชุดอุปกรณ์</td>
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
                    <a class="page-link" href="<?= SITE_URL ?>/sets?page=<?= $p ?>&dept_id=<?= $_GET['dept_id'] ?? '' ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/sets">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มชุดอุปกรณ์</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select name="dept_id" class="form-select" required>
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อชุดอุปกรณ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="กรอกชื่อชุดอุปกรณ์">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ปี <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="year" required placeholder="เช่น 2567"
                            value="<?= date('Y') + 543 ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">มูลค่า</label>
                        <input type="number" step="0.01" class="form-control" name="price" id="addPrice" placeholder="0.00">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="addPriceRemarkLabel">หมายเหตุมูลค่า</label>
                        <input type="text" class="form-control" name="price_remark" id="addPriceRemark">
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/sets">
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> แก้ไขชุดอุปกรณ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">แผนก <span class="text-danger">*</span></label>
                        <select name="dept_id" id="edit_dept_id" class="form-select" required>
                            <option value="">-- เลือกแผนก --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อชุดอุปกรณ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ปี <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_year" name="year" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">มูลค่า</label>
                        <input type="number" step="0.01" class="form-control" id="edit_price" name="price">
                    </div>
                    <div class="mb-3">
                        <label class="form-label" id="editPriceRemarkLabel">หมายเหตุมูลค่า</label>
                        <input type="text" class="form-control" id="edit_price_remark" name="price_remark">
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
function toggleRemarkRequirement(prefix) {
    var priceInput = document.getElementById(prefix === 'add' ? 'addPrice' : 'edit_price');
    var remarkInput = document.getElementById(prefix === 'add' ? 'addPriceRemark' : 'edit_price_remark');
    var label = document.getElementById(prefix === 'add' ? 'addPriceRemarkLabel' : 'editPriceRemarkLabel');
    if (!priceInput || !remarkInput || !label) return;

    if (parseFloat(priceInput.value) > 0) {
        remarkInput.required = true;
        if (!label.innerHTML.includes('text-danger')) {
            label.innerHTML += ' <span class="text-danger">*</span>';
        }
    } else {
        remarkInput.required = false;
        label.innerHTML = label.innerHTML.replace(' <span class="text-danger">*</span>', '');
    }
}

document.getElementById('addModal')?.addEventListener('shown.bs.modal', function() {
    toggleRemarkRequirement('add');
});

document.getElementById('addPrice')?.addEventListener('input', function() {
    toggleRemarkRequirement('add');
});

document.getElementById('editModal')?.addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_dept_id').value = btn.dataset.dept_id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_year').value = btn.dataset.year;
    document.getElementById('edit_price').value = btn.dataset.price;
    document.getElementById('edit_price_remark').value = btn.dataset.price_remark;
    document.getElementById('edit_remark').value = btn.dataset.remark;
    toggleRemarkRequirement('edit');
});

document.getElementById('edit_price')?.addEventListener('input', function() {
    toggleRemarkRequirement('edit');
});
</script>
