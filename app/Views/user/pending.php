<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-person-check me-2"></i>ตรวจสอบบัญชีผู้ใช้งานใหม่</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">รออนุมัติ</li>
            </ol>
        </nav>
    </div>
    <div>
        <span class="badge bg-warning fs-6">
            <i class="bi bi-hourglass-split me-1"></i><?= count($pendingUsers) ?> รายการ
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i>รายชื่อผู้ใช้งานรอการตรวจสอบระบุตัวตน
    </div>
    <div class="card-body p-0">
        <?php if (empty($pendingUsers)): ?>
            <div class="empty-state">
                <i class="bi bi-check-circle text-success"></i>
                <h5>ไม่พบรายชื่อผู้ใช้งานที่รอการตรวจสอบ</h5>
                <p class="text-muted">บัญชีผู้ใช้งานใหม่ทั้งหมดได้รับการดำเนินการแล้ว</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>ชื่อ-นามสกุล</th>
                            <th class="hide-mobile">อีเมล</th>
                            <th class="hide-mobile">รหัสนักศึกษา</th>
                            <th class="hide-mobile">ชั้นปี/ห้อง</th>
                            <th>บทบาท</th>
                            <th class="hide-mobile">วันที่สมัคร</th>
                            <th width="130">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingUsers as $user): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></strong>
                                </td>
                                <td class="hide-mobile"><?= htmlspecialchars($user['email']) ?></td>
                                <td class="hide-mobile"><?= htmlspecialchars($user['sid'] ?? '-') ?></td>
                                <td class="hide-mobile"><?= htmlspecialchars($user['class'] ?? '-') ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role'] === 'teacher' ? 'info' : 'secondary' ?>">
                                        <?= translateRole($user['role']) ?>
                                    </span>
                                </td>
                                <td class="hide-mobile"><?= formatDateThai($user['created_at']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="POST" action="<?= SITE_URL ?>/users/pending/<?= $user['id'] ?>/approve" class="d-inline">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-success" title="อนุมัติ">
                                                <i class="bi bi-check-lg"></i> อนุมัติ
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= SITE_URL ?>/users/pending/<?= $user['id'] ?>/reject" class="d-inline"
                                            onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะปฏิเสธผู้ใช้นี้? ข้อมูลจะถูกลบ');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger" title="ปฏิเสธ">
                                                <i class="bi bi-x-lg"></i> ปฏิเสธ
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>