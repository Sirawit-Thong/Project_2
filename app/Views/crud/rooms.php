<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-door-open"></i> จัดการห้อง</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">
        <i class="bi bi-plus-circle"></i> เพิ่มห้อง
    </button>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th>ชื่อห้อง</th>
                        <th width="120" class="text-center">อุปกรณ์</th>
                        <th>ผู้ดูแลห้อง</th>
                        <th width="160" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td><?= $pagination['offset'] + $i + 1 ?></td>
                                <td><i class="bi bi-door-open text-primary"></i> <?= htmlspecialchars($row['name']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-secondary"><?= $row['equipment_count'] ?? 0 ?></span>
                                </td>
                                <td>
                                    <?php
                                    $managers = $row['managers'] ?? '';
                                    $managerList = $managers ? explode(', ', $managers) : [];
                                    if (!empty($managerList)):
                                        foreach ($managerList as $m):
                                    ?>
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-person"></i> <?= htmlspecialchars(trim($m)) ?>
                                        </span>
                                    <?php
                                        endforeach;
                                    else:
                                    ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                        data-bs-target="#editModal"
                                        data-id="<?= $row['id'] ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>">
                                        <i class="bi bi-pencil-square"></i> แก้ไข
                                    </button>
                                    <form method="POST" action="<?= SITE_URL ?>/rooms/delete/<?= $row['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ต้องการลบห้อง \"<?= htmlspecialchars($row['name']) ?>\" หรือไม่?');">
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
                            <td colspan="5" class="text-center text-muted py-4">ไม่พบข้อมูลห้อง</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($pagination && $pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $pagination['total_pages']; $p++): ?>
                <li class="page-item <?= $p == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= SITE_URL ?>/rooms?page=<?= $p ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= SITE_URL ?>/rooms">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="bi bi-plus-circle"></i> เพิ่มห้องใหม่</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="add_name" class="form-label">ชื่อห้อง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="add_name" name="name" required
                            placeholder="กรอกชื่อห้อง">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> บันทึก</button>
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
                <input type="hidden" name="id" id="edit_id">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title"><i class="bi bi-pencil-square"></i> แก้ไขห้อง</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">ชื่อห้อง <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-warning"><i class="bi bi-check-lg"></i> อัปเดต</button>
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
