<?php
require_once __DIR__ . '/auth.php';
require_admin_login();
require_once __DIR__ . '/../user/models/CaseModel.php';
require_once __DIR__ . '/case_helpers.php';

$adminActive = 'dashboard';
$pageTitle = 'Admin Overview';
$adminName = $_SESSION['fullname'] ?? $_SESSION['admin_fullname'] ?? $_SESSION['username'] ?? 'Admin';
$adminRole = $_SESSION['role'] ?? 'Admin';
$assetBase = '../';
$caseModel = new CaseModel(lcrms_db());
$dashboardSearch = trim((string) ($_GET['search'] ?? ''));
$dashboardStatus = trim((string) ($_GET['status'] ?? ''));
$dashboardPage = max(1, (int) ($_GET['page'] ?? 1));
$dashboardPerPage = 5;
$caseCounts = $caseModel->adminCounts();
$dashboardTotal = $caseModel->countForAdmin($dashboardSearch, $dashboardStatus);
$dashboardTotalPages = max(1, (int) ceil($dashboardTotal / $dashboardPerPage));

if ($dashboardPage > $dashboardTotalPages) {
    $dashboardPage = $dashboardTotalPages;
}

$dashboardCases = $caseModel->listForAdmin($dashboardSearch, $dashboardStatus, $dashboardPage, $dashboardPerPage);
$dashboardStart = $dashboardTotal === 0 ? 0 : (($dashboardPage - 1) * $dashboardPerPage) + 1;
$dashboardEnd = min($dashboardTotal, $dashboardStart + count($dashboardCases) - 1);
$dashboardExportParams = [];

if ($dashboardSearch !== '') {
    $dashboardExportParams['search'] = $dashboardSearch;
}

if ($dashboardStatus !== '') {
    $dashboardExportParams['status'] = $dashboardStatus;
}

