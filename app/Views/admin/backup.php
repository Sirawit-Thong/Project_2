<div class="page-header">
    <h1><i class="bi bi-database-down me-2"></i>สำรองฐานข้อมูลระบบ</h1>
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
// รองรับกรณี controller ยังส่งไม่ครบ — เติม ?? 0 ไว้แล้วด้านล่าง
$tableInfo = $tableInfo ?? [];

// เวลาสำรองล่าสุด — 優先ใช้ตัวแปรจาก Controller ถ้ามี ถ้าไม่มีให้ query เอง (try/catch กัน error)
$lastBackupAt = $lastBackupAt ?? ($lastBackup ?? null);
if (empty($lastBackupAt)) {
    try {
        $pdoTmp = getDB();
        $stmt = $pdoTmp->query("SELECT created_at FROM system_logs WHERE action='Backup' ORDER BY created_at DESC LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!empty($row['created_at'])) {
            $lastBackupAt = $row['created_at'];
        }
    } catch (Throwable $e) {
        $lastBackupAt = null;
    }
}

// ขนาด DB โดยประมาณ — ลองจาก information_schema ถ้าได้ ถ้าไม่ได้ให้ fallback เป็นจำนวนรายการรวม
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
            if ($sz >= 1048576) {
                $dbSizeText = number_format($sz / 1048576, 2) . ' MB';
            } elseif ($sz >= 1024) {
                $dbSizeText = number_format($sz / 1024, 2) . ' KB';
            } else {
                $dbSizeText = number_format((int)$sz) . ' bytes';
            }
        }
    } catch (Throwable $e) {
        $dbSizeText = null;
    }
    if ($dbSizeText === null) {
        $totalRows = array_sum(array_map('intval', (array)$tableInfo));
        $dbSizeText = number_format($totalRows) . ' รายการ (ประมาณ)';
    }
}
?>

<!-- Alert อธิบายความต่างระหว่าง สำรองฐานข้อมูล vs สำรองไฟล์แนบ -->
<div class="alert alert-info d-flex align-items-start" role="alert">
    <i class="bi bi-info-circle flex-shrink-0 me-3 fs-4" aria-hidden="true"></i>
    <div>
        <h6 class="alert-heading mb-1"><i class="bi bi-info-circle me-1"></i>ความแตกต่างระหว่าง สำรองฐานข้อมูล vs สำรองไฟล์แนบ</h6>
        <ul class="mb-1 ps-3 small">
            <li><strong>สำรองฐานข้อมูล (.sql / .sql.gz)</strong> — ไฟล์ SQL dump ประกอบด้วยโครงสร้างและข้อมูลทั้ง 14 ตาราง (ผู้ใช้งาน, สาขา, หมวดหมู่ครุภัณฑ์, เกณฑ์ค่าเสื่อมราคา, ชุดครุภัณฑ์, รายการครุภัณฑ์, ห้อง, ผู้รับผิดชอบห้อง, ครุภัณฑ์, รูปครุภัณฑ์, รายการแจ้งซ่อม, รูปงานซ่อม, แบบประเมินความพึงพอใจ, บันทึกระบบ) ใช้กู้คืนผ่าน phpMyAdmin หรือ CLI <code>mysql</code></li>
            <li><strong>สำรองไฟล์แนบ (uploads.zip)</strong> — ไฟล์ ZIP ของโฟลเดอร์ <code>uploads/</code> ประกอบด้วยรูปครุภัณฑ์ (<code>uploads/equipment/</code>) และรูปงานซ่อม (<code>uploads/repairs/</code>) ซึ่ง <em>ไม่อยู่ในไฟล์ SQL</em> ต้องสำรองแยกและแตกไฟล์กลับไปที่เดิมเมื่อกู้คืน</li>
        </ul>
        <small class="text-muted">คำแนะนำ: ควรสำรอง <strong>ทั้งสองอย่าง</strong> ทุกครั้งหลังมีการเปลี่ยนแปลงสำคัญ และทดสอบกู้คืนเป็นระยะ</small>
    </div>
</div>

<div class="card mb-4"><div class="card-body text-center">
  <i class="bi bi-files fs-1 text-warning"></i>
  <p>สำรองรูปจำนวนมากแบบทีละไฟล์ (InfinityFree)</p>
  <a href="<?= SITE_URL ?>/backup/filebyfile" class="btn btn-warning">ไปหน้าสำรองทีละไฟล์</a>
