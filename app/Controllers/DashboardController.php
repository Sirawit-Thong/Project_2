<?php
/**
 * Dashboard Controller
 * หน้าแดชบอร์ด — ปรับตาม role
 */
class DashboardController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $role = getCurrentRole();

        switch ($role) {
            case 'admin':
                $this->adminDashboard();
                break;
            case 'staff':
                $this->staffDashboard();
                break;
            case 'teacher':
                $this->teacherDashboard();
                break;
            case 'student':
                $this->studentDashboard();
                break;
            default:
                $this->redirect(SITE_URL . '/login');
        }
    }

    private function adminDashboard()
    {
        $pageTitle = 'แดชบอร์ดผู้ดูแลระบบ';
        $viewPath = 'dashboard/admin';
        $userId = getCurrentUserId();

        $totalEquipment = Equipment::totalCount();
        $availableCount = Equipment::countByStatus('available');
        $repairCount = Repair::pendingCount();
        $totalValue = Equipment::getTotalValue();
        $totalRepairs = Repair::totalCount();
        $inProgressRepairs = Repair::countByStatus('in_progress');
        $totalUsers = User::totalCount();
        $statusCounts = Equipment::getStatusCounts();
        $monthlyStats = Repair::getMonthlyStats(6);
        $deptStats = Equipment::countByDepartment();
        $recentRepairs = Repair::getRecent(5);
        $pendingUsers = User::pendingCount();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function staffDashboard()
    {
        $pageTitle = 'แดชบอร์ดเจ้าหน้าที่';
        $viewPath = 'dashboard/staff';
        $totalEquipment = Equipment::totalCount();
        $repairPending = Repair::countByStatus('pending');
        $repairInProgress = Repair::countByStatus('in_progress');
        $recentRepairs = Repair::getRecent(5);
        $pendingUsers = User::pendingCount();

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function teacherDashboard()
    {
        $pageTitle = 'แดชบอร์ดอาจารย์';
        $viewPath = 'dashboard/teacher';
        $userId = getCurrentUserId();
        $totalRepairs = Repair::countByUser($userId);
        $pendingRepairs = Repair::pendingCountByUser($userId);
        $recentRepairs = Repair::getRecentByUser($userId, 5);
        $managedRooms = RoomManager::getManagedRoomCount($userId);

        require __DIR__ . '/../Views/layouts/main.php';
    }

    private function studentDashboard()
    {
        $pageTitle = 'แดชบอร์ดนักศึกษา';
        $viewPath = 'dashboard/student';
        $userId = getCurrentUserId();
        $totalRepairs = Repair::countByUser($userId);
        $pendingRepairs = Repair::pendingCountByUser($userId);
        $recentRepairs = Repair::getRecentByUser($userId, 5);

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function teacherReport()
    {
        $this->requireLogin();
        requireRole('teacher');

        $userId = getCurrentUserId();
        $rooms = Equipment::getReportStatsForTeacher($userId);

        $totals = [
            'total_equipment' => 0,
            'available_count' => 0,
            'repair_count' => 0,
            'broken_count' => 0,
            'disposed_count' => 0,
            'total_value' => 0,
            'need_check_count' => 0,
        ];
        foreach ($rooms as $room) {
            foreach (array_keys($totals) as $key) {
                $totals[$key] += (int) $room[$key];
            }
        }

        $pageTitle = 'รายงานสรุปสถานะครุภัณฑ์ในสังกัด';
        $viewPath = 'dashboard/teacher_report';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function teacherExport()
    {
        $this->requireLogin();
        requireRole('teacher');

        $userId = getCurrentUserId();
        $room = $_GET['room'] ?? '';
        $equipment = Equipment::getForTeacherExport($userId, $room);

        foreach ($equipment as &$row) {
            if (!empty($row['eq_price_remark'])) {
                $row['price_remark'] = $row['eq_price_remark'] . ' (เฉพาะชิ้น)';
            } elseif (!empty($row['item_price_remark'])) {
                $row['price_remark'] = $row['item_price_remark'] . ' (ทั้งรายการ)';
            } elseif (!empty($row['set_price_remark'])) {
                $row['price_remark'] = $row['set_price_remark'] . ' (ทั้งชุด)';
            } else {
                $row['price_remark'] = '';
            }
        }
        unset($row);

        logActivity($userId, 'Export', 'ส่งออกข้อมูลครุภัณฑ์: ' . ($room ?: 'ทั้งหมด'));

        $filename = 'equipment_' . ($room ? $room . '_' : '') . date('Y-m-d') . '.xls';
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        echo "\xEF\xBB\xBF";

        $statuses = [
            'available' => 'พร้อมใช้งาน',
            'repair' => 'ส่งซ่อม',
            'broken' => 'ซ่อมไม่ได้',
            'pending_disposal' => 'รอจำหน่ายออก',
            'disposed' => 'จำหน่ายออก',
        ];
        ?>
<!DOCTYPE html>
<html lang="th">
<head><meta charset="UTF-8"></head>
<body>
    <table border="1">
        <thead>
            <tr style="background-color: #0d6efd; color: white; font-weight: bold;">
                <th>ลำดับ</th>
                <th>รหัสครุภัณฑ์</th>
                <th>ชื่อรายการครุภัณฑ์</th>
                <th>ยี่ห้อ</th>
                <th>รุ่น</th>
                <th>ห้อง/สถานที่</th>
                <th>สถานะ</th>
                <th>ราคา</th>
                <th>วันที่ได้รับ/จัดซื้อ</th>
                <th>ตรวจสอบล่าสุด</th>
                <th>ผู้รับผิดชอบดูแล</th>
                <th>หมายเหตุ</th>
                <th>หมายเหตุงบ/ราคา</th>
            </tr>
        </thead>
        <tbody>
            <?php $n = 1; ?>
            <?php foreach ($equipment as $eq): ?>
                <tr>
                    <td><?= $n++ ?></td>
                    <td><?= htmlspecialchars($eq['code'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['item_name']) ?></td>
                    <td><?= htmlspecialchars($eq['brand'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['model'] ?? '-') ?></td>
                    <td><?= htmlspecialchars($eq['room_name'] ?? '-') ?></td>
                    <td><?= $statuses[$eq['status']] ?? $eq['status'] ?></td>
                    <td style="text-align: right;"><?= $eq['price'] ? number_format($eq['price'], 2) : '-' ?></td>
                    <td><?= $eq['purchase_date'] ?? '-' ?></td>
                    <td><?= $eq['check_date'] ?? '-' ?></td>
                    <td><?= !empty($eq['holder_firstname']) ? htmlspecialchars($eq['holder_firstname'] . ' ' . $eq['holder_lastname']) : '-' ?></td>
                    <td><?= htmlspecialchars($eq['remark'] ?? '') ?></td>
                    <td><?= htmlspecialchars($eq['price_remark'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background-color: #f8f9fa; font-weight: bold;">
                <td colspan="7">รวมทั้งหมด <?= count($equipment) ?> รายการ</td>
                <td style="text-align: right;"><?= number_format(array_sum(array_column($equipment, 'price')), 2) ?></td>
                <td colspan="5"></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
<?php
        exit;
    }
}
