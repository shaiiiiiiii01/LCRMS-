<?php
require_once __DIR__ . '/includes/auth_common.php';
require_once __DIR__ . '/db_connect.php';
require_once __DIR__ . '/admin/models/UserModel.php';

$error = '';
$users = new UserModel($conn);
$users->ensureDefaultAdmin();

function lcrms_upgrade_plaintext_login_password(mysqli $conn, int $id, string $password, string $storedPassword): void
{
    if (!empty(password_get_info($storedPassword)['algo'])) {
        return;
    }

    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');

    if (!$stmt) {
        return;
    }

    mysqli_stmt_bind_param($stmt, 'si', $newHash, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

function lcrms_import_legacy_admin_account(mysqli $conn, UserModel $users, string $username, string $password): ?array
{
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'admin'");

    if (!$tableCheck || !mysqli_fetch_row($tableCheck)) {
        return null;
    }

    $stmt = mysqli_prepare($conn, 'SELECT admin_id, username, password FROM admin WHERE username = ? LIMIT 1');

    if (!$stmt) {
        return null;
    }

    mysqli_stmt_bind_param($stmt, 's', $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $legacyAdmin = $result ? mysqli_fetch_assoc($result) : null;
    mysqli_stmt_close($stmt);

    if (!$legacyAdmin || !lcrms_verify_password($password, (string) $legacyAdmin['password'])) {
        return null;
    }

    if ($users->adminExists()) {
        return null;
    }

    $account = $users->findByUsername((string) $legacyAdmin['username']);

    if (!$account) {
        $users->create([
            'fullname' => (string) $legacyAdmin['username'],
            'username' => (string) $legacyAdmin['username'],
            'password' => $password,
            'role' => 'ADMIN',
        ]);
        $account = $users->findByUsername((string) $legacyAdmin['username']);
    }

    return $account && lcrms_normalize_role((string) $account['role']) === 'ADMIN' ? $account : null;
}

function lcrms_login_redirect_for_role(string $role): string
{
    return lcrms_normalize_role($role) === 'ADMIN'
        ? 'admin/dashboard.php'
        : 'user/dashboard.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lcrms_clear_account_session();

    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $password === '') {
        $error = 'Invalid username or password.';
    } else {
        $account = $users->findByUsername($username);
        $passwordMatches = $account && lcrms_verify_password($password, (string) $account['password']);

        if (!$account || !$passwordMatches) {
            $account = lcrms_import_legacy_admin_account($conn, $users, $username, $password);
            $passwordMatches = (bool) $account;
        }

        if ($account && $passwordMatches) {
            lcrms_upgrade_plaintext_login_password($conn, (int) $account['id'], $password, (string) $account['password']);
            session_regenerate_id(true);
            lcrms_set_account_session($account);

            header('Location: ' . lcrms_login_redirect_for_role((string) $account['role']));
            exit;
        }

        $error = 'Invalid username or password.';
    }
} elseif ($account = lcrms_current_account()) {
    header('Location: ' . lcrms_login_redirect_for_role((string) $account['role']));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | LCRMS</title>
    <link rel="stylesheet" href="assets/css/admin.css?v=4">
</head>
<body class="admin-login-page">
    <main class="admin-login-screen">
        <section class="admin-login-card" aria-labelledby="adminLoginTitle">
            <div class="admin-login-brand">
                <img src="assets/images/oldcab_logo.png" alt="Barangay Old Cabalan logo" class="admin-login-logo">
                <h1 id="adminLoginTitle">Log In to LCRMS</h1>
                <p>Lupon Case and Record Management System</p>
                <span class="portal-badge">LCRMS Portal</span>
            </div>

            <?php if ($error !== ''): ?>
                <p class="admin-login-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form class="admin-login-form" action="login.php" method="post">
                <div class="admin-form-group">
                    <label for="adminUsername">Username</label>
                    <div class="admin-input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="8" r="4"></circle>
                        </svg>
                        <input id="adminUsername" name="username" type="text" placeholder="Username" autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                        <span class="admin-input-side-spacer" aria-hidden="true"></span>
                    </div>
                </div>

                <div class="admin-form-group">
                    <label for="adminPassword">Password</label>
                    <div class="admin-input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                        </svg>
                        <input id="adminPassword" name="password" type="password" placeholder="Password" autocomplete="current-password" required>
                        <button class="admin-input-icon-button" type="button" data-admin-toggle-password aria-label="Show password">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="admin-login-options">
                    <label class="admin-checkbox-row">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                </div>

                <button class="admin-primary-button" type="submit">
                    <span>Log In</span>
                </button>
            </form>

            <div class="admin-login-rule"></div>
            <p class="admin-login-note">Authorized personnel only. All access and activity within this system are monitored and recorded.</p>
        </section>
    </main>

    <?php include __DIR__ . '/includes/admin_footer.php'; ?>

    <script src="assets/js/admin.js"></script>
</body>
</html>
