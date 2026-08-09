<?php
$pageTitle = $pageTitle ?? 'ตรวจนับประจำปี';
?>

<div class="page-header mb-4">
    <h4 class="mb-1"><i class="bi bi-clipboard-check me-2"></i><?= $pageTitle ?></h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/equipment/inspection" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label"><i class="bi bi-door-open me-1"></i>เลือกห้อง</label>
                <select class="form-select" name="room_id" id="roomSelect" required>
                    <option value="">-- เลือกห้องที่ต้องการตรวจนับ --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $selectedRoom == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i>แสดงรายการ
                </button>
            </div>
        </form>
    </div>
</div>

<?php if ($selectedRoom && empty($equipment)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle me-2"></i>ห้องนี้ไม่มีครุภัณฑ์ที่ต้องตรวจนับ
    </div>
<?php elseif ($selectedRoom && !empty($equipment)): ?>
    <form method="POST" action="<?= SITE_URL ?>/equipment/inspection" id="inspectionForm">
        <?= csrf_field() ?>
        <input type="hidden" name="room_id" value="<?= $selectedRoom ?>">

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-list-check me-1"></i>ครุภัณฑ์ในห้องนี้ <?= count($equipment) ?> รายการ</span>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="checkAll">
                    <label class="form-check-label small" for="checkAll">เลือกทั้งหมด</label>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width:50px;" class="text-center">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="checkAllHidden">
                                </div>
                            </th>
                            <th style="width:60px;">#</th>
                            <th>รหัส</th>
                            <th>ชื่อครุภัณฑ์</th>
                            <th>ยี่ห้อ / รุ่น</th>
                            <th>สถานะปัจจุบัน</th>
                            <th style="width:160px;" class="text-center">สถานะใหม่ <span class="text-danger">*</span></th>
                            <th>หมายเหตุ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($equipment as $i => $eq): ?>
                            <tr>
                                <td class="text-center">
                                    <div class="form-check">
                                        <input class="form-check-input eq-check" type="checkbox"
                                            name="items[<?= $eq['id'] ?>][checked]" value="1">
                                    </div>
                                </td>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= sanitize($eq['code']) ?></td>
                                <td><?= sanitize($eq['item_name']) ?></td>
                                <td>
                                    <?= sanitize($eq['brand'] ?? '-') ?>
                                    <?= !empty($eq['model']) ? '/ ' . sanitize($eq['model']) : '' ?>
                                </td>
                                <td><span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>"><?= translateEquipmentStatus($eq['status']) ?></span></td>
                                <td>
                                    <select class="form-select form-select-sm" name="items[<?= $eq['id'] ?>][status]">
                                        <option value="">-- เลือก --</option>
                                        <option value="available" <?= $eq['status'] === 'available' ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                                        <option value="repair">ส่งซ่อม</option>
                                        <option value="broken">ซ่อมไม่ได้</option>
                                        <option value="pending_disposal">รอจำหน่ายออก</option>
                                    </select>
                                </td>
                                <td>
                                    <input type="text" class="form-control form-control-sm"
                                        name="items[<?= $eq['id'] ?>][remark]"
                                        placeholder="หมายเหตุ (ถ้ามี)"
                                        value="<?= sanitize($eq['remark'] ?? '') ?>">
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="bi bi-info-circle me-1"></i>เลือกครุภัณฑ์ที่ต้องการบันทึกผล แล้วกดบันทึก
                </small>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="bi bi-check-lg me-1"></i>บันทึกผลตรวจนับ
                </button>
            </div>
        </div>
    </form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    const eqChecks = document.querySelectorAll('.eq-check');

    if (checkAll) {
        checkAll.addEventListener('change', function() {
            eqChecks.forEach(cb => { cb.checked = checkAll.checked; });
        });
    }

    const form = document.getElementById('inspectionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const checked = form.querySelectorAll('.eq-check:checked');
            if (checked.length === 0) {
                e.preventDefault();
                alert('กรุณาเลือกครุภัณฑ์อย่างน้อย 1 รายการ');
                return;
            }
            let valid = true;
            checked.forEach(cb => {
                const row = cb.closest('tr');
                const statusSelect = row.querySelector('select[name*="[status]"]');
                if (!statusSelect.value) {
                    valid = false;
                }
            });
            if (!valid) {
                e.preventDefault();
                alert('กรุณาเลือกสถานะใหม่สำหรับครุภัณฑ์ทุกที่เลือก');
            }
        });
    }
});
</script>
