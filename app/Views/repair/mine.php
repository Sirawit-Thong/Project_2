<?php
$pageTitle = 'รายการซ่อมของฉัน';
?>

<div class="page-header">
    <h1><i class="bi bi-list-check me-2"></i>รายการซ่อมของฉัน</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">รายการซ่อมของฉัน</li>
        </ol>
    </nav>
</div>

<div class="d-flex justify-content-end mb-3">
    <a href="<?= SITE_URL ?>/repairs/submit" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>แจ้งซ่อมใหม่
    </a>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($repairs)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <p class="mb-3">ยังไม่มีรายการแจ้งซ่อม</p>
                <a href="<?= SITE_URL ?>/repairs/submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>แจ้งซ่อมเลย
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>รหัสครุภัณฑ์</th>
                            <th>รายการ</th>
                            <th>ปัญหา</th>
                            <th class="text-center">สถานะ</th>
                            <th>วันที่แจ้ง</th>
                            <th class="text-center" style="width: 80px;">ดู</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($repairs as $i => $repair): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td><strong><?= htmlspecialchars($repair['eq_code']) ?></strong></td>
                                <td><?= htmlspecialchars($repair['item_name']) ?></td>
                                <td>
                                    <span class="d-inline-block text-truncate" style="max-width: 250px;" title="<?= htmlspecialchars($repair['issue']) ?>">
                                        <?= htmlspecialchars($repair['issue']) ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-<?= getStatusBadgeClass($repair['status']) ?>">
                                        <?= translateRepairStatus($repair['status']) ?>
                                    </span>
                                </td>
                                <td class="text-nowrap"><?= formatDateThai($repair['created_at']) ?></td>
                                <td class="text-center">
                                    <a href="<?= SITE_URL ?>/repairs/<?= $repair['id'] ?>" class="btn btn-outline-primary btn-sm" title="ดูรายละเอียด">
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


