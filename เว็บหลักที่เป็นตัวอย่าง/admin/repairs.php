<?php
/**
 * Repairs Management
 * จัดการการแจ้งซ่อม
 */

$pageTitle = 'รายการแจ้งซ่อมครุภัณฑ์ ทั้งหมด';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions) ? (int) $_GET['per_page'] : 20;

$where = $statusFilter ? "WHERE r.status = ?" : '';
$params = $statusFilter ? [$statusFilter] : [];

$stmt = $pdo->prepare("SELECT COUNT(*) FROM repair r $where");
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();
$pagination = paginate($totalItems, $page, $perPage);

$sql = "SELECT r.*, e.code as equipment_code, i.name as item_name, u.firstname, u.lastname, u.role as user_role
    FROM repair r JOIN equipment e ON r.equipment_id = e.id JOIN items i ON e.item_id = i.id LEFT JOIN users u ON r.user_id = u.id
    $where ORDER BY CASE r.status WHEN 'pending' THEN 1 WHEN 'in_progress' THEN 2 ELSE 3 END, r.created_at DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$repairs = $stmt->fetchAll();

$statusCounts = $pdo->query("SELECT status, COUNT(*) as count FROM repair GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-header">
    <h1><i class="bi bi-wrench-adjustable me-2"></i>รายการแจ้งซ่อมครุภัณฑ์ ทั้งหมด</h1>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="repairs.php" class="text-decoration-none">
                    <h3><?= array_sum($statusCounts) ?></h3><small>ทั้งหมด</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="repairs.php?status=pending" class="text-decoration-none text-warning">
                    <h3><?= $statusCounts['pending'] ?? 0 ?></h3><small>รอดำเนินการ</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="repairs.php?status=in_progress"
                    class="text-decoration-none text-primary">
                    <h3><?= $statusCounts['in_progress'] ?? 0 ?></h3><small>กำลังซ่อม</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="repairs.php?status=completed"
                    class="text-decoration-none text-success">
                    <h3><?= $statusCounts['completed'] ?? 0 ?></h3><small>ซ่อมเสร็จ</small>
                </a></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <?php if ($statusFilter): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
            <?php endif; ?>
            <div class="col-auto">
                <label class="form-label mb-0 me-2">แสดง:</label>
            </div>
            <div class="col-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 120px;"
                    onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>รายการ (<?= $totalItems ?> รายการ)</div>
    <div class="card-body p-0">
        <?php if (empty($repairs)): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i>
                <h5>ไม่พบรายการ</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="hide-mobile">#</th>
                            <th>ครุภัณฑ์</th>
                            <th class="hide-mobile">ผู้แจ้งซ่อม</th>
                            <th>อาการ</th>
                            <th>สถานะ</th>
                            <th class="hide-mobile">วันที่</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repairs as $r): ?>
                            <tr>
                                <td class="hide-mobile"><?= $r['id'] ?></td>
                                <td><strong><?= htmlspecialchars($r['equipment_code'] ?? '-') ?></strong><br><small><?= htmlspecialchars($r['item_name']) ?></small>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></td>
                                <td><?= mb_substr(htmlspecialchars($r['issue']), 0, 50) ?>...</td>
                                <td><span
                                        class="badge bg-<?= getStatusBadgeClass($r['status']) ?>"><?= translateRepairStatus($r['status']) ?></span>
                                </td>
                                <td class="hide-mobile"><?= formatDateTimeThai($r['created_at']) ?></td>
                                <td><a href="repair_detail.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary"><i
                                            class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, 'repairs.php?status=' . urlencode($statusFilter) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>