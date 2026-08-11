<?php
/**
 * Equipment Disposal
 */
$pageTitle = 'บริหารจัดการจำหน่ายครุภัณฑ์ออก';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'pending') {
        $pdo->prepare("UPDATE equipment SET status = 'pending_disposal' WHERE id = ?")->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Pending Disposal', "เปลี่ยนสถานะรอจำหน่าย ID: $id");
        setFlash('success', 'เปลี่ยนสถานะเป็นรอจำหน่าย');
    } elseif ($action === 'dispose') {
        $pdo->prepare("UPDATE equipment SET status = 'disposed' WHERE id = ?")->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Dispose Equipment', "จำหน่ายครุภัณฑ์ ID: $id");
        setFlash('success', 'จำหน่ายครุภัณฑ์แล้ว');
    } elseif ($action === 'restore') {
        $pdo->prepare("UPDATE equipment SET status = 'available' WHERE id = ?")->execute([$id]);
        setFlash('success', 'เปลี่ยนสถานะกลับเป็นปกติ');
    }

    $returnTab = $_GET['tab'] ?? 'pending';
    redirect("equipment_disposal.php?tab=$returnTab");
}

$tab = $_GET['tab'] ?? 'pending';

$statusMap = [
    'pending' => 'pending_disposal',
    'broken' => 'broken',
    'disposed' => 'disposed'
];

if (!array_key_exists($tab, $statusMap)) {
    $tab = 'pending';
}
$activeStatus = $statusMap[$tab];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions) ? (int) $_GET['per_page'] : 20;

// Get counts for all tabs
$counts = [];
foreach ($statusMap as $key => $status) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipment WHERE status = ?");
    $stmt->execute([$status]);
    $counts[$key] = $stmt->fetchColumn();
}

$pagination = paginate($counts[$tab], $page, $perPage);

$orderBy = $tab === 'disposed' ? "e.updated_at DESC" : "e.code ASC";

$items = $pdo->query("
    SELECT e.*, i.name as item_name 
    FROM equipment e 
    JOIN items i ON e.item_id = i.id 
    WHERE e.status = '$activeStatus' 
    ORDER BY $orderBy
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
")->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-trash3 me-2"></i>บริหารจัดการจำหน่ายครุภัณฑ์ออก</h1>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>" href="?tab=pending">รอจำหน่ายออก
            (<?= number_format($counts['pending']) ?>)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'broken' ? 'active' : '' ?>" href="?tab=broken">ซ่อมไม่ได้
            (<?= number_format($counts['broken']) ?>)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'disposed' ? 'active' : '' ?>" href="?tab=disposed">จำหน่ายออก
            (<?= number_format($counts['disposed']) ?>)</a>
    </li>
</ul>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
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
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="empty-state py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3">ไม่มีรายการ</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ</th>
                            <?php if ($tab !== 'disposed'): ?>
                                <th>ห้อง</th>
                            <?php else: ?>
                                <th>วันที่จำหน่ายออก</th>
                            <?php endif; ?>
                            <?php if ($tab !== 'disposed'): ?>
                                <th width="200">ดำเนินการ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $eq): ?>
                            <tr>
                                <td><?= htmlspecialchars($eq['code']) ?></td>
                                <td><?= htmlspecialchars($eq['item_name']) ?></td>

                                <?php if ($tab !== 'disposed'): ?>
                                    <td><?= htmlspecialchars($eq['room'] ?? '-') ?></td>

                                    <td>
                                        <?php if ($tab === 'pending'): ?>
                                            <a href="?action=dispose&id=<?= $eq['id'] ?>&tab=pending" class="btn btn-sm btn-danger"
                                                data-confirm="ยืนยันจำหน่าย?">จำหน่ายออก</a>
                                            <a href="?action=restore&id=<?= $eq['id'] ?>&tab=pending"
                                                class="btn btn-sm btn-outline-secondary">ยกเลิก</a>
                                        <?php elseif ($tab === 'broken'): ?>
                                            <a href="?action=pending&id=<?= $eq['id'] ?>&tab=broken"
                                                class="btn btn-sm btn-warning">เสนอเรื่องจำหน่ายออก</a>
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td><?= formatDateThai($eq['updated_at']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, 'equipment_disposal.php?tab=' . $tab . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>