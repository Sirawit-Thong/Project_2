<?php
header('Content-Type: application/json; charset=utf-8');
session_start();
echo json_encode([
    'sid' => session_id(),
    'csrf' => $_SESSION['csrf_token'] ?? null,
    'user_id' => $_SESSION['user_id'] ?? null,
    'cookie' => $_COOKIE['PHPSESSID'] ?? null,
]);
