<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
$totalItems = $pagination['total_items'] ?? count($rows);
$deptFilter = $_GET['dept_id'] ?? '';
$setFilter = $_GET['set_id'] ?? '';
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
    ? (int) $_GET['per_page']
    : 20;
$paginationBaseUrl = SITE_URL . '/items?dept_id=' . urlencode($deptFilter) . '&set_id=' . urlencode($setFilter) . '&per_page=' . $perPage;
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-box-seam me-2"></i>บริหารจัดการรายการครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">รายการครุภัณฑ์</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>เพิ่มรายการครุภัณฑ์ใหม่
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="<?= SITE_URL ?>/items" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0">สาขาวิชา:</label>
            </div>
            <div class="col-md-3">
                <select name="dept_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label mb-0">ชุดครุภัณฑ์:</label>
            </div>
            <div class="col-md-4">
                <select name="set_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- ทุกชุด --</option>
                    <?php foreach ($allSets as $set): ?>
                        <?php if (!$deptFilter || $set['dept_id'] == $deptFilter): ?>
                            <option value="<?= $set['id'] ?>" <?= $setFilter == $set['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($set['name']) ?> (<?= $set['year'] ?>)
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 120px;"
                    onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <a href="<?= SITE_URL ?>/items" class="btn btn-sm btn-outline-secondary"><i class="bi bi-x-lg"></i> ล้าง</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>รายการครุภัณฑ์ทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="bi bi-box-seam"></i>
                <h5>ยังไม่มีรายการครุภัณฑ์</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="50" class="hide-mobile">ID</th>
                            <th>ชื่อรายการครุภัณฑ์</th>
                            <th class="hide-mobile">ชุดครุภัณฑ์ปีงบประมาณ</th>
                            <th class="hide-mobile">ยี่ห้อ/รุ่นแบบ</th>
                            <th class="text-center">จำนวนที่มี</th>
                            <th class="text-end hide-mobile">ราคาทั้งรายการ</th>
                            <th class="text-center">จำนวนครุภัณฑ์</th>
                            <th width="120">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $item): ?>
                            <tr>
                                <td class="hide-mobile"><?= $item['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    <?php if (!empty($item['remark'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($item['remark']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <?= htmlspecialchars($item['set_name'] ?? '-') ?>
                                    <?php if (!empty($item['set_year'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($item['set_year']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile">
                                    <?= htmlspecialchars($item['brand'] ?? '-') ?>
                                    <?php if (!empty($item['model'])): ?>/ <?= htmlspecialchars($item['model']) ?><?php endif; ?>
                                </td>
                                <td class="text-center"><?= $item['qty'] ?> <?= htmlspecialchars($item['unit'] ?? '') ?></td>
                                <td class="text-end hide-mobile"><?= number_format($item['price'], 2) ?></td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/equipment?item=<?= $item['id'] ?>"
                                        class="badge bg-secondary text-decoration-none">
                                        <?= $item['equipment_count'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-nowrap">
                                        <a href="<?= SITE_URL ?>/equipment/add?item_id=<?= $item['id'] ?>" class="btn btn-sm btn-success"
                                            title="เพิ่มครุภัณฑ์" aria-label="เพิ่มครุภัณฑ์">
                                            <i class="bi bi-plus-lg"></i>
                                        </a>
                                        <button class="btn btn-sm btn-outline-primary" aria-label="แก้ไข"
                                            onclick='editItem(<?= json_encode($item, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' title="แก้ไข">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" action="<?= SITE_URL ?>/items/delete/<?= $item['id'] ?>" class="d-inline"
                                            onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบรายการนี้?');">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ" aria-label="ลบ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination && $pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, $paginationBaseUrl) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/items">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>ลงทะเบียนรายการครุภัณฑ์ใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชุดครุภัณฑ์ปีงบประมาณ <span class="text-danger">*</span></label>
                        <select class="form-select" name="set_id" id="addSetId" required
                            onchange="checkSetPrice('add')">
                            <option value="" data-price="0">-- เลือกชุด --</option>
                            <?php foreach ($allSets as $set): ?>
                                <option value="<?= $set['id'] ?>" data-price="<?= $set['price'] ?>"
                                    <?= $setFilter == $set['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($set['name']) ?> (<?= $set['year'] ?>) -
                                    <?= htmlspecialchars($set['dept_name'] ?? 'ไม่ระบุสาขา') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="addPriceAlert" class="text-info d-none mt-1">
                            <i class="bi bi-info-circle"></i> ชุดครุภัณฑ์นี้ระบุราคารวมไว้แล้ว
                            ราคาทั้งรายการของรายการนี้จะถูกตั้งเป็น 0
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อรายการครุภัณฑ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required
                            placeholder="เช่น เครื่องคอมพิวเตอร์">
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ยี่ห้อ</label>
                            <input type="text" class="form-control" name="brand" placeholder="เช่น DELL">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รุ่น</label>
                            <input type="text" class="form-control" name="model" placeholder="เช่น Optiplex 7090">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">จำนวน</label>
                            <input type="number" class="form-control" name="qty" value="1" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">หน่วย</label>
                            <input type="text" class="form-control" name="unit" placeholder="เช่น เครื่อง, ชุด">
                        </div>
                        <div class="col-md-4 mb-3" id="addPriceContainer">
                            <label class="form-label">ราคาทั้งรายการ (บาท)</label>
                            <input type="number" class="form-control" name="price" id="addPrice" step="0.01" value="0">
                        </div>
                    </div>
                    <div class="mb-3" id="addPriceRemarkContainer">
                        <label class="form-label" id="addPriceRemarkLabel">หมายเหตุราคา (ถ้ามี)</label>
                        <input type="text" class="form-control" name="price_remark" id="addPriceRemark"
                            placeholder="เช่น 75,500 บาท/ชิ้น">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="remark" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
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
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขรายการครุภัณฑ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชุดครุภัณฑ์ <span class="text-danger">*</span></label>
                        <select class="form-select" name="set_id" id="editSetId" required
                            onchange="checkSetPrice('edit')">
                            <option value="" data-price="0">-- เลือกชุด --</option>
                            <?php foreach ($allSets as $set): ?>
                                <option value="<?= $set['id'] ?>" data-price="<?= $set['price'] ?>">
                                    <?= htmlspecialchars($set['name']) ?> (<?= $set['year'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="editPriceAlert" class="text-info d-none mt-1">
                            <i class="bi bi-info-circle"></i> ชุดครุภัณฑ์นี้ระบุราคารวมไว้แล้ว
                            ราคาทั้งรายการของรายการนี้จะถูกตั้งเป็น 0
                        </small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อรายการ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ยี่ห้อ</label>
                            <input type="text" class="form-control" name="brand" id="editBrand">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">รุ่น</label>
                            <input type="text" class="form-control" name="model" id="editModel">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">จำนวน</label>
                            <input type="number" class="form-control" name="qty" id="editQty" min="0">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">หน่วย</label>
                            <input type="text" class="form-control" name="unit" id="editUnit">
                        </div>
                        <div class="col-md-4 mb-3" id="editPriceContainer">
                            <label class="form-label">ราคา/หน่วย (บาท)</label>
                            <input type="number" class="form-control" name="price" id="editPrice" step="0.01">
                        </div>
                    </div>
                    <div class="mb-3" id="editPriceRemarkContainer">
                        <label class="form-label" id="editPriceRemarkLabel">หมายเหตุราคา</label>
                        <input type="text" class="form-control" name="price_remark" id="editPriceRemark"
                            placeholder="คำอธิบายราคาทั้งรายการ">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="remark" id="editRemark" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>บันทึก</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editItem(item) {
        document.getElementById('editId').value = item.id;
        document.getElementById('editSetId').value = item.set_id;
        document.getElementById('editName').value = item.name;
        document.getElementById('editBrand').value = item.brand || '';
        document.getElementById('editModel').value = item.model || '';
        document.getElementById('editQty').value = item.qty;
        document.getElementById('editUnit').value = item.unit || '';
        document.getElementById('editPrice').value = item.price;
        document.getElementById('editPriceRemark').value = item.price_remark || '';
        document.getElementById('editRemark').value = item.remark || '';
        checkSetPrice('edit');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }

    function checkSetPrice(prefix) {
        let setSelect = document.getElementById(prefix + 'SetId');
        let priceInput = document.getElementById(prefix + 'Price');
        let priceContainer = document.getElementById(prefix + 'PriceContainer');
        let priceRemarkContainer = document.getElementById(prefix + 'PriceRemarkContainer');
        let alertText = document.getElementById(prefix + 'PriceAlert');

        if (!setSelect || !priceInput) return;

        let selectedOption = setSelect.options[setSelect.selectedIndex];
        let setPrice = parseFloat(selectedOption?.getAttribute('data-price') || 0);

        if (setPrice > 0) {
            priceInput.value = 0;
            priceInput.readOnly = true;
            if (priceContainer) priceContainer.classList.add('d-none');
            if (priceRemarkContainer) priceRemarkContainer.classList.add('d-none');
            if (alertText) alertText.classList.remove('d-none');
            let remarkInput = document.getElementById(prefix + 'PriceRemark');
            if (remarkInput) remarkInput.required = false;
        } else {
            priceInput.readOnly = false;
            if (priceContainer) priceContainer.classList.remove('d-none');
            if (priceRemarkContainer) priceRemarkContainer.classList.remove('d-none');
            if (alertText) alertText.classList.add('d-none');
            toggleRemarkRequirement(prefix);
        }
    }

    function toggleRemarkRequirement(prefix) {
        let priceInput = document.getElementById(prefix + 'Price');
        let remarkInput = document.getElementById(prefix + 'PriceRemark');
        let label = document.getElementById(prefix + 'PriceRemarkLabel');
        let setSelect = document.getElementById(prefix + 'SetId');

        let selectedOption = setSelect?.options[setSelect.selectedIndex];
        let setPrice = parseFloat(selectedOption?.getAttribute('data-price') || 0);

        if (setPrice > 0) return;

        if (priceInput && remarkInput && label) {
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
    }

    document.addEventListener('DOMContentLoaded', function () {
        let addPrice = document.getElementById('addPrice');
        if (addPrice) {
            addPrice.addEventListener('input', () => toggleRemarkRequirement('add'));
        }

        let editPrice = document.getElementById('editPrice');
        if (editPrice) {
            editPrice.addEventListener('input', () => toggleRemarkRequirement('edit'));
        }

        checkSetPrice('add');
    });
</script>
