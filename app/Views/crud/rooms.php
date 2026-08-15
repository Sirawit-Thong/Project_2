<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
$totalItems = $pagination['total_items'] ?? count($rows);
$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
    ? (int) $_GET['per_page']
    : 20;
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-door-open me-2"></i>บริหารจัดการข้อมูลห้องและสถานที่</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
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
        <form method="GET" action="<?= SITE_URL ?>/rooms" class="row g-2 align-items-center">
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
        <?php if (empty($rows)): ?>
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
                            <th width="120">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $room): ?>
                            <tr>
                                <td class="hide-mobile"><?= $room['id'] ?></td>
                                <td><strong><?= htmlspecialchars($room['name']) ?></strong></td>
                                <td class="hide-mobile">
                                    <?php if (!empty($room['managers'])): ?>
                                        <i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($room['managers']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/equipment?room=<?= $room['id'] ?>"
                                        class="badge bg-primary text-decoration-none">
                                        <?= $room['equipment_count'] ?>
                                    </a>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($room['created_at'] ?? null) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" aria-label="แก้ไข" title="แก้ไข"
                                        onclick="editRoom(<?= $room['id'] ?>, '<?= htmlspecialchars($room['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/rooms/delete/<?= $room['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบห้องนี้?');">
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
            <?= paginationLinks($pagination, SITE_URL . '/rooms?per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/rooms">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>ลงทะเบียนห้องใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
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
            <form method="POST" action="<?= SITE_URL ?>/rooms">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขห้อง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
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
