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
        <label class="search-field">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <circle cx="11" cy="11" r="7"></circle>
                <path d="M20 20l-3.5-3.5"></path>
            </svg>
            <input type="search" placeholder="Search records..." aria-label="Search records">
        </label>

        <button class="icon-button notify-button" type="button" aria-label="Notifications">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
            </svg>
            <span class="notification-dot"></span>
        </button>

        <div class="profile-divider"></div>

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
