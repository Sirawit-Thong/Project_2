<?php
/**
 * Standalone Cron Backup Script
 * เรียกจาก task scheduler / cron:
 *   php scripts/cron_backup.php --token=XXX
 *   php scripts/cron_backup.php --token XXX
 *   php scripts/cron_backup.php token=XXX
 *
 * - ตรวจสอบ token จาก env BACKUP_CRON_TOKEN หรือ constant ถ้าไม่มีให้ bypass สำหรับ CLI
 * - ลอง mysqldump ก่อน ถ้าไม่พร้อม fallback เป็น PHP dump (SELECT + SHOW CREATE TABLE)
 * - ส่ง log ไป system_logs และไฟล์ backups/cron.log + backups/backup.log
 * - ลบไฟล์เก่าเกิน BACKUP_RETENTION_DAYS (default 7 วัน)
 */

// ---------------------------------------------------------------------
// Bootstrap — load DB config & helpers minimal
// ---------------------------------------------------------------------
$projectRoot = dirname(__DIR__);
$configFile = $projectRoot . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'database.php';
if (file_exists($configFile)) {
    require_once $configFile;
}
// env() already defined in database.php; define fallback if not
if (!function_exists('env')) {
    function env($key, $default = null) {
        $v = getenv($key);
        if ($v === false || $v === '') $v = $_SERVER[$key] ?? null;
        return ($v !== null && $v !== '') ? $v : $default;
    }
}

// Try to load SystemLog if available (for DB logging)
$modelFile = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Core' . DIRECTORY_SEPARATOR . 'Model.php';
if (file_exists($modelFile)) require_once $modelFile;
$sysLogFile = $projectRoot . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . 'SystemLog.php';
if (file_exists($sysLogFile)) require_once $sysLogFile;

// Ensure backups dir
$backupDir = $projectRoot . DIRECTORY_SEPARATOR . 'backups';
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
$htaccess = $backupDir . DIRECTORY_SEPARATOR . '.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "Require all denied\n");
}

// ---------------------------------------------------------------------
// Token handling
// ---------------------------------------------------------------------
function parseCliToken(array $argv): ?string {
    $cnt = count($argv);
    for ($i = 1; $i < $cnt; $i++) {
        $arg = $argv[$i];
        if (strpos($arg, '--token=') === 0) return substr($arg, 8);
        if ($arg === '--token' && isset($argv[$i+1])) return $argv[$i+1];
        if (strpos($arg, 'token=') === 0) return substr($arg, 6);
        // support --token:XXX
        if (strpos($arg, '--token:') === 0) return substr($arg, 8);
    }
    return null;
}

$providedToken = parseCliToken($argv ?? []);
if ($providedToken === null) {
    // Also check GET/POST if called via HTTP (e.g., curl web)
    $providedToken = $_GET['token'] ?? $_POST['token'] ?? $_SERVER['HTTP_X_CRON_TOKEN'] ?? null;
    if ($providedToken === null && !empty($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.+)/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
        $providedToken = trim($m[1]);
    }
}

$expectedToken = env('BACKUP_CRON_TOKEN', null);
if (($expectedToken === null || $expectedToken === '') && defined('BACKUP_CRON_TOKEN')) {
    $expectedToken = BACKUP_CRON_TOKEN;
}
if (($expectedToken === null || $expectedToken === '') && getenv('BACKUP_CRON_TOKEN')) {
    $expectedToken = getenv('BACKUP_CRON_TOKEN');
}

$isCli = php_sapi_name() === 'cli';

function cronLog(string $msg): void {
    global $backupDir;
    $line = "[" . date('Y-m-d H:i:s') . "] {$msg}\n";
    @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'cron.log', $line, FILE_APPEND);
    @file_put_contents($backupDir . DIRECTORY_SEPARATOR . 'backup.log', $line, FILE_APPEND);
    // Also echo for CLI
    if (php_sapi_name() === 'cli') {
        echo $line;
    }
}

function logToDb($userId, string $action, string $details): void {
    try {
        if (class_exists('SystemLog') && method_exists('SystemLog', 'log')) {
            SystemLog::log($userId, $action, $details);
        } elseif (function_exists('getDB')) {
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            // cli may be 127.0.0.1
            $stmt->execute([$userId, $action, $details, $ip]);
        }
    } catch (Throwable $e) {
        // DB logging is best-effort
        cronLog("[DB log failed] " . $e->getMessage());
    }
}

