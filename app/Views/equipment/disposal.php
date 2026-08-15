<?php
/**
 * Equipment Disposal View
 * บริหารจัดการจำหน่ายครุภัณฑ์ออก — ตามแบบเว็บออริจินอล
 *
 * Variables from controller:
 *   $tab, $counts, $items, $pagination, $perPage, $perPageOptions
 */
?><div class="page-header">
    <h1><i class="bi bi-trash3 me-2"></i><?= $pageTitle ?></h1>
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/ ">แดชบอร์ด</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>
</div>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'pending' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=pending">รอจำหน่ายออก
            (<?= number_format($counts['pending']) ?>)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'broken' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=broken">ซ่อมไม่ได้
            (<?= number_format($counts['broken']) ?>)</a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'disposed' ? 'active' : '' ?>"
            href="<?= SITE_URL ?>/equipment/disposal?tab=disposed">จำหน่ายออก
            (<?= number_format($counts['disposed']) ?>)</a>
    </li>
</ul>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body py-2">
        <form method="GET" class="row g-2 align-items-center">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
            <div class="col-auto">
                <label class="form-label mb-0 me-2">แสดง:</label>
            </div>
            <div class="col-auto">
                <select name="per_page" class="form-select form-select-sm" style="min-width: 120px;"
                    onchange="this.form.submit()">
                    <?php foreach ($perPageOptions as $opt): ?>
                        <option value="<?= $opt ?>" <?= $perPage === $opt ? 'selected' : '' ?>><?= $opt ?> รายการ</option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">
        <?php if (empty($items)): ?>
            <div class="empty-state py-4">
                <i class="bi bi-inbox fs-1 text-muted"></i>
                <p class="mt-3">ไม่มีรายการ</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>รหัส</th>
                            <th>ชื่อ</th>
                            <?php if ($tab !== 'disposed'): ?>
                                <th>ห้อง</th>
                            <?php else: ?>
                                <th>วันที่จำหน่ายออก</th>
                            <?php endif; ?>
                            <?php if ($tab !== 'disposed'): ?>
                                <th width="200">ดำเนินการ</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $eq): ?>
                            <tr>
                                <td><a href="<?= SITE_URL ?>/equipment/<?= $eq['id'] ?>" class="fw-semibold text-decoration-none"><?= sanitize($eq['code']) ?></a></td>
                                <td><?= sanitize($eq['item_name']) ?></td>

                                <?php if ($tab !== 'disposed'): ?>
                                    <td><i class="bi bi-door-open me-1 text-muted"></i><?= sanitize($eq['room_name'] ?? '-') ?></td>

                                    <td>
                                        <?php if ($tab === 'pending'): ?>
                                            <form method="POST" action="<?= SITE_URL ?>/equipment/disposal"
                                                class="d-inline" onsubmit="return confirm('ยืนยันจำหน่าย?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="dispose">
                                                <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                                <input type="hidden" name="tab" value="pending">
                                                <button type="submit" class="btn btn-sm btn-danger">จำหน่ายออก</button>
                                            </form>
                                            <form method="POST" action="<?= SITE_URL ?>/equipment/disposal"
                                                class="d-inline" onsubmit="return confirm('ยกเลิกการเสนอจำหน่าย?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                                <input type="hidden" name="tab" value="pending">
                                                <button type="submit" class="btn btn-sm btn-outline-secondary">ยกเลิก</button>
                                            </form>
                                        <?php elseif ($tab === 'broken'): ?>
                                            <form method="POST" action="<?= SITE_URL ?>/equipment/disposal"
                                                class="d-inline" onsubmit="return confirm('เสนอเรื่องจำหน่ายออก?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="action" value="propose">
                                                <input type="hidden" name="equipment_id" value="<?= $eq['id'] ?>">
                                                <input type="hidden" name="tab" value="broken">
                                                <button type="submit" class="btn btn-sm btn-warning">เสนอเรื่องจำหน่ายออก</button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td><?= formatDateThai($eq['updated_at']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <?= paginationLinks($pagination, SITE_URL . '/equipment/disposal?tab=' . $tab . '&per_page=' . $perPage) ?>
        </div>
    <?php endif; ?>
</div>