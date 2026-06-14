<?php
require_once __DIR__ . '/auth_common.php';

function user_is_logged_in(): bool
{
    return lcrms_account_is_user();
}

function require_user_login(string $loginPath = '../login.php'): void
{
    // Protected user pages call this before rendering. Missing or expired
    // sessions are redirected to the shared login page.
    $account = lcrms_current_account();

    if (!$account) {
        header('Location: ' . $loginPath);
        exit;
    }

    // Admin sessions are valid, but not valid for user-only pages. Send admins
    // back to the admin dashboard to enforce role-based page boundaries.
    if (lcrms_normalize_role((string) $account['role']) !== 'USER') {
        header('Location: ../admin/dashboard.php');
        exit;
    }
}
?>
