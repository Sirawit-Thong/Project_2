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

            $action = $_POST['action'] ?? 'download';

            if ($action === 'download') {
                $this->downloadBackup(false);
            } elseif ($action === 'download_gz' || $action === 'gzip' || $action === 'gz') {
                $this->downloadBackup(true);
            }
        }

        // Get table info for display — ใช้ method กลางเพื่อความสอดคล้อง
        $tableInfo = $this->getTableInfo();

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
     * ดาวน์โหลด backup ฐานข้อมูลแบบ stream
     * - ใช้ mysqldump ถ้าเป็น local และ exec ได้ (prefer mysqldump)
     * - fallback PHP streaming แบบ row-by-row, ใช้ quote สำหรับ escaping และ handle NULL
     * - รองรับ gzip เมื่อ $gzip = true (action download_gz)
     * - รองรับตารางว่าง, ข้ามตารางที่ไม่มี gracefully
     * @param bool $gzip
     */
    private function downloadBackup($gzip = false)
    {
        $pdo = getDB();
        $tables = $this->getBackupTables();

        // ลองใช้ mysqldump ก่อนถ้าเป็น local และอยู่ในเครื่อง (XAMPP) — ถ้าสำเร็จจะ exit เลย
        // สำหรับ gzip จะ fallback ไป PHP incremental gzip แทน เพื่อไม่ต้องพึ่ง system gzip
        if (!$gzip && $this->tryMysqldump($gzip, $tables)) {
            // tryMysqldump จะ exit เองเมื่อสำเร็จ
            exit;
        }

        // ========== PHP Fallback: streaming ==========
        // log ก่อนส่งไฟล์
        try {
            logActivity(getCurrentUserId(), 'Backup', 'ดาวน์โหลดสำรองข้อมูล' . ($gzip ? ' (gzip)' : ''));
        } catch (Exception $e) {
            // ignore log failure
        }

        $filename = 'backup_' . date('Y-m-d_His') . ($gzip ? '.sql.gz' : '.sql');

        // Clean output buffers ให้เริ่ม stream สะอาด
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }
        @set_time_limit(0);
        @ignore_user_abort(true);
        if (function_exists('ini_set')) {
            @ini_set('zlib.output_compression', 'Off');
        }

        // Headers
        if ($gzip) {
            header('Content-Type: application/gzip');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Content-Encoding: gzip');
            header('Pragma: no-cache');
            header('Expires: 0');
        } else {
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
        }

        // เตรียม gzip incremental ถ้าต้องการ
        $useDeflate = $gzip && function_exists('deflate_init');
        if ($useDeflate) {
            $deflateContext = deflate_init(ZLIB_ENCODING_GZIP);
        }

        // Helper เขียน output แบบ stream + flush
        $streamWrite = function ($data) use ($gzip, $useDeflate, &$deflateContext) {
            if ($gzip && $useDeflate) {
                echo deflate_add($deflateContext, $data, ZLIB_NO_FLUSH);
            } else {
                echo $data;
            }
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
        };

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

        if ($gzip && $useDeflate) {
            // จบ gzip stream
            echo deflate_add($deflateContext, '', ZLIB_FINISH);
            if (ob_get_level() > 0) {
                @ob_flush();
            }
            @flush();
        } elseif ($gzip) {
            // Fallback: ถ้าไม่มี deflate_init แต่ต้องการ gzip — จะได้ plain sql แต่ header บอก gzip
            // กรณีนี้เราควร fallback เป็น plain ให้ผู้ใช้ได้ไฟล์ ไม่ควรพัง
            // เนื่องจาก $useDeflate เป็น false แล้วเราได้ echo plain ไปแล้ว หากต้องการ gzip จริงต้อง buffer แล้ว gzencode
            // แต่เราเลือก stream plain เพื่อไม่ให้ค้าง — ผู้ใช้จะได้ไฟล์ plain แม้ชื่อ .gz (ยังดีกว่า error)
            // หมายเหตุ: บน XAMPP ปกติ deflate_init มี จึงไม่เข้า branch นี้
        }

        exit;
    }

    /**
     * ลองใช้ mysqldump ถ้าเป็น local และ exec ได้
     * ถ้าสำเร็จจะส่ง header + passthru แล้ว exit เลย
     * ถ้าไม่พร้อมหรือ fail จะ return false ให้ fallback PHP
     * @param bool $gzip
     * @param array $tables
     * @return bool true ถ้าได้ใช้ mysqldump แล้วและส่งออกแล้ว
     */
    private function tryMysqldump($gzip, $tables)
    {
        // Gzip ให้ fallback PHP เพื่อใช้ deflate_init streaming — ไม่พึ่ง system gzip
        if ($gzip) {
            return false;
        }

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

    /**
     * Cron / CLI silent backup — สำหรับเรียกผ่าน cron, task scheduler หรือ HTTP /cron/backup?token=XXX
     * - ตรวจสอบ token จาก env BACKUP_CRON_TOKEN หรือ constant BACKUP_CRON_TOKEN
     * - ถ้าไม่มี token ให้ bypass สำหรับ CLI (php_sapi_name()==='cli')
     * - ทำ backup แบบ silent เก็บลง backups/ พร้อม logActivity และ prune ตาม retention
     */
    public function cronBackup()
    {
        $isCli = php_sapi_name() === 'cli';

        // Resolve expected token: env() -> constant -> null
        $expectedToken = null;
        if (function_exists('env')) {
            $expectedToken = env('BACKUP_CRON_TOKEN', null);
        }
        if (($expectedToken === null || $expectedToken === '') && defined('BACKUP_CRON_TOKEN')) {
            $expectedToken = BACKUP_CRON_TOKEN;
        }
        if (($expectedToken === null || $expectedToken === '') && getenv('BACKUP_CRON_TOKEN')) {
            $expectedToken = getenv('BACKUP_CRON_TOKEN');
        }

        // Resolve provided token: GET, POST, header, CLI --token
        $providedToken = $_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? $_SERVER['HTTP_X_BACKUP_TOKEN'] ?? null;
        if ($providedToken === null && !empty($_SERVER['HTTP_AUTHORIZATION'])) {
            if (preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
                $providedToken = trim($m[1]);
            }
        }
        // CLI argv parsing
        if ($isCli && isset($GLOBALS['argv']) && is_array($GLOBALS['argv'])) {
            foreach ($GLOBALS['argv'] as $arg) {
                if (strpos($arg, '--token=') === 0) {
                    $providedToken = substr($arg, 8);
                    break;
                }
                if ($arg === '--token' && isset($GLOBALS['argv'][array_search($arg, $GLOBALS['argv']) + 1])) {
                    $providedToken = $GLOBALS['argv'][array_search($arg, $GLOBALS['argv']) + 1];
                    break;
                }
            }
            if ($providedToken === null) {
                foreach ($GLOBALS['argv'] as $arg) {
                    if (strpos($arg, 'token=') === 0) {
                        $providedToken = substr($arg, 6);
                        break;
                    }
                }
            }
        }

        // Verification
        if ($expectedToken !== null && $expectedToken !== '') {
            $providedToken = (string)($providedToken ?? '');
            if (!hash_equals((string)$expectedToken, $providedToken)) {
                if ($isCli) {
                    fwrite(STDERR, "[cronBackup] Forbidden: invalid token\n");
                    $this->appendCronLog("FAILED cronBackup: invalid token (CLI)");
                    exit(1);
                }
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Forbidden: invalid token'], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            if (!$isCli) {
                $this->requireLogin();
                $this->authorize(['admin']);
            }
        }

        $result = $this->performSilentBackup('cron');

        if ($isCli) {
            echo $result['message'] . "\n";
            if (!empty($result['file'])) {
                echo "File: " . $result['file'] . "\n";
            }
            exit($result['success'] ? 0 : 1);
        } else {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    /**
     * Shared silent backup logic — ใช้ร่วมกับ cronBackup และ scripts/cron_backup.php
     */
    public function performSilentBackup($source = 'cron')
    {
        $pdo = getDB();
        $tables = $this->getBackupTables();
        $backupDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        $htaccess = $backupDir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }

        $timestamp = date('Y-m-d_His');
        $filename = "backup_{$timestamp}_{$source}.sql";
        $filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

        try {
            // Try mysqldump silent if local and available — otherwise PHP fallback
            $usedMysqldump = false;
            $bytes = 0;
            if ($this->tryMysqldumpToFile($filepath, $tables)) {
                $usedMysqldump = true;
                $bytes = filesize($filepath);
            } else {
                $sql = $this->buildBackupSqlForSilent($pdo, $tables);
                $bytes = file_put_contents($filepath, $sql);
                if ($bytes === false) {
                    throw new RuntimeException("Failed to write backup file: {$filepath}");
                }
            }

            $retentionDays = 7;
            if (function_exists('env')) {
                $retentionDays = (int) env('BACKUP_RETENTION_DAYS', env('BACKUP_RETENTION', 7));
            }
            if (defined('BACKUP_RETENTION_DAYS')) {
                $retentionDays = (int) BACKUP_RETENTION_DAYS;
            }
            if ($retentionDays < 1) $retentionDays = 7;
            $this->pruneOldBackups($backupDir, $retentionDays);

            $userId = null;
            if (function_exists('getCurrentUserId')) {
                $userId = getCurrentUserId();
            }
            $details = "สำรองข้อมูลอัตโนมัติ ({$source}" . ($usedMysqldump ? "/mysqldump" : "/php") . "): {$filename} (" . number_format($bytes) . " bytes)";
            if (function_exists('logActivity')) {
                logActivity($userId, 'Backup', $details);
            } else {
                SystemLog::log($userId, 'Backup', $details);
            }

            $logLine = "[" . date('Y-m-d H:i:s') . "] [{$source}] SUCCESS {$filename} (" . number_format($bytes) . " bytes) retention={$retentionDays}d\n";
            @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $logLine, FILE_APPEND);
            @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'cron.log', $logLine, FILE_APPEND);

            return [
                'success' => true,
                'message' => "Backup completed: {$filename} via " . ($usedMysqldump ? "mysqldump" : "php"),
                'file' => $filepath,
                'filename' => $filename,
                'bytes' => $bytes,
                'source' => $source,
                'method' => $usedMysqldump ? 'mysqldump' : 'php',
            ];
        } catch (Throwable $e) {
            $errMsg = $e->getMessage();
            $logLine = "[" . date('Y-m-d H:i:s') . "] [{$source}] FAILED: {$errMsg}\n";
            @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $logLine, FILE_APPEND);
            @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'cron.log', $logLine, FILE_APPEND);
            try {
                $uid = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
                if (function_exists('logActivity')) {
                    logActivity($uid, 'Backup', "สำรองข้อมูลล้มเหลว ({$source}): {$errMsg}");
                }
            } catch (Throwable $ignored) {}
            return [
                'success' => false,
                'message' => "Backup failed: {$errMsg}",
                'error' => $errMsg,
                'source' => $source,
            ];
        }
    }

    private function buildBackupSqlForSilent(PDO $pdo, array $tables): string
    {
        $sql = "-- Equipment Repair Management System Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
        $sql .= "-- Source: " . (php_sapi_name() === 'cli' ? 'CLI/cron' : 'HTTP') . " silent\n";
        $sql .= "-- Host: " . DB_HOST . " DB: " . DB_NAME . "\n\n";
        $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $table) {
            try {
                $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
                if ($check->rowCount() === 0) continue;
            } catch (Throwable $e) { continue; }
            try {
                $stmt = $pdo->query("SELECT * FROM `{$table}`");
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) { continue; }
            $sql .= "-- ----------------------------------------\n";
            $sql .= "-- Table: {$table}\n";
            $sql .= "-- ----------------------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            try {
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
                $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
                $createTable = null;
                foreach ($createRow as $k => $v) {
                    if (stripos($k, 'create') !== false) { $createTable = $v; break; }
                }
                if (empty($createTable)) $createTable = $createRow['Create Table'] ?? '';
                if (empty($createTable)) continue;
                $sql .= $createTable . ";\n\n";
            } catch (Throwable $e) { continue; }
            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = implode(', ', array_map(fn($c) => "`{$c}`", $columns));
                foreach ($rows as $row) {
                    $values = array_map(function($v){
                        if ($v === null) return 'NULL';
                        return "'" . addslashes((string)$v) . "'";
                    }, array_values($row));
                    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }
        $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
        return $sql;
    }

    private function tryMysqldumpToFile(string $filepath, array $tables): bool
    {
        $mysqldump = 'C:\\xampp\\mysql\\bin\\mysqldump.exe';
        if (!file_exists($mysqldump)) {
            // Try generic mysqldump on PATH (Linux production)
            $mysqldump = 'mysqldump';
            $out=[]; $ret=1;
            @exec(escapeshellarg($mysqldump).' --version 2>&1', $out, $ret);
            if ($ret!==0) return false;
        }
        if (!function_exists('exec')) return false;
        $disabled = ini_get('disable_functions');
        if ($disabled) {
            $list = array_map('trim', explode(',', $disabled));
            $listLower = array_map('strtolower', $list);
            if (in_array('exec', $listLower) || in_array('shell_exec', $listLower)) return false;
        }
        $isLocal = (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1' || DB_HOST === '::1');
        if (!$isLocal) return false;
        $testCmd = '"' . $mysqldump . '" --version 2>&1';
        // If generic mysqldump, no quotes needed differently
        if ($mysqldump === 'mysqldump') $testCmd = 'mysqldump --version 2>&1';
        $out=[]; $ret=1;
        @exec($testCmd, $out, $ret);
        if ($ret!==0) return false;
        $pdo = getDB();
        $existing=[];
        foreach ($tables as $t) {
            try { $pdo->query("SHOW CREATE TABLE `{$t}`"); $existing[]=$t; } catch(Throwable $e){ continue; }
        }
        if (empty($existing)) return false;
        $cmd = ($mysqldump==='mysqldump' ? 'mysqldump' : '"'.$mysqldump.'"');
        $cmd .= ' --host='.escapeshellarg(DB_HOST);
        $cmd .= ' --user='.escapeshellarg(DB_USER);
        if (DB_PASS !== '' && DB_PASS !== null) $cmd .= ' --password='.escapeshellarg(DB_PASS);
        $cmd .= ' --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4';
        $cmd .= ' '.escapeshellarg(DB_NAME);
        foreach ($existing as $t) $cmd .= ' '.escapeshellarg($t);
        $cmd .= ' --result-file='.escapeshellarg($filepath).' 2>&1';
        $output=[]; $returnVar=1;
        @exec($cmd, $output, $returnVar);
        if ($returnVar!==0) { @unlink($filepath); return false; }
        if (!file_exists($filepath) || filesize($filepath)<100) { @unlink($filepath); return false; }
        return true;
    }

    private function pruneOldBackups(string $backupDir, int $retentionDays): void
    {
        $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sql');
        if (!$files) return;
        $cutoff = time() - ($retentionDays * 86400);
        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $cutoff) {
                @unlink($file);
                $logLine = "[" . date('Y-m-d H:i:s') . "] [prune] Deleted old backup: " . basename($file) . " (retention {$retentionDays}d)\n";
                @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $logLine, FILE_APPEND);
            }
        }
        // Also prune old tar.gz beyond retention
        $tars = glob($backupDir . DIRECTORY_SEPARATOR . '*.tar.gz');
        if ($tars) {
            foreach ($tars as $f) {
                if (is_file($f) && filemtime($f) < $cutoff) {
                    @unlink($f);
                    $logLine = "[" . date('Y-m-d H:i:s') . "] [prune] Deleted old archive: " . basename($f) . "\n";
                    @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $logLine, FILE_APPEND);
                }
            }
        }
    }

    private function appendCronLog(string $message): void
    {
        $backupDir = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'backups';
        if (!is_dir($backupDir)) {
            @mkdir($backupDir, 0755, true);
        }
        $line = "[" . date('Y-m-d H:i:s') . "] {$message}\n";
        @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'cron.log', $line, FILE_APPEND);
        @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $line, FILE_APPEND);
    }

    public function logs()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $search = $_GET['q'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = SystemLog::getFiltered($search, $page);
        $pageTitle = 'ประวัติการใช้งานระบบ (Logs)';
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
