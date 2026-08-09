<?php
/**
 * Simulate login to debug the issue
 */

// Simulate session
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['HTTP_HOST'] = 'localhost';

session_start();
$_SESSION = [];

// Load app
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/app/Core/Model.php';
require_once __DIR__ . '/app/Core/Router.php';
require_once __DIR__ . '/app/Core/Controller.php';
require_once __DIR__ . '/app/Helpers/functions.php';
require_once __DIR__ . '/app/Models/User.php';
require_once __DIR__ . '/app/Models/SystemLog.php';
require_once __DIR__ . '/app/Models/Equipment.php';
require_once __DIR__ . '/app/Models/EquipmentImage.php';
require_once __DIR__ . '/app/Models/EquipmentStats.php';
require_once __DIR__ . '/app/Models/Repair.php';
require_once __DIR__ . '/app/Models/Room.php';
require_once __DIR__ . '/app/Models/RoomManager.php';
require_once __DIR__ . '/app/Models/Department.php';
require_once __DIR__ . '/app/Models/SetModel.php';
require_once __DIR__ . '/app/Models/Item.php';

// Step 1: Get CSRF token
$token = csrf_token();
echo "CSRF Token: " . $token . "\n";
echo "Token length: " . strlen($token) . "\n";

// Step 2: Verify CSRF
echo "\nVerify correct token: " . (verify_csrf($token) ? 'OK' : 'FAIL') . "\n";
echo "Verify wrong token: " . (verify_csrf('wrong') ? 'FAIL-should-not-verify' : 'OK') . "\n";

// Step 3: Try login with admin credentials
// First check what the admin password hash actually is
$pdo = getDB();
$row = $pdo->query("SELECT id, email, password, role, status FROM users WHERE email = 'admin@rmutsb.ac.th'")->fetch(PDO::FETCH_ASSOC);
echo "\n--- Admin user ---\n";
echo "Email: " . $row['email'] . "\n";
echo "Role: " . $row['role'] . "\n";
echo "Status: " . $row['status'] . "\n";
echo "Hash starts with: " . substr($row['password'], 0, 7) . "\n";
echo "Verify '123456': " . (password_verify('123456', $row['password']) ? 'YES' : 'NO') . "\n";
echo "Verify 'Admin@RMUTSB2024': " . (password_verify('Admin@RMUTSB2024', $row['password']) ? 'YES' : 'NO') . "\n";

// Step 4: Simulate doLogin directly
echo "\n--- Simulating doLogin ---\n";

// Set up POST data
$_POST['email'] = 'admin@rmutsb.ac.th';
$_POST['password'] = '123456';
$_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];

// Test email normalization
$input = strtolower(trim('admin'));
echo "Input 'admin' -> normalized: " . $input . "\n";
if (strpos($input, '@') === false) {
    if (in_array($input, ['admin', 'staff', 'teacher'])) {
        $email = $input . '@rmutsb.ac.th';
        echo "Mapped to: " . $email . "\n";
    }
}

$input2 = strtolower(trim('366408241011'));
echo "Input '366408241011' -> normalized: " . $input2 . "\n";
if (strpos($input2, '@') === false) {
    if (preg_match('/^\d+$/', $input2)) {
        $email2 = $input2 . '-st@rmutsb.ac.th';
        echo "Mapped to: " . $email2 . "\n";
    }
}

// Step 5: Try actual login via AuthController
echo "\n--- Testing AuthController::login ---\n";
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['email'] = 'admin';
$_POST['password'] = '123456';
$_SESSION['login_attempts'] = ['count' => 0, 'first_attempt' => time()];

try {
    $controller = new AuthController();
    $result = $controller->login();
    echo "Login completed\n";
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n--- Session state after login ---\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "user_role: " . ($_SESSION['user_role'] ?? 'NOT SET') . "\n";
echo "user_name: " . ($_SESSION['user_name'] ?? 'NOT SET') . "\n";
echo "login_attempts: " . json_encode($_SESSION['login_attempts'] ?? 'NOT SET') . "\n";
