<?php
/**
 * Pending Users Management
 * จัดการผู้ใช้รออนุมัติ
 */

$pageTitle = 'ตรวจสอบบัญชีผู้ใช้งานใหม่';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

// Handle approve/reject
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $action = $_GET['action'];

    if ($action === 'approve') {
        $stmt = $pdo->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            logActivity($pdo, getCurrentUserId(), 'Approve User', "อนุมัติผู้ใช้ ID: $id");
            setFlash('success', 'อนุมัติผู้ใช้สำเร็จ');
        }
    } elseif ($action === 'reject') {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        if ($stmt->rowCount() > 0) {
            logActivity($pdo, getCurrentUserId(), 'Reject User', "ปฏิเสธผู้ใช้ ID: $id");
            setFlash('warning', 'ปฏิเสธและลบผู้ใช้สำเร็จ');
        }
    }

    redirect('pending_users.php');
}

// Get pending users
$users = $pdo->query("
    SELECT * FROM users 
    WHERE status = 'pending' 
    ORDER BY created_at DESC
")->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-person-check me-2"></i>ตรวจสอบบัญชีผู้ใช้งานใหม่</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">รออนุมัติ</li>
            </ol>
        </nav>
    </div>
    <div>
        <span class="badge bg-warning fs-6">
            <i class="bi bi-hourglass-split me-1"></i><?= count($users) ?> รายการ
        </span>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <i class="bi bi-list-check me-2"></i>รายชื่อผู้ใช้งานรอการตรวจสอบระบุตัวตน
    </div>
    <div class="card-body p-0">
        <?php if (empty($users)): ?>
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
                        <?php foreach ($users as $user): ?>
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
                                <td class="hide-mobile"><?= formatDateTimeThai($user['created_at']) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a href="pending_users.php?action=approve&id=<?= $user['id'] ?>"
                                            class="btn btn-sm btn-success" title="อนุมัติ">
                                            <i class="bi bi-check-lg"></i> อนุมัติ
                                        </a>
                                        <a href="pending_users.php?action=reject&id=<?= $user['id'] ?>"
                                            class="btn btn-sm btn-danger"
                                            data-confirm="คุณแน่ใจหรือไม่ที่จะปฏิเสธผู้ใช้นี้? ข้อมูลจะถูกลบ" title="ปฏิเสธ">
                                            <i class="bi bi-x-lg"></i> ปฏิเสธ
                                        </a>
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

<?php require_once '../includes/footer.php'; ?>