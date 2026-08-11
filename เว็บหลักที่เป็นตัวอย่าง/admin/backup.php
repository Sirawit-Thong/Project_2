<?php
/**
 * Database Backup
 */
$pageTitle = 'สำรองฐานข้อมูลระบบ';
require_once '../includes/header.php';
requireRole('admin');

$pdo = getDB();

// Handle backup download
if (isset($_GET['download'])) {
    // Clear any previous output (e.g. from header.php)
    if (ob_get_level()) {
        ob_end_clean();
    }

    $tables = ['users', 'dept', 'sets', 'items', 'rooms', 'room_managers', 'equipment', 'equipment_img', 'repair', 'repair_img', 'system_logs'];

    $backup = "-- Equipment DB Backup\n-- Date: " . date('Y-m-d H:i:s') . "\n-- ========================================\n\n";
    $backup .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

    foreach ($tables as $table) {
        // Get create table statement
        $createStmt = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);

        $backup .= "-- Table: $table\n";
        $backup .= "DROP TABLE IF EXISTS `$table`;\n";
        $backup .= $createStmt[1] . ";\n\n";

        // Get data
        $result = $pdo->query("SELECT * FROM `$table`");
        $rows = $result->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($rows)) {
            $backup .= "-- Data for: $table\n";
            foreach ($rows as $row) {
                $values = array_map(function ($v) use ($pdo) {
                    if ($v === null)
                        return 'NULL';
                    if (is_int($v) || is_float($v))
                        return $v; // Don't quote numbers
                    return $pdo->quote($v);
                }, $row);
                $backup .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
            }
            $backup .= "\n";
        }
    }

    $backup .= "SET FOREIGN_KEY_CHECKS=1;\n";

    header('Content-Type: application/sql');
    header('Content-Disposition: attachment; filename="backup_' . date('Ymd_His') . '.sql"');
    echo $backup;

    logActivity($pdo, getCurrentUserId(), 'Backup', 'ดาวน์โหลดไฟล์สำรองข้อมูล');
    exit;
}

// Get table sizes
$tableStats = [];
$tables = ['users', 'dept', 'sets', 'items', 'equipment', 'repair', 'system_logs'];
foreach ($tables as $t) {
    $tableStats[$t] = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
}
?>

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
                <a href="backup.php?download=1" class="btn btn-primary btn-lg"><i
                        class="bi bi-download me-2"></i>ดาวน์โหลด Backup</a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="bi bi-table me-2"></i>สถิติข้อมูลในระบบ</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between">ผู้ใช้งาน<span
                        class="badge bg-primary"><?= $tableStats['users'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">สาขา<span
                        class="badge bg-primary"><?= $tableStats['dept'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">ชุดครุภัณฑ์<span
                        class="badge bg-primary"><?= $tableStats['sets'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">รายการครุภัณฑ์<span
                        class="badge bg-primary"><?= $tableStats['items'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">ครุภัณฑ์<span
                        class="badge bg-primary"><?= $tableStats['equipment'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">รายการแจ้งซ่อม<span
                        class="badge bg-primary"><?= $tableStats['repair'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between">บันทึกระบบ<span
                        class="badge bg-primary"><?= $tableStats['system_logs'] ?></span></li>
            </ul>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>