$dashboardExportUrl = 'export_cases.php' . ($dashboardExportParams === [] ? '' : '?' . http_build_query($dashboardExportParams));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <?php include '../includes/admin_nav.php'; ?>
        <div class="admin-sidebar-backdrop" data-admin-sidebar-close></div>

        <div class="admin-content">
            <?php include '../includes/admin_header.php'; ?>

            <main class="admin-main">
                <section class="admin-stats-grid dashboard-stats-grid" aria-label="Case summary">
                    <article class="admin-stat-card dashboard-stat-card accent-blue">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Total Cases</span>
                            <strong><?php echo number_format($caseCounts['total'] ?? 0); ?></strong>
                            <p>All recorded cases</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><rect x="3" y="6" width="18" height="14" rx="2"></rect><path d="M9 12h6M9 16h4"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-purple">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">New Cases</span>
                            <strong><?php echo number_format($caseCounts['new_today'] ?? 0); ?></strong>
                            <p>Cases created today</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path><circle cx="12" cy="12" r="9"></circle></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-red">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Call for Action (CFA)</span>
                            <strong><?php echo number_format($caseCounts['cfa'] ?? 0); ?></strong>
                            <p>Cases requiring action</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-green">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Resolved</span>
                            <strong><?php echo number_format($caseCounts['resolved'] ?? 0); ?></strong>
                            <p>Settled, mediation, and conciliation cases</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="M8 12.5l2.6 2.6L16 9"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-orange">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Endorsed</span>
                            <strong><?php echo number_format($caseCounts['endorsed'] ?? 0); ?></strong>
                            <p>Cases endorsed onward</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M22 2 11 13"></path><path d="m22 2-7 20-4-9-9-4 20-7z"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-yellow">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Dismissed</span>
                            <strong><?php echo number_format($caseCounts['dismissed'] ?? 0); ?></strong>
                            <p>Cases marked dismissed</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6M9 9l6 6"></path></svg>
                        </div>
                    </article>
                </section>

                <section class="dashboard-case-management" aria-labelledby="caseManagementTitle">
                    <div class="dashboard-section-head">
                        <h2 id="caseManagementTitle">Case Management</h2>
                        <div class="cases-toolbar-actions dashboard-shortcut-actions">
                            <a class="export-button" href="<?php echo htmlspecialchars($dashboardExportUrl); ?>">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5M12 15V3"></path></svg>
                                Export
                            </a>
                            <button class="import-button" type="button" data-open-case-import>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5M12 3v12"></path></svg>
                                Import
                            </button>
                        </div>
                    </div>

                    <form class="dashboard-filter-card" action="dashboard.php" method="get" data-admin-case-filters data-admin-dashboard-case-selection>
                        <div class="admin-search-field dashboard-search">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                            <input type="search" name="search" value="<?php echo htmlspecialchars($dashboardSearch); ?>" placeholder="Search by case number, title, or parties..." data-admin-case-search>
                        </div>

                        <div class="dashboard-filter-divider" aria-hidden="true"></div>

                        <div class="filter-row dashboard-filter-row">
                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"></path></svg>
                                Filter by:
                            </span>
                            <select name="status" aria-label="Filter by case status" data-admin-case-filter>
                                <option value="">All Statuses</option>
                                <option value="cfa"<?php echo admin_case_selected($dashboardStatus, 'cfa'); ?>>CFA</option>
                                <option value="m"<?php echo admin_case_selected($dashboardStatus, 'm'); ?>>M</option>
                                <option value="c"<?php echo admin_case_selected($dashboardStatus, 'c'); ?>>C</option>
                                <option value="settled"<?php echo admin_case_selected($dashboardStatus, 'settled'); ?>>Settled</option>
                                <option value="endorsed"<?php echo admin_case_selected($dashboardStatus, 'endorsed'); ?>>Endorsed</option>
                                <option value="dismissed"<?php echo admin_case_selected($dashboardStatus, 'dismissed'); ?>>Dismissed</option>
                            </select>
                            <button class="dashboard-view-selected-button" type="button" data-dashboard-view-selected disabled>View</button>
                        </div>
                    </form>

                    <div class="dashboard-cases-card" data-admin-dashboard-case-selection>
                        <div class="admin-table-wrap dashboard-table-wrap">
                            <table class="admin-cases-table dashboard-cases-table">
                                <thead>
                                    <tr>
                                        <th>Case<br>No.</th>
                                        <th>Case Title</th>
                                        <th>Complainant Title</th>
                                        <th>Nature</th>
                                        <th>Date Filed</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($dashboardCases === []): ?>
                                        <tr>
                                            <td colspan="6">No case records found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($dashboardCases as $case): ?>
                                            <tr class="dashboard-case-row" data-dashboard-case-row data-case-id="<?php echo htmlspecialchars((string) $case['id']); ?>" tabindex="0" role="button" aria-selected="false">
                                                <td><?php echo htmlspecialchars((string) $case['case_number']); ?></td>
                                                <td><?php echo htmlspecialchars((string) $case['case_title']); ?></td>
                                                <td><?php echo htmlspecialchars((string) $case['complainant_title']); ?></td>
                                                <td><?php echo htmlspecialchars((string) ($case['nature_of_case'] ?? '')); ?></td>
                                                <td><?php echo htmlspecialchars(admin_case_date_label($case['date_filed'] ?? null)); ?></td>
                                                <td><span class="case-badge <?php echo admin_case_badge_class((string) $case['case_status']); ?>"><?php echo htmlspecialchars(admin_case_status_label((string) $case['case_status'])); ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="dashboard-pagination">
                            <p>Showing <strong><?php echo number_format($dashboardStart); ?></strong> to <strong><?php echo number_format($dashboardEnd); ?></strong> of <strong><?php echo number_format($dashboardTotal); ?></strong> cases</p>
                            <div class="pagination-buttons">
                                <button type="button" <?php echo $dashboardPage <= 1 ? 'disabled' : 'onclick="window.location.href=\'' . htmlspecialchars(admin_case_page_url($dashboardPage - 1, $dashboardSearch, $dashboardStatus), ENT_QUOTES) . '\'"'; ?>>Previous</button>
                                <?php foreach (admin_case_pagination_pages($dashboardPage, $dashboardTotalPages) as $page): ?>
                                    <?php if (is_string($page)): ?>
                                        <span>...</span>
                                    <?php else: ?>
                                        <button class="<?php echo $page === $dashboardPage ? 'is-active' : ''; ?>" type="button" onclick="window.location.href='<?php echo htmlspecialchars(admin_case_page_url((int) $page, $dashboardSearch, $dashboardStatus), ENT_QUOTES); ?>'"><?php echo $page; ?></button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <button type="button" <?php echo $dashboardPage >= $dashboardTotalPages ? 'disabled' : 'onclick="window.location.href=\'' . htmlspecialchars(admin_case_page_url($dashboardPage + 1, $dashboardSearch, $dashboardStatus), ENT_QUOTES) . '\'"'; ?>>Next</button>
                            </div>
                        </div>
                    </div>

                    <?php include __DIR__ . '/case_import_modal.php'; ?>
                </section>
            </main>

            <?php include '../includes/admin_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/admin.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin.js'); ?>"></script>
</body>
</html>
