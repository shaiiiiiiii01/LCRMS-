<?php
$pageTitle = $pageTitle ?? 'Dashboard Overview';
$userName = $userName ?? ($_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User');
$userRole = $userRole ?? ($_SESSION['role'] ?? (isset($_SESSION['account_role']) ? lcrms_role_label((string) $_SESSION['account_role']) : 'User'));
?>

<header class="user-topbar">
    <div class="topbar-left">
        <button class="icon-button menu-toggle" type="button" data-sidebar-toggle aria-label="Open navigation">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
    </div>

    <div class="topbar-actions">
        <div class="user-profile" aria-label="Current user">
            <div>
                <strong><?php echo htmlspecialchars($userName); ?></strong>
                <span><?php echo htmlspecialchars($userRole); ?></span>
            </div>
            <button class="avatar-button" type="button" aria-label="User account">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M20 21a8 8 0 0 0-16 0"></path>
                    <circle cx="12" cy="8" r="4"></circle>
                </svg>
            </button>
        </div>
    </div>
</header>

