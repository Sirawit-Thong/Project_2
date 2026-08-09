<?php
$pageTitle = $pageTitle ?? 'จำหน่ายครุภัณฑ์';

$activeTab = $_GET['tab'] ?? 'broken';
$brokenBaseUrl = SITE_URL . '/equipment/disposal?tab=broken';
$disposedBaseUrl = SITE_URL . '/equipment/disposal?tab=disposed';
?>

<div class="page-header mb-4">
    <h4 class="mb-1"><i class="bi bi-trash3 me-2"></i><?= $pageTitle ?></h4>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0 small">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<?php
$totalTabs = count($pendingDisposal) + $broken['total'] + $disposed['total'];
?>

<ul class="nav nav-tabs mb-4" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'pending_disposal' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=pending_disposal">
            <i class="bi bi-hourglass-split me-1"></i>รอจำหน่ายออก
            <?php if (!empty($pendingDisposal)): ?>
                <span class="badge bg-warning text-dark ms-1"><?= count($pendingDisposal) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'broken' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=broken">
            <i class="bi bi-exclamation-triangle me-1"></i>ซ่อมไม่ได้
            <?php if ($broken['total'] > 0): ?>
                <span class="badge bg-danger ms-1"><?= number_format($broken['total']) ?></span>
            <?php endif; ?>
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $activeTab === 'disposed' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=disposed">
            <i class="bi bi-check-circle me-1"></i>จำหน่ายแล้ว
            <?php if ($disposed['total'] > 0): ?>
                <span class="badge bg-secondary ms-1"><?= number_format($disposed['total']) ?></span>
            <?php endif; ?>
        </a>
    </li>
</ul>

<?php if ($activeTab === 'pending_disposal'): ?>
    <?php if (empty($pendingDisposal)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>ไม่มีครุภัณฑ์ที่รอจำหน่ายออกในขณะนี้
        </div>
    <?php else: ?>
        <div class="card">
            <div class="card-header">
                <i class="bi bi-hourglass-split me-1"></i>ครุภัณฑ์ที่รอจำหน่ายออก <?= count($pendingDisposal) ?> รายการ
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
                            <th class="text-center">สถานะ</th>
                            <th class="text-center" style="width:180px;">จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingDisposal as $i => $eq): ?>
                            <tr>
                                <td class="text-muted"><?= $i + 1 ?></td>
                                <td><a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="fw-semibold text-decoration-none"><?= sanitize($eq['code']) ?></a></td>
                                <td><?= sanitize($eq['item_name']) ?></td>
                                <td><?= sanitize($eq['brand'] ?? '-') ?></td>
                                <td><i class="bi bi-door-open me-1 text-muted"></i><?= sanitize($eq['room_name'] ?? '-') ?></td>
                                <td class="text-center"><span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>"><?= translateEquipmentStatus($eq['status']) ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" action="<?= SITE_URL ?>/equipment/disposal" class="d-inline" onsubmit="return confirm('ยืนยันจำหน่ายครุภัณฑ์นี้ออกจากระบบ?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="dispose">
                                            <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" title="จำหน่ายออก">
                                                <i class="bi bi-trash3"></i> จำหน่าย
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= SITE_URL ?>/equipment/disposal" class="d-inline" onsubmit="return confirm('ยืนยันกู้คืนครุภัณฑ์นี้กลับเป็นพร้อมใช้งาน?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="กู้คืน">
                                                <i class="bi bi-arrow-counterclockwise"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

<?php elseif ($activeTab === 'broken'): ?>
    <div class="card">
        <div class="card-header">
            <i class="bi bi-exclamation-triangle me-1"></i>ครุภัณฑ์ที่ซ่อมไม่ได้ <?= number_format($broken['total']) ?> รายการ
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
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($broken['equipment'])): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>ไม่มีครุภัณฑ์ในสถานะนี้
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($broken['equipment'] as $i => $eq): ?>
                            <tr>
                                <td class="text-muted"><?= ($broken['pagination']['current_page'] - 1) * $broken['pagination']['per_page'] + $i + 1 ?></td>
                                <td><a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="fw-semibold text-decoration-none"><?= sanitize($eq['code']) ?></a></td>
                                <td><?= sanitize($eq['item_name']) ?></td>
                                <td><?= sanitize($eq['brand'] ?? '-') ?></td>
                                <td><i class="bi bi-door-open me-1 text-muted"></i><?= sanitize($eq['room_name'] ?? '-') ?></td>
                                <td class="text-center"><span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>"><?= translateEquipmentStatus($eq['status']) ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" action="<?= SITE_URL ?>/equipment/disposal" class="d-inline" onsubmit="return confirm('เสนอจำหน่ายครุภัณฑ์นี้?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="propose">
                                            <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-warning" title="เสนอจำหน่าย">
                                                <i class="bi bi-trash3"></i> เสนอจำหน่าย
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <?= paginationLinks($broken['pagination'], $brokenBaseUrl) ?>
        </div>
    </div>

<?php elseif ($activeTab === 'disposed'): ?>
    <div class="card">
        <div class="card-header">
            <i class="bi bi-check-circle me-1"></i>ครุภัณฑ์ที่จำหน่ายแล้ว <?= number_format($disposed['total']) ?> รายการ
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
                        <th class="text-center">สถานะ</th>
                        <th class="text-center" style="width:140px;">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($disposed['equipment'])): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>ไม่มีครุภัณฑ์ในสถานะนี้
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($disposed['equipment'] as $i => $eq): ?>
                            <tr>
                                <td class="text-muted"><?= ($disposed['pagination']['current_page'] - 1) * $disposed['pagination']['per_page'] + $i + 1 ?></td>
                                <td><a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="fw-semibold text-decoration-none"><?= sanitize($eq['code']) ?></a></td>
                                <td><?= sanitize($eq['item_name']) ?></td>
                                <td><?= sanitize($eq['brand'] ?? '-') ?></td>
                                <td><i class="bi bi-door-open me-1 text-muted"></i><?= sanitize($eq['room_name'] ?? '-') ?></td>
                                <td class="text-center"><span class="badge bg-<?= getStatusBadgeClass($eq['status']) ?>"><?= translateEquipmentStatus($eq['status']) ?></span></td>
                                <td class="text-center">
                                    <div class="d-flex gap-1 justify-content-center">
                                        <a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="btn btn-sm btn-outline-primary" title="ดูรายละเอียด">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <form method="POST" action="<?= SITE_URL ?>/equipment/disposal" class="d-inline" onsubmit="return confirm('กู้คืนครุภัณฑ์นี้กลับเป็นพร้อมใช้งาน?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="restore">
                                            <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="กู้คืน">
                                                <i class="bi bi-arrow-counterclockwise"></i> กู้คืน
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            <?= paginationLinks($disposed['pagination'], $disposedBaseUrl) ?>
        </div>
    </div>
<?php endif; ?>
