<?php
/**
 * Set Controller
 * จัดการชุดครุภัณฑ์ — CRUD
 */
class SetController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'จัดการชุดครุภัณฑ์';
        $viewPath = 'crud/sets';
        $deptFilter = $_GET['dept_id'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = SetModel::getFiltered($deptFilter, $page);
        $departments = Department::getAll();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $action = $_POST['action'] ?? '';
            $id = $_POST['id'] ?? null;

            if ($action === 'save') {
                $data = [
                    'dept_id' => $_POST['dept_id'] ?? null,
                    'name' => trim($_POST['name'] ?? ''),
                    'year' => $_POST['year'] ?? null,
                    'price' => (float)($_POST['price'] ?? 0),
                    'price_remark' => trim($_POST['price_remark'] ?? ''),
                    'remark' => trim($_POST['remark'] ?? ''),
                ];

                if (empty($data['name'])) {
                    $this->flash('danger', 'กรุณากรอกชื่อชุดครุภัณฑ์');
                    $this->redirect(SITE_URL . '/sets');
                }

                if (!empty($id) && $id !== '0') {
                    SetModel::update($id, $data);
                    logActivity(getCurrentUserId(), 'Edit Set', 'แก้ไขชุดครุภัณฑ์: ' . $data['name']);
                } else {
                    SetModel::create($data);
                    logActivity(getCurrentUserId(), 'Add Set', 'เพิ่มชุดครุภัณฑ์: ' . $data['name']);
                }
                $this->flash('success', 'บันทึกสำเร็จ');
                $this->redirect(SITE_URL . '/sets');
            }
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        if (SetModel::childItemCount($id) > 0) {
            $this->flash('danger', 'ไม่สามารถลบได้ มีรายการครุภัณฑ์ที่เกี่ยวข้อง');
            $this->redirect(SITE_URL . '/sets');
        }

        SetModel::delete($id);
        logActivity(getCurrentUserId(), 'Delete Set', 'ลบชุดครุภัณฑ์ ID: ' . $id);
        $this->flash('success', 'ลบชุดครุภัณฑ์สำเร็จ');
        $this->redirect(SITE_URL . '/sets');
    }
}
