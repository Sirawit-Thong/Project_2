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
                <form method="POST" action="<?= $formAction ?>" id="equipmentForm">
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
    const existingItemId = '<?= $equipment['item_id'] ?? $_POST['item_id'] ?? '' ?>';

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
            const label = i.name + (i.brand ? ' - ' + i.brand : '') + ' (' + i.existing_count + '/' + i.qty + ')';
            const opt = new Option(label, i.id);
            itemSelect.appendChild(opt);
        });
        itemSelect.disabled = false;
    });

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
                }, 50);
            }, 50);
        }
    }
});
</script>
