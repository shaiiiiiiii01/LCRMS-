<?php
// Every protected page includes one of the auth guard files. Starting the session
// here guarantees session data is available before any page content is sent.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function lcrms_normalize_role(string $role): string
{
    $role = strtoupper(trim($role));

    return $role === 'ADMIN' ? 'ADMIN' : 'USER';
}

function lcrms_role_label(string $role): string
{
    return lcrms_normalize_role($role) === 'ADMIN' ? 'Admin' : 'User';
}

function lcrms_db(): mysqli
{
    global $conn;

    if (!$conn instanceof mysqli) {
        require_once __DIR__ . '/../db_connect.php';
    }

    mysqli_set_charset($conn, 'utf8mb4');

    return $conn;
}

function lcrms_fetch_account_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }

    $conn = lcrms_db();
    $stmt = mysqli_prepare($conn, 'SELECT id, fullname, username, role FROM users WHERE id = ? LIMIT 1');

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $account = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    return $account ?: null;
}

function lcrms_set_account_session(array $account): void
{
    $role = lcrms_normalize_role((string) ($account['role'] ?? 'USER'));
    $roleLabel = lcrms_role_label($role);
    $userId = (int) $account['id'];
    $fullname = (string) ($account['fullname'] ?? $account['username'] ?? '');
    $username = (string) ($account['username'] ?? '');

    // Required session contract used by pages, dashboards, and navigation.
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    $_SESSION['role'] = $roleLabel;
    $_SESSION['fullname'] = $fullname;

    // Backward-compatible keys for existing templates and controllers.
    $_SESSION['account_id'] = $userId;
    $_SESSION['account_fullname'] = $fullname;
    $_SESSION['account_username'] = $username;
    $_SESSION['account_role'] = $role;

    // Admin-specific session data is only present for administrator accounts.
    if ($role === 'ADMIN') {
        $_SESSION['admin_id'] = $userId;
        $_SESSION['admin_username'] = $username;
        $_SESSION['admin_fullname'] = $fullname;
        $_SESSION['admin_role'] = $roleLabel;
    } else {
        unset($_SESSION['admin_id'], $_SESSION['admin_username'], $_SESSION['admin_fullname'], $_SESSION['admin_role']);
    }
}

function lcrms_clear_account_session(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['username'],
        $_SESSION['role'],
        $_SESSION['fullname'],
        $_SESSION['account_id'],
        $_SESSION['account_fullname'],
        $_SESSION['account_username'],
        $_SESSION['account_role'],
        $_SESSION['admin_id'],
        $_SESSION['admin_username'],
        $_SESSION['admin_fullname'],
        $_SESSION['admin_role']
    );
}

function lcrms_current_account(): ?array
{
    // Verify the session against the database on each protected request. This
    // blocks stale or manually crafted sessions before page content loads.
    $id = (int) ($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? $_SESSION['admin_id'] ?? 0);
    $account = lcrms_fetch_account_by_id($id);

    if (!$account) {
        lcrms_clear_account_session();
        return null;
    }

    lcrms_set_account_session($account);

    return $account;
}

function lcrms_account_is_admin(): bool
{
    $account = lcrms_current_account();

    return $account !== null && lcrms_normalize_role((string) $account['role']) === 'ADMIN';
}

function lcrms_account_is_user(): bool
{
    $account = lcrms_current_account();

    return $account !== null && lcrms_normalize_role((string) $account['role']) === 'USER';
}

function lcrms_verify_password(string $password, string $storedHash): bool
{
    $passwordInfo = password_get_info($storedHash);

    if (!empty($passwordInfo['algo'])) {
        return password_verify($password, $storedHash);
    }

    return hash_equals($storedHash, $password);
}

function lcrms_destroy_session(string $redirectPath): void
{
    // Logout clears all session variables, removes the browser session cookie,
    // destroys the server-side session, then returns the visitor to login.
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();

    header('Location: ' . $redirectPath);
    exit;
}
?>

