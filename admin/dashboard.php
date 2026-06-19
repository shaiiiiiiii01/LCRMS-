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
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
</head>
<body class="admin-dashboard-page">
    <div class="admin-layout">
        <?php include '../includes/admin_nav.php'; ?>
        <div class="admin-sidebar-backdrop" data-admin-sidebar-close></div>

        <div class="admin-content">
            <?php include '../includes/admin_header.php'; ?>

            <main class="admin-main">
                <section class="admin-welcome-banner">
                    <div>
                        <h2>Good Day, <?php echo htmlspecialchars($adminName); ?>!</h2>
                        <p><?php echo htmlspecialchars($adminRole); ?> account</p>
                    </div>
                </section>

                <section class="admin-stats-grid dashboard-stats-grid" aria-label="Case summary">
                    <article class="admin-stat-card dashboard-stat-card dashboard-stat-card-wide accent-blue">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Total Cases</span>
                            <strong><?php echo number_format($caseCounts['total'] ?? 0); ?></strong>
                            <p>All recorded cases in the system</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><rect x="3" y="6" width="18" height="14" rx="2"></rect><path d="M9 12h6M9 16h4"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-purple">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">New Cases</span>
                            <strong><?php echo number_format($caseCounts['new_today'] ?? 0); ?></strong>
                            <p>Recently filed cases awaiting review</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path><circle cx="12" cy="12" r="9"></circle></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-cyan">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">M</span>
                            <strong><?php echo number_format($caseCounts['mediation'] ?? 0); ?></strong>
                            <p>Cases currently under mediation process</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h6"></path><path d="M21 12a8.5 8.5 0 0 1-12.2 7.7L3 21l1.4-5.5A8.5 8.5 0 1 1 21 12z"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-green">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">C</span>
                            <strong><?php echo number_format($caseCounts['conciliation'] ?? 0); ?></strong>
                            <p>Cases undergoing settlement discussion</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 3-1.12 3-2.5S17.66 6 16 6s-3 1.12-3 2.5S14.34 11 16 11z"></path><path d="M8 11c1.66 0 3-1.12 3-2.5S9.66 6 8 6 5 7.12 5 8.5 6.34 11 8 11z"></path><path d="M2 19c.5-2.8 2.7-5 6-5 1.4 0 2.6.4 3.5 1.1M22 19c-.5-2.8-2.7-5-6-5-1.4 0-2.6.4-3.5 1.1"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-yellow">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Dismissed</span>
                            <strong><?php echo number_format($caseCounts['dismissed'] ?? 0); ?></strong>
                            <p>Closed and dismissed case records</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6M9 9l6 6"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-red">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">CFA</span>
                            <strong><?php echo number_format($caseCounts['cfa'] ?? 0); ?></strong>
                            <p>Cases approved for further legal action</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                        </div>
                    </article>

                    <article class="admin-stat-card dashboard-stat-card accent-orange">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Endorsed</span>
                            <strong><?php echo number_format($caseCounts['endorsed'] ?? 0); ?></strong>
                            <p>Cases forwarded to the proper authority</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M22 2 11 13"></path><path d="m22 2-7 20-4-9-9-4 20-7z"></path></svg>
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
                                        <th>Case No.</th>
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
                                            <td class="dashboard-empty-cell" colspan="6">No case records found.</td>
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
                            <div class="pagination-buttons">
                                <button type="button" aria-label="Previous page" <?php echo $dashboardPage <= 1 ? 'disabled' : 'onclick="window.location.href=\'' . htmlspecialchars(admin_case_page_url($dashboardPage - 1, $dashboardSearch, $dashboardStatus), ENT_QUOTES) . '\'"'; ?>><svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"></path></svg></button>
                                <?php foreach (admin_case_pagination_pages($dashboardPage, $dashboardTotalPages) as $page): ?>
                                    <?php if (is_string($page)): ?>
                                        <span>...</span>
                                    <?php else: ?>
                                        <button class="<?php echo $page === $dashboardPage ? 'is-active' : ''; ?>" type="button" onclick="window.location.href='<?php echo htmlspecialchars(admin_case_page_url((int) $page, $dashboardSearch, $dashboardStatus), ENT_QUOTES); ?>'"><?php echo $page; ?></button>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                                <button type="button" aria-label="Next page" <?php echo $dashboardPage >= $dashboardTotalPages ? 'disabled' : 'onclick="window.location.href=\'' . htmlspecialchars(admin_case_page_url($dashboardPage + 1, $dashboardSearch, $dashboardStatus), ENT_QUOTES) . '\'"'; ?>><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></button>
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
