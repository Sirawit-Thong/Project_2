<?php
/**
 * Auth Controller
 * จัดการเข้าสู่ระบบ, สมัครสมาชิก, โปรไฟล์, ออกจากระบบ
 */
class AuthController extends Controller
{
    public function login()
    {
        if (isLoggedIn()) {
            $this->redirectByRole();
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
            } else {
                $result = $this->doLogin($email, $password);

                if ($result['success']) {
                    $this->flash('success', 'เข้าสู่ระบบสำเร็จ ยินดีต้อนรับ ' . $_SESSION['user_name']);
                    $this->redirectByRole();
                } else {
                    $error = $result['error'];
                }
            }
        }

        $flash = getFlash();
        $viewPath = 'auth/login';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function registerStudent()
    {
        if (isLoggedIn()) {
            $this->redirectByRole();
        }

        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $sid = trim($_POST['sid'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';
            $class = trim($_POST['class'] ?? '');

            if (empty($sid)) {
                $errors[] = 'กรุณากรอกรหัสนักศึกษา';
            } elseif (!preg_match('/^\d{12}$/', $sid)) {
                $errors[] = 'รหัสนักศึกษาต้องเป็นตัวเลข 12 หลัก';
            }

            if (empty($email)) {
                $errors[] = 'กรุณากรอกอีเมล';
            } elseif (!isUniversityEmail($email)) {
                $errors[] = 'กรุณาใช้อีเมลมหาวิทยาลัย (@rmutsb.ac.th) เท่านั้น';
            }

            if (empty($firstname)) $errors[] = 'กรุณากรอกชื่อ';
            if (empty($lastname)) $errors[] = 'กรุณากรอกนามสกุล';

            if (empty($password)) {
                $errors[] = 'กรุณากรอกรหัสผ่าน';
            } elseif (strlen($password) < 8) {
                $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
            }

            if ($password !== $passwordConfirm) {
                $errors[] = 'รหัสผ่านไม่ตรงกัน';
            }

            if (empty($errors)) {
                $result = $this->doRegister([
                    'sid' => $sid,
                    'email' => $email,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'password' => $password,
                    'role' => 'student',
                    'class' => $class,
                ]);

                if ($result['success']) {
                    $success = true;
                } else {
                    $errors[] = $result['error'];
                }
            }
        }

        $viewPath = 'auth/register_student';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function registerTeacher()
    {
        if (isLoggedIn()) {
            $this->redirectByRole();
        }

        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $email = trim($_POST['email'] ?? '');
            $firstname = trim($_POST['firstname'] ?? '');
            $lastname = trim($_POST['lastname'] ?? '');
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            if (empty($email)) {
                $errors[] = 'กรุณากรอกอีเมล';
            } elseif (!isUniversityEmail($email)) {
                $errors[] = 'กรุณาใช้อีเมลมหาวิทยาลัย (@rmutsb.ac.th) เท่านั้น';
            }

            if (empty($firstname)) $errors[] = 'กรุณากรอกชื่อ';
            if (empty($lastname)) $errors[] = 'กรุณากรอกนามสกุล';

            if (empty($password)) {
                $errors[] = 'กรุณากรอกรหัสผ่าน';
            } elseif (strlen($password) < 8) {
                $errors[] = 'รหัสผ่านต้องมีอย่างน้อย 8 ตัวอักษร';
            }

            if ($password !== $passwordConfirm) {
                $errors[] = 'รหัสผ่านไม่ตรงกัน';
            }

            if (empty($errors)) {
                $result = $this->doRegister([
                    'sid' => null,
                    'email' => $email,
                    'firstname' => $firstname,
                    'lastname' => $lastname,
                    'password' => $password,
                    'role' => 'teacher',
                    'class' => null,
                ]);

                if ($result['success']) {
                    $success = true;
                } else {
                    $errors[] = $result['error'];
                }
            }
        }

        $viewPath = 'auth/register_teacher';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function profile()
    {
        $this->requireLogin();

        $userId = getCurrentUserId();
        $user = User::find($userId);
        if (!$user) {
            $this->flash('danger', 'ไม่พบข้อมูลผู้ใช้');
            $this->redirect(SITE_URL . '/login');
        }

        $errors = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();

            $action = $_POST['action'] ?? '';

            if ($action === 'change_password') {
                $currentPassword = $_POST['current_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($currentPassword)) {
                    $errors[] = 'กรุณากรอกรหัสผ่านปัจจุบัน';
                } elseif (!password_verify($currentPassword, $user['password'])) {
                    $errors[] = 'รหัสผ่านปัจจุบันไม่ถูกต้อง';
                }

                if (empty($newPassword)) {
                    $errors[] = 'กรุณากรอกรหัสผ่านใหม่';
                } elseif (strlen($newPassword) < 8) {
                    $errors[] = 'รหัสผ่านใหม่ต้องมีอย่างน้อย 8 ตัวอักษร';
                }

                if ($newPassword !== $confirmPassword) {
                    $errors[] = 'รหัสผ่านใหม่ไม่ตรงกัน';
                }

                if (empty($errors)) {
                    User::updateWithPassword($userId, [], $newPassword);
                    logActivity($userId, 'Change Password', 'เปลี่ยนรหัสผ่าน');
                    $this->flash('success', 'เปลี่ยนรหัสผ่านสำเร็จ');
                    $this->redirect(SITE_URL . '/profile');
                }
            }

            $user = User::find($userId);
        }

        $stats = [];
        if ($user['role'] === 'admin' || $user['role'] === 'staff') {
            $stats['total_equipment'] = Equipment::totalCount();
            $stats['total_repairs'] = Repair::totalCount();
        } else {
            $stats['my_repairs'] = Repair::countByUser($userId);
            $stats['my_equipment'] = EquipmentStats::countByHolder($userId);
        }

        $managedRooms = [];
        if ($user['role'] === 'teacher') {
            $managedRooms = RoomManager::getManagedRooms($userId);
        }

        $viewPath = 'auth/profile';
        require __DIR__ . '/../Views/layouts/main.php';
    }

    public function logout()
    {
        // Validate CSRF for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCsrf();
        }

        if (isLoggedIn()) {
            logActivity(getCurrentUserId(), 'Logout', 'ออกจากระบบ');
        }

        session_destroy();
        session_start();
        $this->flash('success', 'ออกจากระบบสำเร็จ');
        $this->redirect(SITE_URL . '/login');
    }

