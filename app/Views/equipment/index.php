<?php
$pageTitle = $pageTitle ?? 'รายการครุภัณฑ์';
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$room = $_GET['room'] ?? '';
$dept = $_GET['dept'] ?? '';
$baseUrl = SITE_URL . '/equipment?' . http_build_query(array_filter([
    'search' => $search,
    'status' => $status,
    'room' => $room,
    'dept' => $dept,
]));
$baseUrl = str_replace('search=', 'search=', $baseUrl);
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center mb-4">
    <div>
        <h4 class="mb-1"><i class="bi bi-pc-display me-2"></i><?= $pageTitle ?></h4>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0 small">
                <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                <li class="breadcrumb-item active"><?= $pageTitle ?></li>
            </ol>
        </nav>
    </div>
    <a href="<?= SITE_URL ?>/equipment/add" class="btn btn-primary">
        <i class="bi bi-plus-circle me-1"></i>เพิ่มครุภัณฑ์
    </a>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" action="<?= SITE_URL ?>/equipment" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small"><i class="bi bi-search me-1"></i>ค้นหา</label>
                <input type="text" class="form-control" name="search" placeholder="รหัส / ชื่อ / ยี่ห้อ" value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-building me-1"></i>สาขาวิชา</label>
                <select class="form-select" name="dept">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= $dept == $d['id'] ? 'selected' : '' ?>><?= sanitize($d['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-info-circle me-1"></i>สถานะ</label>
                <select class="form-select" name="status">
                    <option value="">ทั้งหมด</option>
                    <option value="available" <?= $status === 'available' ? 'selected' : '' ?>>พร้อมใช้งาน</option>
                    <option value="repair" <?= $status === 'repair' ? 'selected' : '' ?>>ส่งซ่อม</option>
                    <option value="broken" <?= $status === 'broken' ? 'selected' : '' ?>>ซ่อมไม่ได้</option>
                    <option value="pending_disposal" <?= $status === 'pending_disposal' ? 'selected' : '' ?>>รอจำหน่าย</option>
                    <option value="disposed" <?= $status === 'disposed' ? 'selected' : '' ?>>จำหน่ายแล้ว</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small"><i class="bi bi-door-open me-1"></i>ห้อง</label>
                <select class="form-select" name="room">
                    <option value="">ทั้งหมด</option>
                    <?php foreach ($rooms as $r): ?>
                        <option value="<?= $r['id'] ?>" <?= $room == $r['id'] ? 'selected' : '' ?>><?= sanitize($r['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary"><i class="bi bi-search me-1"></i>ค้นหา</button>
                <a href="<?= SITE_URL ?>/equipment" class="btn btn-outline-secondary"><i class="bi bi-arrow-counterclockwise me-1"></i>รีเซ็ต</a>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ul me-1"></i>ผลลัพธ์ <?= number_format($result['total']) ?> รายการ</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:50px;">#</th>
                    <th>รหัส</th>
                    <th>ชื่อครุภัณฑ์</th>
                    <th>ยี่ห้อ</th>
                    <th>ห้อง</th>
                    <th>ผู้ถือครอง</th>
                    <th class="text-end">มูลค่า</th>
                    <th class="text-center">สถานะ</th>
                    <th class="text-center" style="width:100px;">จัดการ</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($result['equipment'])): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>ไม่พบข้อมูลครุภัณฑ์
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($result['equipment'] as $i => $eq): ?>
                        <tr>
                            <td class="text-muted"><?= ($result['pagination']['current_page'] - 1) * $result['pagination']['per_page'] + $i + 1 ?></td>
                            <td><a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="fw-semibold text-decoration-none"><?= sanitize($eq['code']) ?></a></td>
                            <td><?= sanitize($eq['item_name']) ?></td>
                            <td><?= sanitize($eq['brand'] ?? '-') ?></td>
                            <td><i class="bi bi-door-open me-1 text-muted"></i><?= sanitize($eq['room_name'] ?? '-') ?></td>
                            <td><?= $eq['holder_firstname'] ? sanitize($eq['holder_firstname'] . ' ' . $eq['holder_lastname']) : '<span class="text-muted">-</span>' ?></td>
                            <td class="text-end">
                                <?php
                                $displayPrice = $eq['price'] ?? null;
                                if (!$displayPrice || $displayPrice <= 0) {
                                    $displayPrice = $eq['item_price'] ?? ($eq['set_price'] ?? null);
                                }
                                ?>
                                <?= $displayPrice && $displayPrice > 0 ? formatCurrency($displayPrice) : '<span class="text-muted">-</span>' ?>
                            </td>
                            <td class="text-center">
                                <span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>"><?= translateEquipmentStatus($eq['status']) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-outline-primary" title="ดูรายละเอียด"><i class="bi bi-eye"></i></a>
                                    <a href="<?= SITE_URL ?>/equipment/edit/<?= $eq['id'] ?>" class="btn btn-outline-warning" title="แก้ไข"><i class="bi bi-pencil"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        <?= paginationLinks($result['pagination'], $baseUrl) ?>
    </div>
</div>
