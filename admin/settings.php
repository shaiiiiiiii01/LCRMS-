<?php
require_once __DIR__ . '/auth.php';
require_admin_login();
require_once __DIR__ . '/../db_connect.php';
require_once __DIR__ . '/models/UserModel.php';

$adminActive = 'settings';
$pageTitle = 'Settings';
$adminName = $_SESSION['fullname'] ?? $_SESSION['admin_fullname'] ?? $_SESSION['username'] ?? 'Admin';
$adminRole = $_SESSION['role'] ?? 'Admin';
$assetBase = '../';
$userModel = new UserModel($conn);
$initialUsers = $userModel->list();
$initialAdminProfile = $userModel->adminProfile((int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0));
$initialUsersJson = htmlspecialchars(json_encode($initialUsers, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings | LCRMS Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
</head>
<body>
    <div class="admin-layout admin-settings-layout">
        <?php include '../includes/admin_nav.php'; ?>
        <div class="admin-sidebar-backdrop" data-admin-sidebar-close></div>

        <div class="admin-content">
            <?php include '../includes/admin_header.php'; ?>

            <main class="admin-main users-main" data-user-management data-user-api="users_api.php" data-initial-users="<?php echo $initialUsersJson; ?>">
                <div class="admin-toast" data-user-toast role="status" aria-live="polite" hidden></div>

                <section class="admin-profile-card" aria-labelledby="adminProfileTitle">
                    <div class="admin-profile-card-head">
                        <span class="admin-profile-card-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="8" r="4"></circle>
                            </svg>
                        </span>
                        <div>
                            <h2 id="adminProfileTitle">Admin Profile</h2>
                            <p>Edit the administrator full name, username, and password.</p>
                        </div>
                    </div>

                    <form class="settings-form admin-profile-form" action="#" method="post" data-admin-profile-form novalidate>
                        <div class="admin-form-group">
                            <label for="adminProfileFullname">Full Name</label>
                            <input id="adminProfileFullname" type="text" autocomplete="name" value="<?php echo htmlspecialchars((string) ($initialAdminProfile['fullname'] ?? '')); ?>" data-admin-profile-fullname required readonly tabindex="-1">
                        </div>
                        <div class="admin-form-group">
                            <label for="adminProfileUsername">Username</label>
                            <input id="adminProfileUsername" type="text" autocomplete="username" value="<?php echo htmlspecialchars((string) ($initialAdminProfile['username'] ?? '')); ?>" data-admin-profile-username required readonly tabindex="-1">
                        </div>
                        <div class="admin-form-group">
                            <label for="adminProfilePassword">Password</label>
                            <input id="adminProfilePassword" type="text" autocomplete="new-password" value="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" data-password-unchanged="true" data-admin-profile-password readonly tabindex="-1">
                        </div>
                        <p class="form-info-alert admin-password-note">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="10"></circle><path d="M12 16v-4M12 8h.01"></path></svg>
                            <span>Note: Save your password because you will not be able to access it again.</span>
                        </p>
                        <p class="form-alert" data-admin-profile-error hidden></p>
                        <div class="admin-profile-actions">
                            <button class="admin-primary-button compact" type="submit" data-admin-profile-submit>Edit</button>
                        </div>
                    </form>
                </section>

                <section class="user-management-card" aria-labelledby="userSearchLabel">
                    <div class="user-management-card-head">
                        <div>
                            <h2>User Management</h2>
                            <p>Add, edit, search, and remove LCRMS user accounts.</p>
                        </div>
                    </div>

                    <div class="user-management-toolbar">
                        <div class="user-search-block">
                            <label id="userSearchLabel" for="userSearch">Search User</label>
                            <div class="admin-search-field user-search-field">
                                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                <input id="userSearch" type="search" placeholder="Search full name or username" data-user-search>
                            </div>
                        </div>

                        <button class="user-create-button" type="button" data-open-user-modal>
                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                            <span>Add User</span>
                        </button>
                    </div>

                    <div class="user-table-frame" data-user-table-frame <?php echo $initialUsers === [] ? 'hidden' : ''; ?>>
                        <div class="admin-table-wrap user-table-wrap">
                            <table class="user-management-table">
                                <thead>
                                    <tr>
                                        <th scope="col">Full Name</th>
                                        <th scope="col">Username</th>
                                        <th scope="col">Password</th>
                                    </tr>
                                </thead>
                                <tbody data-user-table-body>
                                    <?php foreach ($initialUsers as $user): ?>
                                        <tr class="user-management-row" data-user-row data-user-id="<?php echo htmlspecialchars((string) $user['id']); ?>" tabindex="0" role="button" aria-label="Edit <?php echo htmlspecialchars((string) $user['fullname']); ?>">
                                            <td><?php echo htmlspecialchars((string) $user['fullname']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $user['username']); ?></td>
                                            <td>
                                                <div class="user-password-cell">
                                                    <span class="password-mask" aria-label="Password hidden">&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;</span>
                                                    <button class="user-action-button edit" type="button" data-user-action="edit" data-user-id="<?php echo htmlspecialchars((string) $user['id']); ?>" aria-label="Edit <?php echo htmlspecialchars((string) $user['fullname']); ?>">
                                                        <i class="fa-regular fa-pen-to-square" aria-hidden="true"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="user-empty-state" data-user-empty-state <?php echo $initialUsers === [] ? '' : 'hidden'; ?>>
                        <span><i class="fa-solid fa-user-plus" aria-hidden="true"></i></span>
                        <h3>No users found</h3>
                        <p>Try another name or add a new user account.</p>
                    </div>
                </section>

                <div class="admin-modal" data-user-modal hidden>
                    <div class="admin-modal-backdrop" data-close-user-modal></div>
                    <section class="admin-modal-panel user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="userModalTitle">
                        <div class="admin-modal-head">
                            <div>
                                <h3 id="userModalTitle" data-user-modal-title>Add User</h3>
                                <p data-user-modal-subtitle>Create a system account for LCRMS access.</p>
                            </div>
                            <button class="admin-icon-button" type="button" data-close-user-modal aria-label="Close user form">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            </button>
                        </div>

                        <form class="settings-form user-form user-modal-form" action="#" method="post" data-user-form novalidate>
                            <input type="hidden" data-user-id>
                            <div class="admin-form-group">
                                <label for="managedName">Full Name</label>
                                <input id="managedName" type="text" autocomplete="off" data-user-name required>
                            </div>
                            <div class="admin-form-group">
                                <label for="managedUsername">Username</label>
                                <input id="managedUsername" type="text" autocomplete="off" data-user-username required>
                            </div>
                            <div class="admin-form-group">
                                <label for="managedPassword">Password</label>
                                <div class="settings-password-shell">
                                    <input id="managedPassword" type="password" autocomplete="new-password" data-user-password>
                                    <button class="admin-input-icon-button" type="button" data-password-visibility-toggle data-password-target="managedPassword" aria-label="Show password">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12z"></path>
                                            <circle cx="12" cy="12" r="3"></circle>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div class="admin-form-group">
                                <label for="managedRole">Role</label>
                                <select id="managedRole" data-user-role required>
                                    <option value="USER">User</option>
                                </select>
                            </div>
                            <p class="form-alert" data-user-form-error hidden></p>
                            <div class="modal-actions">
                                <button class="secondary-button" type="button" data-close-user-modal>Cancel</button>
                                <button class="admin-primary-button compact" type="submit" data-user-submit>Save User</button>
                            </div>
                        </form>
                    </section>
                </div>

                <div class="admin-modal" data-delete-modal hidden>
                    <div class="admin-modal-backdrop" data-close-delete-modal></div>
                    <section class="admin-modal-panel small" role="dialog" aria-modal="true" aria-labelledby="deleteModalTitle">
                        <div class="admin-modal-head">
                            <div>
                                <h3 id="deleteModalTitle">Delete User</h3>
                                <p>Confirm deletion for <strong data-delete-user-name></strong>.</p>
                            </div>
                            <button class="admin-icon-button" type="button" data-close-delete-modal aria-label="Close delete confirmation">
                                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                            </button>
                        </div>
                        <p class="delete-copy">This account will be permanently removed from the user management table.</p>
                        <div class="modal-actions">
                            <button class="secondary-button" type="button" data-close-delete-modal>Cancel</button>
                            <button class="admin-danger-button compact" type="button" data-confirm-delete>Delete User</button>
                        </div>
                    </section>
                </div>
            </main>

            <?php include '../includes/admin_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/admin.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin.js'); ?>"></script>
</body>
</html>
