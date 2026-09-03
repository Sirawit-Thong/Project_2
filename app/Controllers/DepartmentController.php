<?php
/**
 * Department Controller
 * จัดการสาขาวิชา — CRUD
 */
class DepartmentController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'จัดการสาขาวิชา';
        $viewPath = 'crud/departments';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $action = $_POST['action'] ?? '';
            $id = $_POST['id'] ?? null;

            if ($action === 'save') {
                $name = trim($_POST['name'] ?? '');
                if (empty($name)) {
                    $this->flash('danger', 'กรุณากรอกชื่อสาขาวิชา');
                    $this->redirect(SITE_URL . '/departments');
                }

                if (!empty($id) && $id !== '0') {
                    if (Department::isNameTaken($name, $id)) {
                        $this->flash('danger', 'ชื่อสาขาวิชานี้มีในระบบแล้ว');
                        $this->redirect(SITE_URL . '/departments');
                    }
                    Department::update($id, ['name' => $name]);
                    logActivity(getCurrentUserId(), 'แก้ไขสาขาวิชา', 'แก้ไขสาขาวิชา: ' . $name);
                } else {
                    if (Department::isNameTaken($name)) {
                        $this->flash('danger', 'ชื่อสาขาวิชานี้มีในระบบแล้ว');
                        $this->redirect(SITE_URL . '/departments');
                    }
                    Department::create(['name' => $name]);
                    logActivity(getCurrentUserId(), 'เพิ่มสาขาวิชา', 'เพิ่มสาขาวิชา: ' . $name);
                }
                $this->flash('success', 'บันทึกสำเร็จ');
                $this->redirect(SITE_URL . '/departments');
            }
        }

        $departments = Department::getAllWithCounts();
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);
        $this->validateCsrf();

        if (Department::childSetCount($id) > 0) {
            $this->flash('danger', 'ไม่สามารถลบได้ มีชุดครุภัณฑ์ที่เกี่ยวข้อง');
            $this->redirect(SITE_URL . '/departments');
        }

        Department::delete($id);
        logActivity(getCurrentUserId(), 'ลบสาขาวิชา', 'ลบสาขาวิชา รหัส: ' . $id);
        $this->flash('success', 'ลบสาขาวิชาสำเร็จ');
        $this->redirect(SITE_URL . '/departments');
    }
}
