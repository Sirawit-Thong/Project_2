<div class="page-header">
    <h1><i class="bi bi-database-down me-2"></i>สำรองฐานข้อมูลระบบ</h1>
    <p class="text-muted mb-0">สำรองและกู้คืนข้อมูลทั้ง 14 ตาราง — ปลอดภัย ครบถ้วน พร้อมใช้งาน</p>
</div>

<?php
// ตารางทั้งหมดเรียงตามลำดับ FK (ตรงกับ database.sql)
$tableLabels = [
    'users'                  => 'ผู้ใช้งาน',
    'dept'                   => 'สาขา',
    'asset_categories'       => 'หมวดหมู่ครุภัณฑ์',
    'depreciation_settings'  => 'เกณฑ์ค่าเสื่อมราคา',
    'sets'                   => 'ชุดครุภัณฑ์',
    'items'                  => 'รายการครุภัณฑ์',
    'rooms'                  => 'ห้อง',
    'room_managers'          => 'ผู้รับผิดชอบห้อง',
    'equipment'              => 'ครุภัณฑ์',
    'equipment_img'          => 'รูปครุภัณฑ์',
    'repair'                 => 'รายการแจ้งซ่อม',
    'repair_img'             => 'รูปงานซ่อม',
    'satisfaction_surveys'   => 'แบบประเมินความพึงพอใจ',
    'system_logs'            => 'บันทึกระบบ',
];
$tableInfo = $tableInfo ?? [];
$lastBackupAt = $lastBackupAt ?? ($lastBackup ?? null);
if (empty($lastBackupAt)) {
    try {
        $pdoTmp = getDB();
        $stmt = $pdoTmp->query("SELECT created_at FROM system_logs WHERE action='Backup' ORDER BY created_at DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($row['created_at'])) $lastBackupAt = $row['created_at'];
    } catch (Throwable $e) { $lastBackupAt = null; }
}
$dbSizeText = null;
$dbSize = $dbSize ?? null;
if ($dbSize !== null) {
    $dbSizeText = $dbSize;
} else {
    try {
        $pdoTmp2 = isset($pdoTmp) ? $pdoTmp : getDB();
        $dbName = defined('DB_NAME') ? DB_NAME : 'equipment_db';
        $stmt2 = $pdoTmp2->prepare("SELECT SUM(DATA_LENGTH + INDEX_LENGTH) AS sz FROM information_schema.TABLES WHERE TABLE_SCHEMA = ?");
        $stmt2->execute([$dbName]);
        $sz = $stmt2->fetchColumn();
        if ($sz !== false && $sz !== null) {
            if ($sz >= 1048576) $dbSizeText = number_format($sz / 1048576, 2) . ' MB';
            elseif ($sz >= 1024) $dbSizeText = number_format($sz / 1024, 2) . ' KB';
            else $dbSizeText = number_format((int)$sz) . ' bytes';
        }
    } catch (Throwable $e) { $dbSizeText = null; }
    if ($dbSizeText === null) {
        $totalRows = array_sum(array_map('intval', (array)$tableInfo));
        $dbSizeText = number_format($totalRows) . ' รายการ (ประมาณ)';
    }
}
$totalRows = array_sum(array_map('intval', (array)$tableInfo));
?>

<style>
/* Modern backup layout — grid + container queries, no extra deps */
.backup-hero{
  display:flex; flex-wrap:wrap; gap:1rem; align-items:center; justify-content:space-between;
  background: linear-gradient(135deg, #0d6efd 0%, #084298 100%);
  color:#fff; border-radius:1rem; padding:1.25rem 1.5rem; margin-bottom:1.25rem;
}
.backup-hero h2{ margin:0; font-size:clamp(1.1rem,2.5cqi,1.5rem); }
.backup-hero .meta{ display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
.backup-hero .badge{ background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.25); backdrop-filter: blur(6px); }
.backup-layout{
  display:grid;
  grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
  gap:1.25rem;
  align-items:start;
}
.backup-layout .span-full{ grid-column:1 / -1; }
.backup-card{ container: backup-card / inline-size; }
@container backup-card (min-width: 420px){
  .backup-actions{ display:grid; grid-template-columns: 1fr 1fr; gap:.75rem; }
}
.stat-grid{
  display:grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap:.5rem;
}
.stat-item{
  display:grid; grid-template-rows: auto 1fr; /* subgrid fallback */
  gap:.25rem; padding:.6rem .75rem; border:1px solid #e9ecef; border-radius:.6rem; background:#fff;
}
.stat-item .label{ font-size:.8rem; color:#6c757d; }
.stat-item .value{ font-weight:700; font-size:1.05rem; }
@media (prefers-color-scheme: dark){
  .stat-item{ background:#212529; border-color:#343a40; }
}
</style>

<!-- Hero: สรุปสถานะ -->
<div class="backup-hero shadow-sm" role="region" aria-label="สรุปสถานะการสำรอง">
    <div class="d-flex align-items-center gap-3">
        <div class="bg-white text-primary rounded-3 d-flex align-items-center justify-content-center" style="inline-size:48px; block-size:48px;">
            <i class="bi bi-database fs-4" aria-hidden="true"></i>
        </div>
        <div>
            <h2 class="h5 mb-1">ฐานข้อมูลพร้อมสำรอง</h2>
            <div class="small opacity-75">14 ตาราง • <?= number_format($totalRows) ?> รายการ • <?= htmlspecialchars($dbSizeText, ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>
    <div class="meta">
        <span class="badge rounded-pill px-3 py-2"><i class="bi bi-hdd me-1"></i><?= htmlspecialchars($dbSizeText, ENT_QUOTES, 'UTF-8') ?></span>
        <?php if (!empty($lastBackupAt)): ?>
            <span class="badge rounded-pill px-3 py-2" title="<?= htmlspecialchars($lastBackupAt, ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-clock-history me-1"></i><?= htmlspecialchars(formatDateTimeThai($lastBackupAt), ENT_QUOTES, 'UTF-8') ?></span>
        <?php else: ?>
            <span class="badge rounded-pill px-3 py-2">ยังไม่มีประวัติสำรอง</span>
        <?php endif; ?>
        <span class="badge bg-light text-dark rounded-pill px-3 py-2"><?= count($tableLabels) ?> ตาราง</span>
    </div>
</div>

<div class="backup-layout">
    <!-- ดาวน์โหลด -->
    <section class="backup-card" aria-labelledby="dl-title">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span id="dl-title"><i class="bi bi-cloud-download me-2"></i>ดาวน์โหลดไฟล์สำรอง</span>
                <span class="badge bg-primary">SQL</span>
            </div>
            <div class="card-body d-flex flex-column gap-3">
                <div class="d-flex gap-3 align-items-start">
                    <div class="text-primary"><i class="bi bi-file-earmark-code fs-1"></i></div>
                    <div>
                        <h3 class="h6 mb-1">ไฟล์ SQL มาตรฐาน</h3>
                        <p class="small text-muted mb-0">นำไปกู้คืนได้ทันทีด้วย phpMyAdmin หรือ <code>mysql</code> CLI • <code>SET NAMES utf8mb4</code> + <code>FOREIGN_KEY_CHECKS=0</code> ครบ</p>
                    </div>
                </div>

                <form method="POST" action="<?= SITE_URL ?>/backup" class="d-grid">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="download">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-download me-2"></i>ดาวน์โหลด .sql
                    </button>
                    <small class="text-muted text-center mt-1">ไฟล์ .sql พร้อมนำไปใส่ถังข้อมูลใหม่ได้ทันที</small>
                </form>

                <div class="alert alert-light border small mb-0 d-flex gap-2">
                    <i class="bi bi-lightbulb text-warning fs-5"></i>
                    <div>กู้คืน: phpMyAdmin &gt; นำเข้า หรือ <code>mysql -u root --default-character-set=utf8mb4 equipment_db &lt; backup.sql</code></div>
                </div>
            </div>
            <div class="card-footer small text-muted d-flex justify-content-between">
                <span><i class="bi bi-shield-check me-1"></i>ใช้ <code>$pdo-&gt;quote</code> + <code>unbuffered</code> ปลอดภัย UTF-8</span>
                <span><?= htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </section>

    <!-- สถิติ -->
    <section class="backup-card" aria-labelledby="stat-title">
        <div class="card shadow-sm h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span id="stat-title"><i class="bi bi-table me-2"></i>สถิติข้อมูล</span>
                <span class="badge bg-light text-dark border"><?= number_format($totalRows) ?> รายการ</span>
            </div>
            <div class="card-body">
                <div class="stat-grid" role="list">
                    <?php foreach ($tableLabels as $table => $label): ?>
                    <div class="stat-item" role="listitem">
                        <span class="label"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="value"><?= number_format($tableInfo[$table] ?? 0) ?> <small class="text-muted fw-normal"><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></small></span>
                    </div>
                    <?php endforeach; ?>
                    <?php $extraTables = array_diff_key((array)$tableInfo, $tableLabels); foreach ($extraTables as $table => $count): ?>
                    <div class="stat-item" role="listitem">
                        <span class="label"><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="value"><?= number_format($count ?? 0) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="card-footer small text-muted">
                <i class="bi bi-info-circle me-1"></i>นับจาก <code>SELECT COUNT(*)</code> แบบ real-time
            </div>
        </div>
    </section>

    <!-- คำแนะนำ -->
    <section class="span-full">
        <div class="card shadow-sm">
            <div class="card-header"><i class="bi bi-life-preserver me-2"></i>คำแนะนำการสำรอง &amp; กู้คืน</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="bi bi-shield-check text-success me-1"></i>แนวทางสำรอง</h6>
                        <ul class="small mb-0 ps-3">
                            <li>สำรอง <strong>ทุกสัปดาห์</strong> หรือหลังเพิ่ม/แก้ไขครุภัณฑ์จำนวนมาก</li>
                            <li>เก็บไฟล์ไว้ <strong>อย่างน้อย 2 ที่</strong> (เครื่องตนเอง + Drive)</li>
                            <li>ตั้งชื่อ <code>backup_YYYY-MM-DD_His.sql</code> เพื่อย้อนง่าย</li>
                            <li>ทดสอบกู้คืนบน DB ทดสอบก่อนใช้จริง</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="fw-bold"><i class="bi bi-arrow-counterclockwise text-primary me-1"></i>วิธีกู้คืน</h6>
                        <ol class="small mb-0 ps-3">
                            <li>phpMyAdmin &gt; เลือก DB &gt; นำเข้า &gt; เลือก <code>.sql</code></li>
                            <li>หรือ CLI: <code>mysql -u root --default-character-set=utf8mb4 equipment_db &lt; backup.sql</code></li>
                            <li>ตรวจ <code>system_logs</code> ว่าไม่มี error</li>
                        </ol>
                    </div>
                </div>
                <?php if (!empty($lastBackupAt)): ?>
                <div class="small text-muted mt-3">อ้างอิง <code>system_logs WHERE action='Backup'</code> ล่าสุด: <?= htmlspecialchars($lastBackupAt, ENT_QUOTES, 'UTF-8') ?></div>
                <?php endif; ?>
            </div>
            <div class="card-footer small text-muted d-flex gap-2 align-items-center">
                <i class="bi bi-exclamation-triangle text-warning"></i>ไฟล์สำรองมีข้อมูลส่วนบุคคล — เก็บอย่างปลอดภัย หลีกเลี่ยงแชร์สาธารณะ
            </div>
        </div>
    </section>
</div>
