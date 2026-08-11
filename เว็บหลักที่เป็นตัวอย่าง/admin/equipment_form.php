<?php
/**
 * Equipment Form
 * ฟอร์มเพิ่ม/แก้ไขครุภัณฑ์
 */

$pageTitle = 'บริหารจัดการข้อมูลครุภัณฑ์';
require_once '../includes/header.php';
requireRole(['admin', 'staff']);

$pdo = getDB();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$equipment = null;
$errors = [];

// If editing, get existing equipment
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM equipment WHERE id = ?");
    $stmt->execute([$id]);
    $equipment = $stmt->fetch();

    if (!$equipment) {
        setFlash('danger', 'ไม่พบครุภัณฑ์ที่ต้องการ');
        redirect('equipment.php');
    }
}

// Handle image deletion
if (isset($_GET['delete_image']) && is_numeric($_GET['delete_image']) && $isEdit) {
    $imgId = (int) $_GET['delete_image'];

    // Get image info first
    $stmt = $pdo->prepare("SELECT * FROM equipment_img WHERE id = ? AND equipment_id = ?");
    $stmt->execute([$imgId, $id]);
    $img = $stmt->fetch();

    if ($img) {
        // Delete file from filesystem
        $filepath = UPLOAD_PATH . $img['path'];
        if (file_exists($filepath)) {
            unlink($filepath);
        }

        // Delete from database
        $stmt = $pdo->prepare("DELETE FROM equipment_img WHERE id = ?");
        $stmt->execute([$imgId]);

        logActivity($pdo, getCurrentUserId(), 'Delete Equipment Image', "ลบรูปครุภัณฑ์ ID: $id, Image ID: $imgId");
        setFlash('success', 'ลบรูปภาพสำเร็จ');
    }
    redirect("equipment_form.php?id=$id");
}


// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_id = (int) ($_POST['item_id'] ?? 0);
    $code = trim($_POST['code'] ?? '') ?: null;
    $room_id = (int) ($_POST['room_id'] ?? 0) ?: null;
    $status = $_POST['status'] ?? 'available';
    $purchase_date = $_POST['purchase_date'] ?: null;
    $check_date = $_POST['check_date'] ?: null;
    $price = (float) ($_POST['price'] ?? 0);
    $price_remark = trim($_POST['price_remark'] ?? '') ?: null;
    $holder_id = (int) ($_POST['holder_id'] ?? 0) ?: null;
    $remark = trim($_POST['remark'] ?? '') ?: null;

    // Validation
    if (empty($item_id))
        $errors[] = 'กรุณาเลือกรายการครุภัณฑ์';

    // Check code duplicate
    if ($code) {
        $stmt = $pdo->prepare("SELECT id FROM equipment WHERE code = ? AND id != ?");
        $stmt->execute([$code, $id]);
        if ($stmt->fetch()) {
            $errors[] = 'รหัสครุภัณฑ์นี้มีอยู่แล้ว';
        }
    }

    // Check quantity limit (only for new equipment)
    if (!$isEdit && $item_id) {
        $stmt = $pdo->prepare("SELECT i.qty, i.name, (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id) as existing_count FROM items i WHERE i.id = ?");
        $stmt->execute([$item_id]);
        $itemInfo = $stmt->fetch();

        if ($itemInfo && $itemInfo['qty'] > 0) {
            $remaining = $itemInfo['qty'] - $itemInfo['existing_count'];
            if ($remaining <= 0) {
                $errors[] = "ไม่สามารถเพิ่มได้ รายการ \"" . htmlspecialchars($itemInfo['name']) . "\" มีจำนวน {$itemInfo['qty']} ชิ้น ลงทะเบียนครบแล้ว";
            }
        }
    }

    if (empty($errors)) {
        try {
            if ($isEdit) {
                // Fetch item and set price to override equipment price if necessary
                $stmtPrice = $pdo->prepare("SELECT i.price as item_price, s.price as set_price FROM items i LEFT JOIN sets s ON i.set_id = s.id WHERE i.id = ?");
                $stmtPrice->execute([$item_id]);
                $parentPrices = $stmtPrice->fetch(PDO::FETCH_ASSOC);

                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $price = 0; // Force price to 0 if parent item or set has price
                }

                $stmt = $pdo->prepare("
                    UPDATE equipment SET item_id = ?, code = ?, room_id = ?, status = ?, 
                    purchase_date = ?, check_date = ?, price = ?, price_remark = ?, holder_id = ?, remark = ?
                    WHERE id = ?
                ");
                $stmt->execute([$item_id, $code, $room_id, $status, $purchase_date, $check_date, $price, $price_remark, $holder_id, $remark, $id]);
                logActivity($pdo, getCurrentUserId(), 'Update Equipment', "แก้ไขครุภัณฑ์ ID: $id");
            } else {
                // Fetch item and set price to override equipment price if necessary
                $stmtPrice = $pdo->prepare("SELECT i.price as item_price, s.price as set_price FROM items i LEFT JOIN sets s ON i.set_id = s.id WHERE i.id = ?");
                $stmtPrice->execute([$item_id]);
                $parentPrices = $stmtPrice->fetch(PDO::FETCH_ASSOC);

                if ($parentPrices && ($parentPrices['item_price'] > 0 || $parentPrices['set_price'] > 0)) {
                    $price = 0; // Force price to 0 if parent item or set has price
                }

                $stmt = $pdo->prepare("
                    INSERT INTO equipment (item_id, code, room_id, status, purchase_date, check_date, price, price_remark, holder_id, remark)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$item_id, $code, $room_id, $status, $purchase_date, $check_date, $price, $price_remark, $holder_id, $remark]);
                $id = $pdo->lastInsertId();
                logActivity($pdo, getCurrentUserId(), 'Add Equipment', "เพิ่มครุภัณฑ์ ID: $id");
            }

            // Handle purchase images upload
            if (!empty($_FILES['purchase_images']['name'][0])) {
                foreach ($_FILES['purchase_images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['purchase_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['purchase_images']['name'][$key],
                            'type' => $_FILES['purchase_images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'size' => $_FILES['purchase_images']['size'][$key]
                        ];

                        $result = uploadImage($file, 'equipment');
                        if ($result['success']) {
                            $stmt = $pdo->prepare("INSERT INTO equipment_img (equipment_id, path, type) VALUES (?, ?, ?)");
                            $stmt->execute([$id, $result['path'], 'purchase']);
                        }
                    }
                }
            }

            // Handle current condition images upload
            if (!empty($_FILES['current_images']['name'][0])) {
                foreach ($_FILES['current_images']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['current_images']['error'][$key] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['current_images']['name'][$key],
                            'type' => $_FILES['current_images']['type'][$key],
                            'tmp_name' => $tmpName,
                            'size' => $_FILES['current_images']['size'][$key]
                        ];

                        $result = uploadImage($file, 'equipment');
                        if ($result['success']) {
                            $stmt = $pdo->prepare("INSERT INTO equipment_img (equipment_id, path, type) VALUES (?, ?, ?)");
                            $stmt->execute([$id, $result['path'], 'current_condition']);
                        }
                    }
                }
            }

            setFlash('success', $isEdit ? 'แก้ไขครุภัณฑ์สำเร็จ' : 'เพิ่มครุภัณฑ์สำเร็จ');
            $referrer = $_POST['referrer'] ?? 'equipment.php';
            redirect($referrer);
        } catch (PDOException $e) {
            $errors[] = 'เกิดข้อผิดพลาด: ' . $e->getMessage();
        }
    }
}

// Get departments for filter
$departments = $pdo->query("SELECT * FROM dept ORDER BY name")->fetchAll();

// Get sets for filter  
$sets = $pdo->query("SELECT s.*, d.name as dept_name FROM sets s LEFT JOIN dept d ON s.dept_id = d.id ORDER BY s.year DESC, s.name")->fetchAll();

