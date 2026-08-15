<div class="page-header">
    <h1><i class="bi bi-database-down me-2"></i>สำรองฐานข้อมูลระบบ</h1>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><i class="bi bi-cloud-download me-2"></i>ดาวน์โหลด Backup</div>
            <div class="card-body text-center">
                <i class="bi bi-database fs-1 text-primary mb-3 d-block"></i>
                <p>ดาวน์โหลดไฟล์ SQL สำหรับสำรองฐานข้อมูลระบบทั้งหมด</p>
                <form method="POST" action="<?= SITE_URL ?>/backup">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="download">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="bi bi-download me-2"></i>ดาวน์โหลด Backup
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-table me-2"></i>สถิติข้อมูลในระบบ</div>
            <ul class="list-group list-group-flush">
                <?php
                $tableLabels = [
                    'users' => 'ผู้ใช้งาน',
                    'dept' => 'สาขา',
                    'sets' => 'ชุดครุภัณฑ์',
                    'items' => 'รายการครุภัณฑ์',
                    'rooms' => 'ห้อง',
                    'room_managers' => 'ผู้รับผิดชอบห้อง',
                    'equipment' => 'ครุภัณฑ์',
                    'equipment_img' => 'รูปครุภัณฑ์',
                    'repair' => 'รายการแจ้งซ่อม',
                    'repair_img' => 'รูปงานซ่อม',
                    'system_logs' => 'บันทึกระบบ',
                ];
                foreach ($tableInfo as $table => $count):
                ?>
                <li class="list-group-item d-flex justify-content-between">
                    <?= $tableLabels[$table] ?? $table ?>
                    <span class="badge bg-primary"><?= number_format($count) ?></span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>