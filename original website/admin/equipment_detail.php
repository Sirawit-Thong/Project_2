<?php
/**
 * Equipment Detail
 * รายละเอียดครุภัณฑ์
 */

$pageTitle = 'รายละเอียดข้อมูลครุภัณฑ์';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$id) {
    setFlash('danger', 'ไม่พบครุภัณฑ์ที่ต้องการ');
    redirect('equipment.php');
}

// Get equipment details
$stmt = $pdo->prepare("
    SELECT e.*, e.price_remark as eq_price_remark, 
           i.name as item_name, i.brand, i.model, i.unit, i.price_remark as item_price_remark,
           s.name as set_name, s.year as set_year, s.price_remark as set_price_remark,
           d.name as dept_name,
           u.firstname as holder_firstname, u.lastname as holder_lastname,
           rm.name as room
    FROM equipment e
    JOIN items i ON e.item_id = i.id
    JOIN sets s ON i.set_id = s.id
    LEFT JOIN dept d ON s.dept_id = d.id
    LEFT JOIN users u ON e.holder_id = u.id
    LEFT JOIN rooms rm ON e.room_id = rm.id
    WHERE e.id = ?
");
$stmt->execute([$id]);
$equipment = $stmt->fetch();

if (!$equipment) {
    setFlash('danger', 'ไม่พบครุภัณฑ์ที่ต้องการ');
    redirect('equipment.php');
}

// Get images
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
                <li class="breadcrumb-item"><a href="equipment.php">ครุภัณฑ์</a></li>
                <li class="breadcrumb-item active"><?= htmlspecialchars($equipment['code'] ?? 'รายละเอียด') ?></li>
            </ol>
        </nav>
    </div>
    <div>
        <a href="equipment_form.php?id=<?= $id ?>&ref=<?= urlencode($_SERVER['HTTP_REFERER'] ?? 'equipment.php') ?>"
            class="btn btn-primary">
            <i class="bi bi-pencil me-1"></i>แก้ไข
        </a>
        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
            <i class="bi bi-arrow-left me-1"></i>กลับ
        </button>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <!-- Equipment Info -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-info-circle me-2"></i>ข้อมูลทั่วไปของครุภัณฑ์</span>
                <span class="badge bg-<?= getStatusBadgeClass($equipment['status']) ?> fs-6">
                    <?= translateEquipmentStatus($equipment['status']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th width="40%">รหัสครุภัณฑ์:</th>
                                <td><strong><?= htmlspecialchars($equipment['code'] ?? '-') ?></strong></td>
                            </tr>
                            <tr>
                                <th>ชื่อรายการ:</th>
                                <td><?= htmlspecialchars($equipment['item_name']) ?></td>
                            </tr>
                            <tr>
                                <th>ยี่ห้อ/รุ่น:</th>
                                <td><?= htmlspecialchars($equipment['brand'] ?? '-') ?> /
                                    <?= htmlspecialchars($equipment['model'] ?? '-') ?>
                                </td>
                            </tr>
                            <tr>
                                <th>ชุดครุภัณฑ์:</th>
                                <td><?= htmlspecialchars($equipment['set_name']) ?> (<?= $equipment['set_year'] ?>)</td>
                            </tr>
                            <tr>
                                <th>สาขา:</th>
                                <td><?= htmlspecialchars($equipment['dept_name'] ?? '-') ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th width="40%">ห้อง/สถานที่:</th>
                                <td><?= htmlspecialchars($equipment['room'] ?? '-') ?></td>
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
                                    <td><?= formatCurrency($equipment['price']) ?></td>
                                </tr>
                            <?php endif; ?>

                            <?php if ($displayRemark): ?>
                                <tr>
                                    <th>หมายเหตุราคา:</th>
                                    <td><span class="text-info"><i
                                                class="bi bi-tag-fill me-1"></i><?= $displayRemark ?></span></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <th>วันที่จัดซื้อ:</th>
                                <td><?= formatDateThai($equipment['purchase_date']) ?></td>
                            </tr>
                            <tr>
                                <th>ตรวจเช็คล่าสุด:</th>
                                <td><?= formatDateThai($equipment['check_date']) ?></td>
                            </tr>
                            <tr>
                                <th>ผู้รับผิดชอบดูแล:</th>
                                <td>
                                    <?php if ($equipment['holder_firstname']): ?>
                                        <?= htmlspecialchars($equipment['holder_firstname'] . ' ' . $equipment['holder_lastname']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>

                <?php if ($equipment['remark']): ?>
                    <hr>
                    <div>
                        <strong>หมายเหตุ:</strong><br>
                        <?= nl2br(htmlspecialchars($equipment['remark'])) ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Repair History -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-wrench me-2"></i>ประวัติการแจ้งซ่อมและการบำรุงรักษา (<?= count($repairs) ?> รายการ)
            </div>
            <div class="card-body p-0">
                <?php if (empty($repairs)): ?>
                    <div class="empty-state py-4">
                        <i class="bi bi-check-circle text-success"></i>
                        <p>ยังไม่มีประวัติการซ่อม</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>วันที่แจ้ง</th>
                                    <th class="hide-mobile">ผู้แจ้ง</th>
                                    <th class="hide-mobile">อาการ</th>
                                    <th>สถานะ</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($repairs as $repair): ?>
                                    <tr>
                                        <td><?= formatDateTimeThai($repair['created_at']) ?></td>
                                        <td class="hide-mobile">
                                            <?php if ($repair['firstname']): ?>
                                                <?= htmlspecialchars($repair['firstname'] . ' ' . $repair['lastname']) ?>
                                            <?php else: ?>
                                                <span class="text-muted">ผู้ใช้ถูกลบออกจากระบบ</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="hide-mobile"><?= mb_substr(htmlspecialchars($repair['issue']), 0, 50) ?>...
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getStatusBadgeClass($repair['status']) ?>">
                                                <?= translateRepairStatus($repair['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="repair_detail.php?id=<?= $repair['id'] ?>"
                                                class="btn btn-sm btn-outline-primary">
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
    </div>

    <div class="col-lg-4">
        <!-- Images -->
        <div class="card">
            <div class="card-header">
                <i class="bi bi-images me-2"></i>รูปภาพ
            </div>
            <div class="card-body">
                <?php if (empty($images)): ?>
                    <div class="text-center text-muted">
                        <i class="bi bi-image fs-1 d-block mb-2"></i>
                        ยังไม่มีรูปภาพ
                    </div>
                <?php else: ?>
                    <?php
                    $purchaseImages = array_filter($images, fn($img) => $img['type'] === 'purchase');
                    $currentImages = array_filter($images, fn($img) => $img['type'] === 'current_condition');
                    ?>

                    <?php if (!empty($purchaseImages)): ?>
                        <h6 class="mb-2"><i class="bi bi-cart me-1"></i>ภาพถ่ายเมื่อแรกรับ/จัดซื้อ</h6>
                        <div class="image-gallery mb-3">
                            <?php foreach ($purchaseImages as $img): ?>
                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" alt="Purchase Image"
                                    class="lightbox-trigger"
                                    data-img-src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                    data-img-caption="ภาพถ่ายเมื่อแรกรับ/จัดซื้อ" style="cursor: pointer;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($currentImages)): ?>
                        <h6 class="mb-2"><i class="bi bi-camera me-1"></i>ภาพถ่ายสภาพปัจจุบัน</h6>
                        <div class="image-gallery">
                            <?php foreach ($currentImages as $img): ?>
                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>" alt="Current Image"
                                    class="lightbox-trigger"
                                    data-img-src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                    data-img-caption="ภาพถ่ายสภาพปัจจุบัน" style="cursor: pointer;">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-dark">
            <div class="modal-header border-0 pb-0">
                <span id="lightboxCaption" class="text-white"></span>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-0">
                <img id="lightboxImage" src="" alt="Full Image" class="img-fluid" style="max-height: 80vh;">
            </div>
            <div class="modal-footer border-0 justify-content-center pt-0">
                <a id="lightboxDownload" href="" download class="btn btn-outline-light btn-sm">
                    <i class="bi bi-download me-1"></i>ดาวน์โหลด
                </a>
            </div>
        </div>
    </div>
</div>

<script>
    // Lightbox functionality
    document.querySelectorAll('.lightbox-trigger').forEach(function (img) {
        img.addEventListener('click', function () {
            const imgSrc = this.getAttribute('data-img-src');
            const imgCaption = this.getAttribute('data-img-caption');

            document.getElementById('lightboxImage').src = imgSrc;
            document.getElementById('lightboxCaption').textContent = imgCaption;
            document.getElementById('lightboxDownload').href = imgSrc;

            new bootstrap.Modal(document.getElementById('imageLightboxModal')).show();
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>