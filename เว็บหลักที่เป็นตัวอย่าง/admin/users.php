<?php
/**
 * User Management
 * จัดการผู้ใช้งาน
 */

// Load config and functions first (no HTML output yet)
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

requireRole('admin');

$pdo = getDB();

// Handle delete - BEFORE any HTML output
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Don't allow deleting self
    if ($id === getCurrentUserId()) {
        setFlash('danger', 'ไม่สามารถลบบัญชีตัวเองได้');
    } else {
        // Safe to delete (Constraints ON DELETE SET NULL/CASCADE in DB handle relations)
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            logActivity($pdo, getCurrentUserId(), 'Delete User', "ลบผู้ใช้ ID: $id");
            setFlash('success', 'ลบผู้ใช้สำเร็จ');
        }
    }
    redirect('users.php');
}


// Now include header (outputs HTML)
$pageTitle = 'บริหารจัดการบัญชีผู้ใช้งาน';
require_once '../includes/header.php';

// Get filter parameters
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 15;

// Build query
$where = [];
$params = [];

if ($search) {
    $where[] = "(firstname LIKE ? OR lastname LIKE ? OR email LIKE ? OR sid LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
}

if ($roleFilter) {
    $where[] = "role = ?";
    $params[] = $roleFilter;
}

if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total
$countSql = "SELECT COUNT(*) FROM users $whereClause";
$stmt = $pdo->prepare($countSql);
$stmt->execute($params);
$totalItems = $stmt->fetchColumn();

$pagination = paginate($totalItems, $page, $perPage);

// Get users
$sql = "SELECT * FROM users $whereClause ORDER BY created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-people me-2"></i>บริหารจัดการบัญชีผู้ใช้งาน</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ผู้ใช้งาน</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="user_form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>เพิ่มชื่อบัญชีใหม่
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="ระบุคำค้นหา..."
                        value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <select name="role" class="form-select">
                    <option value="">-- ทุกบทบาท --</option>
                    <option value="admin" <?= $roleFilter === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                    <option value="staff" <?= $roleFilter === 'staff' ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                    <option value="teacher" <?= $roleFilter === 'teacher' ? 'selected' : '' ?>>อาจารย์</option>
                    <option value="student" <?= $roleFilter === 'student' ? 'selected' : '' ?>>นักศึกษา</option>
                </select>
            </div>
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="approved" <?= $statusFilter === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                    <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-1">
                <a href="users.php" class="btn btn-outline-secondary" title="ล้างตัวกรอง">
                    <i class="bi bi-x-lg"></i> ล้าง
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>รายชื่อผู้ใช้งานทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
            <div class="empty-state">
                <i class="bi bi-person-x"></i>
                <h5>ไม่พบข้อมูลบัญชีผู้ใช้ที่ค้นหา</h5>
                <p class="text-muted">ลองเปลี่ยนเงื่อนไขการค้นหา</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="60" class="hide-mobile">ID</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th class="hide-mobile">อีเมล</th>
                            <th>รหัส</th>
                            <th>บทบาท</th>
                            <th>สถานะ</th>
                            <th class="hide-mobile">วันที่สมัคร</th>
                            <th width="120">ดําเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="hide-mobile"><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></strong>
                                    <?php if ($user['class']): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($user['class']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['sid'] ?? '-') ?></td>
                                <td>
                                    <span
                                        class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'primary' : ($user['role'] === 'teacher' ? 'info' : 'secondary')) ?>">
                                        <?= translateRole($user['role']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($user['status']) ?>">
                                        <?= translateUserStatus($user['status']) ?>
                                    </span>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($user['created_at']) ?></td>
                                <td>
                                    <a href="user_form.php?id=<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary"
                                        title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                        <a href="users.php?delete=<?= $user['id'] ?>" class="btn btn-sm btn-outline-danger"
                                            data-confirm="คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?" title="ลบ">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    <?php endif; ?>
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
            <?= paginationLinks($pagination, 'users.php?search=' . urlencode($search) . '&role=' . urlencode($roleFilter) . '&status=' . urlencode($statusFilter)) ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            const selects = filterForm.querySelectorAll('select');
            selects.forEach(select => {
                select.addEventListener('change', function () {
                    filterForm.submit();
                });
            });
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>