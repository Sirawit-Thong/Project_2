<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
$totalItems = $pagination['total_items'] ?? count($rows);
$deptFilter = $_GET['dept_id'] ?? '';
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
    ? (int) $_GET['per_page']
    : 20;
$paginationBaseUrl = SITE_URL . '/sets?dept_id=' . urlencode($deptFilter) . '&per_page=' . $perPage;
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-collection me-2"></i>บริหารจัดการชุดครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ชุดครุภัณฑ์</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>เพิ่มชุดครุภัณฑ์ใหม่
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" action="<?= SITE_URL ?>/sets" class="row g-2 align-items-center">
            <div class="col-auto">
                <label class="form-label mb-0 me-2">สาขาวิชา:</label>
            </div>
            <div class="col-md-3">
                <select name="dept_id" class="form-select form-select-sm">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-search me-1"></i>กรอง
                </button>
            </div>
            <div class="col-auto ms-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 120px;"
                    onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <a href="<?= SITE_URL ?>/sets" class="btn btn-sm btn-outline-secondary">ล้าง</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>รายการชุดครุภัณฑ์ทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($rows)): ?>
            <div class="empty-state">
                <i class="bi bi-collection"></i>
                <h5>ยังไม่มีชุดครุภัณฑ์</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="60" class="hide-mobile">ID</th>
                            <th>ชื่อชุดครุภัณฑ์</th>
                            <th class="hide-mobile">สาขาวิชา</th>
                            <th class="text-center hide-mobile">ปีงบประมาณ</th>
                            <th class="text-end hide-mobile">มูลค่ารวมทั้งสิ้น</th>
                            <th class="text-center">รายการ</th>
                            <th class="text-center">ครุภัณฑ์</th>
                            <th width="120">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $set): ?>
                            <tr>
                                <td class="hide-mobile"><?= $set['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($set['name']) ?></strong>
                                    <?php if (!empty($set['remark'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($set['remark']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($set['dept_name'] ?? '-') ?></td>
                                <td class="text-center hide-mobile"><?= htmlspecialchars($set['year']) ?></td>
                                <td class="text-end hide-mobile"><?= number_format($set['price'], 2) ?></td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/items?set_id=<?= $set['id'] ?>" class="badge bg-primary text-decoration-none">
                                        <?= $set['item_count'] ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/equipment?set=<?= $set['id'] ?>"
                                        class="badge bg-secondary text-decoration-none">
                                        <?= $set['equipment_count'] ?>
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" aria-label="แก้ไข" title="แก้ไข" onclick='editSet(<?= json_encode($set, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/sets/delete/<?= $set['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบชุดนี้?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" aria-label="ลบ" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/sets">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>ลงทะเบียนชุดครุภัณฑ์ใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">สาขาวิชา</label>
                        <select class="form-select" name="dept_id">
                            <option value="">-- เลือกสาขา --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อชุดครุภัณฑ์ <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ปีงบประมาณ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="year" required placeholder="เช่น 2567"
                                value="<?= date('Y') + 543 ?>"><!-- ปี พ.ศ. ปัจจุบัน -->
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">มูลค่ารวมทั้งสิ้น (บาท)</label>
                            <input type="number" class="form-control" name="price" id="addPrice" step="0.01" value="0">
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label" id="addPriceRemarkLabel">หมายเหตุราคา (เช่น 5 ล้านบาท/ชุด)</label>
                            <input type="text" class="form-control" name="price_remark" id="addPriceRemark"
                                placeholder="คำอธิบายราคาชุด">
                        </div>
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
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/sets">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขชุดครุภัณฑ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">สาขา</label>
                        <select class="form-select" name="dept_id" id="editDeptId">
                            <option value="">-- เลือกสาขา --</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ชื่อชุด <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">ปีงบประมาณ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="year" id="editYear" required>
                        </div>
                        <div class="col-md-5 mb-3">
                            <label class="form-label">ราคารวม (บาท)</label>
                            <input type="number" class="form-control" name="price" id="editPrice" step="0.01">
                        </div>
                        <div class="col-md-7 mb-3">
                            <label class="form-label" id="editPriceRemarkLabel">หมายเหตุราคา</label>
                            <input type="text" class="form-control" name="price_remark" id="editPriceRemark"
                                placeholder="คำอธิบายราคาชุด">
                        </div>
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
    function toggleRemarkRequirement(prefix) {
        let priceInput = document.getElementById(prefix + 'Price');
        let remarkInput = document.getElementById(prefix + 'PriceRemark');
        let label = document.getElementById(prefix + 'PriceRemarkLabel');

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
            toggleRemarkRequirement('add');
        }

        let editPrice = document.getElementById('editPrice');
        if (editPrice) {
            editPrice.addEventListener('input', () => toggleRemarkRequirement('edit'));
        }
    });

    function editSet(set) {
        document.getElementById('editId').value = set.id;
        document.getElementById('editDeptId').value = set.dept_id || '';
        document.getElementById('editName').value = set.name;
        document.getElementById('editYear').value = set.year;
        document.getElementById('editPrice').value = set.price;
        document.getElementById('editPriceRemark').value = set.price_remark || '';
        document.getElementById('editRemark').value = set.remark || '';
        toggleRemarkRequirement('edit');
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>
