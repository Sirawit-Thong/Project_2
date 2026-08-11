<?php
/**
 * System Logs
 */
$pageTitle = 'ประวัติการใช้งานระบบ (Logs)';
require_once '../includes/header.php';
requireRole('admin');

$pdo = getDB();
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 30;

$total = $pdo->query("SELECT COUNT(*) FROM system_logs")->fetchColumn();
$pagination = paginate($total, $page, $perPage);

$logs = $pdo->query("SELECT l.*, u.firstname, u.lastname 
    FROM system_logs l LEFT JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}")->fetchAll();
?>

<div class="page-header">
    <h1><i class="bi bi-journal-text me-2"></i>ประวัติการใช้งานระบบ (Logs)</h1>
</div>

<div class="card">
    <div class="card-header"><i class="bi bi-list me-2"></i>รายการบันทึกเหตุการณ์ (<?= number_format($total) ?>)</div>
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th width="150">เวลา</th>
                    <th>ผู้ใช้</th>
                    <th>การกระทำ</th>
                    <th class="hide-mobile">รายละเอียด</th>
                    <th class="hide-mobile">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><small><?= formatDateTimeThai($log['created_at']) ?></small></td>
                        <td><?= $log['firstname'] ? htmlspecialchars($log['firstname'] . ' ' . $log['lastname']) : '<span class="text-muted">ระบบ</span>' ?>
                        </td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($log['action']) ?></span></td>
                        <td class="hide-mobile"><small><?= htmlspecialchars($log['details'] ?? '-') ?></small></td>
                        <td class="hide-mobile"><small><?= htmlspecialchars($log['ip_address']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer"><?= paginationLinks($pagination, 'logs.php?') ?></div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>