<?php
$userActive = $userActive ?? '';
$assetBase = $assetBase ?? '../';
$userNavUsername = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User';
$userNavRole = $_SESSION['role'] ?? 'User';

$navItems = [
    [
        'key' => 'dashboard',
        'label' => 'Dashboard',
        'href' => 'dashboard.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1.5"></rect><rect x="14" y="3" width="7" height="7" rx="1.5"></rect><rect x="3" y="14" width="7" height="7" rx="1.5"></rect><rect x="14" y="14" width="7" height="7" rx="1.5"></rect></svg>',
    ],
    [
        'key' => 'add_cases',
        'label' => 'Add New Case',
        'href' => 'add_cases.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"></circle><path d="M12 8v8M8 12h8"></path></svg>',
    ],
    [
        'key' => 'my_entries',
        'label' => 'My Entries',
        'href' => 'my_entries.php',
        'icon' => '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 6h13M8 12h13M8 18h13"></path><path d="M3.5 6h.01M3.5 12h.01M3.5 18h.01"></path></svg>',
    ],
];
?>

<aside class="user-sidebar" id="userSidebar" aria-label="User navigation">
    <div class="sidebar-brand">
        <img src="<?php echo $assetBase; ?>assets/images/old-cab-logo.png" alt="Barangay Old Cabalan logo" class="sidebar-logo">
        <div class="sidebar-title">LCRMS</div>
    </div>

    <nav class="sidebar-menu">
        <?php foreach ($navItems as $item): ?>
            <a class="sidebar-link <?php echo $userActive === $item['key'] ? 'is-active' : ''; ?>" href="<?php echo $item['href']; ?>">
                <span class="sidebar-icon"><?php echo $item['icon']; ?></span>
                <span><?php echo $item['label']; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-account" aria-label="Signed in user">
        <span class="sidebar-account-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M20 21a8 8 0 0 0-16 0"></path>
                <circle cx="12" cy="8" r="4"></circle>
            </svg>
        </span>
        <div>
            <strong><?php echo htmlspecialchars($userNavUsername); ?></strong>
            <span><?php echo htmlspecialchars($userNavRole); ?></span>
        </div>
    </div>

    <a class="sidebar-link sidebar-logout" href="logout.php">
        <span class="sidebar-icon">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                <path d="M16 17l5-5-5-5"></path>
                <path d="M21 12H9"></path>
            </svg>
        </span>
        <span>Logout</span>
    </a>
</aside>
