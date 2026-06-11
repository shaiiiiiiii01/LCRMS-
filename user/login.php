<?php
require_once __DIR__ . '/../includes/auth_common.php';
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/../admin/models/UserModel.php';

$users = new UserModel($conn);
$error = '';

function upgrade_plaintext_portal_password(mysqli $conn, int $id, string $password, string $storedPassword): void
{
    if (!empty(password_get_info($storedPassword)['algo'])) {
        return;
    }

    $newHash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = mysqli_prepare($conn, 'UPDATE users SET password = ? WHERE id = ?');
    mysqli_stmt_bind_param($stmt, 'si', $newHash, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    lcrms_clear_account_session();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username and password are required.';
    } else {
        $account = $users->findByUsername($username);

        if ($account && lcrms_verify_password($password, (string) $account['password']) && lcrms_normalize_role((string) $account['role']) !== 'USER') {
            $error = 'This is an Admin account. Please log in from the Admin Login page.';
        } elseif ($account && lcrms_verify_password($password, (string) $account['password'])) {
            upgrade_plaintext_portal_password($conn, (int) $account['id'], $password, (string) $account['password']);
            // Regenerate the session ID on login, then store User identity,
            // username, and role for all protected user pages.
            session_regenerate_id(true);
            lcrms_set_account_session($account);

            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
} elseif ($account = lcrms_current_account()) {
    header('Location: ' . (lcrms_normalize_role((string) $account['role']) === 'ADMIN' ? '../admin/dashboard.php' : 'dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body class="login-page">
    <main class="login-screen">
        <section class="login-card" aria-labelledby="loginTitle">
            <div class="login-brand">
                <img src="../assets/images/old-cab-logo.png" alt="Barangay Old Cabalan logo" class="login-logo">
                <h1 id="loginTitle">Log In to LCRMS</h1>
                <p>Lupong Case and Record Management System</p>
            </div>

            <?php if ($error !== ''): ?>
                <p class="login-error" role="alert"><?php echo htmlspecialchars($error); ?></p>
            <?php endif; ?>

            <form class="login-form" action="login.php" method="post">
                <div class="form-group">
                    <label for="username">Username</label>
                    <div class="input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="8" r="4"></circle>
                        </svg>
                        <input id="username" name="username" type="text" placeholder="Enter your username" autocomplete="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-shell">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="5" y="11" width="14" height="10" rx="2"></rect>
                            <path d="M8 11V8a4 4 0 0 1 8 0v3"></path>
                        </svg>
                        <input id="password" name="password" type="password" placeholder="Enter your password" autocomplete="current-password" required>
                        <button class="input-icon-button" type="button" data-toggle-password aria-label="Show password">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="login-options">
                    <label class="checkbox-row">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#">Forgot password?</a>
                </div>

                <button class="primary-button" type="submit">
                    <span>Log In</span>
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M9 18l6-6-6-6"></path>
                    </svg>
                </button>
            </form>

            <div class="login-rule"></div>
            <p class="login-note">Authorized personnel only. All access and activity within this system are monitored and recorded.</p>
        </section>
    </main>

    <?php include '../includes/user_footer.php'; ?>

    <script src="../assets/js/user.js"></script>
</body>
</html>
