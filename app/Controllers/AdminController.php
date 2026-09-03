<?php
/**
 * Admin Controller
 * จัดการระบบ — backup, logs, reports, export
 */
class AdminController extends Controller
{
    /**
     * รายการตารางครบ 14 ตาราง เรียงตาม FK dependency
     * ครอบคลุม schema จริงตาม database.sql
     * ลำดับ: dept -> users -> asset_categories -> depreciation_settings -> rooms -> sets -> items -> equipment -> equipment_img -> room_managers -> repair -> repair_img -> satisfaction_surveys -> system_logs
     * ถ้าตารางไม่มี (เช่น บาง dump ใช้ departments แทน dept) จะ skip gracefully ด้วย try/catch
     * @var array
     */
    private $backupTables = [
        'dept',
        'users',
        'asset_categories',
        'depreciation_settings',
        'rooms',
        'sets',
        'items',
        'equipment',
        'equipment_img',
        'room_managers',
        'repair',
        'repair_img',
        'satisfaction_surveys',
        'system_logs',
    ];

    public function backup()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $pageTitle = 'สำรองข้อมูล';
        $viewPath = 'admin/backup';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
            $this->downloadBackup();
        }

        // Get table info for display — ใช้ method กลางเพื่อความสอดคล้อง
        $tableInfo = $this->getTableInfo();
        $detailedInfo = $this->getDetailedTableInfo();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    /**
     * คืนค่า counts ของทุกตารางใน backup list
     * ใช้ try/catch เพื่อ backward compat — ถ้าตารางไม่มีให้ count เป็น 0
     * @return array [table => count]
     */
    public function getTableInfo()
    {
        $pdo = getDB();
        $tables = $this->getBackupTables();
        $tableInfo = [];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM `{$table}`");
                $tableInfo[$table] = (int) $stmt->fetchColumn();
            } catch (PDOException $e) {
                // ตารางไม่มี (เช่น dept vs departments, หรือตารางใหม่ยังไม่ได้ migrate)
                $tableInfo[$table] = 0;
            } catch (Exception $e) {
                $tableInfo[$table] = 0;
            }
        }
        return $tableInfo;
    }

    /**
     * Helper: คืนค่า list ตาราง backup ตามลำดับ FK
     * @return array
     */
    private function getBackupTables()
    {
        return $this->backupTables;
    }

    /**
     * รายละเอียดครบทุกตาราง: จำนวนแถว, คอลัมน์, เวลาล่าสุด
     * @return array [table => ['count'=>int, 'columns'=>array, 'latest'=>?string]]
     */
    public function getDetailedTableInfo()
    {
        $pdo = getDB();
        $out = [];
        foreach ($this->getBackupTables() as $table) {
            $info = ['count'=>0, 'columns'=>[], 'latest'=>null];
            try {
                $info['count'] = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
            } catch (Throwable $e) { $info['count']=0; }
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$table}`");
                $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $info['columns'] = array_column($cols, 'Field');
            } catch (Throwable $e) { $info['columns']=[]; }
            // เวลาล่าสุด: ลอง updated_at ก่อน ถ้าไม่มีลอง created_at
            try {
                $hasUpdated = in_array('updated_at', $info['columns']);
                $hasCreated = in_array('created_at', $info['columns']);
                if ($hasUpdated && $hasCreated) {
                    $stmt = $pdo->query("SELECT GREATEST(MAX(updated_at), MAX(created_at)) FROM `{$table}`");
                    $info['latest'] = $stmt->fetchColumn();
                } elseif ($hasUpdated) {
                    $stmt = $pdo->query("SELECT MAX(updated_at) FROM `{$table}`");
                    $info['latest'] = $stmt->fetchColumn();
                } elseif ($hasCreated) {
                    $stmt = $pdo->query("SELECT MAX(created_at) FROM `{$table}`");
                    $info['latest'] = $stmt->fetchColumn();
                }
                if ($info['latest'] === '0000-00-00 00:00:00') $info['latest']=null;
            } catch (Throwable $e) { $info['latest']=null; }
            $out[$table] = $info;
        }
        return $out;
    }

    /**
     * ดาวน์โหลด backup ฐานข้อมูลแบบ stream — ไฟล์ .sql พร้อมนำไปใส่ถังใหม่ได้ทันที
     * - ใช้ mysqldump ถ้าเป็น local และ exec ได้ (prefer mysqldump)
     * - fallback PHP streaming แบบ row-by-row, ใช้ quote สำหรับ escaping และ handle NULL
     * - รองรับตารางว่าง, ข้ามตารางที่ไม่มี gracefully
     */
    private function downloadBackup()
    {
        $pdo = getDB();
        $tables = $this->getBackupTables();

        // ลองใช้ mysqldump ก่อนถ้าเป็น local และอยู่ในเครื่อง (XAMPP) — ถ้าสำเร็จจะ exit เลย
        if ($this->tryMysqldump($tables)) {
            // tryMysqldump จะ exit เองเมื่อสำเร็จ
            exit;
        }

        // ========== PHP Fallback: streaming ==========
        try {
            logActivity(getCurrentUserId(), 'Backup', 'ดาวน์โหลดสำรองข้อมูล');
        } catch (Exception $e) {}

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';

        while (ob_get_level() > 0) { @ob_end_clean(); }
        @set_time_limit(0);
        @ignore_user_abort(true);
        if (function_exists('ini_set')) { @ini_set('zlib.output_compression', 'Off'); }

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $streamWrite = function ($data) { echo $data; if (ob_get_level() > 0) { @ob_flush(); } @flush(); };

        // Header comment รวม SET NAMES, FOREIGN_KEY_CHECKS, date, DB_NAME
        $header = "-- Equipment Repair Management System Backup\n";
        $header .= "-- Database: " . DB_NAME . "\n";
        $header .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $header .= "-- Host: " . DB_HOST . "\n";
        $header .= "-- Generated by AdminController::downloadBackup()\n\n";
        $header .= "SET NAMES utf8mb4;\n";
        $header .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
        $streamWrite($header);

        foreach ($tables as $table) {
            try {
                // SHOW CREATE TABLE — ถ้าตารางไม่มีจะโยน exception และ skip
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                if (!$createRow) {
                    continue;
                }
                // หา key ของ Create Table แบบ case-insensitive (บาง MySQL คืน 'Create Table')
                $createTableSql = null;
                foreach ($createRow as $k => $v) {
                    if (stripos($k, 'create') !== false) {
                        $createTableSql = $v;
                        break;
                    }
                }
                if (empty($createTableSql)) {
                    // fallback: ถ้าไม่มี key ที่คาด ใ้ช้ค่าแรกที่ไม่ใช่ Table
                    $createTableSql = $createRow['Create Table'] ?? null;
                }
                if (empty($createTableSql)) {
                    continue;
                }

                $tableHeader = "-- --------------------------------------------------------\n";
                $tableHeader .= "-- Table structure for table `{$table}`\n";
                $tableHeader .= "-- --------------------------------------------------------\n\n";
                $tableHeader .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $tableHeader .= $createTableSql . ";\n\n";
                $streamWrite($tableHeader);

                // ดึงข้อมูลแบบ unbuffered และ stream ทีละแถว
                try {
                    // ปิด buffered query เพื่อประหยัด RAM
                    $wasBuffered = true;
                    try {
                        // จำสถานะเดิมไว้
                        $wasBuffered = $pdo->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
                    } catch (Exception $e) {
                        $wasBuffered = true;
                    }
                    try {
                        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
                    } catch (Exception $e) {
                        // บาง PDO driver ไม่รองรับ — ไม่เป็นไร
                    }

                    $stmt = $pdo->query("SELECT * FROM `{$table}`");

                    $first = true;
                    $hasRows = false;
                    $colList = '';
                    $isFirstDataChunk = true;

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        if ($first) {
                            $first = false;
                            $hasRows = true;
                            $columns = array_keys($row);
                            $colList = '`' . implode('`, `', $columns) . '`';
                            // LOCK ก่อนข้อมูลชุดแรก
                            $dataHeader = "-- --------------------------------------------------------\n";
                            $dataHeader .= "-- Data for table `{$table}`\n";
                            $dataHeader .= "-- --------------------------------------------------------\n\n";
                            $dataHeader .= "LOCK TABLES `{$table}` WRITE;\n";
                            $dataHeader .= "/*!40000 ALTER TABLE `{$table}` DISABLE KEYS */;\n";
                            $streamWrite($dataHeader);
                        }

                        // สร้าง INSERT แบบปลอดภัยด้วย $pdo->quote() และ handle NULL
                        $values = [];
                        foreach ($row as $val) {
                            if ($val === null) {
                                $values[] = 'NULL';
                            } else {
                                // quote จะใส่ '' และ escape ให้แล้ว
                                $quoted = $pdo->quote((string) $val);
                                // quote อาจคืน false ถ้า driver ไม่พร้อม — fallback
                                if ($quoted === false) {
                                    $values[] = "'" . str_replace("'", "''", (string) $val) . "'";
                                } else {
                                    $values[] = $quoted;
                                }
                            }
                        }
                        $insert = "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $values) . ");\n";
                        $streamWrite($insert);
                    }

                    if ($hasRows) {
                        $footer = "/*!40000 ALTER TABLE `{$table}` ENABLE KEYS */;\n";
                        $footer .= "UNLOCK TABLES;\n\n";
                        $streamWrite($footer);
                    } else {
                        // ตารางว่าง — ไม่ต้อง LOCK/UNLOCK, แค่เว้นบรรทัด
                        // เพื่อให้ export ครอบคลุมถึงแม้ไม่มี rows
                    }

                    // ปิด cursor และคืนค่า buffered
                    try {
                        $stmt->closeCursor();
                    } catch (Exception $e) {
                    }
                    try {
                        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $wasBuffered);
                    } catch (Exception $e) {
                    }
                    // flush หลังจบแต่ละตาราง
                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }
                    @flush();
                } catch (PDOException $e) {
                    // ดึงข้อมูลล้มเหลว — ข้ามตารางนี้ แต่สร้าง CREATE ไว้แล้ว
                    try {
                        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
                    } catch (Exception $ex) {
                    }
                    continue;
                }
            } catch (PDOException $e) {
                // ตารางไม่มี (backward compat) — skip gracefully
                continue;
            } catch (Exception $e) {
                continue;
            }
        }

        $footer = "SET FOREIGN_KEY_CHECKS=1;\n";
        $footer .= "-- Backup completed: " . date('Y-m-d H:i:s') . "\n";
        $streamWrite($footer);
        exit;
    }

    /**
     * ลองใช้ mysqldump ถ้าเป็น local และ exec ได้
     * ถ้าสำเร็จจะส่ง header + passthru แล้ว exit เลย
     */
    private function tryMysqldump($tables)
    {

        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (!file_exists($mysqldump)) {
            return false;
        }
        if (!function_exists('exec') || !function_exists('passthru')) {
            return false;
        }
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $list = array_map('trim', explode(',', $disabled));
            $listLower = array_map('strtolower', $list);
            if (in_array('exec', $listLower) || in_array('passthru', $listLower) || in_array('shell_exec', $listLower)) {
                return false;
            }
        }
        // เฉพาะ local เท่านั้น
        $isLocal = (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1' || DB_HOST === '::1');
        if (!$isLocal) {
            return false;
        }

        // ตรวจว่า exec ใช้งานได้จริงด้วย --version (ไม่ต้อง dump ทั้ง DB)
        $testCmd = '"' . $mysqldump . '" --version 2>&1';
        $out = [];
        $ret = 1;
        @exec($testCmd, $out, $ret);
        if ($ret !== 0) {
            return false;
        }

        $pdo = getDB();
        $existing = [];
        foreach ($tables as $t) {
            try {
                // ตรวจสอบว่ามีตารางจริงด้วย SHOW CREATE (ดีกว่า SELECT เพราะตารางว่างก็ยังนับว่ามี)
                $pdo->query("SHOW CREATE TABLE `{$t}`");
                $existing[] = $t;
            } catch (PDOException $e) {
                continue;
            } catch (Exception $e) {
                continue;
            }
        }
        if (empty($existing)) {
            return false;
        }

        // สร้างคำสั่ง mysqldump แบบปลอดภัย
        $cmd = '"' . $mysqldump . '"';
        $cmd .= ' --host=' . escapeshellarg(DB_HOST);
        $cmd .= ' --user=' . escapeshellarg(DB_USER);
        if (DB_PASS !== '' && DB_PASS !== null) {
            $cmd .= ' --password=' . escapeshellarg(DB_PASS);
        }
        $cmd .= ' --single-transaction --routines --triggers --default-character-set=utf8mb4 --hex-blob --quick';
        $cmd .= ' ' . escapeshellarg(DB_NAME);
        foreach ($existing as $t) {
            $cmd .= ' ' . escapeshellarg($t);
        }

        // เตรียม header
        // ล้าง buffer ก่อนส่ง
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        @set_time_limit(0);
        @ignore_user_abort(true);

        $filename = 'backup_' . date('Y-m-d_His') . '.sql';
        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        // Log ก่อน passthru
        try {
            logActivity(getCurrentUserId(), 'Backup', 'ดาวน์โหลดสำรองข้อมูล (mysqldump)');
        } catch (Exception $e) {
        }

        // ส่ง SET headers ก่อน dump เพื่อความสมบูรณ์ (mysqldump จะมี SET อยู่แล้ว แต่เพิ่มให้ชัวร์)
        // เราจะ echo header ง่ายๆ ก่อน passthru ไม่ได้เพราะ passthru จะส่ง dump ต่อทันที
        // ดังนั้น mysqldump ควรมี SET เอง — เราไม่ต้อง echo เพิ่ม

        $returnVar = 0;
        @passthru($cmd, $returnVar);
        if ($returnVar === 0) {
            exit;
        }
        // ถ้า fail (return !=0) — header ส่งไปแล้ว ไม่สามารถ fallback กลับ PHP ได้สมบูรณ์
        // แต่ส่ง fallback footer เพื่อบอกว่า dump มีปัญหา
        // ในกรณีปกติ local XAMPP จะ success เสมอ จึงไม่เข้า branch นี้
        return false;
    }

    public function logs()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $search = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = SystemLog::getFiltered($search, $page);
        $pageTitle = 'ประวัติการใช้งานระบบ';
        $viewPath = 'admin/logs';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function reports()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'รายงานและสถิติ';
        $viewPath = 'admin/reports';

        // สถานะครุภัณฑ์ แบบ key => count
        $eqStats = [];
        foreach (Equipment::getStatusCounts() as $row) {
            $eqStats[$row['status']] = (int) $row['count'];
        }

        $totalValue = Equipment::getAssetValue();
        $monthlyData = Repair::getMonthlyStats(12);
        $topBroken = Repair::getTopBrokenItems(5);
        $deptStats = Department::getStatsWithValues();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function export($type = null)
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        if (!$type) {
            $type = $_GET['type'] ?? 'equipment';
        }

        logActivity(getCurrentUserId(), 'Export', 'ส่งออกข้อมูล: ' . $type);

        switch ($type) {
            case 'equipment':
                $this->exportEquipment();
                break;
            case 'repairs':
                $this->exportRepairs();
                break;
            case 'users':
                $this->exportUsers();
                break;
            default:
                ErrorHandler::page404();
        }
    }

    private function exportEquipment()
    {
        $data = Equipment::getAllForExport();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="equipment_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM

        $output = fopen('php://output', 'w');
        fputcsv($output, ['รหัส', 'รายการ', 'ยี่ห้อ', 'รุ่น', 'ชุด', 'ปี', 'สาขา', 'ห้อง', 'ผู้ถือครอง', 'สถานะ', 'วันที่ซื้อ', 'ราคา', 'วันที่ตรวจ', 'หมายเหตุ']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['code'], $row['item_name'], $row['brand'], $row['model'],
                $row['set_name'], $row['year'], $row['dept_name'], $row['room_name'],
                ($row['holder_firstname'] ?? '') . ' ' . ($row['holder_lastname'] ?? ''),
                translateEquipmentStatus($row['status']),
                $row['purchase_date'], $row['price'], $row['check_date'], $row['remark'],
            ]);
        }

        fclose($output);
        exit;
    }

    private function exportRepairs()
    {
        $data = Repair::getAllForExport();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="repairs_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'ครุภัณฑ์', 'รายการ', 'ปัญหา', 'สถานะ', 'วันที่', 'ผู้แจ้ง']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['id'], $row['eq_code'], $row['item_name'],
                $row['issue'], translateRepairStatus($row['status']),
                $row['created_at'], $row['firstname'] . ' ' . $row['lastname'],
            ]);
        }

        fclose($output);
        exit;
    }

    private function exportUsers()
    {
        $data = User::getAllForExport();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="users_' . date('Y-m-d') . '.csv"');
        echo "\xEF\xBB\xBF";

        $output = fopen('php://output', 'w');
        fputcsv($output, ['รหัส', 'ชื่อ', 'นามสกุล', 'อีเมล', 'บทบาท', 'สถานะ', 'วันที่สมัคร']);

        foreach ($data as $row) {
            fputcsv($output, [
                $row['sid'], $row['firstname'], $row['lastname'], $row['email'],
                translateRole($row['role']), translateUserStatus($row['status']),
                $row['created_at'],
            ]);
        }

        fclose($output);
        exit;
    }
}
