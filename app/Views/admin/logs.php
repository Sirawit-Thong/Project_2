<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
$q = $_GET['q'] ?? '';
$baseUrl = SITE_URL . '/logs?' . ($q !== '' ? 'q=' . urlencode($q) : '');
?>

<div class="page-header">
    <h1><i class="bi bi-journal-text me-2"></i>ประวัติการใช้งานระบบ (Logs)</h1>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/logs" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">ค้นหา</label>
                <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($q) ?>"
                    placeholder="ค้นหาจาก ID, ชื่อ, อีเมล, การกระทำ, รายละเอียด, IP...">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-primary w-100">
                    <i class="bi bi-search"></i> ค้นหา
                </button>
            </div>
            <div class="col-md-2">
                <a href="<?= SITE_URL ?>/logs" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-x-circle"></i> ล้าง
                </a>
            </div>
        </form>
    </div>
</div>

    <div class="card">
    <div class="card-header"><i class="bi bi-list me-2"></i>รายการบันทึกเหตุการณ์ (<?= number_format($pagination['total_items'] ?? 0) ?>)</div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th width="150">เวลา</th>
                    <th>ผู้ใช้ (ID)</th>
                    <th>การกระทำ</th>
                    <th class="hide-mobile">รายละเอียด</th>
                    <th class="hide-mobile">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($rows)): ?>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><small><?= formatDateTimeThai($row['created_at'] ?? '') ?></small></td>
                            <td>
                                <?php if (!empty($row['user_id'])): ?>
                                    <span class="badge bg-primary me-1">#<?= (int) $row['user_id'] ?></span>
                                    <span><?= htmlspecialchars(trim($row['user_name'] ?? '') ?: '-') ?></span>
                                    <?php if (!empty($row['user_email'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['user_email']) ?><?php if (!empty($row['user_role'])): ?> (<?= htmlspecialchars(translateRole($row['user_role'])) ?>)<?php endif; ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">ระบบ</span>
                                    <?php if (!empty($row['user_name'])): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($row['user_name']) ?></small>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-secondary"><?= htmlspecialchars($row['action'] ?? '') ?></span></td>
                            <td class="hide-mobile"><small><?= htmlspecialchars($row['details'] ?? '-') ?></small></td>
                            <td class="hide-mobile"><small><?= htmlspecialchars($row['ip_address'] ?? '') ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">ไม่พบบันทึกระบบ</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination && ($pagination['total_pages'] ?? 0) > 1): ?>
        <div class="card-footer"><?= paginationLinks($pagination, $baseUrl) ?></div>
    <?php endif; ?>
</div>
