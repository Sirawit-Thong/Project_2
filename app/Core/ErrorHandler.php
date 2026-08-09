<?php
/**
 * Error Handler
 * จับ uncaught exceptions, fatal errors, warnings
 */

class ErrorHandler
{
    public static function register()
    {
        set_exception_handler([self::class, 'handleException']);
        set_error_handler([self::class, 'handleError']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleException(Throwable $e)
    {
        http_response_code(500);
        self::render(500, $e->getMessage(), $e->getFile(), $e->getLine());
    }

    public static function handleError($errno, $errstr, $errfile = '', $errline = 0)
    {
        if (!(error_reporting() & $errno)) {
            return false;
        }
        throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
    }

    public static function handleShutdown()
    {
        $error = error_get_last();
        if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
            http_response_code(500);
            self::render(500, $error['message'], $error['file'], $error['line']);
        }
    }

    public static function page404()
    {
        http_response_code(404);
        self::render(404, 'ไม่พบหน้าที่ต้องการ');
    }

    public static function page403()
    {
        http_response_code(403);
        self::render(403, 'คุณไม่มีสิทธิ์เข้าถึงหน้านี้');
    }

    private static function render($code, $message, $file = '', $line = 0)
    {
        $titles = [
            403 => 'ไม่มีสิทธิ์',
            404 => 'ไม่พบหน้า',
            500 => 'ข้อผิดพลาดภายใน',
        ];
        $title = $titles[$code] ?? 'ข้อผิดพลาด';
        $icons = [403 => 'shield-lock', 404 => 'question-circle', 500 => 'exclamation-triangle'];
        $icon = $icons[$code] ?? 'exclamation-circle';

        // Only show file info in development
        $isDev = defined('SITE_URL') && strpos(SITE_URL, 'localhost') !== false;

        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $siteName = defined('SITE_NAME') ? SITE_NAME : 'System';
        $siteUrl = defined('SITE_URL') ? SITE_URL : '/';
        ?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $code ?> — <?= htmlspecialchars($title) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Prompt', sans-serif; background: #f5f7fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .error-card { max-width: 500px; text-align: center; }
    </style>
</head>
<body>
    <div class="error-card">
        <i class="bi bi-<?= $icon ?> display-1 text-<?= $code === 403 ? 'warning' : ($code === 404 ? 'info' : 'danger') ?>"></i>
        <h1 class="display-4 fw-bold mt-3"><?= $code ?></h1>
        <h4 class="mb-3"><?= htmlspecialchars($title) ?></h4>
        <p class="text-muted mb-4"><?= htmlspecialchars($message) ?></p>
        <?php if ($code === 500 && $file && $isDev): ?>
            <div class="alert alert-light text-start small mb-4">
                <code><?= htmlspecialchars($file) ?>:<?= $line ?></code>
            </div>
        <?php endif; ?>
        <a href="<?= $siteUrl ?>" class="btn btn-primary me-2">
            <i class="bi bi-house me-1"></i>หน้าหลัก
        </a>
        <button onclick="history.back()" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i>ย้อนกลับ
        </button>
    </div>
</body>
</html>
<?php
        exit;
    }
}
