<?php
/**
 * Sets Management
 * จัดการชุดครุภัณฑ์
 */

$pageTitle = 'บริหารจัดการชุดครุภัณฑ์';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $dept_id = (int) ($_POST['dept_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $year = trim($_POST['year'] ?? '');
    $price = (float) ($_POST['price'] ?? 0);
    $price_remark = trim($_POST['price_remark'] ?? '');
    $remark = trim($_POST['remark'] ?? '');

    if (empty($name) || empty($year)) {
        setFlash('danger', 'กรุณากรอกข้อมูลที่จำเป็น');
    } elseif ($price > 0 && empty($price_remark)) {
        setFlash('danger', 'กรุณาระบุหมายเหตุราคา เนื่องจากมีการใส่ราคารวมของชุดครุภัณฑ์');
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO sets (dept_id, name, year, price, price_remark, remark) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$dept_id ?: null, $name, $year, $price, $price_remark ?: null, $remark ?: null]);
                logActivity($pdo, getCurrentUserId(), 'Add Set', "เพิ่มชุด: $name");
                setFlash('success', 'เพิ่มชุดครุภัณฑ์สำเร็จ');
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $pdo->prepare("UPDATE sets SET dept_id = ?, name = ?, year = ?, price = ?, price_remark = ?, remark = ? WHERE id = ?");
                $stmt->execute([$dept_id ?: null, $name, $year, $price, $price_remark ?: null, $remark ?: null, $id]);
                logActivity($pdo, getCurrentUserId(), 'Update Set', "แก้ไขชุด ID: $id");
                setFlash('success', 'แก้ไขชุดครุภัณฑ์สำเร็จ');
            }
        } catch (PDOException $e) {
            setFlash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
        }
    }
    redirect('sets.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM items WHERE set_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('danger', 'ไม่สามารถลบได้ เนื่องจากมีรายการครุภัณฑ์ในชุดนี้');
    } else {
        $stmt = $pdo->prepare("DELETE FROM sets WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Delete Set', "ลบชุด ID: $id");
        setFlash('success', 'ลบชุดครุภัณฑ์สำเร็จ');
    }
    redirect('sets.php');
}

// Get departments for dropdown
$departments = $pdo->query("SELECT * FROM dept ORDER BY name")->fetchAll();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions) ? (int) $_GET['per_page'] : 20;

$deptFilter = $_GET['dept'] ?? '';
$where = $deptFilter ? "WHERE s.dept_id = " . (int) $deptFilter : '';

// Count total
$countSql = "SELECT COUNT(*) FROM sets s $where";
$totalItems = $pdo->query($countSql)->fetchColumn();
$pagination = paginate($totalItems, $page, $perPage);

$sets = $pdo->query("
    SELECT s.*, d.name as dept_name,
           COUNT(DISTINCT i.id) as item_count,
           COUNT(DISTINCT e.id) as equipment_count
    FROM sets s
    LEFT JOIN dept d ON s.dept_id = d.id
    LEFT JOIN items i ON s.id = i.set_id
    LEFT JOIN equipment e ON i.id = e.item_id
    $where
    GROUP BY s.id
    ORDER BY s.year DESC, s.name
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-collection me-2"></i>บริหารจัดการชุดครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
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
        <form method="GET" class="row g-2 align-items-center auto-submit">
            <div class="col-auto">
                <label class="form-label mb-0 me-2">สาขาวิชา:</label>
            </div>
            <div class="col-md-3">
                <select name="dept" class="form-select form-select-sm">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
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
                <a href="sets.php" class="btn btn-sm btn-outline-secondary">ล้าง</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>รายการชุดครุภัณฑ์ทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($sets)): ?>
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
                            <th width="120">ดําเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sets as $set): ?>
                            <tr>
                                <td class="hide-mobile"><?= $set['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($set['name']) ?></strong>
                                    <?php if ($set['remark']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($set['remark']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($set['dept_name'] ?? '-') ?></td>
                                <td class="text-center hide-mobile"><?= htmlspecialchars($set['year']) ?></td>
                                <td class="text-end hide-mobile"><?= number_format($set['price'], 2) ?></td>
                                <td class="text-center">
                                    <a href="items.php?set=<?= $set['id'] ?>" class="badge bg-primary text-decoration-none">
                                        <?= $set['item_count'] ?>
                                    </a>
                                </td>
                                <td class="text-center">
                                    <a href="equipment.php?set=<?= $set['id'] ?>"
                                        class="badge bg-secondary text-decoration-none">
                                        <?= $set['equipment_count'] ?>
                                    </a>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" onclick='editSet(<?= json_encode($set) ?>)'>
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="sets.php?delete=<?= $set['id'] ?>" class="btn btn-sm btn-outline-danger"
                                        data-confirm="คุณแน่ใจหรือไม่ที่จะลบชุดนี้?">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, 'sets.php?dept=' . urlencode($deptFilter) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>ลงทะเบียนชุดครุภัณฑ์ใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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
                                value="<?= date('Y') + 543 ?>">
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
            <form method="POST" action="">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editId">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขชุดครุภัณฑ์</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
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

<?php require_once '../includes/footer.php'; ?>