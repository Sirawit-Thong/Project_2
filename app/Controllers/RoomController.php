<?php
/**
 * Room Controller
 * จัดการห้อง/สถานที่ — CRUD
 */
class RoomController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pageTitle = 'จัดการห้อง/สถานที่';
        $viewPath = 'crud/rooms';
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPageOptions = [10, 20, 50, 100];
        $perPage = isset($_GET['per_page']) && in_array((int) $_GET['per_page'], $perPageOptions)
            ? (int) $_GET['per_page']
            : 20;

        $result = Room::getFiltered($page, $perPage);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $action = $_POST['action'] ?? '';
            $id = $_POST['id'] ?? null;

            if ($action === 'save') {
                $name = trim($_POST['name'] ?? '');

                if (empty($name)) {
                    $this->flash('danger', 'กรุณากรอกชื่อห้อง');
                    $this->redirect(SITE_URL . '/rooms');
                }

                if (!empty($id) && $id !== '0') {
                    if (Room::isNameTaken($name, $id)) {
                        $this->flash('danger', 'ชื่อห้องนี้มีในระบบแล้ว');
                        $this->redirect(SITE_URL . '/rooms');
                    }
                    Room::update($id, ['name' => $name]);
                    logActivity(getCurrentUserId(), 'แก้ไขห้อง', 'แก้ไขห้อง: ' . $name);
                } else {
                    if (Room::isNameTaken($name)) {
                        $this->flash('danger', 'ชื่อห้องนี้มีในระบบแล้ว');
                        $this->redirect(SITE_URL . '/rooms');
                    }
                    Room::create(['name' => $name]);
                    logActivity(getCurrentUserId(), 'เพิ่มห้อง', 'เพิ่มห้อง: ' . $name);
                }
                $this->flash('success', 'บันทึกสำเร็จ');
                $this->redirect(SITE_URL . '/rooms');
            }
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);
        $this->validateCsrf();

        if (Room::equipmentCount($id) > 0) {
            $this->flash('danger', 'ไม่สามารถลบได้ มีครุภัณฑ์ในห้องนี้');
            $this->redirect(SITE_URL . '/rooms');
        }

        Room::delete($id);
        logActivity(getCurrentUserId(), 'ลบห้อง', 'ลบห้อง รหัส: ' . $id);
        $this->flash('success', 'ลบห้องสำเร็จ');
        $this->redirect(SITE_URL . '/rooms');
    }
}
