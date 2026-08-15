<?php
/**
 * Staff Dashboard View
 * แดชบอร์ดเจ้าหน้าที่
 *
 * Variables from controller:
 *   $totalEquipment, $repairPending, $repairInProgress,
 *   $recentRepairs, $pendingUsers
 */
$user = getCurrentUser();
?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800"><i class="bi bi-speedometer2 me-2 text-primary"></i>แดชบอร์ดเจ้าหน้าที่</h1>
        <p class="text-muted mb-0">สวัสดี, <?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></p>
    </div>
    <div>
        <div class="bg-white px-3 py-2 rounded shadow-sm border">
            <i class="bi bi-calendar3 me-2 text-primary"></i>
            <span class="text-dark fw-medium"><?= formatDateThai(date('Y-m-d')) ?></span>
        </div>
    </div>
</div>

<!-- Stats Cards -->
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-primary">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">ครุภัณฑ์ทั้งหมด</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($totalEquipment) ?> รายการ
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-pc-display fa-2x text-gray-300 fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-warning">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">แจ้งซ่อม (รอดำเนินการ)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($repairPending) ?> รายการ
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-hourglass-split fa-2x text-gray-300 fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-info">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">กำลังซ่อม</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($repairInProgress) ?> รายการ
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-tools fa-2x text-gray-300 fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card bg-white border-0 shadow-sm h-100 py-2 border-start border-4 border-danger">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">สมาชิกรอการอนุมัติ</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800">
                            <?= number_format($pendingUsers) ?> คน
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="bi bi-person-plus fa-2x text-gray-300 fs-1 opacity-25"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($pendingUsers > 0): ?>
<div class="mb-4">
    <a href="<?= SITE_URL ?>/users/pending" class="btn btn-warning rounded-pill">
        <i class="bi bi-person-check me-2"></i>อนุมัติสมาชิก (<?= $pendingUsers ?>)
    </a>
</div>
<?php endif; ?>

<!-- Recent Repairs -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-clock-history me-2 text-primary"></i>รายการแจ้งซ่อมล่าสุด</h6>
        <a href="<?= SITE_URL ?>/repairs" class="btn btn-sm btn-outline-primary rounded-pill px-3">
            ดูทั้งหมด <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentRepairs)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox display-4 mb-3 d-block text-gray-300"></i>
                <p>ไม่มีรายการแจ้งซ่อม</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="ps-4">#</th>
                            <th>ครุภัณฑ์</th>
                            <th class="hide-mobile">ผู้แจ้ง</th>
                            <th>สถานะ</th>
                            <th class="hide-mobile">วันที่แจ้ง</th>
                            <th class="text-end pe-4">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentRepairs as $r): ?>
                            <tr>
                                <td class="ps-4">
                                    <a href="<?= SITE_URL ?>/repairs/<?= $r['id'] ?>" class="text-muted small text-decoration-none">
                                        #<?= $r['id'] ?>
                                    </a>
                                </td>
                                <td>
                                    <div class="d-flex flex-column">
                                        <span class="fw-medium text-dark"><?= htmlspecialchars($r['item_name']) ?></span>
                                        <span class="small text-muted"><?= htmlspecialchars($r['equipment_code'] ?? $r['code'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td class="hide-mobile">
                                    <?php if (!empty($r['firstname'])): ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-secondary text-white me-2"
                                                style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size: 12px;">
                                                <?= mb_substr($r['firstname'], 0, 1) . mb_substr($r['lastname'], 0, 1) ?>
                                            </div>
                                            <span><?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar-circle bg-secondary text-white me-2"
                                                style="width: 32px; height: 32px; border-radius: 50%; display:flex; align-items:center; justify-content:center; font-size: 12px;">
                                                ?
                                            </div>
                                            <span class="text-muted">ผู้ใช้ถูกลบ</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge rounded-pill bg-<?= getStatusBadgeClass($r['status']) ?>">
                                        <?= translateRepairStatus($r['status']) ?>
                                    </span>
                                </td>
                                <td class="hide-mobile text-muted small"><?= formatDateThai($r['created_at']) ?></td>
                                <td class="text-end pe-4">
                                    <a href="<?= SITE_URL ?>/repairs/<?= $r['id'] ?>"
                                        class="btn btn-sm btn-light text-primary rounded-circle" data-bs-toggle="tooltip"
                                        title="ดูรายละเอียด" aria-label="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
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
