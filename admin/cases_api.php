<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/auth.php';
    require_admin_api_login();
    require_once __DIR__ . '/../db_connect.php';
    require_once __DIR__ . '/../user/models/CaseModel.php';

    if (!$conn instanceof mysqli || mysqli_connect_errno()) {
        throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
    }

    $caseModel = new CaseModel($conn);
    $action = $_GET['action'] ?? 'detail';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'detail') {
        $case = $caseModel->findByIdForAdmin((int) ($_GET['id'] ?? 0));

        if (!$case) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Case record not found.',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'case' => $case,
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $payload = $_POST;

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '{}', true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $result = $caseModel->updateForAdmin((int) ($payload['id'] ?? 0), $payload, lcrms_current_account() ?? []);
        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);
        http_response_code($status);
        echo json_encode($result);
        exit;
    }

    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported request.',
    ]);
} catch (Throwable $exception) {
    error_log('LCRMS admin case API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
