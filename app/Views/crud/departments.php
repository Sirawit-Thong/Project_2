<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-building"></i> จัดการแผนก</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> เพิ่มแผนก
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th>ชื่อแผนก</th>
                        <th width="120" class="text-center">ชุดอุปกรณ์</th>
                        <th width="120" class="text-center">อุปกรณ์ทั้งหมด</th>
                        <th width="160" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($departments)): ?>
                        <?php foreach ($departments as $i => $dept): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($dept['name']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-info"><?= $dept['set_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $dept['equipment_count'] ?? 0 ?></span>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $dept['id'] ?>"
                                        data-name="<?= htmlspecialchars($dept['name']) ?>">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/departments/delete/<?= $dept['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ต้องการลบแผนก \"<?= htmlspecialchars($dept['name']) ?>\" หรือไม่?');">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i> ลบ
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลแผนก</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/departments">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มแผนกใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">ชื่อแผนก <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required
                            placeholder="กรอกชื่อแผนก">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-lg"></i> บันทึก
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
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> แก้ไขแผนก</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อแผนก <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required
                            placeholder="กรอกชื่อแผนก">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="bi bi-check-lg"></i> อัปเดต
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('editModal')?.addEventListener('show.bs.modal', function(e) {
    var btn = e.relatedTarget;
    document.getElementById('edit_id').value = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
});
</script>
