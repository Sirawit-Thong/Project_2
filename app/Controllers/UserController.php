<?php
/**
 * User Controller
 * จัดการผู้ใช้ — list, add, edit, pending, delete
 */
class UserController extends Controller
{
    public function index()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $search = $_GET['search'] ?? '';
        $role = $_GET['role'] ?? '';
        $status = $_GET['status'] ?? '';
        $page = max(1, (int)($_GET['page'] ?? 1));

        $result = User::getFiltered($search, $role, $status, $page);
        $pageTitle = 'จัดการผู้ใช้';
        $viewPath = 'user/index';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function add()
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $pageTitle = 'เพิ่มผู้ใช้';
        $viewPath = 'user/form';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $data = $this->inputs([
                'sid' => null, 'firstname' => '', 'lastname' => '',
                'email' => '', 'password' => '', 'class' => null,
                'role' => 'student', 'status' => 'approved',
            ]);

            $errors = [];
            if (empty($data['firstname'])) $errors[] = 'กรุณากรอกชื่อ';
            if (empty($data['lastname'])) $errors[] = 'กรุณากรอกนามสกุล';
            if (empty($data['email'])) $errors[] = 'กรุณากรอกอีเมล';
            if (empty($data['password'])) $errors[] = 'กรุณากรอกรหัสผ่าน';

            if (!empty($data['email']) && User::isEmailTaken($data['email'])) {
                $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
            }
            if (!empty($data['sid']) && User::isSidTaken($data['sid'])) {
                $errors[] = 'รหัสนักศึกษานี้ถูกใช้งานแล้ว';
            }

            if (empty($errors)) {
                User::createWithPassword($data);
                logActivity(getCurrentUserId(), 'Add User', 'เพิ่มผู้ใช้: ' . $data['email']);
                $this->flash('success', 'เพิ่มผู้ใช้สำเร็จ');
                $this->redirect(SITE_URL . '/users');
            }

            $this->flash('danger', implode('<br>', $errors));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function edit($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        $user = User::find($id);
        if (!$user) ErrorHandler::page404();

        $pageTitle = 'แก้ไขผู้ใช้';
        $viewPath = 'user/form';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $data = $this->inputs([
                'sid' => null, 'firstname' => '', 'lastname' => '',
                'email' => '', 'class' => null,
                'role' => 'student', 'status' => 'approved',
            ]);
            $password = $_POST['password'] ?? null;

            $errors = [];
            if (empty($data['firstname'])) $errors[] = 'กรุณากรอกชื่อ';
            if (empty($data['lastname'])) $errors[] = 'กรุณากรอกนามสกุล';
            if (empty($data['email'])) $errors[] = 'กรุณากรอกอีเมล';
            if (!empty($data['email']) && User::isEmailTaken($data['email'], $id)) {
                $errors[] = 'อีเมลนี้ถูกใช้งานแล้ว';
            }
            if (!empty($data['sid']) && User::isSidTaken($data['sid'], $id)) {
                $errors[] = 'รหัสนักศึกษานี้ถูกใช้งานแล้ว';
            }

            if (empty($errors)) {
                User::updateWithPassword($id, $data, $password);
                logActivity(getCurrentUserId(), 'Edit User', 'แก้ไขผู้ใช้: ' . $data['email']);
                $this->flash('success', 'แก้ไขผู้ใช้สำเร็จ');
                $this->redirect(SITE_URL . '/users');
            }

            $this->flash('danger', implode('<br>', $errors));
        }

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function pending()
    {
        $this->requireLogin();
        $this->authorize(['admin', 'staff']);

        $pendingUsers = User::getPending();
        $pageTitle = 'รออนุมัติผู้ใช้';
        $viewPath = 'user/pending';

        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function approve($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        User::approve($id);
        logActivity(getCurrentUserId(), 'Approve User', 'อนุมัติผู้ใช้ ID: ' . $id);
        $this->flash('success', 'อนุมัติผู้ใช้สำเร็จ');
        $this->redirect(SITE_URL . '/users/pending');
    }

    public function reject($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        User::rejectPending($id);
        logActivity(getCurrentUserId(), 'Reject User', 'ปฏิเสธผู้ใช้ ID: ' . $id);
        $this->flash('success', 'ปฏิเสธผู้ใช้สำเร็จ');
        $this->redirect(SITE_URL . '/users/pending');
    }

    public function delete($id)
    {
        $this->requireLogin();
        $this->authorize(['admin']);

        if ($id == getCurrentUserId()) {
            $this->flash('danger', 'ไม่สามารถลบตัวเองได้');
            $this->redirect(SITE_URL . '/users');
        }

        User::delete($id);
        logActivity(getCurrentUserId(), 'Delete User', 'ลบผู้ใช้ ID: ' . $id);
        $this->flash('success', 'ลบผู้ใช้สำเร็จ');
        $this->redirect(SITE_URL . '/users');
    }
}
