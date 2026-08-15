<?php
$isEdit = $equipment !== null;
$formAction = $isEdit ? SITE_URL . '/equipment/edit/' . $equipment['id'] : SITE_URL . '/equipment/add';
$items = $allItems;
$sets = $allSets;

// Form values - include item_id from URL if provided (for quick add from items page)
$formData = [
    'item_id' => $_POST['item_id'] ?? ($equipment['item_id'] ?? ($preselectedItemId ?? '')),
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
?>

<!-- Page Header -->
<div class="page-header">
    <h1><i
            class="bi bi-<?= $isEdit ? 'pencil' : 'plus-circle' ?> me-2"></i><?= $isEdit ? 'แก้ไขข้อมูลครุภัณฑ์' : 'ลงทะเบียนครุภัณฑ์ใหม่' ?>
    </h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment">ครุภัณฑ์</a></li>
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
                <form method="POST" action="<?= $formAction ?>" enctype="multipart/form-data" id="equipmentForm">
                    <?= csrf_field() ?>

                    <!-- Item Selection with Filters -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">สาขา</label>
                            <select class="form-select form-select-sm" id="filterDept">
                                <option value="">-- ทุกสาขา --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['id'] ?>"><?= sanitize($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">ชุดครุภัณฑ์</label>
                            <select class="form-select form-select-sm" id="filterSet">
                                <option value="">-- ทุกชุด --</option>
                                <?php foreach ($sets as $set): ?>
                                    <option value="<?= $set['id'] ?>" data-dept="<?= $set['dept_id'] ?>">
                                        <?= sanitize($set['name']) ?> (<?= $set['year'] ?>)
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
                                        <?= sanitize($item['name']) ?>
                                        (<?= sanitize($item['brand'] ?? '-') ?>)<?= $qtyText ?>
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
                                value="<?= sanitize($formData['code']) ?>"
                                placeholder="เช่น 7440-001-0001.1/551 วท.">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ห้อง/สถานที่</label>
                            <select class="form-select" name="room_id" id="roomSelect">
                                <option value="">-- เลือกห้อง --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= $room['id'] ?>" <?= $formData['room_id'] == $room['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($room['name']) ?>
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
                                value="<?= sanitize($formData['purchase_date']) ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ราคา (บาท) <span id="priceSourceLabel"
                                    class="badge bg-secondary ms-1 d-none"></span></label>
                            <input type="number" class="form-control" name="price" id="eqPrice" step="0.01"
                                value="<?= sanitize($formData['price']) ?>">
                        </div>
                    </div>

                    <div class="alert alert-info d-none py-2 mb-3" id="priceAlert">
                        <i class="bi bi-info-circle me-1"></i> <span id="priceAlertText">ชุดครุภัณฑ์ หรือ
                            รายการครุภัณฑ์มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกตั้งเป็น 0</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">หมายเหตุราคา (กรณีพิเศษรายชิ้น)</label>
                        <input type="text" class="form-control" name="price_remark" id="eqPriceRemark"
                            value="<?= sanitize($formData['price_remark']) ?>"
                            placeholder="คำอธิบายราคาเฉพาะชิ้นนี้ (ถ้ามี)">
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">วันที่ตรวจเช็คล่าสุด</label>
                            <input type="date" class="form-control" name="check_date"
                                value="<?= sanitize($formData['check_date']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ผู้รับผิดชอบดูแล</label>
                            <select class="form-select" name="holder_id" id="holderSelect">
                                <option value="">-- ไม่ระบุ --</option>
                                <?php foreach ($holders as $holder): ?>
                                    <option value="<?= $holder['id'] ?>" <?= $formData['holder_id'] == $holder['id'] ? 'selected' : '' ?>>
                                        <?= sanitize($holder['firstname'] . ' ' . $holder['lastname']) ?>
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
                            rows="2"><?= sanitize($formData['remark']) ?></textarea>
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
                                        $purchaseImages = array_filter($existingImages, fn($img) => ($img['type'] ?? '') === 'purchase');
                                        foreach ($purchaseImages as $img):
                                        ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= sanitize($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <form method="POST" action="<?= SITE_URL ?>/equipment/delete-image/<?= $equipment['id'] ?>"
                                                    onsubmit="return confirm('ลบรูปนี้?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                    <button type="submit" class="btn-delete-img" title="ลบรูปนี้" aria-label="ลบรูปนี้">×</button>
                                                </form>
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
                                        $currentImages = array_filter($existingImages, fn($img) => ($img['type'] ?? '') === 'current_condition');
                                        foreach ($currentImages as $img):
                                        ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= sanitize($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <form method="POST" action="<?= SITE_URL ?>/equipment/delete-image/<?= $equipment['id'] ?>"
                                                    onsubmit="return confirm('ลบรูปนี้?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                    <button type="submit" class="btn-delete-img" title="ลบรูปนี้" aria-label="ลบรูปนี้">×</button>
                                                </form>
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
                            line-height: 1;
                            padding: 0;
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
                        value="<?= sanitize($_GET['ref'] ?? '') ?>">
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
                const remainingParsed = parseInt(selected.dataset.remaining);
                const remaining = Number.isNaN(remainingParsed) ? 999 : remainingParsed;
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
            const siteUrl = '<?= SITE_URL ?>';
            if (referrer && referrer.indexOf(siteUrl) === 0) {
                window.location.href = referrer;
            } else {
                window.location.href = '<?= SITE_URL ?>/equipment';
            }
        });

        // Room manager mapping (from PHP)
        const roomManagersData = <?= json_encode($managersByRoom ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

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
                    <span class="btn-delete-img delete-preview" data-manager="${key}" data-idx="${idx}" role="button" aria-label="ลบรูปนี้">×</span>
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
