<?php
/**
 * Department Management
 * จัดการสาขาวิชา
 */

$pageTitle = 'บริหารจัดการข้อมูลสาขาวิชา';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');

    if (empty($name)) {
        setFlash('danger', 'กรุณากรอกชื่อสาขา');
    } else {
        try {
            if ($action === 'add') {
                $stmt = $pdo->prepare("INSERT INTO dept (name) VALUES (?)");
                $stmt->execute([$name]);
                logActivity($pdo, getCurrentUserId(), 'Add Department', "เพิ่มสาขา: $name");
                setFlash('success', 'เพิ่มสาขาสำเร็จ');
            } elseif ($action === 'edit' && $id > 0) {
                $stmt = $pdo->prepare("UPDATE dept SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                logActivity($pdo, getCurrentUserId(), 'Update Department', "แก้ไขสาขา ID: $id");
                setFlash('success', 'แก้ไขสาขาสำเร็จ');
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                setFlash('danger', 'ชื่อสาขานี้มีอยู่แล้ว');
            } else {
                setFlash('danger', 'เกิดข้อผิดพลาด: ' . $e->getMessage());
            }
        }
    }
    redirect('departments.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int) $_GET['delete'];

    // Check if has sets
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM sets WHERE dept_id = ?");
    $stmt->execute([$id]);
    if ($stmt->fetchColumn() > 0) {
        setFlash('danger', 'ไม่สามารถลบได้ เนื่องจากมีชุดครุภัณฑ์ในสาขานี้');
    } else {
        $stmt = $pdo->prepare("DELETE FROM dept WHERE id = ?");
        $stmt->execute([$id]);
        logActivity($pdo, getCurrentUserId(), 'Delete Department', "ลบสาขา ID: $id");
        setFlash('success', 'ลบสาขาสำเร็จ');
    }
    redirect('departments.php');
}

// Get departments with counts
$departments = $pdo->query("
    SELECT d.*, 
           COUNT(DISTINCT s.id) as set_count,
           COUNT(DISTINCT e.id) as equipment_count
    FROM dept d
    LEFT JOIN sets s ON d.id = s.dept_id
    LEFT JOIN items i ON s.id = i.set_id
    LEFT JOIN equipment e ON i.id = e.item_id
    GROUP BY d.id
    ORDER BY d.name
")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-building me-2"></i>บริหารจัดการข้อมูลสาขาวิชา</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">สาขาวิชา</li>
            </ol>
        </nav>
    </div>
    <div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
            <i class="bi bi-plus-lg me-1"></i>เพิ่มสาขาวิชาใหม่
        </button>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list me-2"></i>รายชื่อสาขาวิชาทั้งหมด (<?= count($departments) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($departments)): ?>
            <div class="empty-state">
                <i class="bi bi-building"></i>
                <h5>ยังไม่มีสาขาวิชา</h5>
                <p class="text-muted">คลิกปุ่ม "เพิ่มสาขา" เพื่อเริ่มต้น</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th width="60" class="hide-mobile">ID</th>
                            <th>ชื่อสาขา</th>
                            <th class="text-center">ชุดครุภัณฑ์ในสังกัด</th>
                            <th class="text-center">จำนวนครุภัณฑ์</th>
                            <th class="hide-mobile">วันที่สร้าง</th>
                            <th width="120">ดําเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="hide-mobile"><?= $dept['id'] ?></td>
                                <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                <td class="text-center">
                                    <a href="sets.php?dept=<?= $dept['id'] ?>"
                                        class="badge bg-primary text-decoration-none"><?= $dept['set_count'] ?></a>
                                </td>
                                <td class="text-center">
                                    <a href="equipment.php?dept=<?= $dept['id'] ?>"
                                        class="badge bg-secondary text-decoration-none"><?= $dept['equipment_count'] ?></a>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($dept['created_at']) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary"
                                        onclick="editDept(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="departments.php?delete=<?= $dept['id'] ?>" class="btn btn-sm btn-outline-danger"
                                        data-confirm="คุณแน่ใจหรือไม่ที่จะลบสาขานี้?">
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
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="">
                <input type="hidden" name="action" value="add">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>เพิ่มสาขาใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อสาขา <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required
                            placeholder="เช่น เทคโนโลยีสารสนเทศ">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>บันทึก
                    </button>
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
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขสาขา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">ชื่อสาขา <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="editName" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg me-1"></i>บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function editDept(id, name) {
        document.getElementById('editId').value = id;
        document.getElementById('editName').value = name;
        new bootstrap.Modal(document.getElementById('editModal')).show();
    }
</script>

<?php require_once '../includes/footer.php'; ?>