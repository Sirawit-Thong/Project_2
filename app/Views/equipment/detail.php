<?php
$pageTitle = $pageTitle ?? 'รายละเอียดครุภัณฑ์';
?>

<div class="page-header mb-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center">
        <div>
            <h4 class="mb-1"><i class="bi bi-pc-display-horizontal me-2"></i><?= $pageTitle ?></h4>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0 small">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/equipment">รายการครุภัณฑ์</a></li>
                    <li class="breadcrumb-item active"><?= sanitize($equipment['code']) ?></li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= SITE_URL ?>/equipment/edit/<?= $equipment['id'] ?>" class="btn btn-warning btn-sm">
                <i class="bi bi-pencil me-1"></i>แก้ไข
            </a>
            <a href="<?= SITE_URL ?>/equipment" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left me-1"></i>กลับ
            </a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-info-circle me-1"></i>ข้อมูลครุภัณฑ์</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th style="width:130px;" class="text-muted">รหัสครุภัณฑ์</th>
                                <td class="fw-bold"><?= sanitize($equipment['code']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ชื่อครุภัณฑ์</th>
                                <td><?= sanitize($equipment['item_name']) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ยี่ห้อ / รุ่น</th>
                                <td><?= sanitize($equipment['brand'] ?? '-') ?> <?= !empty($equipment['model']) ? '/ ' . sanitize($equipment['model']) : '' ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">สถานะ</th>
                                <td><span class="badge bg-<?= getStatusBadgeClass($equipment['status']) ?> fs-6"><?= translateEquipmentStatus($equipment['status']) ?></span></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <table class="table table-borderless mb-0">
                            <tr>
                                <th style="width:130px;" class="text-muted">สาขาวิชา</th>
                                <td><?= sanitize($equipment['dept_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ชุดครุภัณฑ์</th>
                                <td><?= sanitize($equipment['set_name'] ?? '-') ?> <?= !empty($equipment['year']) ? '(' . sanitize($equipment['year']) . ')' : '' ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ห้อง / สถานที่</th>
                                <td><i class="bi bi-door-open me-1"></i><?= sanitize($equipment['room_name'] ?? '-') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">ผู้ถือครอง</th>
                                <td><?= !empty($equipment['holder_firstname']) ? sanitize($equipment['holder_firstname'] . ' ' . $equipment['holder_lastname']) : '-' ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-cash-coin me-1"></i>วันที่และมูลค่า</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="text-muted small">วันที่จัดซื้อ</div>
                        <div class="fw-semibold"><?= formatDateThai($equipment['purchase_date']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">วันที่ตรวจล่าสุด</div>
                        <div class="fw-semibold"><?= formatDateThai($equipment['check_date']) ?></div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-muted small">มูลค่า</div>
                        <div class="fw-semibold text-primary">
                            <?php
                            $displayPrice = $equipment['price'] ?? null;
                            if ((!$displayPrice || $displayPrice <= 0) && !empty($equipment['item_price'])) {
                                $displayPrice = $equipment['item_price'];
                            } elseif ((!$displayPrice || $displayPrice <= 0) && !empty($equipment['set_price'])) {
                                $displayPrice = $equipment['set_price'];
                            }
                            ?>
                            <?= ($displayPrice && $displayPrice > 0) ? formatCurrency($displayPrice) : '-' ?>
                        </div>
                    </div>
                </div>
                <?php if (!empty($equipment['eq_price_remark']) || !empty($equipment['item_price_remark']) || !empty($equipment['set_price_remark'])): ?>
                    <hr class="my-2">
                    <div class="text-muted small">
                        <i class="bi bi-info-circle me-1"></i>
                        หมายเหตุมูลค่า: <?= sanitize($equipment['eq_price_remark'] ?? $equipment['item_price_remark'] ?? $equipment['set_price_remark'] ?? '') ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($equipment['remark'])): ?>
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-journal-text me-1"></i>หมายเหตุ</div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(sanitize($equipment['remark'])) ?></p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <?php if (!empty($images)): ?>
            <div class="card mb-4">
                <div class="card-header"><i class="bi bi-images me-1"></i>รูปภาพ (<?= count($images) ?>)</div>
                <div class="card-body">
                    <div class="row g-2">
                        <?php foreach ($images as $img): ?>
                            <div class="col-6">
                                <a href="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>" data-lightbox="equipment" data-title="<?= sanitize($img['type'] ?? 'รูปภาพ') ?>">
                                    <img src="<?= SITE_URL ?>/uploads/<?= $img['path'] ?>"
                                        alt="<?= sanitize($img['type'] ?? 'รูปภาพ') ?>"
                                        class="img-fluid rounded border" style="width:100%; height:120px; object-fit:cover;">
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($repairHistory)): ?>
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span><i class="bi bi-tools me-1"></i>ประวัติการซ่อม (<?= count($repairHistory) ?>)</span>
                </div>
                <div class="list-group list-group-flush">
                    <?php foreach ($repairHistory as $rh): ?>
                        <a href="<?= SITE_URL ?>/repairs/<?= $rh['id'] ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-semibold small"><?= sanitize($rh['problem'] ?? 'รายการซ่อม #' . $rh['id']) ?></div>
                                    <small class="text-muted">
                                        <i class="bi bi-person me-1"></i><?= sanitize($rh['firstname'] . ' ' . $rh['lastname']) ?>
                                    </small>
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-<?= getStatusBadgeClass($rh['status']) ?>"><?= translateRepairStatus($rh['status']) ?></span>
                                    <div><small class="text-muted"><?= formatDateTimeThai($rh['created_at']) ?></small></div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/lightbox2/dist/css/lightbox.min.css">
<script src="https://cdn.jsdelivr.net/npm/lightbox2/dist/js/lightbox.min.js"></script>
<script>
lightbox.option {
    resizeDuration: 200,
    wrapAround: true,
    fadeDuration: 200
};
</script>
