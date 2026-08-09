<?php
$pageTitle = 'จัดการผู้ใช้';
?>

<div class="page-header">
    <h1><i class="bi bi-people me-2"></i>จัดการผู้ใช้</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">จัดการผู้ใช้</li>
        </ol>
    </nav>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/users" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small">ค้นหา</label>
                <input type="text" class="form-control" name="search" placeholder="ชื่อ, อีเมล, รหัส..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small">บทบาท</label>
                <select class="form-select" name="role">
                    <option value="">ทั้งหมด</option>
                    <option value="admin" <?= ($_GET['role'] ?? '') === 'admin' ? 'selected' : '' ?>>ผู้ดูแลระบบ</option>
                    <option value="staff" <?= ($_GET['role'] ?? '') === 'staff' ? 'selected' : '' ?>>เจ้าหน้าที่</option>
                    <option value="teacher" <?= ($_GET['role'] ?? '') === 'teacher' ? 'selected' : '' ?>>อาจารย์</option>
                    <option value="student" <?= ($_GET['role'] ?? '') === 'student' ? 'selected' : '' ?>>นักศึกษา</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small">สถานะ</label>
                <select class="form-select" name="status">
                    <option value="">ทั้งหมด</option>
                    <option value="approved" <?= ($_GET['status'] ?? '') === 'approved' ? 'selected' : '' ?>>อนุมัติแล้ว</option>
                    <option value="pending" <?= ($_GET['status'] ?? '') === 'pending' ? 'selected' : '' ?>>รออนุมัติ</option>
                    <option value="rejected" <?= ($_GET['status'] ?? '') === 'rejected' ? 'selected' : '' ?>>ถูกปฏิเสธ</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>ค้นหา
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= SITE_URL ?>/users" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>รีเซ็ต
                </a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>รายการผู้ใช้ทั้งหมด (<?= number_format($result['total']) ?> คน)</span>
        <a href="<?= SITE_URL ?>/users/add" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>เพิ่มผู้ใช้
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($result['users'])): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-person-x fs-1 d-block mb-2"></i>
                <p>ไม่พบข้อมูลผู้ใช้</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:60px;">#</th>
                            <th>ชื่อ-นามสกุล</th>
                            <th>อีเมล</th>
                            <th>รหัส</th>
                            <th class="text-center">บทบาท</th>
                            <th class="text-center">สถานะ</th>
                            <th class="text-center" style="width:140px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['users'] as $i => $user): ?>
                            <tr>
                                <td class="text-muted"><?= ($result['pagination']['current_page'] - 1) * 10 + $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-secondary d-inline-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;min-width:32px;">
                                            <i class="bi bi-person text-white small"></i>
                                        </div>
                                        <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['sid'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    $roleColors = ['admin' => 'danger', 'staff' => 'warning', 'teacher' => 'info', 'student' => 'secondary'];
                                    $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $roleColor ?>"><?= translateRole($user['role']) ?></span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= getStatusBadgeClass($user['status']) ?>">
                                        <?= translateUserStatus($user['status']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/users/edit/<?= $user['id'] ?>" class="btn btn-outline-primary btn-sm me-1" title="แก้ไข">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="POST" action="<?= SITE_URL ?>/users/delete/<?= $user['id'] ?>" class="d-inline"
                                        onsubmit="return confirm('ยืนยันลบผู้ใช้นี้? การกระทำนี้ไม่สามารถย้อนกลับได้');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-outline-danger btn-sm" title="ลบ">
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
    <?php if (!empty($result['pagination'])): ?>
        <div class="card-footer">
            <?php
            $params = http_build_query(array_filter([
                'search' => $_GET['search'] ?? '',
                'role' => $_GET['role'] ?? '',
                'status' => $_GET['status'] ?? '',
            ]));
            $baseUrl = SITE_URL . '/users?' . ($params ? $params . '&' : '');
            ?>
            <?= paginationLinks($result['pagination'], $baseUrl) ?>
        </div>
    <?php endif; ?>
</div>


