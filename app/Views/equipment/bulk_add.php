<?php
$pageTitle = $pageTitle ?? 'เพิ่มครุภัณฑ์จำนวนมาก';
?>

<div class="page-header mb-4">
    <h4 class="mb-1"><i class="bi bi-plus-square me-2"></i><?= $pageTitle ?></h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment">รายการครุภัณฑ์</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<form method="POST" action="<?= SITE_URL ?>/equipment/bulk-add" id="bulkAddForm">
    <?= csrf_field() ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-tags me-1"></i>เลือกประเภทครุภัณฑ์</div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">สาขาวิชา <span class="text-danger">*</span></label>
                            <select class="form-select" id="deptSelect" required>
                                <option value="">-- เลือกสาขาวิชา --</option>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= sanitize($d['name']) ?></option>
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
                    <div id="qtyInfo" class="text-muted small mt-2 d-none">
                        <i class="bi bi-info-circle me-1"></i>
                        <span id="qtyInfoText"></span>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-list-ol me-1"></i>รหัสครุภัณฑ์ที่ต้องการเพิ่ม</span>
                    <button type="button" class="btn btn-sm btn-success" id="addRowBtn">
                        <i class="bi bi-plus-lg me-1"></i>เพิ่มแถว
                    </button>
                </div>
                <div class="card-body">
                    <div id="codeRows">
                        <div class="input-group mb-2 code-row">
                            <span class="input-group-text bg-light code-num">1</span>
                            <input type="text" class="form-control" name="codes[]" placeholder="เช่น CP-001" required>
                            <button type="button" class="btn btn-outline-danger remove-row-btn" title="ลบ"><i class="bi bi-x-lg"></i></button>
                        </div>
                    </div>
                    <div class="text-muted small mt-2" id="rowCount">จำนวน 1 รายการ</div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-gear me-1"></i>ตั้งค่าทั่วไป</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">ห้อง / สถานที่</label>
                        <select class="form-select" name="room_id">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($rooms as $r): ?>
                                <option value="<?= $r['id'] ?>"><?= sanitize($r['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ผู้ถือครอง</label>
                        <select class="form-select" name="holder_id">
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($holders as $h): ?>
                                <option value="<?= $h['id'] ?>"><?= sanitize($h['firstname'] . ' ' . $h['lastname']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">สถานะ</label>
                        <select class="form-select" name="status">
                            <option value="available">พร้อมใช้งาน</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">หมายเหตุ</label>
                        <input type="text" class="form-control" name="remark" placeholder="หมายเหตุทั่วไป">
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-clipboard me-1"></i>เติมรหัสด่วน</div>
                <div class="card-body">
                    <p class="text-muted small mb-2">วางรหัสครุภัณฑ์แยกบรรทัด (จะเพิ่มทีละบรรทัดโดยอัตโนมัติ)</p>
                    <textarea class="form-control mb-2" id="quickFill" rows="5" placeholder="CP-001&#10;CP-002&#10;CP-003"></textarea>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="quickFillBtn">
                        <i class="bi bi-clipboard-plus me-1"></i>เติมหากรหัส
                    </button>
                </div>
            </div>

            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                    <i class="bi bi-plus-circle me-1"></i>เพิ่มครุภัณฑ์ทั้งหมด
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const allItems = <?= json_encode($allItems, JSON_UNESCAPED_UNICODE) ?>;
    const allSets = <?= json_encode($allSets, JSON_UNESCAPED_UNICODE) ?>;

    const deptSelect = document.getElementById('deptSelect');
    const setSelect = document.getElementById('setSelect');
    const itemSelect = document.getElementById('itemSelect');
    const codeRows = document.getElementById('codeRows');
    const addRowBtn = document.getElementById('addRowBtn');
    const quickFillBtn = document.getElementById('quickFillBtn');
    const quickFill = document.getElementById('quickFill');
    const qtyInfo = document.getElementById('qtyInfo');
    const qtyInfoText = document.getElementById('qtyInfoText');
    const rowCountEl = document.getElementById('rowCount');

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
        qtyInfo.classList.add('d-none');

        if (!deptId) { setSelect.disabled = true; return; }
        getSetsForDept(deptId).forEach(s => {
            setSelect.appendChild(new Option(s.name + (s.year ? ' (' + s.year + ')' : ''), s.id));
        });
        setSelect.disabled = false;
    });

    setSelect.addEventListener('change', function() {
        const setId = this.value;
        itemSelect.innerHTML = '<option value="">-- เลือกรายการ --</option>';
        qtyInfo.classList.add('d-none');

        if (!setId) { itemSelect.disabled = true; return; }
        getItemsForSet(setId).forEach(i => {
            itemSelect.appendChild(new Option(i.name + (i.brand ? ' - ' + i.brand : ''), i.id));
        });
        itemSelect.disabled = false;
    });

    itemSelect.addEventListener('change', function() {
        const item = allItems.find(i => String(i.id) === String(this.value));
        if (item) {
            qtyInfo.classList.remove('d-none');
            qtyInfoText.textContent = item.name + ' — กำหนด ' + item.qty + ' ชิ้น, มีแล้ว ' + item.existing_count + ' ชิ้น';
        } else {
            qtyInfo.classList.add('d-none');
        }
    });

    let rowIdx = 1;

    function addRow(value) {
        rowIdx++;
        const div = document.createElement('div');
        div.className = 'input-group mb-2 code-row';
        div.innerHTML = '<span class="input-group-text bg-light code-num">' + rowIdx + '</span>' +
            '<input type="text" class="form-control" name="codes[]" placeholder="เช่น CP-' + String(rowIdx).padStart(3, '0') + '"' + (value ? ' value="' + value.replace(/"/g, '&quot;') + '"' : '') + ' required>' +
            '<button type="button" class="btn btn-outline-danger remove-row-btn" title="ลบ"><i class="bi bi-x-lg"></i></button>';
        codeRows.appendChild(div);
        renumber();
    }

    function renumber() {
        const rows = codeRows.querySelectorAll('.code-row');
        rows.forEach((r, i) => {
            r.querySelector('.code-num').textContent = i + 1;
        });
        rowCountEl.textContent = 'จำนวน ' + rows.length + ' รายการ';
    }

    addRowBtn.addEventListener('click', function() { addRow(); });

    codeRows.addEventListener('click', function(e) {
        const btn = e.target.closest('.remove-row-btn');
        if (!btn) return;
        const rows = codeRows.querySelectorAll('.code-row');
        if (rows.length <= 1) return;
        btn.closest('.code-row').remove();
        renumber();
    });

    quickFillBtn.addEventListener('click', function() {
        const lines = quickFill.value.split('\n')
            .map(l => l.trim())
            .filter(l => l.length > 0);
        if (lines.length === 0) return;
        lines.forEach(code => addRow(code));
        quickFill.value = '';
    });
});
</script>
