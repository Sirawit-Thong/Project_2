<?php
/**
 * Equipment Management
 * จัดการทะเบียนครุภัณฑ์
 */

$pageTitle = 'ทะเบียนครุภัณฑ์';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Check if has repairs
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM repair WHERE equipment_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('danger', 'ไม่สามารถลบได้ เนื่องจากมีประวัติการซ่อม');
    } else {
        // Delete images
        $stmt = $pdo->prepare("DELETE FROM equipment_img WHERE equipment_id = ?");
        $stmt->execute([$id]);

        $stmt = $pdo->prepare("DELETE FROM equipment WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Delete Equipment', "ลบครุภัณฑ์ ID: $id");
        setFlash('success', 'ลบครุภัณฑ์สำเร็จ');
    }
    redirect('equipment.php');
}

// Get filter parameters
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$roomFilter = $_GET['room'] ?? '';
$itemFilter = $_GET['item'] ?? '';
$deptFilter = $_GET['dept'] ?? '';
$setFilter = $_GET['set'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions) ? (int) $_GET['per_page'] : 20;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(e.code LIKE ? OR i.name LIKE ? OR i.brand LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
}

if ($statusFilter) {
    $where[] = "e.status = ?";
    $params[] = $statusFilter;
}

if ($roomFilter) {
    $where[] = "e.room_id = ?";
    $params[] = $roomFilter;
}

if ($itemFilter) {
    $where[] = "e.item_id = ?";
    $params[] = $itemFilter;
}

if ($deptFilter) {
    $where[] = "s.dept_id = ?";
    $params[] = $deptFilter;
}

