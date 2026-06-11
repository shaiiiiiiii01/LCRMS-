<?php
declare(strict_types=1);

header('Content-Type: application/json');

require_once __DIR__ . '/auth.php';
require_admin_api_login();
require_once '../db_connect.php';
require_once __DIR__ . '/controllers/UserController.php';

$controller = new UserController(new UserModel($conn));
$controller->handle();
