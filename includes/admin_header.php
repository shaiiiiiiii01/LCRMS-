<?php
$pageTitle = $pageTitle ?? 'Admin Overview';
$adminRole = $adminRole ?? ($_SESSION['role'] ?? (isset($_SESSION['account_role']) ? lcrms_role_label((string) $_SESSION['account_role']) : 'Admin'));
$headerMode = $headerMode ?? 'title';
$breadcrumbParent = $breadcrumbParent ?? 'Administrator Portal';
$adminName = $adminName ?? ($_SESSION['fullname'] ?? $_SESSION['admin_fullname'] ?? $_SESSION['username'] ?? 'Admin');
?>

<header class="admin-topbar">
    <div class="admin-topbar-left">
        <button class="admin-icon-button admin-menu-toggle" type="button" data-admin-sidebar-toggle aria-label="Open navigation">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 6h16M4 12h16M4 18h16"></path>
            </svg>
        </button>

        <?php if ($headerMode === 'breadcrumb'): ?>
            <div class="admin-breadcrumb">
                <span><?php echo htmlspecialchars($breadcrumbParent); ?></span>
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M9 18l6-6-6-6"></path></svg>
                <strong><?php echo htmlspecialchars($pageTitle); ?></strong>
            </div>
        <?php else: ?>
            <h1><?php echo htmlspecialchars($pageTitle); ?></h1>
        <?php endif; ?>
    </div>

    <div class="admin-profile" aria-label="Current administrator">
        <div>
            <strong><?php echo htmlspecialchars($adminName); ?></strong>
            <span><?php echo htmlspecialchars($adminRole); ?></span>
        </div>
        <button class="admin-avatar-button <?php echo $headerMode === 'breadcrumb' ? 'is-green' : ''; ?>" type="button" aria-label="Admin account">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="8" r="4"></circle>
            </svg>
        </button>
    </div>
</header>
