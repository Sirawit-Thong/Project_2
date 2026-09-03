<div class="page-header">
    <h1><i class="bi bi-database me-2"></i>สำรองฐานข้อมูลระบบ</h1>
</div>

<?php
$tableLabels = [
    'users'                 => 'ผู้ใช้งาน',
    'dept'                  => 'สาขา',
    'asset_categories'      => 'หมวดหมู่ครุภัณฑ์',
    'depreciation_settings' => 'เกณฑ์ค่าเสื่อมราคา',
    'sets'                  => 'ชุดครุภัณฑ์',
    'items'                 => 'รายการครุภัณฑ์',
    'rooms'                 => 'ห้อง',
    'room_managers'         => 'ผู้รับผิดชอบห้อง',
    'equipment'             => 'ครุภัณฑ์',
    'equipment_img'         => 'รูปครุภัณฑ์',
    'repair'                => 'รายการแจ้งซ่อม',
    'repair_img'            => 'รูปงานซ่อม',
    'satisfaction_surveys'  => 'แบบประเมินความพึงพอใจ',
    'system_logs'           => 'บันทึกระบบ',
];
$tableInfo = $tableInfo ?? [];
$detailedInfo = $detailedInfo ?? [];
// หาเวลาล่าสุดรวม
$overallLatest = null;
foreach ($detailedInfo as $info) {
    if (!empty($info['latest']) && ($overallLatest === null || $info['latest'] > $overallLatest)) $overallLatest = $info['latest'];
}
if ($overallLatest === null && !empty($detailedInfo)) {
    // fallback จาก system_logs
    try { $overallLatest = $detailedInfo['system_logs']['latest'] ?? null; } catch(Throwable $e){}
}
?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="card shadow-sm h-100" style="border-radius:1rem;">
            <div class="card-header bg-white" style="border-radius:1rem 1rem 0 0;"><i class="bi bi-cloud-download me-2"></i>ดาวน์โหลด Backup</div>
            <div class="card-body text-center py-4 d-flex flex-column">
                <i class="bi bi-database fs-1 text-primary mb-3 d-block" aria-hidden="true"></i>
                <p class="mb-1">ดาวน์โหลดไฟล์ SQL สำหรับสำรองฐานข้อมูลระบบทั้งหมด</p>
                <p class="small text-muted mb-3">14 ตาราง • ทุกคอลัมน์ • ทุกรายการ (<?= number_format(array_sum(array_map('intval', (array)$tableInfo))) ?> รายการ)</p>
                <form method="POST" action="<?= SITE_URL ?>/backup" class="mt-auto">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="download">
                    <button type="submit" class="btn btn-primary btn-lg" style="background:#3b5bff; border-color:#3b5bff; border-radius:.6rem; padding:.6rem 1.5rem;">
                        <i class="bi bi-download me-1"></i> ดาวน์โหลด Backup
                    </button>
                </form>
                <small class="text-muted d-block mt-2">ไฟล์ .sql นำไปใส่ถังใหม่ได้ทันที</small>
                <?php if ($overallLatest): ?>
                <div class="alert alert-light border small mt-3 mb-0 text-start">
                    <i class="bi bi-clock-history me-1"></i>ข้อมูลล่าสุด: <strong><?= htmlspecialchars($overallLatest, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="text-muted">(<?= htmlspecialchars(formatDateTimeThai($overallLatest), ENT_QUOTES, 'UTF-8') ?>)</span>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer small text-muted" style="border-radius:0 0 1rem 1rem;">
                <i class="bi bi-info-circle me-1"></i>สำรองครบทุกตาราง ทุกคอลัมน์ ทุกรายการ
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm" style="border-radius:1rem;">
            <div class="card-header bg-white d-flex justify-content-between align-items-center" style="border-radius:1rem 1rem 0 0;">
                <span><i class="bi bi-table me-2"></i>สถิติข้อมูลในระบบ</span>
                <span class="badge bg-light text-dark border"><?= count($tableLabels) ?> ตาราง</span>
            </div>
            <ul class="list-group list-group-flush" style="max-height: 520px; overflow-y:auto;">
                <?php foreach ($tableLabels as $table => $label):
                    $info = $detailedInfo[$table] ?? ['count'=>$tableInfo[$table] ?? 0, 'columns'=>[], 'latest'=>null];
                    $count = $info['count'] ?? 0;
                    $cols = $info['columns'] ?? [];
                    $latest = $info['latest'] ?? null;
                ?>
                <li class="list-group-item">
                    <div class="d-flex justify-content-between align-items-center">
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> <small class="text-muted">(<?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?>)</small></span>
                        <span class="badge bg-primary rounded-pill" style="background:#3b5bff !important;"><?= number_format($count) ?></span>
                    </div>
                    <?php if (!empty($cols)): ?>
                    <div class="small text-muted mt-1" style="line-height:1.3;">
                        <i class="bi bi-columns me-1"></i><?= count($cols) ?> คอลัมน์: <?= htmlspecialchars(implode(', ', $cols), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                    <?php endif; ?>
                    <div class="small <?= $latest ? 'text-success' : 'text-muted' ?> mt-1">
                        <i class="bi bi-clock me-1"></i>ข้อมูลล่าสุด: <?= $latest ? htmlspecialchars($latest, ENT_QUOTES, 'UTF-8') . ' (' . htmlspecialchars(formatDateTimeThai($latest), ENT_QUOTES, 'UTF-8') . ')' : '— ยังไม่มีข้อมูล —' ?>
                    </div>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="card-footer small text-muted d-flex justify-content-between" style="border-radius:0 0 1rem 1rem;">
                <span>รวม <?= number_format(array_sum(array_map('intval', (array)$tableInfo))) ?> รายการ</span>
                <span><i class="bi bi-database me-1"></i><?= htmlspecialchars(defined('DB_NAME')?DB_NAME:'equipment_db', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
</div>
