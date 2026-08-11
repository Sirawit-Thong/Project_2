<?php
/**
 * Rooms Management
 * จัดการห้อง/สถานที่
 */

$pageTitle = 'บริหารจัดการข้อมูลห้องและสถานที่';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        setFlash('danger', 'กรุณากรอกชื่อห้อง');
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO rooms (name) VALUES (?)");
                $stmt->execute([$name]);
                logActivity($pdo, getCurrentUserId(), 'Add Room', "เพิ่มห้อง: $name");
                setFlash('success', 'เพิ่มห้องสำเร็จ');
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $pdo->prepare("UPDATE rooms SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                logActivity($pdo, getCurrentUserId(), 'Update Room', "แก้ไขห้อง ID: $id");
                setFlash('success', 'แก้ไขห้องสำเร็จ');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                setFlash('danger', 'ชื่อห้องนี้มีอยู่แล้ว');
            } else {
                setFlash('danger', 'เกิดข้อผิดพลาด');
            }
        }
    }
    redirect('rooms.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Check if room is in use
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM equipment WHERE room_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('danger', 'ไม่สามารถลบได้ เนื่องจากมีครุภัณฑ์ในห้องนี้');
    } else {
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Delete Room', "ลบห้อง ID: $id");
        setFlash('success', 'ลบห้องสำเร็จ');
    }
    redirect('rooms.php');
}

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions) ? (int) $_GET['per_page'] : 20;

$countSql = "SELECT COUNT(*) FROM rooms";
$totalItems = $pdo->query($countSql)->fetchColumn();
$pagination = paginate($totalItems, $page, $perPage);

// Get rooms with counts
$rooms = $pdo->query("
    SELECT r.*, 
           COUNT(DISTINCT e.id) as equipment_count,
           (SELECT GROUP_CONCAT(CONCAT(u.firstname, ' ', u.lastname) SEPARATOR ', ')
            FROM room_managers rm
            JOIN users u ON rm.user_id = u.id
            WHERE rm.room_id = r.id) as managers
    FROM rooms r
    LEFT JOIN equipment e ON r.id = e.room_id
    GROUP BY r.id
    ORDER BY r.name
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-door-open me-2"></i>บริหารจัดการข้อมูลห้องและสถานที่</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ห้อง/สถานที่</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>ลงทะเบียนห้องใหม่
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
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
    <div class="card-header">
        <i class="bi bi-list me-2"></i>รายชื่อห้องทั้งหมด (<?= number_format($totalItems) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($rooms)): ?>
            <div class="empty-state">
                <i class="bi bi-door-open"></i>
                <h5>ยังไม่มีห้อง</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="60" class="hide-mobile">ID</th>
                            <th>ชื่อห้อง</th>
                            <th class="hide-mobile">ผู้รับผิดชอบดูแล</th>
                            <th class="text-center">จำนวนครุภัณฑ์ในห้อง</th>
                            <th class="hide-mobile">วันที่สร้าง</th>
                            <th width="120">ดําเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td class="hide-mobile"><?= $room['id'] ?></td>
                                <td><strong><?= htmlspecialchars($room['name']) ?></strong></td>
                                <td class="hide-mobile">
                                    <?php if ($room['managers']): ?>
                                        <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($room['managers']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="equipment.php?room=<?= $room['id'] ?>"
                                        class="badge bg-primary text-decoration-none">
                                        <?= $room['equipment_count'] ?>
                                    </a>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($room['created_at']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="editRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="rooms.php?delete=<?= $room['id'] ?>" class="btn btn-sm btn-outline-danger"
                                        data-confirm="คุณแน่ใจหรือไม่ที่จะลบห้องนี้?">
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
            <?= paginationLinks($pagination, 'rooms.php?per_page=' . $perPage) ?>
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
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>ลงทะเบียนห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อห้อง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="เช่น 6301">
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
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขห้อง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อห้อง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
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
    function editRoom(id, name) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>