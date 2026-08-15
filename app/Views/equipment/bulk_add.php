<?php
$pageTitle = $pageTitle ?? 'เพิ่มครุภัณฑ์หลายรายการ';
$errors = $errors ?? [];
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-plus-square me-2"></i>เพิ่มครุภัณฑ์หลายรายการ</h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment">ครุภัณฑ์</a></li>
                <li class="breadcrumb-item active">เพิ่มหลายรายการ</li>
            </ol>
        </nav>
    </div>
    <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </button>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
                <li><?= sanitize($error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-list-check me-2"></i>ข้อมูลครุภัณฑ์
            </div>
            <div class="card-body">
                <form method="POST" action="<?= SITE_URL ?>/equipment/bulk-add" id="bulkAddForm">
                    <?= csrf_field() ?>

                    <!-- Item Selection with Filters -->
                    <div class="row g-2 mb-3">
                        <div class="col-md-3">
                            <label class="form-label small">สาขา</label>
                            <select class="form-select form-select-sm" id="filterDept">
                                <option value="">-- ทุกสาขา --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= (int) $dept['id'] ?>"><?= sanitize($dept['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">ชุดครุภัณฑ์</label>
                            <select class="form-select form-select-sm" id="filterSet">
                                <option value="">-- ทุกชุด --</option>
                                <?php foreach ($allSets as $set): ?>
                                    <option value="<?= (int) $set['id'] ?>" data-dept="<?= $set['dept_id'] ?>">
                                        <?= sanitize($set['name']) ?> (<?= sanitize($set['year']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label small">รายการ <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="item_id" id="itemSelect" required>
                                <option value="">-- เลือกรายการ --</option>
                                <?php foreach ($allItems as $item):
                                    $remaining = $item['qty'] > 0 ? (int) $item['qty'] - (int) ($item['existing_count'] ?? 0) : 999;
                                    $qtyText = $item['qty'] > 0 ? " [{$item['existing_count']}/{$item['qty']}]" : "";
                                    ?>
                                    <option value="<?= (int) $item['id'] ?>" data-dept="<?= $item['dept_id'] ?>"
                                        data-set="<?= (int) $item['set_id'] ?>" data-qty="<?= (int) $item['qty'] ?>"
                                        data-remaining="<?= $remaining ?>" data-item-price="<?= (float) ($item['price'] ?? 0) ?>"
                                        data-set-price="<?= (float) ($item['set_price'] ?? 0) ?>"
                                        <?= $remaining <= 0 ? 'disabled' : '' ?>>
                                        <?= sanitize($item['name']) ?>
                                        (<?= sanitize($item['brand'] ?? '-') ?>)<?= $qtyText ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="itemQtyInfo" class="form-text"></div>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">ห้อง/สถานที่</label>
                            <select class="form-select" name="room_id" id="roomSelect">
                                <option value="">-- เลือกห้อง --</option>
                                <?php foreach ($rooms as $room): ?>
                                    <option value="<?= (int) $room['id'] ?>"><?= sanitize($room['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ผู้รับผิดชอบ</label>
                            <select class="form-select" name="holder_id" id="holderSelect">
                                <option value="">-- เลือกผู้รับผิดชอบ --</option>
                                <?php foreach ($holders as $t): ?>
                                    <option value="<?= (int) $t['id'] ?>">
                                        <?= sanitize($t['firstname'] . ' ' . $t['lastname']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="holderInfo" class="form-text"></div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">สถานะ</label>
                            <select class="form-select" name="status">
                                <option value="available">พร้อมใช้งาน</option>
                                <option value="repair">ส่งซ่อม</option>
                                <option value="broken">ซ่อมไม่ได้</option>
                                <option value="pending_disposal">รอจำหน่ายออก</option>
                                <option value="disposed">จำหน่ายออก</option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-4">
                            <label class="form-label">วันที่จัดซื้อ</label>
                            <input type="date" class="form-control" name="purchase_date">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">ราคาต่อชิ้น (บาท) <span id="priceSourceLabel"
                                    class="badge bg-secondary ms-1 d-none"></span></label>
                            <input type="number" class="form-control" name="price" id="eqPrice" step="0.01">
                        </div>
                    </div>

                    <div class="alert alert-info d-none py-2 mb-3" id="priceAlert">
                        <i class="bi bi-info-circle me-1"></i> <span id="priceAlertText">ชุดครุภัณฑ์ หรือ
                            รายการครุภัณฑ์มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกตั้งเป็น 0</span>
                    </div>

                    <hr>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0"><i class="bi bi-123 me-1"></i>รหัสครุภัณฑ์ <span
                                    class="text-danger">*</span></label>
                            <button type="button" class="btn btn-success btn-sm" id="addCodeBtn">
                                <i class="bi bi-plus me-1"></i>เพิ่มช่อง
                            </button>
                        </div>
                        <div id="codesContainer">
                            <div class="input-group mb-2 code-row">
                                <span class="input-group-text">1</span>
                                <input type="text" class="form-control" name="codes[]"
                                    placeholder="เช่น 7440-001-0001.1/567 วท." required>
                                <button type="button" class="btn btn-outline-danger remove-code" disabled aria-label="ลบแถวนี้"><i
                                        class="bi bi-x"></i></button>
                            </div>
                        </div>
                        <div class="form-text">ระบุรหัสครุภัณฑ์แต่ละชิ้น หรือใช้ปุ่ม "เพิ่มช่อง" เพื่อเพิ่มหลายชิ้น
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-check-lg me-1"></i>บันทึกทั้งหมด
                        </button>
                        <button type="button" class="btn btn-outline-secondary" onclick="history.back()">
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
                <ul class="mb-0 small">
                    <li>เลือกรายการครุภัณฑ์ที่ต้องการเพิ่ม</li>
                    <li>กรอกข้อมูลทั่วไป (ห้อง, สถานะ, วันที่จัดซื้อ, ราคา) ซึ่งจะใช้กับทุกชิ้น</li>
                    <li>กดปุ่ม "เพิ่มช่อง" เพื่อเพิ่มรหัสครุภัณฑ์หลายชิ้น</li>
                    <li>รหัสครุภัณฑ์แต่ละช่องจะสร้างเป็น 1 รายการ</li>
                    <li>สามารถเพิ่มรูปภาพได้ภายหลังโดยแก้ไขทีละชิ้น</li>
                </ul>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-header bg-warning text-dark">
                <i class="bi bi-lightbulb me-2"></i>Quick Fill
            </div>
            <div class="card-body">
                <label class="form-label small">เพิ่มหลายรหัสพร้อมกัน (1 บรรทัด = 1 รหัส)</label>
                <textarea class="form-control mb-2" id="quickFillArea" rows="4"
                    placeholder="วางรหัสครุภัณฑ์ที่นี่&#10;แต่ละบรรทัดจะเพิ่มเป็น 1 ช่อง"></textarea>
                <button type="button" class="btn btn-warning btn-sm w-100" id="quickFillBtn">
                    <i class="bi bi-magic me-1"></i>เติมลงช่อง
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterDept = document.getElementById('filterDept');
        const filterSet = document.getElementById('filterSet');
        const itemSelect = document.getElementById('itemSelect');
        const codesContainer = document.getElementById('codesContainer');
        const addCodeBtn = document.getElementById('addCodeBtn');

        // Store all options
        const allSetOptions = Array.from(filterSet.options).slice(1);
        const allItemOptions = Array.from(itemSelect.options).slice(1);

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
            itemSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';

            allItemOptions.forEach(opt => {
                const matchDept = !deptId || opt.dataset.dept == deptId;
                const matchSet = !setId || opt.dataset.set == setId;
                if (matchDept && matchSet) {
                    itemSelect.appendChild(opt.cloneNode(true));
                }
            });
            updateQtyInfo();
        });

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
                    // Temporarily update Set options based on Dept
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
            // Trigger qty update and price cascade logic
            updateQtyInfo();
            checkPriceCascade();
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

        // Price cascade logic
        const eqPrice = document.getElementById('eqPrice');
        const priceAlert = document.getElementById('priceAlert');
        const priceAlertText = document.getElementById('priceAlertText');
        const priceSourceLabel = document.getElementById('priceSourceLabel');

        function checkPriceCascade() {
            const selectedOpt = itemSelect.options[itemSelect.selectedIndex];
            if (!selectedOpt || !selectedOpt.value) {
                if (eqPrice) eqPrice.readOnly = false;
                if (priceAlert) priceAlert.classList.add('d-none');
                if (priceSourceLabel) priceSourceLabel.classList.add('d-none');
                return;
            }

            const itemPrice = parseFloat(selectedOpt.dataset.itemPrice || 0);
            const setPrice = parseFloat(selectedOpt.dataset.setPrice || 0);

            if (setPrice > 0) {
                if (eqPrice) { eqPrice.value = 0; eqPrice.readOnly = true; }
                if (priceAlertText) priceAlertText.textContent = "หมวดหมู่ 'ชุดครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
                if (priceAlert) priceAlert.classList.remove('d-none');
                if (priceSourceLabel) { priceSourceLabel.textContent = "(ราคารวมอยู่ที่ชุดครุภัณฑ์)"; priceSourceLabel.classList.remove('d-none'); }
            } else if (itemPrice > 0) {
                if (eqPrice) { eqPrice.value = 0; eqPrice.readOnly = true; }
                if (priceAlertText) priceAlertText.textContent = "หมวดหมู่ 'รายการครุภัณฑ์' มีการระบุราคารวมไว้แล้ว ราคาของครุภัณฑ์ชิ้นนี้จะถูกบังคับเป็น 0";
                if (priceAlert) priceAlert.classList.remove('d-none');
                if (priceSourceLabel) { priceSourceLabel.textContent = "(ราคารวมอยู่ที่รายการครุภัณฑ์)"; priceSourceLabel.classList.remove('d-none'); }
            } else {
                if (eqPrice) eqPrice.readOnly = false;
                if (priceAlert) priceAlert.classList.add('d-none');
                if (priceSourceLabel) priceSourceLabel.classList.add('d-none');
            }
        }

        // Add code row
        function addCodeRow(value = '') {
            const rowNum = codesContainer.children.length + 1;
            const div = document.createElement('div');
            div.className = 'input-group mb-2 code-row';
            div.innerHTML = `
            <span class="input-group-text">${rowNum}</span>
            <input type="text" class="form-control" name="codes[]" placeholder="เช่น 7440-001-0001.1/567 วท." value="${value.replace(/"/g, '&quot;')}" required>
            <button type="button" class="btn btn-outline-danger remove-code" aria-label="ลบแถวนี้"><i class="bi bi-x"></i></button>
        `;
            codesContainer.appendChild(div);
            updateRemoveButtons();
        }

        // Update remove buttons state
        function updateRemoveButtons() {
            const rows = codesContainer.querySelectorAll('.code-row');
            rows.forEach((row, idx) => {
                row.querySelector('.input-group-text').textContent = idx + 1;
                row.querySelector('.remove-code').disabled = rows.length <= 1;
            });
        }

        addCodeBtn.addEventListener('click', () => addCodeRow());

        // Remove code row
        codesContainer.addEventListener('click', function (e) {
            if (e.target.closest('.remove-code')) {
                e.target.closest('.code-row').remove();
                updateRemoveButtons();
            }
        });

        // Quick fill
        document.getElementById('quickFillBtn').addEventListener('click', function () {
            const text = document.getElementById('quickFillArea').value.trim();
            if (!text) return;

            const lines = text.split('\n').map(line => line.trim()).filter(line => line);

            // Clear existing rows
            codesContainer.innerHTML = '';

            // Add rows for each line
            lines.forEach(line => addCodeRow(line));

            document.getElementById('quickFillArea').value = '';
        });

        // Room manager mapping (from PHP)
        const roomManagersData = <?= json_encode($managersByRoom, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;

        // Room-based holder suggestion
        const roomSelect = document.getElementById('roomSelect');
        const holderSelect = document.getElementById('holderSelect');
        const holderInfo = document.getElementById('holderInfo');
        const allHolderOptions = Array.from(holderSelect.options);

        roomSelect.addEventListener('change', function () {
            const roomId = this.value;
            const managers = roomManagersData[roomId] || [];

            // Reset holder select
            holderSelect.innerHTML = '<option value="">-- เลือกผู้รับผิดชอบ --</option>';

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

                // Auto-select first manager
                holderSelect.value = managers[0].user_id;
                holderInfo.innerHTML = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>เลือกผู้รับผิดชอบห้องอัตโนมัติ</span>';
            } else {
                // No managers, show all options
                allHolderOptions.slice(1).forEach(opt => {
                    holderSelect.appendChild(opt.cloneNode(true));
                });
                holderInfo.innerHTML = '';
            }
        });
    });
</script>
