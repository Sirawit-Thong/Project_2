<?php
/**
 * Admin Controller
 * จัดการระบบ — backup, logs, reports, export
 */
class AdminController extends Controller
{
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
                $this->downloadBackup();
            }
        }

        // Get table info for display
        $pdo = getDB();
        $tables = ['users', 'dept', 'sets', 'items', 'rooms', 'room_managers', 'equipment', 'equipment_img', 'repair', 'repair_img', 'system_logs'];
        $tableInfo = [];
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) FROM {$table}");
            $tableInfo[$table] = (int) $stmt->fetchColumn();
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function downloadBackup()
    {
        $pdo = getDB();
        $tables = ['users', 'dept', 'sets', 'items', 'rooms', 'room_managers', 'equipment', 'equipment_img', 'repair', 'repair_img', 'system_logs'];

        $sql = "-- Equipment Repair Management System Backup\n";
        $sql .= "-- Date: " . date('Y-m-d H:i:s') . "\n\n";

        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT * FROM {$table}");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $sql .= "-- Table: {$table}\n";
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";

            $createStmt = $pdo->query("SHOW CREATE TABLE {$table}");
            $createRow = $createStmt->fetch(PDO::FETCH_ASSOC);
            $sql .= $createRow['Create Table'] . ";\n\n";

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = implode(', ', array_map(function ($c) { return "`{$c}`"; }, $columns));

                foreach ($rows as $row) {
                    $values = array_map(function ($v) {
                        if ($v === null) return 'NULL';
                        return "'" . addslashes($v) . "'";
                    }, $row);
                    $sql .= "INSERT INTO `{$table}` ({$colList}) VALUES (" . implode(', ', $values) . ");\n";
                }
                $sql .= "\n";
            }
        }

        logActivity(getCurrentUserId(), 'Backup', 'ดาวน์โหลดสำรองข้อมูล');

        header('Content-Type: application/sql; charset=utf-8');
        header('Content-Disposition: attachment; filename="backup_' . date('Y-m-d_His') . '.sql"');
        header('Content-Length: ' . strlen($sql));
        echo $sql;
        exit;
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
