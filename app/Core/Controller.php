<?php
/**
 * Base Controller
 * คลาสแม่สำหรับ Controller ทั้งหมด
 */

class Controller
{
    /**
     * โหลด view template พร้อมส่ง data
     * View file path อยู่ภายใต้ views/ เช่น 'admin/equipment_list'
     */
    protected function render($viewFile, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $viewFile . '.php';

        if (!file_exists($viewPath)) {
            die("View not found: {$viewFile}");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // ครอบด้วย layout ถ้ามี $layout
        $layout = $data['layout'] ?? 'layouts/main';
        $layoutPath = __DIR__ . '/../../views/' . $layout . '.php';

        if (file_exists($layoutPath)) {
            extract($data);
            require $layoutPath;
        } else {
            echo $content;
        }
    }

    /**
     * โหลด view เฉพาะส่วน (ไม่ครอบ layout)
     */
    protected function partial($viewFile, $data = [])
    {
        extract($data);
        $viewPath = __DIR__ . '/../../views/' . $viewFile . '.php';

        if (file_exists($viewPath)) {
            require $viewPath;
        }
    }

    /**
     * Redirect
     */
    protected function redirect($url)
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * JSON response
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * ตั้งค่า Flash message
     */
    protected function flash($type, $message)
    {
        setFlash($type, $message);
    }

    /**
     * ตรวจสอบ role แล้ว redirect ถ้าไม่มีสิทธิ์
     */
    protected function authorize($roles)
    {
        if (!isLoggedIn()) {
            $this->flash('warning', 'กรุณาเข้าสู่ระบบก่อน');
            $this->redirect(SITE_URL . '/login');
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        if (!in_array($_SESSION['user_role'], $roles)) {
            $this->flash('danger', 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
            $this->redirect(SITE_URL . '/login');
        }
    }

    /**
     * ตรวจสอบว่า login แล้ว
     */
    protected function requireLogin()
    {
        if (!isLoggedIn()) {
            $this->flash('warning', 'กรุณาเข้าสู่ระบบก่อน');
            $this->redirect(SITE_URL . '/login');
        }
    }

    /**
     * ตรวจสอบ CSRF token สำหรับ POST request
     */
    protected function validateCsrf()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_csrf();
        }
    }

    /**
     * รับค่า POST ที่ sanitizize แล้ว
     */
    protected function input($key, $default = null)
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $source = ($method === 'POST') ? $_POST : $_GET;
        return $source[$key] ?? $default;
    }

    /**
     * รับ array ของ POST values
     */
    protected function inputs(array $keys)
    {
        $data = [];
        foreach ($keys as $key => $default) {
            if (is_int($key)) {
                $data[$default] = $this->input($default);
            } else {
                $data[$key] = $this->input($key, $default);
            }
        }
        return $data;
    }
}