if ($setFilter) {
    $where[] = "i.set_id = ?";
    $params[] = $setFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total - include sets table join when needed for dept/set filter
$countJoins = "JOIN items i ON e.item_id = i.id";
if ($deptFilter || $setFilter) {
    $countJoins .= " JOIN sets s ON i.set_id = s.id";
}
$countSql = "SELECT COUNT(*) FROM equipment e $countJoins $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

$pagination = paginate($totalItems, $page, $perPage);

// Get equipment
$sql = "
    SELECT e.*, e.price_remark as eq_price_remark, 
           i.name as item_name, i.brand, i.model, i.price_remark as item_price_remark,
           s.name as set_name, s.year as set_year, s.price_remark as set_price_remark,
           d.name as dept_name,
           u.firstname as holder_firstname, u.lastname as holder_lastname,
           rm.name as room_name
    FROM equipment e
    JOIN items i ON e.item_id = i.id
    JOIN sets s ON i.set_id = s.id
    LEFT JOIN dept d ON s.dept_id = d.id
    LEFT JOIN users u ON e.holder_id = u.id
    LEFT JOIN rooms rm ON e.room_id = rm.id
    $whereClause
    ORDER BY e.code ASC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$equipment = $stmt->fetchAll();

// Get rooms for filter
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

// Get departments for filter
$departments = $pdo->query("SELECT id, name FROM dept ORDER BY name")->fetchAll();

// Get sets for filter
$sets = $pdo->query("SELECT id, name, year, dept_id FROM sets ORDER BY year DESC, name")->fetchAll();

// Get items for filter
$itemsQuery = $pdo->query("
    SELECT i.id, i.name, i.brand, i.set_id, s.dept_id 
    FROM items i
    JOIN sets s ON i.set_id = s.id
    ORDER BY s.year DESC, i.name
")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-pc-display me-2"></i>ทะเบียนครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ครุภัณฑ์</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <a href="equipment_form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>ลงทะเบียนครุภัณฑ์ใหม่
        </a>
        <a href="equipment_bulk_add.php" class="btn btn-success">
            <i class="bi bi-plus-square me-1"></i>ลงทะเบียนหลายรายการ
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm">
            <!-- Row 1 -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="ระบุคำค้นหา..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept" id="filterDept" class="form-select">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= $deptFilter == $dept['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dept['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="set" id="filterSet" class="form-select">
                    <option value="">-- ทุกชุดครุภัณฑ์ --</option>
                    <?php foreach ($sets as $set): ?>
                        <option value="<?= $set['id'] ?>" data-dept="<?= $set['dept_id'] ?>" <?= $setFilter == $set['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($set['name']) ?> (
                            <?= htmlspecialchars($set['year']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Row 2 -->
            <div class="col-md-4">
                <select name="item" id="filterItem" class="form-select">
                    <option value="">-- ทุกรายการครุภัณฑ์ --</option>
                    <?php foreach ($itemsQuery as $it): ?>
                        <option value="<?= $it['id'] ?>" data-dept="<?= $it['dept_id'] ?>" data-set="<?= $it['set_id'] ?>"
                            <?= $itemFilter == $it['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($it['name']) ?>
                            <?= $it['brand'] ? '(' . htmlspecialchars($it['brand']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>พร้อมใช้งาน
                    </option>
                    <option value="repair" <?= $statusFilter === 'repair' ? 'selected' : '' ?>>ส่งซ่อม</option>
                    <option value="broken" <?= $statusFilter === 'broken' ? 'selected' : '' ?>>ซ่อมไม่ได้</option>
                    <option value="pending_disposal" <?= $statusFilter === 'pending_disposal' ? 'selected' : '' ?>>
                        รอจำหน่ายออก</option>
                    <option value="disposed" <?= $statusFilter === 'disposed' ? 'selected' : '' ?>>จำหน่ายออก</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="room" class="form-select">
                    <option value="">-- ทุกห้อง --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>" <?= $roomFilter == $room['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($room['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-1">
                    <a href="equipment.php" class="btn btn-outline-secondary" title="ล้างตัวกรอง">
                        <i class="bi bi-x-lg"></i>ล้าง
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>รายการครุภัณฑ์ทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($equipment)): ?>
            <div class="empty-state">
                <i class="bi bi-pc-display"></i>
                <h5>ไม่พบข้อมูลครุภัณฑ์ที่ค้นหา</h5>
                <p class="text-muted">ลองเปลี่ยนเงื่อนไขการค้นหา</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ชื่อ/ยี่ห้อ/รุ่น</th>
                            <th class="hide-mobile">ห้อง</th>
                            <th class="hide-mobile">ผู้รับผิดชอบดูแล</th>
                            <th class="text-end hide-mobile">ราคา</th>
                            <th>สถานะ</th>
                            <th width="140">ดําเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $eq): ?>
                            <tr>
                                <td>
                                    <a href="equipment_detail.php?id=<?= $eq['id'] ?>">
                                        <strong><?= htmlspecialchars($eq['code'] ?? 'N/A') ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?= htmlspecialchars($eq['item_name']) ?>
                                    <br><small class="text-muted">
                                        <?= htmlspecialchars($eq['brand'] ?? '') ?>         <?= htmlspecialchars($eq['model'] ?? '') ?>
                                    </small>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($eq['room_name'] ?? '-') ?></td>
                                <td class="hide-mobile">
                                    <?php if ($eq['holder_firstname']): ?>
                                        <?= htmlspecialchars($eq['holder_firstname'] . ' ' . $eq['holder_lastname']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end hide-mobile">
                                    <?php
                                    // Cascade logic for determining which remark to show (most specific first)
                                    $displayRemark = '';
                                    if (!empty($eq['eq_price_remark'])) {
                                        $displayRemark = $eq['eq_price_remark'] . ' (เฉพาะชิ้น)';
                                    } elseif (!empty($eq['item_price_remark'])) {
                                        $displayRemark = $eq['item_price_remark'] . ' (ทั้งรายการ)';
                                    } elseif (!empty($eq['set_price_remark'])) {
                                        $displayRemark = $eq['set_price_remark'] . ' (ทั้งชุด)';
                                    }

                                    // Only show price if it's greater than 0, or if there is no inherited remark
                                    if ($eq['price'] > 0 || !$displayRemark):
                                        ?>
                                        <?= number_format($eq['price'], 2) ?>
                                    <?php endif; ?>

                                    <?php if ($displayRemark): ?>
                                        <?= ($eq['price'] > 0 || !$displayRemark) ? '<br>' : '' ?>
                                        <span class="badge bg-info text-dark" title="<?= htmlspecialchars($displayRemark) ?>"
                                            data-bs-toggle="tooltip" style="cursor: help;"><i
                                                class="bi bi-info-circle me-1"></i>มีหมายเหตุราคา</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>">
                                        <?= translateEquipmentStatus($eq['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="equipment_detail.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-info"
                                        title="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="equipment_form.php?id=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary"
                                        title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="equipment.php?delete=<?= $eq['id'] ?>" class="btn btn-sm btn-outline-danger"
                                        data-confirm="คุณแน่ใจหรือไม่ที่จะลบครุภัณฑ์นี้?" title="ลบ">
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
            <?= paginationLinks($pagination, 'equipment.php?search=' . urlencode($search) . '&status=' . urlencode($statusFilter) . '&room=' . urlencode($roomFilter) . '&dept=' . urlencode($deptFilter) . '&set=' . urlencode($setFilter) . '&item=' . urlencode($itemFilter) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Cascading Filters Logic
        const filterDept = document.getElementById('filterDept');
        const filterSet = document.getElementById('filterSet');
        const filterItem = document.getElementById('filterItem');

        if (filterDept && filterSet && filterItem) {
            const allSetOptions = Array.from(filterSet.options).slice(1);
            const allItemOptions = Array.from(filterItem.options).slice(1);

            function updateSets() {
                const deptId = filterDept.value;
                const currentSet = filterSet.value;
                filterSet.innerHTML = '<option value="">-- ทุกชุดครุภัณฑ์ --</option>';

                let hasValidSet = false;
                allSetOptions.forEach(opt => {
                    if (!deptId || opt.dataset.dept == deptId) {
                        const newOpt = opt.cloneNode(true);
                        if (newOpt.value == currentSet) {
                            newOpt.selected = true;
                            hasValidSet = true;
                        }
                        filterSet.appendChild(newOpt);
                    }
                });

                if (!hasValidSet && currentSet) {
                    filterSet.value = '';
                }
                updateItems();
            }

            function updateItems() {
                const deptId = filterDept.value;
                const setId = filterSet.value;
                const currentItem = filterItem.value;
                filterItem.innerHTML = '<option value="">-- ทุกรายการครุภัณฑ์ --</option>';

                let hasValidItem = false;
                allItemOptions.forEach(opt => {
                    const matchDept = !deptId || opt.dataset.dept == deptId;
                    const matchSet = !setId || opt.dataset.set == setId;
                    if (matchDept && matchSet) {
                        const newOpt = opt.cloneNode(true);
                        if (newOpt.value == currentItem) {
                            newOpt.selected = true;
                            hasValidItem = true;
                        }
                        filterItem.appendChild(newOpt);
                    }
                });

                if (!hasValidItem && currentItem) {
                    filterItem.value = '';
                }
            }

            // Initialize state on load without triggering form submit
            updateSets();

            filterDept.addEventListener('change', function () {
                filterSet.value = ''; // Reset set when dept changes
                filterItem.value = ''; // Reset item when dept changes
                updateSets();
                document.getElementById('filterForm').submit();
            });

            filterSet.addEventListener('change', function () {
                filterItem.value = ''; // Reset item when set changes
                updateItems();
                document.getElementById('filterForm').submit();
            });

            filterItem.addEventListener('change', function () {
                document.getElementById('filterForm').submit();
            });
        }

        // Auto-submit for other filters
        const otherSelects = document.querySelectorAll('#filterForm select:not(#filterDept):not(#filterSet):not(#filterItem)');
        otherSelects.forEach(select => {
            select.addEventListener('change', function () {
                document.getElementById('filterForm').submit();
            });
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>