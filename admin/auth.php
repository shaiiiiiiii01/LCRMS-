<?php
require_once __DIR__ . '/../includes/auth_common.php';

function admin_is_logged_in(): bool
{
    return lcrms_account_is_admin();
}

function require_admin_login(string $loginPath = '../login.php'): void
{
    // Protected admin pages call this before rendering. If no valid session is
    // found, the visitor is sent to the shared login page instead of seeing page content.
    $account = lcrms_current_account();

    if (!$account) {
        header('Location: ' . $loginPath);
        exit;
    }

    // Role validation keeps normal User accounts out of the admin area. Logged
    // in users are redirected to their own dashboard instead of being logged out.
    if (lcrms_normalize_role((string) $account['role']) !== 'ADMIN') {
        header('Location: ../user/dashboard.php');
        exit;
    }
}

function require_admin_api_login(): void
{
    // API endpoints cannot safely redirect, so they return JSON status codes.
    $account = lcrms_current_account();

    if (!$account) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'message' => 'Admin login is required.',
        ]);
        exit;
    }

    if (lcrms_normalize_role((string) $account['role']) !== 'ADMIN') {
        http_response_code(403);
        echo json_encode([
            'success' => false,
            'message' => 'Admin permission is required.',
        ]);
        exit;
    }
}
?>

