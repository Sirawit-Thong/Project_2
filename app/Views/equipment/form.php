<?php
$isEdit = $equipment !== null;
$formAction = $isEdit ? SITE_URL . '/equipment/edit/' . $equipment['id'] : SITE_URL . '/equipment/add';
?>

<div class="page-header mb-4">
    <h4 class="mb-1">
        <i class="bi <?= $isEdit ? 'bi-pencil-square' : 'bi-plus-circle' ?> me-2"></i><?= $pageTitle ?>
    </h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment">รายการครุภัณฑ์</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <form method="POST" action="<?= $formAction ?>" id="equipmentForm" enctype="multipart/form-data">
                    <?= csrf_field() ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">รหัสครุภัณฑ์ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="code" required
                                placeholder="เช่น CP-001"
                                value="<?= sanitize($equipment['code'] ?? ($_POST['code'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">สถานะ <span class="text-danger">*</span></label>
                            <select class="form-select" name="status">
                                <?php
                                $statuses = [
                                    'available' => 'พร้อมใช้งาน',
                                    'repair' => 'ส่งซ่อม',
                                    'broken' => 'ซ่อมไม่ได้',
                                    'pending_disposal' => 'รอจำหน่ายออก',
                                    'disposed' => 'จำหน่ายออก',
                                ];
                                $currentStatus = $equipment['status'] ?? ($_POST['status'] ?? 'available');
                                foreach ($statuses as $val => $label):
                                ?>
                                    <option value="<?= $val ?>" <?= $currentStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="bi bi-tags me-1"></i>ประเภทครุภัณฑ์</h6>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">สาขาวิชา <span class="text-danger">*</span></label>
                            <select class="form-select" id="deptSelect" required>
                                <option value="">-- เลือกสาขาวิชา --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>" <?= ($equipment['dept_id'] ?? $_POST['dept_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ชุดครุภัณฑ์ <span class="text-danger">*</span></label>
                            <select class="form-select" id="setSelect" required disabled>
                                <option value="">-- เลือกชุดครุภัณฑ์ --</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">รายการครุภัณฑ์ <span class="text-danger">*</span></label>
                            <select class="form-select" name="item_id" id="itemSelect" required disabled>
                                <option value="">-- เลือกรายการ --</option>
                            </select>
                            <div id="itemQtyInfo" class="form-text"></div>
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="bi bi-info-circle me-1"></i>รายละเอียด</h6>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">ห้อง / สถานที่</label>
                            <select class="form-select" name="room_id">
                                <option value="">-- ไม่ระบุ --</option>
                                <?php foreach ($rooms as $r): ?>
                                    <option value="<?= $r['id'] ?>" <?= ($equipment['room_id'] ?? $_POST['room_id'] ?? '') == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">ผู้ถือครอง</label>
                            <select class="form-select" name="holder_id">
                                <option value="">-- ไม่ระบุ --</option>
                                    <?php foreach ($holders as $h): ?>
                                        <option value="<?= $h['id'] ?>" <?= ($equipment['holder_id'] ?? $_POST['holder_id'] ?? '') == $h['id'] ? 'selected' : '' ?>><?= sanitize($h['firstname'] . ' ' . $h['lastname']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div id="holderInfo" class="form-text"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">วันที่จัดซื้อ</label>
                            <input type="date" class="form-control" name="purchase_date"
                                value="<?= $equipment['purchase_date'] ?? ($_POST['purchase_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">วันที่ตรวจล่าสุด</label>
                            <input type="date" class="form-control" name="check_date"
                                value="<?= $equipment['check_date'] ?? ($_POST['check_date'] ?? '') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">มูลค่า (บาท)</label>
                            <input type="number" step="0.01" class="form-control" name="price"
                                placeholder="0.00"
                                value="<?= $equipment['price'] ?? ($_POST['price'] ?? '') ?>">
                        </div>
                    </div>

                    <div class="alert alert-info d-none py-2 mb-3" id="priceAlert">
                        <i class="bi bi-info-circle me-1"></i> <span>ชุดครุภัณฑ์ หรือรายการครุภัณฑ์มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกตั้งเป็น 0</span>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">หมายเหตุมูลค่า</label>
                            <input type="text" class="form-control" name="price_remark"
                                placeholder="หมายเหตุเกี่ยวกับมูลค่า"
                                value="<?= sanitize($equipment['price_remark'] ?? ($_POST['price_remark'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">หมายเหตุทั่วไป</label>
                            <input type="text" class="form-control" name="remark"
                                placeholder="หมายเหตุเพิ่มเติม"
                                value="<?= sanitize($equipment['remark'] ?? ($_POST['remark'] ?? '')) ?>">
                        </div>
                    </div>

                    <hr>

                    <h6 class="mb-3"><i class="bi bi-images me-1"></i>รูปภาพครุภัณฑ์</h6>

                    <div class="row">
                        <!-- Purchase Images Zone -->
                        <div class="col-md-6 mb-3">
                            <div class="card border-info h-100">
                                <div class="card-header bg-info text-white py-2 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-cart-check me-1"></i>ภาพถ่ายเมื่อแรกรับ/จัดซื้อ</span>
                                    <label for="purchaseInput" class="btn btn-light btn-sm py-0 px-2 mb-0">
                                        <i class="bi bi-plus-lg"></i> เพิ่มรูป
                                    </label>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="d-none" id="purchaseInput" accept="image/*">
                                    <input type="file" class="d-none" id="purchaseFiles" name="purchase_images[]" multiple>
                                    <div class="d-flex flex-wrap gap-2" id="purchasePreview" style="min-height: 90px;">
                                        <?php
                                        $purchaseImages = array_filter($existingImages, fn($img) => ($img['type'] ?? '') === 'purchase');
                                        foreach ($purchaseImages as $img):
                                        ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <form method="POST" action="<?= SITE_URL ?>/equipment/delete-image/<?= $equipment['id'] ?>"
                                                    onsubmit="return confirm('ลบรูปนี้?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                    <button type="submit" class="btn-delete-img" title="ลบรูปนี้">×</button>
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
                                <div class="card-header bg-success text-white py-2 d-flex justify-content-between align-items-center">
                                    <span><i class="bi bi-camera me-1"></i>ภาพถ่ายสภาพปัจจุบัน</span>
                                    <label for="currentInput" class="btn btn-light btn-sm py-0 px-2 mb-0">
                                        <i class="bi bi-plus-lg"></i> เพิ่มรูป
                                    </label>
                                </div>
                                <div class="card-body">
                                    <input type="file" class="d-none" id="currentInput" accept="image/*">
                                    <input type="file" class="d-none" id="currentFiles" name="current_images[]" multiple>
                                    <div class="d-flex flex-wrap gap-2" id="currentPreview" style="min-height: 90px;">
                                        <?php
                                        $currentImages = array_filter($existingImages, fn($img) => ($img['type'] ?? '') === 'current_condition');
                                        foreach ($currentImages as $img):
                                        ?>
                                            <div class="position-relative img-thumb">
                                                <img src="<?= SITE_URL ?>/uploads/<?= htmlspecialchars($img['path']) ?>"
                                                    class="rounded" style="width: 100px; height: 80px; object-fit: cover;">
                                                <form method="POST" action="<?= SITE_URL ?>/equipment/delete-image/<?= $equipment['id'] ?>"
                                                    onsubmit="return confirm('ลบรูปนี้?');">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="image_id" value="<?= $img['id'] ?>">
                                                    <button type="submit" class="btn-delete-img" title="ลบรูปนี้">×</button>
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
                        .btn-delete-img:hover { background: #bb2d3b; color: white; }
                        .img-thumb { position: relative; }
                        .img-thumb.preview img { opacity: 0.7; border: 2px dashed #ffc107; }
                        .img-thumb .badge-pending {
                            position: absolute;
                            bottom: 2px;
                            left: 2px;
                            font-size: 9px;
                        }
                    </style>

                    <div class="form-text mb-3"><i class="bi bi-info-circle me-1"></i>เลือกรูปทีละรูปหรือหลายรูปพร้อมกันได้ รูป preview สามารถกดลบได้ก่อนบันทึก</div>

                    <hr>

                    <div class="d-flex justify-content-between">
                        <a href="<?= SITE_URL ?>/equipment" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i>ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i><?= $isEdit ? 'บันทึกการแก้ไข' : 'เพิ่มครุภัณฑ์' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allItems = <?= json_encode($allItems, JSON_UNESCAPED_UNICODE) ?>;
    const allSets = <?= json_encode($allSets, JSON_UNESCAPED_UNICODE) ?>;
    const existingItemId = '<?= $preselectedItemId ?? $equipment['item_id'] ?? $_POST['item_id'] ?? '' ?>';

    const deptSelect = document.getElementById('deptSelect');
    const setSelect = document.getElementById('setSelect');
    const itemSelect = document.getElementById('itemSelect');

    function getSetsForDept(deptId) {
        return allSets.filter(s => String(s.dept_id) === String(deptId));
    }

    function getItemsForSet(setId) {
        return allItems.filter(i => String(i.set_id) === String(setId));
    }

    deptSelect.addEventListener('change', function() {
        const deptId = this.value;
        setSelect.innerHTML = '<option value="">-- เลือกชุดครุภัณฑ์ --</option>';
        itemSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';
        itemSelect.disabled = true;

        if (!deptId) {
            setSelect.disabled = true;
            return;
        }

        const sets = getSetsForDept(deptId);
        sets.forEach(s => {
            const opt = new Option(s.name + (s.year ? ' (' + s.year + ')' : ''), s.id);
            setSelect.appendChild(opt);
        });
        setSelect.disabled = false;
    });

    setSelect.addEventListener('change', function() {
        const setId = this.value;
        itemSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';

        if (!setId) {
            itemSelect.disabled = true;
            return;
        }

        const items = getItemsForSet(setId);
        items.forEach(i => {
            const remaining = i.qty > 0 ? i.qty - i.existing_count : 999;
            const label = i.name + (i.brand ? ' - ' + i.brand : '') + ' (' + i.existing_count + '/' + i.qty + ')';
            const opt = new Option(label, i.id);
            opt.dataset.itemPrice = i.price || 0;
            opt.dataset.setPrice = i.set_price || 0;
            opt.dataset.qty = i.qty || 0;
            opt.dataset.existing = i.existing_count || 0;
            itemSelect.appendChild(opt);
        });
        itemSelect.disabled = false;
    });

    // ---- Qty info + price cascade ----
    const qtyInfo = document.getElementById('itemQtyInfo');
    const priceInput = document.querySelector('input[name="price"]');
    const priceAlert = document.getElementById('priceAlert');

    function updateQtyInfo() {
        if (!qtyInfo) return;
        const selected = itemSelect.options[itemSelect.selectedIndex];
        if (selected && selected.value) {
            const qty = parseInt(selected.dataset.qty) || 0;
            const existing = parseInt(selected.dataset.existing) || 0;
            if (qty > 0) {
                const remaining = qty - existing;
                qtyInfo.innerHTML = remaining > 0
                    ? '<span class="text-info"><i class="bi bi-info-circle me-1"></i>เพิ่มได้อีก ' + remaining + ' ชิ้น (จาก ' + qty + ')</span>'
                    : '<span class="text-danger"><i class="bi bi-exclamation-circle me-1"></i>รายการเต็มแล้ว!</span>';
            } else {
                qtyInfo.innerHTML = '';
            }
        } else {
            qtyInfo.innerHTML = '';
        }
    }

    function updatePriceCascade() {
        const selected = itemSelect.options[itemSelect.selectedIndex];
        if (!selected || !selected.value) {
            priceInput.readOnly = false;
            priceInput.disabled = false;
            if (priceAlert) priceAlert.classList.add('d-none');
            return;
        }

        const itemPrice = parseFloat(selected.dataset.itemPrice || 0);
        const setPrice = parseFloat(selected.dataset.setPrice || 0);

        if (setPrice > 0) {
            priceInput.value = 0;
            priceInput.readOnly = true;
            priceInput.disabled = true;
            if (priceAlert) {
                priceAlert.classList.remove('d-none');
                priceAlert.querySelector('span').textContent = "หมวดหมู่ 'ชุดครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
            }
        } else if (itemPrice > 0) {
            priceInput.value = 0;
            priceInput.readOnly = true;
            priceInput.disabled = true;
            if (priceAlert) {
                priceAlert.classList.remove('d-none');
                priceAlert.querySelector('span').textContent = "หมวดหมู่ 'รายการครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
            }
        } else {
            priceInput.readOnly = false;
            priceInput.disabled = false;
            if (priceAlert) priceAlert.classList.add('d-none');
        }
    }

    itemSelect.addEventListener('change', function() {
        updateQtyInfo();
        updatePriceCascade();
    });

    // ---- Room-based holder suggestion ----
    const roomSelect = document.querySelector('select[name="room_id"]');
    const holderSelect = document.querySelector('select[name="holder_id"]');
    const holderInfo = document.getElementById('holderInfo');
    const managersByRoom = <?= json_encode($managersByRoom ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const allHolderOptions = Array.from(holderSelect.options);
    const currentHolderId = '<?= $equipment['holder_id'] ?? ($_POST['holder_id'] ?? '') ?>';

    if (roomSelect && holderSelect) {
        roomSelect.addEventListener('change', function() {
            const roomId = this.value;
            const managers = managersByRoom[roomId] || [];

            holderSelect.innerHTML = '<option value="">-- ไม่ระบุ --</option>';

            if (managers.length > 0) {
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

                const optGroup2 = document.createElement('optgroup');
                optGroup2.label = '─── อื่นๆ ───';
                allHolderOptions.slice(1).forEach(opt => {
                    if (!managers.find(m => m.user_id == opt.value)) {
                        optGroup2.appendChild(opt.cloneNode(true));
                    }
                });
                holderSelect.appendChild(optGroup2);

                if (!currentHolderId) {
                    holderSelect.value = managers[0].user_id;
                    if (holderInfo) {
                        holderInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>เลือกผู้รับผิดชอบห้องอัตโนมัติ</span>';
                    }
                } else {
                    holderSelect.value = currentHolderId;
                    if (holderInfo) holderInfo.innerHTML = '';
                }
            } else {
                allHolderOptions.slice(1).forEach(opt => {
                    holderSelect.appendChild(opt.cloneNode(true));
                });
                if (currentHolderId) {
                    holderSelect.value = currentHolderId;
                }
                if (holderInfo) holderInfo.innerHTML = '';
            }
        });
    }

    // If editing or POST data, pre-select the cascade
    if (existingItemId) {
        const targetItem = allItems.find(i => String(i.id) === String(existingItemId));
        if (targetItem) {
            deptSelect.value = targetItem.dept_id;
            deptSelect.dispatchEvent(new Event('change'));

            setTimeout(function() {
                setSelect.value = targetItem.set_id;
                setSelect.dispatchEvent(new Event('change'));

                setTimeout(function() {
                    itemSelect.value = existingItemId;
                    itemSelect.dispatchEvent(new Event('change'));
                    updateQtyInfo();
                    updatePriceCascade();
                }, 50);
            }, 50);
        }
    }

    // Trigger holder suggestion on load if room selected
    if (roomSelect && roomSelect.value) {
        roomSelect.dispatchEvent(new Event('change'));
    }

    // ---- Image picker + preview (add mode) ----
    function wireImagePicker(inputId, fileId, previewId) {
        const input = document.getElementById(inputId);
        const files = document.getElementById(fileId);
        const preview = document.getElementById(previewId);

        input.addEventListener('change', function() {
            files.files = input.files;
            renderPreview(preview, files.files);
        });

        preview.addEventListener('click', function(e) {
            const del = e.target.closest('.btn-delete-img');
            if (!del) return;
            const idx = parseInt(del.dataset.idx);
            if (isNaN(idx)) return;

            const dt = new DataTransfer();
            Array.from(files.files).forEach((f, i) => { if (i !== idx) dt.items.add(f); });
            files.files = dt.files;
            renderPreview(preview, files.files);
            input.value = '';
        });
    }

    function renderPreview(preview, fileList) {
        const files = Array.from(fileList);
        preview.innerHTML = '';
        if (files.length === 0) {
            const msg = document.createElement('div');
            msg.className = 'text-muted small w-100 text-center py-3 no-img-msg';
            msg.innerHTML = '<i class="bi bi-image me-1"></i>ยังไม่มีรูป - กดปุ่ม "เพิ่มรูป" เพื่อเลือก';
            preview.appendChild(msg);
            return;
        }

        files.forEach((file, i) => {
            const wrap = document.createElement('div');
            wrap.className = 'position-relative img-thumb preview';
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.className = 'rounded';
            img.style.cssText = 'width: 100px; height: 80px; object-fit: cover;';
            img.title = file.name;
            const del = document.createElement('button');
            del.type = 'button';
            del.className = 'btn-delete-img';
            del.dataset.idx = i;
            del.innerHTML = '&times;';
            const badge = document.createElement('span');
            badge.className = 'badge bg-warning text-dark badge-pending';
            badge.textContent = 'ใหม่';
            wrap.appendChild(img);
            wrap.appendChild(del);
            wrap.appendChild(badge);
            preview.appendChild(wrap);
        });
    }

    wireImagePicker('purchaseInput', 'purchaseFiles', 'purchasePreview');
    wireImagePicker('currentInput', 'currentFiles', 'currentPreview');
});
</script>
