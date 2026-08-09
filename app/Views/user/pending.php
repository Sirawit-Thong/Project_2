<?php
$pageTitle = 'รออนุมัติผู้ใช้';
?>

<div class="page-header">
    <h1><i class="bi bi-person-check me-2"></i>รออนุมัติผู้ใช้</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">รออนุมัติผู้ใช้</li>
        </ol>
    </nav>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-clock-history me-2"></i>ผู้ใช้ที่รอการอนุมัติ (<?= count($pendingUsers) ?> คน)
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingUsers)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-check-circle fs-1 d-block mb-2 text-success"></i>
                <p>ไม่มีผู้ใช้ที่รอการอนุมัติ</p>
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
                            <th>วันที่สมัคร</th>
                            <th class="text-center" style="width:180px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $i => $user): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle bg-warning d-inline-flex align-items-center justify-content-center me-2" style="width:32px;height:32px;min-width:32px;">
                                            <i class="bi bi-person text-white small"></i>
                                        </div>
                                        <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></strong>
                                    </div>
                                </td>
                                <td class="text-muted"><?= htmlspecialchars($user['email']) ?></td>
                                <td><?= htmlspecialchars($user['sid'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                    $roleColors = ['teacher' => 'info', 'student' => 'secondary'];
                                    $roleColor = $roleColors[$user['role']] ?? 'secondary';
                                    ?>
                                    <span class="badge bg-<?= $roleColor ?>"><?= translateRole($user['role']) ?></span>
                                </td>
                                <td class="text-nowrap"><?= formatDateThai($user['created_at']) ?></td>
                                <td class="text-center">
                                    <form method="POST" action="<?= SITE_URL ?>/users/pending/<?= $user['id'] ?>/approve" class="d-inline">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-success btn-sm me-1" title="อนุมัติ">
                                            <i class="bi bi-check-lg me-1"></i>อนุมัติ
                                        </button>
                                    </form>
                                    <form method="POST" action="<?= SITE_URL ?>/users/pending/<?= $user['id'] ?>/reject" class="d-inline"
                                        onsubmit="return confirm('ยืนยันปฏิเสธผู้ใช้นี้?');">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="btn btn-danger btn-sm" title="ปฏิเสธ">
                                            <i class="bi bi-x-lg me-1"></i>ปฏิเสธ
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


