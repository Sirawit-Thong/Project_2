<?php
$currentStatus = $_GET['status'] ?? '';
$perPageOptions = $perPageOptions ?? [10, 20, 50, 100];
$perPage = $perPage ?? 20;
?>

<div class="page-header">
    <h1><i class="bi bi-wrench-adjustable me-2"></i>รายการแจ้งซ่อมครุภัณฑ์ ทั้งหมด</h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active">รายการแจ้งซ่อมครุภัณฑ์ ทั้งหมด</li>
        </ol>
    </nav>
</div>

<!-- Status Count Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="<?= SITE_URL ?>/repairs" class="text-decoration-none">
                    <h3><?= array_sum($statusCounts) ?></h3><small>ทั้งหมด</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="<?= SITE_URL ?>/repairs?status=pending" class="text-decoration-none text-warning">
                    <h3><?= $statusCounts['pending'] ?? 0 ?></h3><small>รอดำเนินการ</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="<?= SITE_URL ?>/repairs?status=in_progress"
                    class="text-decoration-none text-primary">
                    <h3><?= $statusCounts['in_progress'] ?? 0 ?></h3><small>กำลังซ่อม</small>
                </a></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body py-3"><a href="<?= SITE_URL ?>/repairs?status=completed"
                    class="text-decoration-none text-success">
                    <h3><?= $statusCounts['completed'] ?? 0 ?></h3><small>ซ่อมเสร็จ</small>
                </a></div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <?php if ($currentStatus): ?>
                <input type="hidden" name="status" value="<?= htmlspecialchars($currentStatus) ?>">
            <?php endif; ?>
            <div class="col-auto">
                <label class="form-label mb-0 me-2">แสดง:</label>
            </div>
            <div class="col-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 120px;"
                    onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-table me-2"></i>รายการ (<?= $result['total'] ?> รายการ)</div>
    <div class="card-body p-0">
        <?php if (empty($result['repairs'])): ?>
            <div class="empty-state"><i class="bi bi-inbox"></i>
                <h5>ไม่พบรายการ</h5>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th class="hide-mobile">#</th>
                            <th>ครุภัณฑ์</th>
                            <th class="hide-mobile">ผู้แจ้งซ่อม</th>
                            <th>อาการ</th>
                            <th>สถานะ</th>
                            <th class="hide-mobile">วันที่</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['repairs'] as $repair): ?>
                            <tr>
                                <td class="hide-mobile"><?= $repair['id'] ?></td>
                                <td><strong><?= sanitize($repair['eq_code'] ?? '-') ?></strong><br><small><?= sanitize($repair['item_name']) ?></small>
                                </td>
                                <td class="hide-mobile"><?= sanitize($repair['firstname'] . ' ' . $repair['lastname']) ?></td>
                                <td><?= mb_substr(sanitize($repair['issue']), 0, 50) ?>...</td>
                                <td><span
                                        class="badge bg-<?= getStatusBadgeClass($repair['status']) ?>"><?= translateRepairStatus($repair['status']) ?></span>
                                </td>
                                <td class="hide-mobile"><?= formatDateTimeThai($repair['created_at']) ?></td>
                                <td><a href="<?= SITE_URL ?>/repairs/<?= $repair['id'] ?>" class="btn btn-sm btn-outline-primary"><i
                                            class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($result['pagination']['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($result['pagination'], SITE_URL . '/repairs?status=' . urlencode($currentStatus) . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>
