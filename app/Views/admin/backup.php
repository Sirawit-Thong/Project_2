<div class="page-header">
    <h1><i class="bi bi-database me-2"></i>สำรองฐานข้อมูลระบบ</h1>
</div>

<?php
$tableLabels = [
    'users'         => 'ผู้ใช้งาน',
    'dept'          => 'สาขา',
    'sets'          => 'ชุดครุภัณฑ์',
    'items'         => 'รายการครุภัณฑ์',
    'equipment'     => 'ครุภัณฑ์',
    'repair'        => 'รายการแจ้งซ่อม',
    'system_logs'   => 'บันทึกระบบ',
];
$tableInfo = $tableInfo ?? [];
// เติมให้ครบ 14 ตารางในไฟล์สำรอง แต่หน้าแสดงแค่ 7 ตัวหลักตามดีไซน์โล้น
?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card shadow-sm" style="border-radius:1rem;">
            <div class="card-header bg-white" style="border-radius:1rem 1rem 0 0;"><i class="bi bi-cloud-download me-2"></i>ดาวน์โหลด Backup</div>
            <div class="card-body text-center py-4">
                <i class="bi bi-database fs-1 text-primary mb-3 d-block" aria-hidden="true"></i>
                <p class="mb-3">ดาวน์โหลดไฟล์ SQL สำหรับสำรองฐานข้อมูลระบบทั้งหมด</p>
                <form method="POST" action="<?= SITE_URL ?>/backup">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="download">
                    <button type="submit" class="btn btn-primary" style="background:#3b5bff; border-color:#3b5bff; border-radius:.6rem; padding:.6rem 1.5rem;">
                        <i class="bi bi-download me-1"></i> ดาวน์โหลด Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm" style="border-radius:1rem;">
            <div class="card-header bg-white d-flex align-items-center" style="border-radius:1rem 1rem 0 0;"><i class="bi bi-table me-2"></i>สถิติข้อมูลในระบบ</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($tableLabels as $table => $label): ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                    <span class="badge bg-primary rounded-pill" style="background:#3b5bff !important;"><?= number_format($tableInfo[$table] ?? 0) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>
