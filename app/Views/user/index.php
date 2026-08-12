<?php
$search = $_GET['search'] ?? '';
$roleFilter = $_GET['role'] ?? '';
$statusFilter = $_GET['status'] ?? '';

$baseUrl = SITE_URL . '/users?' . http_build_query(array_filter([
    'search' => $search,
    'role' => $roleFilter,
    'status' => $statusFilter,
]));
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-people me-2"></i>บริหารจัดการบัญชีผู้ใช้งาน</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ผู้ใช้งาน</li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="<?= SITE_URL ?>/users/add" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>เพิ่มชื่อบัญชีใหม่
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm" action="<?= SITE_URL ?>/users">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="ระบุคำค้นหา..."
                        value="<?= sanitize($search) ?>">
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
                <a href="<?= SITE_URL ?>/users" class="btn btn-outline-secondary" title="ล้างตัวกรอง">
                    <i class="bi bi-x-lg"></i> ล้าง
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Users Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>รายชื่อผู้ใช้งานทั้งหมด (<?= number_format($result['total']) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($result['users'])): ?>
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
                            <th width="120">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['users'] as $user): ?>
                            <tr>
                                <td class="hide-mobile"><?= $user['id'] ?></td>
                                <td>
                                    <strong><?= sanitize($user['firstname'] . ' ' . $user['lastname']) ?></strong>
                                    <?php if ($user['class']): ?>
                                        <br><small class="text-muted"><?= sanitize($user['class']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="hide-mobile"><?= sanitize($user['email']) ?></td>
                                <td><?= sanitize($user['sid'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'staff' ? 'primary' : ($user['role'] === 'teacher' ? 'info' : 'secondary')) ?>">
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
                                    <a href="<?= SITE_URL ?>/users/edit/<?= $user['id'] ?>" class="btn btn-sm btn-outline-primary"
                                        title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($user['id'] !== getCurrentUserId()): ?>
                                        <form method="POST" action="<?= SITE_URL ?>/users/delete/<?= $user['id'] ?>" class="d-inline"
                                            onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบผู้ใช้นี้?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if (($result['pagination']['total_pages'] ?? 1) > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($result['pagination'], $baseUrl) ?>
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
