<?php
/**
 * Main Layout
 * ครอบทุกหน้า — header + sidebar + content + footer
 */
if (ob_get_level() === 0) {
    ob_start();
}
$rootDir = __DIR__ . '/../../..';
require_once $rootDir . '/includes/header.php';
?>

<?php if (isset($viewPath) && file_exists(__DIR__ . '/../' . $viewPath . '.php')): ?>
    <?php require __DIR__ . '/../' . $viewPath . '.php'; ?>
<?php endif; ?>

<?php require_once $rootDir . '/includes/footer.php'; ?>