</div></div>

<div class="row g-4">
    <!-- Card 1: ดาวน์โหลด Backup SQL + GZIP -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><i class="bi bi-database me-2" aria-hidden="true"></i>ดาวน์โหลด Backup ฐานข้อมูล</div>
            <div class="card-body text-center d-flex flex-column">
                <i class="bi bi-database fs-1 text-primary mb-3 d-block" aria-hidden="true"></i>
                <p class="mb-3">ดาวน์โหลดไฟล์ SQL สำหรับสำรองฐานข้อมูลระบบทั้งหมด<br><small class="text-muted">รองรับทั้งไฟล์ .sql ปกติและ .sql.gz แบบบีบอัด</small></p>
                <form method="POST" action="<?= SITE_URL ?>/backup" class="d-grid gap-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="download">
                    <button type="submit" class="btn btn-primary btn-lg" aria-label="ดาวน์โหลดไฟล์ Backup ฐานข้อมูลรูปแบบ SQL">
                        <i class="bi bi-download me-2" aria-hidden="true"></i>ดาวน์โหลด Backup (.sql)
                    </button>
                </form>
                <form method="POST" action="<?= SITE_URL ?>/backup" class="d-grid mt-2">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="gzip">
                    <button type="submit" class="btn btn-outline-primary" aria-label="ดาวน์โหลดไฟล์ Backup แบบบีบอัด GZIP">
                        <i class="bi bi-file-earmark-zip me-2" aria-hidden="true"></i>ดาวน์โหลด GZIP (.sql.gz)
                    </button>
                </form>
                <small class="text-muted d-block mt-2">ไฟล์ GZIP จะถูกส่งแบบบีบอัดหากเซิร์ฟเวอร์รองรับ (ประหยัดแบนด์วิธ)</small>
                <div class="mt-auto pt-3 small text-muted text-start bg-light rounded p-2">
                    <i class="bi bi-lightbulb me-1"></i>นำไฟล์ .sql ไปกู้คืนได้ที่ phpMyAdmin &gt; นำเข้า (Import) หรือคำสั่ง <code>mysql -u root equipment_db &lt; backup.sql</code>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: ดาวน์โหลดไฟล์แนบ uploads.zip -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><i class="bi bi-file-earmark-zip me-2" aria-hidden="true"></i>ดาวน์โหลดไฟล์แนบ</div>
            <div class="card-body text-center d-flex flex-column">
                <i class="bi bi-file-earmark-zip fs-1 text-success mb-3 d-block" aria-hidden="true"></i>
                <p class="mb-1">ดาวน์โหลดไฟล์แนบทั้งหมดเป็นไฟล์ ZIP</p>
                <p class="small text-muted mb-3">รวมรูปครุภัณฑ์และรูปงานซ่อมจากโฟลเดอร์ <code>uploads/</code><br>ใช้ควบคู่กับไฟล์ SQL เพื่อกู้คืนระบบให้สมบูรณ์</p>
                <form method="POST" action="<?= SITE_URL ?>/backup" class="d-grid">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="files">
                    <button type="submit" class="btn btn-success btn-lg" aria-label="ดาวน์โหลดไฟล์แนบทั้งหมดเป็น ZIP">
                        <i class="bi bi-download me-2" aria-hidden="true"></i>ดาวน์โหลดไฟล์แนบ (uploads.zip)
                    </button>
                </form>
                <small class="text-muted d-block mt-2">หากไม่มีไฟล์แนบ ระบบจะแจ้งว่าไม่พบไฟล์ให้ดาวน์โหลด</small>
                <div class="mt-auto pt-3 small text-muted text-start bg-light rounded p-2">
                    <i class="bi bi-folder2-open me-1"></i>แตกไฟล์แล้ววางทับโฟลเดอร์ <code>uploads/</code> เดิมบนเซิร์ฟเวอร์
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: สถิติข้อมูล ครบ 14 ตาราง (รองรับ ?? 0) -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-table me-2" aria-hidden="true"></i>สถิติข้อมูลในระบบ</span>
                <span class="badge bg-secondary"><?= count($tableLabels) ?> ตาราง</span>
            </div>
            <ul class="list-group list-group-flush" role="list">
                <?php foreach ($tableLabels as $table => $label): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?> <small class="text-muted">(<?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?>)</small></span>
                    <span class="badge bg-primary rounded-pill"><?= number_format($tableInfo[$table] ?? 0) ?></span>
                </li>
                <?php endforeach; ?>
                <?php
                // แสดงตารางอื่น ๆ ที่อาจมีใน $tableInfo แต่ไม่อยู่ใน $tableLabels (รองรับกรณีเพิ่มตารางใหม่เป็น 15)
                $extraTables = array_diff_key((array)$tableInfo, $tableLabels);
                foreach ($extraTables as $table => $count):
                ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <span><?= htmlspecialchars($table, ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="badge bg-secondary rounded-pill"><?= number_format($count ?? 0) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
            <div class="card-footer small text-muted d-flex justify-content-between">
                <span>รวมทั้งหมด</span>
                <span class="fw-bold"><?= number_format(array_sum(array_map('intval', (array)$tableInfo))) ?> รายการ</span>
            </div>
        </div>
    </div>

    <!-- Card 4: คำแนะนำ + ขนาด DB + เวลาสำรองล่าสุด -->
    <div class="col-lg-6">
        <div class="card h-100 shadow-sm">
            <div class="card-header"><i class="bi bi-info-circle me-2" aria-hidden="true"></i>คำแนะนำการสำรอง / กู้คืน</div>
            <div class="card-body">
                <h6 class="fw-bold"><i class="bi bi-shield-check me-1 text-success"></i>แนวทางสำรองข้อมูล</h6>
                <ul class="small mb-3 ps-3">
                    <li>สำรอง <strong>ทุกสัปดาห์</strong> หรือหลังเพิ่ม/แก้ไขครุภัณฑ์จำนวนมาก</li>
                    <li>เก็บไฟล์สำรองไว้ <strong>อย่างน้อย 2 ที่</strong> (เช่น เครื่องตนเอง + Google Drive)</li>
                    <li>ตั้งชื่อไฟล์ให้มีวันที่ เช่น <code>backup_2025-09-03.sql</code> เพื่อย้อนกลับง่าย</li>
                    <li>ทดสอบกู้คืนบนเครื่องทดสอบก่อนนำไปใช้จริง</li>
                </ul>
                <h6 class="fw-bold"><i class="bi bi-arrow-counterclockwise me-1 text-primary"></i>วิธีกู้คืน</h6>
                <ol class="small mb-3 ps-3">
                    <li>นำเข้าไฟล์ <code>.sql</code> ผ่าน phpMyAdmin &gt; นำเข้า หรือ <code>mysql -u root equipment_db &lt; backup.sql</code></li>
                    <li>แตกไฟล์ <code>uploads.zip</code> แล้วอัปโหลดทับโฟลเดอร์ <code>uploads/</code> บนเซิร์ฟเวอร์</li>
                    <li>ตรวจสอบสิทธิ์โฟลเดอร์ <code>uploads/</code> ให้เขียนได้ (755)</li>
                </ol>
                <hr>
                <div class="small">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted"><i class="bi bi-hdd me-1"></i>ขนาด DB โดยประมาณ</span>
                        <span class="badge bg-light text-dark border"><?= htmlspecialchars($dbSizeText, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted"><i class="bi bi-clock-history me-1"></i>สำรองล่าสุด</span>
                        <?php if (!empty($lastBackupAt)): ?>
                            <span class="badge bg-success" title="<?= htmlspecialchars($lastBackupAt, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatDateTimeThai($lastBackupAt), ENT_QUOTES, 'UTF-8') ?></span>
                        <?php else: ?>
                            <span class="text-muted">— ยังไม่มีประวัติสำรอง —</span>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($lastBackupAt)): ?>
                    <small class="text-muted d-block mt-1">อ้างอิงจาก system_logs where action='Backup' ล่าสุด</small>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-footer small text-muted">
                <i class="bi bi-exclamation-triangle me-1 text-warning"></i>คำเตือน: ไฟล์สำรองอาจมีข้อมูลส่วนบุคคล ควรเก็บรักษาอย่างปลอดภัย
            </div>
        </div>
    </div>
</div>
