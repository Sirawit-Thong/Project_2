<?php
$pageTitle = 'รายการแจ้งซ่อม';
$currentStatus = $_GET['status'] ?? '';
$perPageOptions = $perPageOptions ?? [10, 20, 50, 100];
$perPage = $perPage ?? 20;
?>

<div class="page-header">
    <h1><i class="bi bi-tools me-2"></i>รายการแจ้งซ่อม</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">รายการแจ้งซ่อม</li>
        </ol>
    </nav>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="<?= SITE_URL ?>/repairs" class="text-decoration-none text-body">
            <div class="card border-start border-primary border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ทั้งหมด</div>
                            <h4 class="mb-0"><?= number_format($result['total']) ?></h4>
                        </div>
                        <i class="bi bi-tools fs-1 text-primary opacity-25"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= SITE_URL ?>/repairs?status=pending" class="text-decoration-none text-body">
            <div class="card border-start border-warning border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">รอดำเนินการ</div>
                            <h4 class="mb-0"><?= number_format($statusCounts['pending'] ?? 0) ?></h4>
                        </div>
                        <i class="bi bi-hourglass-split fs-1 text-warning opacity-25"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= SITE_URL ?>/repairs?status=in_progress" class="text-decoration-none text-body">
            <div class="card border-start border-info border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">กำลังซ่อม</div>
                            <h4 class="mb-0"><?= number_format($statusCounts['in_progress'] ?? 0) ?></h4>
                        </div>
                        <i class="bi bi-gear fs-1 text-info opacity-25"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="<?= SITE_URL ?>/repairs?status=completed" class="text-decoration-none text-body">
            <div class="card border-start border-success border-4 h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-muted small">ซ่อมเสร็จ</div>
                            <h4 class="mb-0"><?= number_format($statusCounts['completed'] ?? 0) ?></h4>
                        </div>
                        <i class="bi bi-check-circle fs-1 text-success opacity-25"></i>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <?php if ($currentStatus): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
            <?php endif; ?>
            <div class="col-auto">
                <label class="form-label mb-0 me-2">แสดง:</label>
            </div>
            <div class="col-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 130px;" onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-2"></i>รายการทั้งหมด</span>
        <a href="<?= SITE_URL ?>/repairs/submit" class="btn btn-primary btn-sm">
            <i class="bi bi-plus-circle me-1"></i>แจ้งซ่อมใหม่
        </a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($result['repairs'])): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                <p>ยังไม่มีรายการแจ้งซ่อม</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 60px;">#</th>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ผู้แจ้ง</th>
                            <th>ปัญหา</th>
                            <th class="text-center">สถานะ</th>
                            <th>วันที่แจ้ง</th>
                            <th class="text-center" style="width: 80px;">ดู</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['repairs'] as $i => $repair): ?>
                            <tr>
                                <td class="text-muted"><?= $result['pagination']['offset'] + $i + 1 ?></td>
                                <td>
                                    <strong><?= htmlspecialchars($repair['eq_code']) ?></strong>
                                </td>
                                <td><?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?></td>
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
    <?php if (!empty($result['pagination'])): ?>
        <div class="card-footer">
            <?= paginationLinks($result['pagination'], SITE_URL . '/repairs?status=' . urlencode($currentStatus) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>


