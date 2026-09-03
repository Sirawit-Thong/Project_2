<?php
/**
 * Front Controller
 * จุดเริ่มต้นของทุก Request — route ไปยัง Controller ที่ถูกต้อง
 */

require_once __DIR__ . '/app/init.php';

$router = new Router();

// ============================================
// Public Routes
// ============================================
$router->get('/', 'DashboardController@index');
$router->get('/login', 'AuthController@login');
$router->post('/login', 'AuthController@login');
$router->get('/register/student', 'AuthController@registerStudent');
$router->post('/register/student', 'AuthController@registerStudent');
$router->get('/register/teacher', 'AuthController@registerTeacher');
$router->post('/register/teacher', 'AuthController@registerTeacher');
$router->post('/logout', 'AuthController@logout');
$router->get('/logout', 'AuthController@logout'); // รองรับ ?i=1 และ redirect 307 ที่แปลง POST→GET
// ============================================
// Authenticated Routes — Profile
// ============================================
$router->get('/profile', 'AuthController@profile');
$router->post('/profile', 'AuthController@profile');

// ============================================
// Equipment Routes
// ============================================
$router->get('/equipment', 'EquipmentController@index');
$router->get('/equipment/add', 'EquipmentController@add');
$router->post('/equipment/add', 'EquipmentController@add');
$router->get('/equipment/edit/{id}', 'EquipmentController@edit');
$router->post('/equipment/edit/{id}', 'EquipmentController@edit');
$router->post('/equipment/delete-image/{id}', 'EquipmentController@deleteImage');
$router->post('/equipment/delete/{id}', 'EquipmentController@delete');
$router->get('/equipment/bulk-add', 'EquipmentController@bulkAdd');
$router->post('/equipment/bulk-add', 'EquipmentController@bulkAdd');
$router->get('/equipment/my', 'EquipmentController@myEquipment');
$router->post('/equipment/my', 'EquipmentController@myEquipment');
$router->get('/equipment/{id}', 'EquipmentController@detail');
$router->get('/equipment/inspection', 'EquipmentController@inspection');
$router->post('/equipment/inspection', 'EquipmentController@inspection');
$router->get('/equipment/disposal', 'EquipmentController@disposal');
$router->post('/equipment/disposal', 'EquipmentController@disposal');

// ============================================
// Repair Routes
// ============================================
$router->get('/repairs', 'RepairController@index');
$router->get('/repairs/submit', 'RepairController@submit');
$router->post('/repairs/submit', 'RepairController@submit');
$router->get('/repairs/mine', 'RepairController@mine');
$router->get('/repairs/{id}', 'RepairController@detail');
$router->post('/repairs/{id}', 'RepairController@detail');

// ============================================
// Depreciation Routes (ค่าเสื่อมราคา)
// ============================================
$router->get('/depreciation', 'DepreciationController@index');
$router->get('/depreciation/settings', 'DepreciationController@settings');
$router->post('/depreciation/settings', 'DepreciationController@settings');
$router->get('/depreciation/report', 'DepreciationController@report');
$router->get('/depreciation/export', 'DepreciationController@export');
$router->get('/depreciation/my', 'DepreciationController@my');
$router->get('/depreciation/my/export', 'DepreciationController@myExport');

// ============================================
// Satisfaction Routes (ความพึงพอใจ)
// ============================================
$router->post('/satisfaction/submit/{id}', 'SatisfactionController@submit');
$router->get('/satisfaction', 'SatisfactionController@dashboard');
$router->get('/satisfaction/export', 'SatisfactionController@export');

// ============================================
// Teacher Report Routes
// ============================================
$router->get('/teacher/report', 'DashboardController@teacherReport');
$router->get('/teacher/export', 'DashboardController@teacherExport');

// ============================================
// User Management Routes
// ============================================
$router->get('/users', 'UserController@index');
$router->get('/users/add', 'UserController@add');
$router->post('/users/add', 'UserController@add');
$router->get('/users/edit/{id}', 'UserController@edit');
$router->post('/users/edit/{id}', 'UserController@edit');
$router->get('/users/pending', 'UserController@pending');
$router->post('/users/pending/{id}/approve', 'UserController@approve');
$router->post('/users/pending/{id}/reject', 'UserController@reject');
$router->post('/users/{id}/delete', 'UserController@delete');

// ============================================
// CRUD Routes — Departments, Sets, Items, Rooms, Room Managers
// ============================================
$router->get('/departments', 'DepartmentController@index');
$router->post('/departments', 'DepartmentController@index');
$router->post('/departments/delete/{id}', 'DepartmentController@delete');

$router->get('/sets', 'SetController@index');
$router->post('/sets', 'SetController@index');
$router->post('/sets/delete/{id}', 'SetController@delete');

$router->get('/items', 'ItemController@index');
$router->post('/items', 'ItemController@index');
$router->post('/items/delete/{id}', 'ItemController@delete');

$router->get('/rooms', 'RoomController@index');
$router->post('/rooms', 'RoomController@index');
$router->post('/rooms/delete/{id}', 'RoomController@delete');

$router->get('/room-managers', 'RoomManagerController@index');
$router->post('/room-managers', 'RoomManagerController@index');
$router->post('/room-managers/delete/{id}', 'RoomManagerController@delete');
$router->post('/room-managers/sync/{mode}', 'RoomManagerController@sync');

// ============================================
// Admin Routes — Backup, Logs, Reports
// ============================================
$router->get('/backup', 'AdminController@backup');
$router->post('/backup', 'AdminController@backup');
$router->get('/logs', 'AdminController@logs');
$router->get('/reports', 'AdminController@reports');
$router->get('/reports/export', 'AdminController@export');
$router->get('/reports/export/{type}', 'AdminController@export');

// ============================================
// Dispatch
// ============================================
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Strip base path (e.g. /P) from URI
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
if ($basePath && strpos($uri, $basePath) === 0) {
    $uri = substr($uri, strlen($basePath));
}
$uri = $uri ?: '/';

$result = $router->dispatch($method, $uri);

if ($result) {
    $_GET = array_merge($_GET, $result['params']);
    $router->callHandler($result['handler'], $result['params']);
} else {
    ErrorHandler::page404();
}
