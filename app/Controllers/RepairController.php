<?php
/**
 * Repair Controller
 * จัดการแจ้งซ่อม — list, submit, mine, detail
 */
class RepairController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
            ? (int) $_GET['per_page'] : 20;

        $result = Repair::getFiltered($status, $page, $perPage);
        $statusCounts = Repair::getStatusCounts();
        $pageTitle = 'รายการซ่อม';
        $viewPath = 'repair/index';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function submit()
    {
        $this->requireLogin();
        $role = getCurrentRole();

        if (!in_array($role, ['teacher', 'student'])) {
            ErrorHandler::page403();
        }

        $pageTitle = 'ส่งรายการแจ้งซ่อมบำรุงครุภัณฑ์';
        $viewPath = 'repair/submit';
        $equipment = Equipment::getAvailableWithRoom();
        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $equipmentId = $_POST['equipment_id'] ?? null;
            $issue = trim($_POST['issue'] ?? '');
            if (empty($equipmentId)) $errors[] = 'กรุณาเลือกครุภัณฑ์';
            if (empty($issue)) $errors[] = 'กรุณากรอกรายละเอียดปัญหา';

            if (empty($errors)) {
                $repairId = Repair::createRepair($equipmentId, getCurrentUserId(), $issue);

                // Handle image uploads
                if (!empty($_FILES['images']['name'][0])) {
                    foreach ($_FILES['images']['name'] as $key => $name) {
                        if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                            $file = [
                                'name' => $name,
                                'type' => $_FILES['images']['type'][$key],
                                'tmp_name' => $_FILES['images']['tmp_name'][$key],
                                'size' => $_FILES['images']['size'][$key],
                            ];
                            $result = uploadImage($file, 'repair');
                            if ($result['success']) {
                                Repair::addImage($repairId, $result['path']);
                            }
                        }
                    }
                }

                logActivity(getCurrentUserId(), 'Submit Repair', 'แจ้งซ่อม ID: ' . $repairId);
                $this->flash('success', 'แจ้งซ่อมสำเร็จ');
                $this->redirect(SITE_URL . '/repairs/mine');
            }
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function mine()
    {
        $this->requireLogin();
        $role = getCurrentRole();

        if (!in_array($role, ['teacher', 'student'])) {
            ErrorHandler::page403();
        }

        $userId = getCurrentUserId();
        $repairs = Repair::getByUser($userId);
        $pageTitle = 'รายการซ่อมของฉัน';
        $viewPath = 'repair/mine';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function detail($id)
    {
        $this->requireLogin();
        $role = getCurrentRole();
        $userId = getCurrentUserId();

        if (in_array($role, ['admin', 'staff'])) {
            $repair = Repair::getDetail($id);
        } else {
            $repair = Repair::getDetailForUser($id, $userId);
        }

        if (!$repair) ErrorHandler::page404();

        $images = Repair::getImages($id);
        $pageTitle = 'รายละเอียดการซ่อม';
        $viewPath = 'repair/detail';

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($role, ['admin', 'staff'])) {
            $this->validateCsrf();

            $newStatus = $_POST['status'] ?? null;
            $validStatuses = ['pending', 'in_progress', 'completed', 'cannot_fix'];

            if ($newStatus && in_array($newStatus, $validStatuses)) {
                Repair::updateStatus($id, $newStatus);
                logActivity($userId, 'Update Repair Status', 'เปลี่ยนสถานะซ่อม ID: ' . $id . ' → ' . $newStatus);
                $this->flash('success', 'อัปเดตสถานะสำเร็จ');
            } else {
                $this->flash('danger', 'สถานะไม่ถูกต้อง');
            }

            $this->redirect(SITE_URL . '/repairs/' . $id);
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }
}
