<?php
$search = $_GET['search'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$roomFilter = $_GET['room'] ?? '';
$itemFilter = $_GET['item'] ?? '';
$deptFilter = $_GET['dept'] ?? '';
$setFilter = $_GET['set'] ?? '';

$perPageOptions = [10, 20, 50, 100];
$perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions, true)
    ? (int) $_GET['per_page'] : 20;

$isTeacher = ($currentRole === 'teacher');

$baseUrl = SITE_URL . '/equipment?' . http_build_query(array_filter([
    'search' => $search,
    'status' => $statusFilter,
    'room' => $roomFilter,
    'dept' => $deptFilter,
    'set' => $setFilter,
    'item' => $itemFilter,
    'per_page' => $perPage,
]));
?>

<!-- Page Header -->
<div class="page-header d-flex justify-content-between align-items-center">
    <div>
        <h1><i class="bi bi-pc-display me-2"></i><?= $pageTitle ?></h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active">ครุภัณฑ์</li>
            </ol>
        </nav>
    </div>
    <?php if (!$isTeacher): ?>
        <div class="d-flex gap-2">
            <a href="<?= SITE_URL ?>/equipment/add" class="btn btn-primary">
                <i class="bi bi-plus-lg me-1"></i>ลงทะเบียนครุภัณฑ์ใหม่
            </a>
            <a href="<?= SITE_URL ?>/equipment/bulk-add" class="btn btn-success">
                <i class="bi bi-plus-square me-1"></i>ลงทะเบียนหลายรายการ
            </a>
        </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3" id="filterForm" action="<?= SITE_URL ?>/equipment">
            <!-- Row 1 -->
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="search" placeholder="ระบุคำค้นหา..."
                        value="<?= sanitize($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="dept" id="filterDept" class="form-select">
                    <option value="">-- ทุกสาขา --</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $deptFilter == $d['id'] ? 'selected' : '' ?>>
                            <?= sanitize($d['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <select name="set" id="filterSet" class="form-select">
                    <option value="">-- ทุกชุดครุภัณฑ์ --</option>
                    <?php foreach ($sets as $s): ?>
                        <option value="<?= $s['id'] ?>" data-dept="<?= $s['dept_id'] ?>" <?= $setFilter == $s['id'] ? 'selected' : '' ?>>
                            <?= sanitize($s['name']) ?> (<?= sanitize($s['year']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Row 2 -->
            <div class="col-md-4">
                <select name="item" id="filterItem" class="form-select">
                    <option value="">-- ทุกรายการครุภัณฑ์ --</option>
                    <?php foreach ($items as $it): ?>
                        <option value="<?= $it['id'] ?>" data-dept="<?= $it['dept_id'] ?>" data-set="<?= $it['set_id'] ?>"
                            <?= $itemFilter == $it['id'] ? 'selected' : '' ?>>
                            <?= sanitize($it['name']) ?>
                            <?= $it['brand'] ? '(' . sanitize($it['brand']) . ')' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select">
                    <option value="">-- ทุกสถานะ --</option>
                    <option value="available" <?= $statusFilter === 'available' ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                    <option value="repair" <?= $statusFilter === 'repair' ? 'selected' : '' ?>>ส่งซ่อม</option>
                    <option value="broken" <?= $statusFilter === 'broken' ? 'selected' : '' ?>>ซ่อมไม่ได้</option>
                    <option value="pending_disposal" <?= $statusFilter === 'pending_disposal' ? 'selected' : '' ?>>รอจำหน่ายออก</option>
                    <option value="disposed" <?= $statusFilter === 'disposed' ? 'selected' : '' ?>>จำหน่ายออก</option>
                </select>
            </div>
            <div class="col-md-2">
                <select name="room" class="form-select">
                    <option value="">-- ทุกห้อง --</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $roomFilter == $r['id'] ? 'selected' : '' ?>>
                            <?= sanitize($r['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <select name="per_page" class="form-select">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <div class="d-flex gap-1">
                    <a href="<?= SITE_URL ?>/equipment" class="btn btn-outline-secondary" title="ล้างตัวกรอง">
                        <i class="bi bi-x-lg"></i>ล้าง
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Equipment Table -->
<div class="card">
    <div class="card-header">
        <i class="bi bi-table me-2"></i>รายการครุภัณฑ์ทั้งหมด (<?= number_format($result['total']) ?> รายการ)
    </div>
    <div class="card-body p-0">
        <?php if (empty($result['equipment'])): ?>
            <div class="empty-state">
                <i class="bi bi-pc-display"></i>
                <h5>ไม่พบข้อมูลครุภัณฑ์ที่ค้นหา</h5>
                <p class="text-muted">ลองเปลี่ยนเงื่อนไขการค้นหา</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>รหัสครุภัณฑ์</th>
                            <th>ชื่อ/ยี่ห้อ/รุ่น</th>
                            <th class="hide-mobile">ห้อง</th>
                            <th class="hide-mobile">ผู้รับผิดชอบดูแล</th>
                            <th class="text-end hide-mobile">ราคา</th>
                            <th>สถานะ</th>
                            <th width="140">ดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result['equipment'] as $eq): ?>
                            <tr>
                                <td>
                                    <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>">
                                        <strong><?= sanitize($eq['code'] ?? 'N/A') ?></strong>
                                    </a>
                                </td>
                                <td>
                                    <?= sanitize($eq['item_name']) ?>
                                    <br><small class="text-muted">
                                        <?= sanitize($eq['brand'] ?? '') ?> <?= sanitize($eq['model'] ?? '') ?>
                                    </small>
                                </td>
                                <td class="hide-mobile"><?= sanitize($eq['room_name'] ?? '-') ?></td>
                                <td class="hide-mobile">
                                    <?php if ($eq['holder_firstname']): ?>
                                        <?= sanitize($eq['holder_firstname'] . ' ' . $eq['holder_lastname']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end hide-mobile">
                                    <?php
                                    $displayRemark = '';
                                    if (!empty($eq['eq_price_remark'])) {
                                        $displayRemark = $eq['eq_price_remark'] . ' (เฉพาะชิ้น)';
                                    } elseif (!empty($eq['item_price_remark'])) {
                                        $displayRemark = $eq['item_price_remark'] . ' (ทั้งรายการ)';
                                    } elseif (!empty($eq['set_price_remark'])) {
                                        $displayRemark = $eq['set_price_remark'] . ' (ทั้งชุด)';
                                    }

                                    if ($eq['price'] > 0 || !$displayRemark):
                                        ?>
                                        <?= number_format($eq['price'], 2) ?>
                                    <?php endif; ?>

                                    <?php if ($displayRemark): ?>
                                        <?= ($eq['price'] > 0 || !$displayRemark) ? '<br>' : '' ?>
                                        <span class="badge bg-info text-dark" title="<?= sanitize($displayRemark) ?>"
                                            data-bs-toggle="tooltip" style="cursor: help;"><i
                                                class="bi bi-info-circle me-1"></i>มีหมายเหตุราคา</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>">
                                        <?= translateEquipmentStatus($eq['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-info"
                                        title="ดูรายละเอียด" aria-label="ดูรายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <?php if (!$isTeacher): ?>
                                        <a href="<?= SITE_URL ?>/equipment/edit/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary"
                                            title="แก้ไข" aria-label="แก้ไข">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="<?= SITE_URL ?>/equipment/delete/<?= $eq['id'] ?>" class="d-inline"
                                            onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบครุภัณฑ์นี้?')">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="ลบ" aria-label="ลบ">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if (($result['pagination']['total_pages'] ?? 1) > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($result['pagination'], $baseUrl) ?>
        </div>
    <?php endif; ?>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });

        // Cascading Filters Logic
        const filterDept = document.getElementById('filterDept');
        const filterSet = document.getElementById('filterSet');
        const filterItem = document.getElementById('filterItem');

        if (filterDept && filterSet && filterItem) {
            const allSetOptions = Array.from(filterSet.options).slice(1);
            const allItemOptions = Array.from(filterItem.options).slice(1);

            function updateSets() {
                const deptId = filterDept.value;
                const currentSet = filterSet.value;
                filterSet.innerHTML = '<option value="">-- ทุกชุดครุภัณฑ์ --</option>';

                let hasValidSet = false;
                allSetOptions.forEach(opt => {
                    if (!deptId || opt.dataset.dept == deptId) {
                        const newOpt = opt.cloneNode(true);
                        if (newOpt.value == currentSet) {
                            newOpt.selected = true;
                            hasValidSet = true;
                        }
                        filterSet.appendChild(newOpt);
                    }
                });

                if (!hasValidSet && currentSet) {
                    filterSet.value = '';
                }
                updateItems();
            }

            function updateItems() {
                const deptId = filterDept.value;
                const setId = filterSet.value;
                const currentItem = filterItem.value;
                filterItem.innerHTML = '<option value="">-- ทุกรายการครุภัณฑ์ --</option>';

                let hasValidItem = false;
                allItemOptions.forEach(opt => {
                    const matchDept = !deptId || opt.dataset.dept == deptId;
                    const matchSet = !setId || opt.dataset.set == setId;
                    if (matchDept && matchSet) {
                        const newOpt = opt.cloneNode(true);
                        if (newOpt.value == currentItem) {
                            newOpt.selected = true;
                            hasValidItem = true;
                        }
                        filterItem.appendChild(newOpt);
                    }
                });

                if (!hasValidItem && currentItem) {
                    filterItem.value = '';
                }
            }

            // Initialize state on load without triggering form submit
            updateSets();

            filterDept.addEventListener('change', function () {
                filterSet.value = '';
                filterItem.value = '';
                updateSets();
                document.getElementById('filterForm').submit();
            });

            filterSet.addEventListener('change', function () {
                filterItem.value = '';
                updateItems();
                document.getElementById('filterForm').submit();
            });

            filterItem.addEventListener('change', function () {
                document.getElementById('filterForm').submit();
            });
        }

        // Auto-submit for other filters
        const otherSelects = document.querySelectorAll('#filterForm select:not(#filterDept):not(#filterSet):not(#filterItem)');
        otherSelects.forEach(select => {
            select.addEventListener('change', function () {
                document.getElementById('filterForm').submit();
            });
        });
    });
</script>