// Verification
if ($expectedToken !== null && $expectedToken !== '') {
    $providedToken = (string)($providedToken ?? '');
    if (!hash_equals((string)$expectedToken, $providedToken)) {
        cronLog("[cron_backup] FAILED: invalid token");
        logToDb(null, 'Backup', 'สำรองข้อมูลล้มเหลว (cron): token ไม่ถูกต้อง');
        fwrite(STDERR, "Forbidden: invalid backup token\n");
        exit(1);
    }
} else {
    // No token configured — allow CLI bypass, but if HTTP, require token or warn
    if (!$isCli) {
        cronLog("[cron_backup] FAILED: no token configured but HTTP access denied (require CLI or configure BACKUP_CRON_TOKEN)");
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success'=>false,'error'=>'Forbidden: no token configured, CLI only'], JSON_UNESCAPED_UNICODE);
        exit(1);
    }
    cronLog("[cron_backup] No token configured — allowing CLI bypass");
}

// ---------------------------------------------------------------------
// Helpers: tables, build SQL, prune, mysqldump
// ---------------------------------------------------------------------
function getBackupTables(): array {
    // เรียงตาม FK dependency เหมือน AdminController::$backupTables เพื่อกู้คืนไม่ติด FK
    return ['dept', 'users', 'asset_categories', 'depreciation_settings', 'rooms', 'sets', 'items', 'equipment', 'equipment_img', 'room_managers', 'repair', 'repair_img', 'satisfaction_surveys', 'system_logs'];
}

function buildBackupSqlPhp(PDO $pdo, array $tables): string {
    $sql = "-- Equipment Repair Management System Backup (standalone cron)\n";
    $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n";
    $sql .= "-- Host: " . DB_HOST . " DB: " . DB_NAME . "\n";
    $sql .= "-- Source: scripts/cron_backup.php (PHP fallback)\n\n";
    $sql .= "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        try {
            $check = $pdo->query("SHOW TABLES LIKE '{$table}'");
            if ($check->rowCount() === 0) continue;
        } catch (Throwable $e) { continue; }

        try {
            $stmt = $pdo->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            $sql .= "-- Table {$table}: error reading data: " . $e->getMessage() . "\n";
            continue;
        }

        $sql .= "-- ----------------------------------------\n";
        $sql .= "-- Table: {$table}\n";
        $sql .= "-- ----------------------------------------\n";
        $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
        try {
            $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            $createTable = $createRow['Create Table'] ?? $createRow['create table'] ?? '';
            $sql .= $createTable . ";\n\n";
        } catch (Throwable $e) {
            $sql .= "-- Could not get CREATE TABLE for {$table}: " . $e->getMessage() . "\n\n";
            continue;
        }

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

function pruneOldBackups(string $dir, int $retentionDays): int {
    $count = 0;
    $files = glob($dir . DIRECTORY_SEPARATOR . 'backup_*.sql');
    if (!$files) return 0;
    $cutoff = time() - ($retentionDays * 86400);
    foreach ($files as $f) {
        if (is_file($f) && @filemtime($f) < $cutoff) {
            if (@unlink($f)) {
                $count++;
                cronLog("[prune] Deleted old backup: " . basename($f) . " (retention {$retentionDays}d)");
            }
        }
    }
    // Also prune tar gz if any
    $tars = glob($dir . DIRECTORY_SEPARATOR . '*.tar.gz');
    if ($tars) {
        foreach ($tars as $f) {
            if (is_file($f) && @filemtime($f) < $cutoff) {
                if (@unlink($f)) {
                    $count++;
                    cronLog("[prune] Deleted old tar: " . basename($f));
                }
            }
        }
    }
    return $count;
}

