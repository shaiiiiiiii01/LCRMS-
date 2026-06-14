<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/auth_common.php';
    require_once __DIR__ . '/../db_connect.php';
    require_once __DIR__ . '/controllers/CaseController.php';

    if (!$conn instanceof mysqli || mysqli_connect_errno()) {
        throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
    }

    $account = lcrms_current_account();

    if (!$account) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid session. Login is required before saving a case.',
        ]);
        exit;
    }

    $controller = new CaseController(new CaseModel($conn), $account);
    $controller->handle();
} catch (Throwable $exception) {
    error_log('LCRMS case API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
    exit;
}