    private function doLogin($email, $password)
    {
        $input = strtolower(trim($email));

        if (strpos($input, '@') === false) {
            if (in_array($input, ['admin', 'staff', 'teacher'])) {
                $email = $input . '@rmutsb.ac.th';
            } elseif (preg_match('/^\d+$/', $input) || preg_match('/^\d+-\d+$/', $input)) {
                $email = $input . '-st@rmutsb.ac.th';
            } else {
                $email = $input . '@rmutsb.ac.th';
            }
        }

        $user = User::findByEmail($email);

        if (!$user && strpos($input, '@') === false) {
            $user = User::findBySid($input);
        }

        if (!$user) {
            return ['success' => false, 'error' => 'ไม่พบอีเมลหรือรหัสนักศึกษานี้ในระบบ'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'error' => 'รหัสผ่านไม่ถูกต้อง'];
        }

        if ($user['status'] === 'pending') {
            return ['success' => false, 'error' => 'บัญชีของคุณยังรออนุมัติ กรุณารอการอนุมัติจากผู้ดูแลระบบ'];
        }

        if ($user['status'] === 'rejected') {
            return ['success' => false, 'error' => 'บัญชีของคุณถูกปฏิเสธ กรุณาติดต่อผู้ดูแลระบบ'];
        }

        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);

        // Rotate CSRF token — token เก่าของ pre-login session ใช้ไม่ได้อีกต่อไป
        unset($_SESSION['csrf_token']);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
        $_SESSION['user_email'] = $user['email'];

        logActivity($user['id'], 'Login', 'เข้าสู่ระบบสำเร็จ');

        return ['success' => true, 'user' => $user];
    }

    private function doRegister($data)
    {
        if (User::isEmailTaken($data['email'])) {
            return ['success' => false, 'error' => 'อีเมลนี้ถูกใช้งานแล้ว'];
        }

        if ($data['role'] === 'student' && !empty($data['sid'])) {
            if (User::isSidTaken($data['sid'])) {
                return ['success' => false, 'error' => 'รหัสนักศึกษานี้ถูกใช้งานแล้ว'];
            }
        }

        User::createWithPassword([
            'sid' => $data['sid'] ?? null,
            'firstname' => $data['firstname'],
            'lastname' => $data['lastname'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => 'pending',
            'class' => $data['class'] ?? null,
        ]);

        logActivity(null, 'Register', 'สมัครสมาชิกใหม่: ' . $data['email']);

        return ['success' => true, 'message' => 'สมัครสมาชิกสำเร็จ กรุณารอการอนุมัติจากผู้ดูแลระบบ'];
    }

    private function redirectByRole()
    {
        $this->redirect(SITE_URL . '/');
    }
}