function tryMysqldump(string $filepath): bool {
    if (!function_exists('exec')) {
        cronLog("[mysqldump] exec() disabled — skip");
        return false;
    }
    // Check if mysqldump exists
    $candidates = ['mysqldump', 'C:\\xampp\\mysql\\bin\\mysqldump.exe', '/usr/bin/mysqldump', '/usr/local/bin/mysqldump'];
    $found = false;
    $mysqldumpBin = 'mysqldump';
    foreach ($candidates as $cand) {
        $out = [];
        $ret = 1;
        @exec(escapeshellarg($cand) . ' --version 2>&1', $out, $ret);
        if ($ret === 0) {
            $found = true;
            $mysqldumpBin = $cand;
            cronLog("[mysqldump] Found: {$cand} => " . implode(' ', $out));
            break;
        }
    }
    if (!$found) {
        // Try `which` / `where`
        $out = []; $ret = 1;
        @exec('which mysqldump 2>&1', $out, $ret);
        if ($ret === 0 && !empty($out[0]) && file_exists(trim($out[0]))) {
            $found = true;
            $mysqldumpBin = trim($out[0]);
            cronLog("[mysqldump] Found via which: {$mysqldumpBin}");
        } else {
            @exec('where mysqldump 2>&1', $out, $ret);
            if ($ret === 0 && !empty($out[0])) {
                $found = true;
                $mysqldumpBin = trim($out[0]);
                cronLog("[mysqldump] Found via where: {$mysqldumpBin}");
            }
        }
    }
    if (!$found) {
        cronLog("[mysqldump] Not available — fallback to PHP dump");
        return false;
    }

    // Build command — escape credentials
    // Use --single-transaction for InnoDB consistency, --routines --events --triggers if needed? keep simple.
    $host = DB_HOST;
    $user = DB_USER;
    $pass = DB_PASS;
    $dbname = DB_NAME;
    // Handle host:port
    $port = '';
    if (strpos($host, ':') !== false) {
        [$host, $port] = explode(':', $host, 2);
        $port = ' --port=' . escapeshellarg($port);
    }
    $cmd = escapeshellarg($mysqldumpBin)
        . ' --host=' . escapeshellarg($host)
        . $port
        . ' --user=' . escapeshellarg($user)
        . ($pass !== '' ? ' --password=' . escapeshellarg($pass) : '')
        . ' --single-transaction --quick --skip-lock-tables'
        . ' ' . escapeshellarg($dbname)
        . ' --result-file=' . escapeshellarg($filepath)
        . ' 2>&1';
    cronLog("[mysqldump] Executing: " . preg_replace('/--password=\S+/', '--password=***', $cmd));
    $output = [];
    $returnVar = 1;
    @exec($cmd, $output, $returnVar);
    if ($returnVar !== 0) {
        cronLog("[mysqldump] Failed (exit {$returnVar}): " . implode("\n", $output));
        @unlink($filepath);
        return false;
    }
    if (!file_exists($filepath) || filesize($filepath) < 100) {
        cronLog("[mysqldump] Output too small or missing — fallback");
        @unlink($filepath);
        return false;
    }
    cronLog("[mysqldump] Success: " . basename($filepath) . " (" . number_format(filesize($filepath)) . " bytes)");
    return true;
}

// ---------------------------------------------------------------------
// Main backup execution
// ---------------------------------------------------------------------
$timestamp = date('Y-m-d_His');
$filename = "backup_{$timestamp}_cron.sql";
$filepath = $backupDir . DIRECTORY_SEPARATOR . $filename;

cronLog("[cron_backup] Starting backup to {$filename}");

$success = false;
$bytes = 0;
$method = 'php';

try {
    $pdo = getDB();
} catch (Throwable $e) {
    cronLog("[cron_backup] DB connection failed: " . $e->getMessage());
    logToDb(null, 'Backup', 'สำรองข้อมูลล้มเหลว (cron): ต่อ DB ไม่ได้ ' . $e->getMessage());
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

// Try mysqldump first, fallback to PHP
if (tryMysqldump($filepath)) {
    $success = true;
    $bytes = filesize($filepath);
    $method = 'mysqldump';
} else {
    cronLog("[cron_backup] Falling back to PHP dump");
    try {
        $tables = getBackupTables();
        $sql = buildBackupSqlPhp($pdo, $tables);
        $bytes = file_put_contents($filepath, $sql);
        if ($bytes === false) throw new RuntimeException("Failed to write {$filepath}");
        $success = true;
        $method = 'php';
        cronLog("[cron_backup] PHP dump success: {$filename} (" . number_format($bytes) . " bytes)");
    } catch (Throwable $e) {
        cronLog("[cron_backup] PHP dump failed: " . $e->getMessage());
        @unlink($filepath);
        logToDb(null, 'Backup', 'สำรองข้อมูลล้มเหลว (cron PHP): ' . $e->getMessage());
        fwrite(STDERR, "Backup failed: " . $e->getMessage() . "\n");
        exit(1);
    }
}

// Retention
$retentionDays = 7;
$envRetention = env('BACKUP_RETENTION_DAYS', env('BACKUP_RETENTION', null));
if ($envRetention !== null && $envRetention !== '') $retentionDays = (int)$envRetention;
if (defined('BACKUP_RETENTION_DAYS')) $retentionDays = (int)BACKUP_RETENTION_DAYS;
if ($retentionDays < 1) $retentionDays = 7;
cronLog("[cron_backup] Retention: {$retentionDays} days");
pruneOldBackups($backupDir, $retentionDays);

// Log to system_logs
try {
    $details = "สำรองข้อมูลอัตโนมัติ (cron/{$method}): {$filename} (" . number_format($bytes) . " bytes)";
    logToDb(null, 'Backup', $details);
} catch (Throwable $ignored) {}

// File log final
cronLog("[cron_backup] SUCCESS {$filename} via {$method} (" . number_format($bytes) . " bytes) retention={$retentionDays}d");

// Exit code 0
exit(0);
