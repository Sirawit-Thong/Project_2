<?php
/**
 * Teacher Equipment Detail
 * ดูรายละเอียดครุภัณฑ์ (สำหรับอาจารย์)
 */
$pageTitle = 'รายละเอียดข้อมูลครุภัณฑ์';
require_once '../includes/header.php';
requireRole('teacher');

$pdo = getDB();
$userId = getCurrentUserId();
$id = (int) ($_GET['id'] ?? 0);

if (!$id) {
    setFlash('danger', 'ไม่พบครุภัณฑ์');
    redirect('my_equipment.php');
}

// Verify teacher has access to this equipment
$stmt = $pdo->prepare("
    SELECT e.*, e.price_remark as eq_price_remark, 
           i.name as item_name, i.brand, i.model, i.price_remark as item_price_remark,
           s.name as set_name, s.year as set_year, s.price_remark as set_price_remark, d.name as dept_name,
           u.firstname as holder_firstname, u.lastname as holder_lastname,
           r.name as room
    FROM equipment e
    JOIN items i ON e.item_id = i.id
    JOIN sets s ON i.set_id = s.id
    LEFT JOIN dept d ON s.dept_id = d.id
    LEFT JOIN users u ON e.holder_id = u.id
    LEFT JOIN rooms r ON e.room_id = r.id
    WHERE e.id = ? AND (e.holder_id = ? OR e.room_id IN (SELECT room_id FROM room_managers WHERE user_id = ?))
");
$stmt->execute([$id, $userId, $userId]);
$equipment = $stmt->fetch();

if (!$equipment) {
    setFlash('danger', 'ไม่มีสิทธิ์ดูครุภัณฑ์นี้');
    redirect('my_equipment.php');
}

// Get equipment images
$stmt = $pdo->prepare("SELECT * FROM equipment_img WHERE equipment_id = ? ORDER BY type, created_at DESC");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Get repair history
$stmt = $pdo->prepare("
    SELECT r.*, u.firstname, u.lastname 
    FROM repair r 
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.equipment_id = ? 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$stmt->execute([$id]);
$repairs = $stmt->fetchAll();
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-pc-display me-2"></i>รายละเอียดข้อมูลครุภัณฑ์</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="my_equipment.php">ตรวจสอบครุภัณฑ์</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($equipment['code'] ?? 'รายละเอียด') ?></li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </button>
</div>

<div class="row">
    <!-- Main Info -->
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>ข้อมูลทั่วไปของครุภัณฑ์
            </div>
            <div class="card-body">
                <table class="table table-borderless">
                    <tr>
                        <th style="width: 150px;">รหัสครุภัณฑ์:</th>
                        <td><code class="fs-5"><?= htmlspecialchars($equipment['code'] ?? '-') ?></code></td>
                    </tr>
                    <tr>
                        <th>ชื่อรายการครุภัณฑ์:</th>
                        <td><?= htmlspecialchars($equipment['item_name']) ?></td>
                    </tr>
                    <tr>
                        <th>ยี่ห้อ/รุ่น:</th>
                        <td><?= htmlspecialchars($equipment['brand'] ?? '-') ?> /
                            <?= htmlspecialchars($equipment['model'] ?? '-') ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ชุดครุภัณฑ์ปีงบประมาณ:</th>
                        <td><?= htmlspecialchars($equipment['set_name']) ?> (<?= $equipment['set_year'] ?>)</td>
                    </tr>
                    <tr>
                        <th>สาขา:</th>
                        <td><?= htmlspecialchars($equipment['dept_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <th>ห้อง/สถานที่ตั้ง:</th>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($equipment['room'] ?? '-') ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th>สถานะ:</th>
                        <td><span
                                class="badge bg-<?= getStatusBadgeClass($equipment['status']) ?> fs-6"><?= translateEquipmentStatus($equipment['status']) ?></span>
                        </td>
                    </tr>
                    <?php
                    // Cascade logic for determining which remark to show (most specific first)
                    $displayRemark = '';
                    if (!empty($equipment['eq_price_remark'])) {
                        $displayRemark = $equipment['eq_price_remark'] . ' <small class="text-muted">(เฉพาะชิ้น)</small>';
                    } elseif (!empty($equipment['item_price_remark'])) {
                        $displayRemark = $equipment['item_price_remark'] . ' <small class="text-muted">(ทั้งรายการ)</small>';
                    } elseif (!empty($equipment['set_price_remark'])) {
                        $displayRemark = $equipment['set_price_remark'] . ' <small class="text-muted">(ทั้งชุด)</small>';
                    }
                    ?>

                    <?php if ($equipment['price'] > 0 || empty($displayRemark)): ?>
                        <tr>
                            <th>ราคา:</th>
                            <td><?= $equipment['price'] ? number_format($equipment['price'], 2) . ' บาท' : '-' ?></td>
                        </tr>
                    <?php endif; ?>

                    <?php if ($displayRemark): ?>
                        <tr>
                            <th>หมายเหตุราคา:</th>
                            <td><span class="text-info"><i class="bi bi-tag-fill me-1"></i><?= $displayRemark ?></span></td>
                        </tr>
                    <?php endif; ?>
                    <tr>
                        <th>วันที่จัดซื้อ:</th>
                        <td><?= $equipment['purchase_date'] ? formatDateThai($equipment['purchase_date']) : '-' ?></td>
                    </tr>
                    <tr>
                        <th>ตรวจสอบล่าสุด:</th>
                        <td>
                            <?php if ($equipment['check_date']): ?>
                                <span class="text-success"><i
                                        class="bi bi-check-circle me-1"></i><?= formatDateThai($equipment['check_date']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th>ผู้รับผิดชอบดูแล:</th>
                        <td><?= $equipment['holder_firstname'] ? htmlspecialchars($equipment['holder_firstname'] . ' ' . $equipment['holder_lastname']) : '-' ?>
                        </td>
                    </tr>
                    <?php if ($equipment['remark']): ?>
                        <tr>
                            <th>หมายเหตุ:</th>
                            <td><?= nl2br(htmlspecialchars($equipment['remark'])) ?></td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Repair History -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-wrench me-2"></i>ประวัติการแจ้งซ่อมและการบำรุงรักษา
            </div>
            <div class="card-body p-0">
                <?php if (empty($repairs)): ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-check-circle text-success"></i>
                        <p>ไม่มีประวัติการซ่อม</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>วันที่แจ้ง</th>
                                    <th class="hide-mobile">อาการ</th>
                                    <th class="hide-mobile">ผู้แจ้ง</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repairs as $r): ?>
                                    <tr>
                                        <td><?= formatDateThai($r['created_at']) ?></td>
                                        <td class="hide-mobile"><?= htmlspecialchars(mb_substr($r['issue'], 0, 50)) ?>...</td>
                                        <td class="hide-mobile">
                                            <?php if ($r['firstname']): ?>
                                                <?= htmlspecialchars($r['firstname'] . ' ' . $r['lastname']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">ผู้ใช้ถูกลบ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><span
                                                class="badge bg-<?= getStatusBadgeClass($r['status']) ?>"><?= translateRepairStatus($r['status']) ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Images -->
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-images me-2"></i>รูปภาพ
            </div>
            <div class="card-body">
                <?php if (empty($images)): ?>
                    <div class="empty-state py-3">
                        <i class="bi bi-image"></i>
                        <p>ไม่มีรูปภาพ</p>
                    </div>
                <?php else: ?>
                    <div class="row g-2">
                        <?php foreach ($images as $img): ?>
                            <div class="col-6">
                                <a href="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>" target="_blank">
                                    <img src="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>" class="img-fluid rounded"
                                        alt="Equipment Image">
                                </a>
                                <small class="text-muted d-block mt-1">
                                    <?= $img['type'] === 'purchase' ? 'ภาพถ่ายเมื่อแรกรับ/จัดซื้อ' : 'ภาพถ่ายสภาพปัจจุบัน' ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php require_once '../includes/footer.php'; ?>