// Get items for dropdown
$items = $pdo->query("
    SELECT i.*, s.id as set_id, s.name as set_name, s.year as set_year, s.dept_id, s.price as set_price, d.name as dept_name,
           (SELECT COUNT(*) FROM equipment e WHERE e.item_id = i.id) as existing_count
    FROM items i
    JOIN sets s ON i.set_id = s.id
    LEFT JOIN dept d ON s.dept_id = d.id
    ORDER BY s.year DESC, i.name
")->fetchAll();

// Get rooms for dropdown
// Get rooms
$rooms = $pdo->query("SELECT id, name FROM rooms ORDER BY name")->fetchAll();

// Get teachers/staff for holder dropdown
$holders = $pdo->query("SELECT id, firstname, lastname, role FROM users WHERE status = 'approved' AND role IN ('admin', 'staff', 'teacher') ORDER BY firstname")->fetchAll();

// Get room managers mapping
$roomManagers = $pdo->query("
    SELECT rm.room_id, r.name as room_name, u.id as user_id, u.firstname, u.lastname
    FROM room_managers rm
    JOIN rooms r ON rm.room_id = r.id
    JOIN users u ON rm.user_id = u.id
    ORDER BY r.name, u.firstname
")->fetchAll();

// Group managers by room id
$managersByRoom = [];
foreach ($roomManagers as $rm) {
    $managersByRoom[$rm['room_id']][] = $rm;
}

// Generate temp session for new equipment image uploads
$tempSession = $isEdit ? '' : uniqid('eq_');

// Form values - include item_id from URL if provided (for quick add from items page)
$preselectedItemId = $_GET['item_id'] ?? '';
$formData = [
    'item_id' => $_POST['item_id'] ?? ($equipment['item_id'] ?? $preselectedItemId),
    'code' => $_POST['code'] ?? ($equipment['code'] ?? ''),
    'room_id' => $_POST['room_id'] ?? ($equipment['room_id'] ?? ''),
    'status' => $_POST['status'] ?? ($equipment['status'] ?? 'available'),
    'purchase_date' => $_POST['purchase_date'] ?? ($equipment['purchase_date'] ?? ''),
    'check_date' => $_POST['check_date'] ?? ($equipment['check_date'] ?? ''),
    'price' => $_POST['price'] ?? ($equipment['price'] ?? 0),
    'price_remark' => $_POST['price_remark'] ?? ($equipment['price_remark'] ?? ''),
    'holder_id' => $_POST['holder_id'] ?? ($equipment['holder_id'] ?? ''),
    'remark' => $_POST['remark'] ?? ($equipment['remark'] ?? ''),
];

// Get existing images
$existingImages = [];
if ($isEdit) {
    $stmt = $pdo->prepare("SELECT * FROM equipment_img WHERE equipment_id = ? ORDER BY created_at DESC");
    $stmt->execute([$id]);
    $existingImages = $stmt->fetchAll();
}
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i
            class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i><?= $isEdit ? 'แก้ไขข้อมูลครุภัณฑ์' : 'ลงทะเบียนครุภัณฑ์ใหม่' ?>
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="equipment.php">ครุภัณฑ์</a></li>
            <li class="breadcrumb-item active"><?= $isEdit ? 'แก้ไข' : 'เพิ่ม' ?></li>
        </ol>
    </nav>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-pc-display me-2"></i>รายละเอียดข้อมูลครุภัณฑ์
            </div>
            <div class="card-body">
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0 ps-3">
                            <?php foreach ($errors as $error): ?>
                                <li><?= $error ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" enctype="multipart/form-data" id="equipmentForm">
                    <input type="hidden" name="temp_session" id="tempSession" value="<?= $tempSession ?>">

                    <!-- Item Selection with Filters -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">สาขา</label>
                            <select class="form-select form-select-sm" id="filterDept">
                                <option value="">-- ทุกสาขา --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= htmlspecialchars($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">ชุดครุภัณฑ์</label>
                            <select class="form-select form-select-sm" id="filterSet">
                                <option value="">-- ทุกชุด --</option>
                                <?php foreach ($sets as $set): ?>
                                    <option value="<?= $set['id'] ?>" data-dept="<?= $set['dept_id'] ?>">
                                        <?= htmlspecialchars($set['name']) ?> (<?= $set['year'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">รายการ <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="item_id" id="itemSelect" required>
                                <option value="">-- เลือกรายการ --</option>
                                <?php foreach ($items as $item):
                                     $remaining = $item['qty'] > 0 ? $item['qty'] - $item['existing_count'] : 999;
                                     $qtyText = $item['qty'] > 0 ? " [{$item['existing_count']}/{$item['qty']}]" : "";
                                    ?>
                                    <option value="<?= $item['id'] ?>" data-dept="<?= $item['dept_id'] ?>"
                                        data-set="<?= $item['set_id'] ?>" data-item-price="<?= $item['price'] ?>"
                                        data-set-price="<?= $item['set_price'] ?>"
                                        data-qty="<?= $item['qty'] ?>" data-remaining="<?= $remaining ?>" 
                                        <?= $formData['item_id'] == $item['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($item['name']) ?>
                                        (<?= htmlspecialchars($item['brand'] ?? '-') ?>)<?= $qtyText ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="itemQtyInfo" class="form-text"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">รหัสครุภัณฑ์</label>
                            <input type="text" class="form-control" name="code"
                                value="<?= htmlspecialchars($formData['code']) ?>"
                                placeholder="เช่น 7440-001-0001.1/551 วท.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ห้อง/สถานที่</label>
                            <select class="form-select" name="room_id" id="roomSelect">
                                <option value="">-- เลือกห้อง --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['id'] ?>" <?= $formData['room_id'] == $room['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($room['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" required>
                                <option value="available" <?= $formData['status'] === 'available' ? 'selected' : '' ?>>
                                    พร้อมใช้งาน
                                </option>
                                <option value="repair" <?= $formData['status'] === 'repair' ? 'selected' : '' ?>>ส่งซ่อม
                                </option>
                                <option value="broken" <?= $formData['status'] === 'broken' ? 'selected' : '' ?>>ซ่อมไม่ได้
                                </option>
                                <option value="pending_disposal" <?= $formData['status'] === 'pending_disposal' ? 'selected' : '' ?>>รอจำหน่ายออก</option>
                                <option value="disposed" <?= $formData['status'] === 'disposed' ? 'selected' : '' ?>>
                                    จำหน่ายออก</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">วันที่จัดซื้อ</label>
                            <input type="date" class="form-control" name="purchase_date"
                                value="<?= htmlspecialchars($formData['purchase_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ราคา (บาท) <span id="priceSourceLabel"
                                    class="badge bg-secondary ms-1 d-none"></span></label>
                            <input type="number" class="form-control" name="price" id="eqPrice" step="0.01"
                                value="<?= htmlspecialchars($formData['price']) ?>">
                        </div>
                    </div>

                    <div class="alert alert-info d-none py-2 mb-3" id="priceAlert">
                        <i class="bi bi-info-circle me-1"></i> <span id="priceAlertText">ชุดครุภัณฑ์ หรือ
                            รายการครุภัณฑ์มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกตั้งเป็น 0</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">หมายเหตุราคา (กรณีพิเศษรายชิ้น)</label>
                        <input type="text" class="form-control" name="price_remark" id="eqPriceRemark"
                            value="<?= htmlspecialchars($formData['price_remark']) ?>"
                            placeholder="คำอธิบายราคาเฉพาะชิ้นนี้ (ถ้ามี)">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">วันที่ตรวจเช็คล่าสุด</label>
                            <input type="date" class="form-control" name="check_date"
                                value="<?= htmlspecialchars($formData['check_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ผู้รับผิดชอบดูแล</label>
                            <select class="form-select" name="holder_id" id="holderSelect">
                                <option value="">-- ไม่ระบุ --</option>
                                <?php foreach ($holders as $holder): ?>
                                    <option value="<?= $holder['id'] ?>" <?= $formData['holder_id'] == $holder['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($holder['firstname'] . ' ' . $holder['lastname']) ?>
                                        (<?= translateRole($holder['role']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="holderInfo" class="form-text"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <textarea class="form-control" name="remark"
                            rows="2"><?= htmlspecialchars($formData['remark']) ?></textarea>
                    </div>

                    <hr>

                    <!-- Image Upload Zones -->
                    <h6 class="mb-3"><i class="bi bi-images me-1"></i>รูปภาพครุภัณฑ์</h6>

                    <div class="row">
                        <!-- Purchase Images Zone -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-info h-100">
                                <div
                                    class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cart-check me-1"></i>ภาพถ่ายเมื่อแรกรับ/จัดซื้อ</span>
                                    <label for="purchaseInput" class="btn btn-light btn-sm py-0 px-2 mb-0">
                                        <i class="bi bi-plus-lg"></i> เพิ่มรูป
                                    </label>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="d-none" id="purchaseInput" accept="image/*">
                                    <input type="file" class="d-none" id="purchaseFiles" name="purchase_images[]"
                                        multiple>
                                    <div class="d-flex flex-wrap gap-2" id="purchasePreview" style="min-height: 90px;">
                                        <?php
                                        $purchaseImages = array_filter($existingImages, fn($img) => $img['type'] === 'purchase');
                                        foreach ($purchaseImages as $img):
                                            ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <a href="equipment_form.php?id=<?= $id ?>&delete_image=<?= $img['id'] ?>&ref=<?= urlencode($_GET['ref'] ?? $_SERVER['HTTP_REFERER'] ?? '') ?>"
                                                    class="btn-delete-img" data-confirm="ลบรูปนี้?">×</a>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($purchaseImages)): ?>
                                            <div class="text-muted small w-100 text-center py-3 no-img-msg">
                                                <i class="bi bi-image me-1"></i>ยังไม่มีรูป - กดปุ่ม "เพิ่มรูป" เพื่อเลือก
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Condition Images Zone -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-success h-100">
                                <div
                                    class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-camera me-1"></i>ภาพถ่ายสภาพปัจจุบัน</span>
                                    <label for="currentInput" class="btn btn-light btn-sm py-0 px-2 mb-0">
                                        <i class="bi bi-plus-lg"></i> เพิ่มรูป
                                    </label>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="d-none" id="currentInput" accept="image/*">
                                    <input type="file" class="d-none" id="currentFiles" name="current_images[]"
                                        multiple>
                                    <div class="d-flex flex-wrap gap-2" id="currentPreview" style="min-height: 90px;">
                                        <?php
                                        $currentImages = array_filter($existingImages, fn($img) => $img['type'] === 'current_condition');
                                        foreach ($currentImages as $img):
                                            ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <a href="equipment_form.php?id=<?= $id ?>&delete_image=<?= $img['id'] ?>&ref=<?= urlencode($_GET['ref'] ?? $_SERVER['HTTP_REFERER'] ?? '') ?>"
                                                    class="btn-delete-img" data-confirm="ลบรูปนี้?">×</a>
                                            </div>
                                        <?php endforeach; ?>
                                        <?php if (empty($currentImages)): ?>
                                            <div class="text-muted small w-100 text-center py-3 no-img-msg">
                                                <i class="bi bi-image me-1"></i>ยังไม่มีรูป - กดปุ่ม "เพิ่มรูป" เพื่อเลือก
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <style>
                        .btn-delete-img {
                            position: absolute;
                            top: -5px;
                            right: -5px;
                            width: 22px;
                            height: 22px;
                            background: #dc3545;
                            color: white;
                            border-radius: 50%;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            text-decoration: none;
                            font-size: 14px;
                            font-weight: bold;
                            cursor: pointer;
                            border: 2px solid white;
                            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
                        }

                        .btn-delete-img:hover {
                            background: #bb2d3b;
                            color: white;
                        }

                        .img-thumb {
                            position: relative;
                        }

                        .img-thumb.preview img {
                            opacity: 0.7;
                            border: 2px dashed #ffc107;
                        }

                        .img-thumb .badge-pending {
                            position: absolute;
                            bottom: 2px;
                            left: 2px;
                            font-size: 9px;
                        }
                    </style>

                    <input type="hidden" name="referrer"
                        value="<?= htmlspecialchars($_GET['ref'] ?? $_SERVER['HTTP_REFERER'] ?? 'equipment.php') ?>">
                    <div class="form-text mb-3"><i class="bi bi-info-circle me-1"></i>เลือกรูปทีละรูป
                        หรือหลายรูปพร้อมกันได้ รูป preview สามารถกดลบได้ก่อนบันทึก</div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'บันทึกการแก้ไข' : 'ลงทะเบียนครุภัณฑ์' ?>
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="cancelBtn">
                            <i class="bi bi-x-lg me-1"></i>ยกเลิก
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle me-2"></i>คำแนะนำ
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>รหัสครุภัณฑ์ควรเป็นรูปแบบมาตรฐาน</li>
                    <li>เลือกสถานะให้ตรงกับสภาพปัจจุบัน</li>
                    <li>สามารถอัปโหลดรูปภาพได้หลายรูป</li>
                    <li>ผู้รับผิดชอบคืออาจารย์หรือเจ้าหน้าที่ที่ดูแลครุภัณฑ์ชิ้นนี้</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterDept = document.getElementById('filterDept');
        const filterSet = document.getElementById('filterSet');
        const itemSelect = document.getElementById('itemSelect');

        // Store all options
        const allSetOptions = Array.from(filterSet.options).slice(1);
        const allItemOptions = Array.from(itemSelect.options).slice(1);

        // Auto-select dept/set based on preselected item
        const selectedItem = itemSelect.options[itemSelect.selectedIndex];
        if (selectedItem && selectedItem.value) {
            const itemDept = selectedItem.dataset.dept;
            const itemSet = selectedItem.dataset.set;

            // Set dept
            if (itemDept) {
                filterDept.value = itemDept;
            }

            // Filter and set options
            filterSet.innerHTML = '<option value="">-- ทุกชุด --</option>';
            allSetOptions.forEach(opt => {
                if (!itemDept || opt.dataset.dept == itemDept) {
                    const newOpt = opt.cloneNode(true);
                    if (newOpt.value == itemSet) newOpt.selected = true;
                    filterSet.appendChild(newOpt);
                }
            });
        }

        // Filter Set by Dept
        filterDept.addEventListener('change', function () {
            const deptId = this.value;
            filterSet.innerHTML = '<option value="">-- ทุกชุด --</option>';

            allSetOptions.forEach(opt => {
                if (!deptId || opt.dataset.dept == deptId) {
                    filterSet.appendChild(opt.cloneNode(true));
                }
            });

            filterSet.dispatchEvent(new Event('change'));
        });

        // Filter Item by Set
        filterSet.addEventListener('change', function () {
            const setId = this.value;
            const deptId = filterDept.value;
            const currentValue = itemSelect.value;
            itemSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';

            allItemOptions.forEach(opt => {
                const matchDept = !deptId || opt.dataset.dept == deptId;
                const matchSet = !setId || opt.dataset.set == setId;
                if (matchDept && matchSet) {
                    const newOpt = opt.cloneNode(true);
                    if (newOpt.value == currentValue) newOpt.selected = true;
                    itemSelect.appendChild(newOpt);
                }
            });
            // We do NOT dispatch change here again, otherwise it creates an infinite loop with the itemSelect listener
            updateQtyInfo();
        });

        // Extracted qty info logic
        const qtyInfo = document.getElementById('itemQtyInfo');
        function updateQtyInfo() {
            const selected = itemSelect.options[itemSelect.selectedIndex];
            if (selected && selected.value) {
                const qty = parseInt(selected.dataset.qty) || 0;
                const remaining = parseInt(selected.dataset.remaining) || 999;
                if (qty > 0) {
                    if (remaining > 0) {
                        qtyInfo.innerHTML = `<span class="text-info"><i class="bi bi-info-circle me-1"></i>เพิ่มได้อีก ${remaining} ชิ้น (จาก ${qty})</span>`;
                    } else {
                        qtyInfo.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>รายการเต็มแล้ว!</span>`;
                    }
                } else {
                    qtyInfo.innerHTML = '';
                }
            } else {
                qtyInfo.innerHTML = '';
            }
        }

        // Whenever Item is selected, automatically select Dept and Set if not already selected
        itemSelect.addEventListener('change', function () {
            const selectedItem = this.options[this.selectedIndex];
            if (selectedItem && selectedItem.value) {
                const itemDept = selectedItem.dataset.dept;
                const itemSet = selectedItem.dataset.set;

                let needsUpdate = false;

                if (itemDept && filterDept.value !== itemDept) {
                    filterDept.value = itemDept;
                    needsUpdate = true;
                }

                if (needsUpdate) {
                    // Temporarily update Set options based on Dept without triggering Item reset
                    filterSet.innerHTML = '<option value="">-- ทุกชุด --</option>';
                    allSetOptions.forEach(opt => {
                        if (opt.dataset.dept == itemDept) {
                            filterSet.appendChild(opt.cloneNode(true));
                        }
                    });
                }

                if (itemSet && filterSet.value !== itemSet) {
                    filterSet.value = itemSet;
                }
            }
            updateQtyInfo();
        });

        // Price cascade logic
        const eqPrice = document.getElementById('eqPrice');
        const priceAlert = document.getElementById('priceAlert');
        const priceAlertText = document.getElementById('priceAlertText');
        const priceSourceLabel = document.getElementById('priceSourceLabel');

        itemSelect.addEventListener('change', function () {
            const selectedOpt = this.options[this.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) {
                eqPrice.readOnly = false;
                priceAlert.classList.add('d-none');
                priceSourceLabel.classList.add('d-none');
                return;
            }

            const itemPrice = parseFloat(selectedOpt.dataset.itemPrice || 0);
            const setPrice = parseFloat(selectedOpt.dataset.setPrice || 0);

            if (setPrice > 0) {
                eqPrice.value = 0;
                eqPrice.readOnly = true;
                priceAlertText.textContent = "หมวดหมู่ 'ชุดครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
                priceAlert.classList.remove('d-none');
                priceSourceLabel.textContent = "(ราคารวมอยู่ที่ชุดครุภัณฑ์)";
                priceSourceLabel.classList.remove('d-none');
            } else if (itemPrice > 0) {
                eqPrice.value = 0;
                eqPrice.readOnly = true;
                priceAlertText.textContent = "หมวดหมู่ 'รายการครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
                priceAlert.classList.remove('d-none');
                priceSourceLabel.textContent = "(ราคารวมอยู่ที่รายการครุภัณฑ์)";
                priceSourceLabel.classList.remove('d-none');
            } else {
                eqPrice.readOnly = false;
                priceAlert.classList.add('d-none');
                priceSourceLabel.classList.add('d-none');
            }
        });

        // Trigger cascade and qty on load
        if (itemSelect.value) {
            itemSelect.dispatchEvent(new Event('change'));
        }

        // Cancel button - go back to referrer
        document.getElementById('cancelBtn').addEventListener('click', function () {
            const referrer = document.querySelector('input[name="referrer"]').value;
            window.location.href = referrer || 'equipment.php';
        });

        // Room manager mapping (from PHP)
        const roomManagersData = <?= json_encode($managersByRoom, JSON_UNESCAPED_UNICODE) ?>;

        // Room-based holder suggestion
        const roomSelect = document.getElementById('roomSelect');
        const holderSelect = document.getElementById('holderSelect');
        const holderInfo = document.getElementById('holderInfo');
        const allHolderOptions = Array.from(holderSelect.options);
        const currentHolderId = '<?= $formData['holder_id'] ?>';

        roomSelect.addEventListener('change', function () {
            const roomId = this.value;
            const managers = roomManagersData[roomId] || [];

            // Reset holder select
            holderSelect.innerHTML = '<option value="">-- ไม่ระบุ --</option>';

            if (managers.length > 0) {
                // Add room managers as suggested options
                const optGroup = document.createElement('optgroup');
                optGroup.label = '★ ผู้รับผิดชอบห้องนี้';
                managers.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.user_id;
                    opt.textContent = m.firstname + ' ' + m.lastname;
                    opt.className = 'text-primary fw-bold';
                    optGroup.appendChild(opt);
                });
                holderSelect.appendChild(optGroup);

                // Add separator
                const optGroup2 = document.createElement('optgroup');
                optGroup2.label = '─── อื่นๆ ───';
                allHolderOptions.slice(1).forEach(opt => {
                    if (!managers.find(m => m.user_id == opt.value)) {
                        optGroup2.appendChild(opt.cloneNode(true));
                    }
                });
                holderSelect.appendChild(optGroup2);

                // Auto-select first manager if no current holder
                if (!currentHolderId) {
                    holderSelect.value = managers[0].user_id;
                    holderInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>เลือกผู้รับผิดชอบห้องอัตโนมัติ</span>';
                } else {
                    holderSelect.value = currentHolderId;
                    holderInfo.innerHTML = '';
                }
            } else {
                // No managers, show all options
                allHolderOptions.slice(1).forEach(opt => {
                    holderSelect.appendChild(opt.cloneNode(true));
                });
                if (currentHolderId) {
                    holderSelect.value = currentHolderId;
                }
                holderInfo.innerHTML = '';
            }
        });

        // Image upload managers
        const imageManagers = {
            purchase: { files: [], input: 'purchaseInput', filesInput: 'purchaseFiles', preview: 'purchasePreview' },
            current: { files: [], input: 'currentInput', filesInput: 'currentFiles', preview: 'currentPreview' }
        };

        // Setup image upload for each zone
        Object.keys(imageManagers).forEach(key => {
            const manager = imageManagers[key];
            const triggerInput = document.getElementById(manager.input);
            const filesInput = document.getElementById(manager.filesInput);
            const previewContainer = document.getElementById(manager.preview);

            triggerInput.addEventListener('change', function () {
                const file = this.files[0];
                if (!file) return;

                // Add to files array
                manager.files.push(file);

                // Remove "no image" message
                const noImgMsg = previewContainer.querySelector('.no-img-msg');
                if (noImgMsg) noImgMsg.remove();

                // Create preview
                const reader = new FileReader();
                reader.onload = function (e) {
                    const idx = manager.files.length - 1;
                    const div = document.createElement('div');
                    div.className = 'position-relative img-thumb preview';
                    div.dataset.idx = idx;
                    div.innerHTML = `
                    <img src="${e.target.result}" class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                    <span class="btn-delete-img delete-preview" data-manager="${key}" data-idx="${idx}">×</span>
                    <span class="badge bg-warning text-dark badge-pending">รอบันทึก</span>
                `;
                    previewContainer.appendChild(div);
                };
                reader.readAsDataURL(file);

                // Clear trigger input
                this.value = '';

                // Update hidden file input
                updateFilesInput(manager, filesInput);
            });
        });

        // Update hidden file input with DataTransfer
        function updateFilesInput(manager, filesInput) {
            const dt = new DataTransfer();
            manager.files.forEach(file => {
                if (file) dt.items.add(file);
            });
            filesInput.files = dt.files;
        }

        // Delete preview image
        document.addEventListener('click', function (e) {
            if (e.target.classList.contains('delete-preview')) {
                e.preventDefault();
                const managerKey = e.target.dataset.manager;
                const idx = parseInt(e.target.dataset.idx);
                const manager = imageManagers[managerKey];

                // Remove from array (set to null to keep indexes)
                manager.files[idx] = null;

                // Remove preview element
                e.target.closest('.img-thumb').remove();

                // Update hidden input
                const filesInput = document.getElementById(manager.filesInput);
                updateFilesInput(manager, filesInput);

                // Show "no image" if empty
                const previewContainer = document.getElementById(manager.preview);
                const hasImages = previewContainer.querySelectorAll('.img-thumb').length > 0;
                if (!hasImages) {
                    previewContainer.innerHTML = `
                    <div class="text-muted small w-100 text-center py-3 no-img-msg">
                        <i class="bi bi-image me-1"></i>ยังไม่มีรูป - กดปุ่ม "เพิ่มรูป" เพื่อเลือก
                    </div>
                `;
                }
            }
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>