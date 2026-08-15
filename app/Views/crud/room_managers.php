<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-people me-2"></i>จัดการผู้ดูแลห้อง</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/ ">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">กำหนดผู้ดูแลห้อง</li>
            </ol>
        </nav>
    </div>
    <div>
        <form method="POST" action="<?= SITE_URL ?>/room-managers/sync/overwrite" class="d-inline"
            onsubmit="return confirm('ต้องการเขียนทับผู้ดูแลห้องทั้งหมดหรือไม่? ข้อมูลเดิมจะถูกลบและสร้างใหม่');">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="submit" class="btn btn-danger">
                <i class="bi bi-arrow-repeat"></i> เขียนทับทั้งหมด
            </button>
        </form>
        <form method="POST" action="<?= SITE_URL ?>/room-managers/sync/fill" class="d-inline"
            onsubmit="return confirm('ต้องการเติมผู้ดูแลห้องที่ว่างเปล่าหรือไม่?');">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <button type="submit" class="btn btn-success">
                <i class="bi bi-magic"></i> เติมผู้ดูแลห้องว่าง
            </button>
        </form>
    </div>
</div>

<!-- Add Form -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-light">
        <h6 class="mb-0"><i class="bi bi-plus-circle"></i> เพิ่มผู้ดูแลห้อง</h6>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= SITE_URL ?>/room-managers" class="row g-3 align-items-end">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="col-md-4">
                <label class="form-label">ห้อง <span class="text-danger">*</span></label>
                <select name="room_id" class="form-select" required>
                    <option value="">-- เลือกห้อง --</option>
                    <?php foreach ($rooms as $room): ?>
                        <option value="<?= $room['id'] ?>"><?= htmlspecialchars($room['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">ผู้ใช้ <span class="text-danger">*</span></label>
                <select name="user_id" class="form-select" required>
                    <option value="">-- เลือกผู้ใช้ --</option>
                    <?php foreach ($users as $user): ?>
                        <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-plus-lg"></i> เพิ่ม
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Table -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th>ห้อง</th>
                        <th>ชื่อผู้ดูแล</th>
                        <th width="120" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($managers)): ?>
                        <?php foreach ($managers as $i => $mgr): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><i class="bi bi-door-open text-primary"></i> <?= htmlspecialchars($mgr['room_name']) ?></td>
                                <td><i class="bi bi-person"></i> <?= htmlspecialchars($mgr['firstname'] . ' ' . $mgr['lastname']) ?></td>
                                <td class="text-center">
                                    <form method="POST" action="<?= SITE_URL ?>/room-managers/delete/<?= $mgr['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ต้องการลบผู้ดูแลห้องนี้หรือไม่?');">
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
                            <td colspan="4" class="text-center text-muted py-4">ไม่พบข้อมูลผู้ดูแลห้อง</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
