<?php
$rows = $result['rows'] ?? [];
$pagination = $result['pagination'] ?? null;
?>

<div class="mb-4">
    <h4><i class="bi bi-journal-text"></i> บันทึกระบบ (Logs)</h4>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/logs" class="row g-3 align-items-end">
            <div class="col-md-8">
                <label class="form-label">ค้นหา</label>
                <input type="text" class="form-control" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
                    placeholder="ค้นหาจากชื่อผู้ใช้, การกระทำ, รายละเอียด, IP...">
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

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th width="60">#</th>
                        <th width="170">วันเวลา</th>
                        <th width="150">ผู้ใช้</th>
                        <th>การกระทำ</th>
                        <th>รายละเอียด</th>
                        <th width="130">IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($rows)): ?>
                        <?php foreach ($rows as $i => $row): ?>
                            <tr>
                                <td><?= $pagination['offset'] + $i + 1 ?></td>
                                <td>
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i>
                                        <?= htmlspecialchars($row['timestamp'] ?? $row['created_at'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <i class="bi bi-person"></i>
                                    <?= htmlspecialchars($row['user_name'] ?? '') ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?= htmlspecialchars($row['action'] ?? '') ?></span>
                                </td>
                                <td>
                                    <small><?= htmlspecialchars($row['details'] ?? '') ?></small>
                                </td>
                                <td>
                                    <code><?= htmlspecialchars($row['ip_address'] ?? $row['ip'] ?? '') ?></code>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">ไม่พบบันทึกระบบ</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($pagination && $pagination['total_pages'] > 1): ?>
    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= ($pagination['current_page'] <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= SITE_URL ?>/logs?page=<?= $pagination['current_page'] - 1 ?>&q=<?= urlencode($_GET['q'] ?? '') ?>">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>
            <?php
            $start = max(1, $pagination['current_page'] - 2);
            $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
            ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <li class="page-item <?= $p == $pagination['current_page'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= SITE_URL ?>/logs?page=<?= $p ?>&q=<?= urlencode($_GET['q'] ?? '') ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= ($pagination['current_page'] >= $pagination['total_pages']) ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= SITE_URL ?>/logs?page=<?= $pagination['current_page'] + 1 ?>&q=<?= urlencode($_GET['q'] ?? '') ?>">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif; ?>
