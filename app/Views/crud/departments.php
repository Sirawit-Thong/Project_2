<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-building me-2"></i>บริหารจัดการข้อมูลสาขาวิชา</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
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
                            <th width="120">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $dept): ?>
                            <tr>
                                <td class="hide-mobile"><?= $dept['id'] ?></td>
                                <td><strong><?= htmlspecialchars($dept['name']) ?></strong></td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/sets?dept_id=<?= $dept['id'] ?>"
                                        class="badge bg-primary text-decoration-none"><?= $dept['set_count'] ?></a>
                                </td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/equipment?dept=<?= $dept['id'] ?>"
                                        class="badge bg-secondary text-decoration-none"><?= $dept['equipment_count'] ?></a>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($dept['created_at'] ?? null) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" aria-label="แก้ไข" title="แก้ไข"
                                        onclick="editDept(<?= $dept['id'] ?>, '<?= htmlspecialchars($dept['name'], ENT_QUOTES) ?>')">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/departments/delete/<?= $dept['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบสาขานี้?');">
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
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/departments">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-plus-circle me-2"></i>เพิ่มสาขาใหม่</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
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
            <form method="POST" action="<?= SITE_URL ?>/departments">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="editId">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>แก้ไขสาขา</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="ปิด"></button>
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
