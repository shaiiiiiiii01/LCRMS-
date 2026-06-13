<?php
$adminActive = $adminActive ?? '';
$assetBase = $assetBase ?? '../';
$adminNavUsername = $_SESSION['fullname'] ?? $_SESSION['admin_fullname'] ?? $_SESSION['username'] ?? 'Admin';
$adminNavRole = $_SESSION['role'] ?? 'Admin';

$adminNavItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
    ],
    [
        'key' => 'cases',
        'label' => 'Cases',
        'href' => 'cases.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6M9 13h6M9 17h4"></path></svg>',
    ],
    [
        'key' => 'settings',
        'label' => 'Settings',
        'href' => 'settings.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 1.55V21a2 2 0 1 1-4 0v-.08a1.7 1.7 0 0 0-1-1.55 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-1.55-1H3a2 2 0 1 1 0-4h.08a1.7 1.7 0 0 0 1.55-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-1.55V3a2 2 0 1 1 4 0v.08a1.7 1.7 0 0 0 1 1.55 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.37.52.95.85 1.55.85H21a2 2 0 1 1 0 4h-.08A1.7 1.7 0 0 0 19.4 15z"></path></svg>',
    ],
];
?>

<aside class="admin-sidebar" id="adminSidebar" aria-label="Admin navigation">
    <div class="admin-sidebar-brand">
        <img src="<?php echo $assetBase; ?>assets/images/oldcab_logo.png" alt="Barangay Old Cabalan logo" class="admin-sidebar-logo">
        <div class="admin-sidebar-title">LCRMS</div>
        <p>Administrator Portal</p>
    </div>

    <nav class="admin-sidebar-menu">
        <?php foreach ($adminNavItems as $item): ?>
            <a class="admin-sidebar-link <?php echo $adminActive === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo $item['href']; ?>">
                <span class="admin-sidebar-icon"><?php echo $item['icon']; ?></span>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="admin-sidebar-account" aria-label="Signed in administrator">
        <span class="admin-sidebar-account-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="8" r="4"></circle>
            </svg>
        </span>
        <div>
            <strong><?php echo htmlspecialchars($adminNavUsername); ?></strong>
            <span><?php echo htmlspecialchars($adminNavRole); ?></span>
        </div>
    </div>

    <a class="admin-sidebar-link admin-sidebar-logout" href="logout.php">
        <span class="admin-sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <path d="M16 17l5-5-5-5"></path>
                <path d="M21 12H9"></path>
            </svg>
        </span>
        <span>Logout</span>
    </a>
</aside>
