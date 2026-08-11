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
}
