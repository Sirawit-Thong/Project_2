<?php
/**
 * Logout
 * ออกจากระบบ
 */

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

logoutUser();
setFlash('success', 'ออกจากระบบสำเร็จ');
redirect(SITE_URL . '/login.php');
