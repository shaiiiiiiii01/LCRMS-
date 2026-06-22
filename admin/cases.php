<?php
require_once __DIR__ . '/auth.php';
require_admin_login();
require_once __DIR__ . '/../user/models/CaseModel.php';
require_once __DIR__ . '/case_helpers.php';

$adminActive = 'cases';
$pageTitle = 'Case Management';
$adminName = $_SESSION['fullname'] ?? $_SESSION['admin_fullname'] ?? $_SESSION['username'] ?? 'Admin';
$adminRole = $_SESSION['role'] ?? 'Admin';
$assetBase = '../';
$caseModel = new CaseModel(lcrms_db());
$caseSearch = trim((string) ($_GET['search'] ?? ''));
$caseStatus = trim((string) ($_GET['status'] ?? ''));
$caseDateFilter = trim((string) ($_GET['date_filter'] ?? ''));
$caseDateValue = trim((string) ($_GET['date_value'] ?? ''));
$casePage = max(1, (int) ($_GET['page'] ?? 1));
$casePerPage = 20;
$caseCounts = $caseModel->adminCounts();
$caseTotal = $caseModel->countForAdmin($caseSearch, $caseStatus, $caseDateFilter, $caseDateValue);
$caseTotalPages = max(1, (int) ceil($caseTotal / $casePerPage));

if ($casePage > $caseTotalPages) {
    $casePage = $caseTotalPages;
}

$cases = $caseModel->listForAdmin($caseSearch, $caseStatus, $casePage, $casePerPage, $caseDateFilter, $caseDateValue);
$caseStart = $caseTotal === 0 ? 0 : (($casePage - 1) * $casePerPage) + 1;
$caseEnd = min($caseTotal, $caseStart + count($cases) - 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cases | LCRMS Admin</title>
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/admin.css'); ?>">
</head>
<body class="admin-dashboard-page">
    <div class="admin-layout">
        <?php include '../includes/admin_nav.php'; ?>
        <div class="admin-sidebar-backdrop" data-admin-sidebar-close></div>

        <div class="admin-content">
            <?php include '../includes/admin_header.php'; ?>

            <main class="admin-main cases-main">
                <section class="admin-stats-grid dashboard-stats-grid case-summary-strip" aria-label="Case totals">
                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-blue<?php echo $caseStatus === '' && $caseSearch === '' ? ' is-active' : ''; ?>" href="cases.php#caseSearch" data-case-filter-card data-case-filter-status="">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Total Cases</span>
                            <strong><?php echo number_format($caseCounts['total'] ?? 0); ?></strong>
                            <p>All recorded cases in the system</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><rect x="3" y="6" width="18" height="14" rx="2"></rect><path d="M9 12h6M9 16h4"></path></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-purple<?php echo $caseStatus === 'new' ? ' is-active' : ''; ?>" href="cases.php?status=new#caseSearch" data-case-filter-card data-case-filter-status="new">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">New Cases</span>
                            <strong><?php echo number_format($caseCounts['new_today'] ?? 0); ?></strong>
                            <p>Recently filed cases awaiting review</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"></path><circle cx="12" cy="12" r="9"></circle></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-cyan<?php echo $caseStatus === 'm' ? ' is-active' : ''; ?>" href="cases.php?status=m#caseSearch" data-case-filter-card data-case-filter-status="m">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">M</span>
                            <strong><?php echo number_format($caseCounts['mediation'] ?? 0); ?></strong>
                            <p>Cases currently under mediation process</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M7 8h10M7 12h6"></path><path d="M21 12a8.5 8.5 0 0 1-12.2 7.7L3 21l1.4-5.5A8.5 8.5 0 1 1 21 12z"></path></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-green<?php echo $caseStatus === 'c' ? ' is-active' : ''; ?>" href="cases.php?status=c#caseSearch" data-case-filter-card data-case-filter-status="c">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">C</span>
                            <strong><?php echo number_format($caseCounts['conciliation'] ?? 0); ?></strong>
                            <p>Cases undergoing settlement discussion</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 3-1.12 3-2.5S17.66 6 16 6s-3 1.12-3 2.5S14.34 11 16 11z"></path><path d="M8 11c1.66 0 3-1.12 3-2.5S9.66 6 8 6 5 7.12 5 8.5 6.34 11 8 11z"></path><path d="M2 19c.5-2.8 2.7-5 6-5 1.4 0 2.6.4 3.5 1.1M22 19c-.5-2.8-2.7-5-6-5-1.4 0-2.6.4-3.5 1.1"></path></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-yellow<?php echo $caseStatus === 'dismissed' ? ' is-active' : ''; ?>" href="cases.php?status=dismissed#caseSearch" data-case-filter-card data-case-filter-status="dismissed">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Dismissed</span>
                            <strong><?php echo number_format($caseCounts['dismissed'] ?? 0); ?></strong>
                            <p>Closed and dismissed case records</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"></circle><path d="m15 9-6 6M9 9l6 6"></path></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-red<?php echo $caseStatus === 'cfa' ? ' is-active' : ''; ?>" href="cases.php?status=cfa#caseSearch" data-case-filter-card data-case-filter-status="cfa">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">CFA</span>
                            <strong><?php echo number_format($caseCounts['cfa'] ?? 0); ?></strong>
                            <p>Cases approved for further legal action</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M10.3 3.9 1.8 18a2 2 0 0 0 1.7 3h17a2 2 0 0 0 1.7-3L13.7 3.9a2 2 0 0 0-3.4 0z"></path><path d="M12 9v4M12 17h.01"></path></svg>
                        </div>
                    </a>

                    <a class="admin-stat-card dashboard-stat-card cases-filter-card accent-orange<?php echo $caseStatus === 'endorsed' ? ' is-active' : ''; ?>" href="cases.php?status=endorsed#caseSearch" data-case-filter-card data-case-filter-status="endorsed">
                        <div class="dashboard-stat-copy">
                            <span class="dashboard-stat-title">Endorsed</span>
                            <strong><?php echo number_format($caseCounts['endorsed'] ?? 0); ?></strong>
                            <p>Cases forwarded to the proper authority</p>
                        </div>
                        <div class="dashboard-stat-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M22 2 11 13"></path><path d="m22 2-7 20-4-9-9-4 20-7z"></path></svg>
                        </div>
                    </a>
                </section>

                <section class="cases-panel">
                    <form class="cases-toolbar" id="caseSearch" action="cases.php" method="get" data-admin-case-filters>
                        <div class="case-search-block">
                            <label>Search Cases</label>
                            <div class="admin-search-field wide">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"></circle><path d="M20 20l-3.5-3.5"></path></svg>
                                <input type="search" name="search" value="<?php echo htmlspecialchars($caseSearch); ?>" placeholder="Case Number, Complainant, or Title..." data-admin-case-search>
                            </div>
                        </div>

                        <div class="filter-row">
                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 3H2l8 9.46V19l4 2v-8.54z"></path></svg>
                                Filter by:
                            </span>
                            <select name="status" aria-label="Case status filter" data-admin-case-filter>
                                <option value="">All Statuses</option>
                                <option value="new"<?php echo admin_case_selected($caseStatus, 'new'); ?>>New Cases</option>
                                <option value="cfa"<?php echo admin_case_selected($caseStatus, 'cfa'); ?>>CFA</option>
                                <option value="resolved"<?php echo admin_case_selected($caseStatus, 'resolved'); ?>>Resolved</option>
                                <option value="m"<?php echo admin_case_selected($caseStatus, 'm'); ?>>M</option>
                                <option value="c"<?php echo admin_case_selected($caseStatus, 'c'); ?>>C</option>
                                <option value="endorsed"<?php echo admin_case_selected($caseStatus, 'endorsed'); ?>>Endorsed</option>
                                <option value="dismissed"<?php echo admin_case_selected($caseStatus, 'dismissed'); ?>>Dismissed</option>
                            </select>
                        </div>

                        <div class="filter-row date-filter-row">
                            <span>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"></rect><path d="M16 2v4M8 2v4M3 10h18"></path></svg>
                                Date:
                            </span>
                            <select name="date_filter" aria-label="Date field filter" data-admin-case-filter>
                                <option value="">Select Date</option>
                                <option value="date_filed"<?php echo admin_case_selected($caseDateFilter, 'date_filed'); ?>>Date Filed</option>
                                <option value="date_initial_confrontation"<?php echo admin_case_selected($caseDateFilter, 'date_initial_confrontation'); ?>>Initial Confrontation</option>
                                <option value="date_settlement_award"<?php echo admin_case_selected($caseDateFilter, 'date_settlement_award'); ?>>Settlement / Award</option>
                                <option value="date_execution"<?php echo admin_case_selected($caseDateFilter, 'date_execution'); ?>>Execution</option>
                            </select>
                            <input type="date" name="date_value" value="<?php echo htmlspecialchars($caseDateValue); ?>" aria-label="Date value in MM/DD/YYYY format" title="MM/DD/YYYY" data-admin-case-date>
                        </div>

                        <div class="cases-toolbar-actions">
                            <button class="export-button" type="submit" formaction="export_cases.php" formmethod="get">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M7 10l5 5 5-5M12 15V3"></path></svg>
                                Export
                            </button>
                            <button class="import-button" type="button" data-open-case-import>
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><path d="M17 8l-5-5-5 5M12 3v12"></path></svg>
                                Import
                            </button>
                        </div>
                    </form>

                    <div class="admin-table-wrap" data-admin-cases data-case-api="cases_api.php">
                        <table class="admin-cases-table">
                            <thead>
                                <tr>
                                    <th>Case Number</th>
                                    <th>Case Title</th>
                                    <th>Complainant Title</th>
                                    <th>Nature</th>
                                    <th>Status</th>
                                    <th>Date Filed</th>
                                    <th>Encoder</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($cases === []): ?>
                                    <tr>
                                        <td class="admin-cases-empty-cell" colspan="7">No case records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cases as $case): ?>
                                        <tr class="admin-case-row" data-admin-case-row data-case-id="<?php echo htmlspecialchars((string) $case['id']); ?>" tabindex="0" role="button">
                                            <td><?php echo htmlspecialchars((string) $case['case_number']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $case['case_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $case['complainant_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($case['nature_of_case'] ?? '')); ?></td>
                                            <td><span class="case-badge <?php echo admin_case_badge_class((string) $case['case_status']); ?>"><?php echo htmlspecialchars(admin_case_status_label((string) $case['case_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(admin_case_date_label($case['date_filed'] ?? null)); ?></td>
                                            <td><?php echo htmlspecialchars((string) $case['created_by']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="cases-pagination">
                        <div class="pagination-buttons">
                            <button type="button" aria-label="Previous page" <?php echo $casePage <= 1 ? 'disabled' : 'data-case-page-url="' . htmlspecialchars(admin_case_page_url($casePage - 1, $caseSearch, $caseStatus, $caseDateFilter, $caseDateValue), ENT_QUOTES) . '"'; ?>><svg viewBox="0 0 24 24"><path d="M15 18l-6-6 6-6"></path></svg></button>
                            <?php foreach (admin_case_pagination_pages($casePage, $caseTotalPages) as $page): ?>
                                <?php if (is_string($page)): ?>
                                    <span>...</span>
                                <?php else: ?>
                                    <button class="<?php echo $page === $casePage ? 'is-active' : ''; ?>" type="button" data-case-page-url="<?php echo htmlspecialchars(admin_case_page_url((int) $page, $caseSearch, $caseStatus, $caseDateFilter, $caseDateValue), ENT_QUOTES); ?>"><?php echo $page; ?></button>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <button type="button" aria-label="Next page" <?php echo $casePage >= $caseTotalPages ? 'disabled' : 'data-case-page-url="' . htmlspecialchars(admin_case_page_url($casePage + 1, $caseSearch, $caseStatus, $caseDateFilter, $caseDateValue), ENT_QUOTES) . '"'; ?>><svg viewBox="0 0 24 24"><path d="M9 18l6-6-6-6"></path></svg></button>
                        </div>
                    </div>

                    <?php include __DIR__ . '/case_import_modal.php'; ?>
                </section>

                <section class="admin-lower-actions">
                    <article class="system-status-card">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 15.5a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7z"></path><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.8 1.8 0 0 0-1.98-.36 1.8 1.8 0 0 0-1.08 1.65V21a2 2 0 1 1-4 0v-.09A1.8 1.8 0 0 0 8.8 19.26a1.8 1.8 0 0 0-1.98.36l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.8 1.8 0 0 0 4.35 15a1.8 1.8 0 0 0-1.65-1.08H2.6a2 2 0 1 1 0-4h.09A1.8 1.8 0 0 0 4.35 8.8a1.8 1.8 0 0 0-.36-1.98l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.8 1.8 0 0 0 1.98.36h.01A1.8 1.8 0 0 0 9.9 2.7V2.6a2 2 0 1 1 4 0v.09a1.8 1.8 0 0 0 1.08 1.65 1.8 1.8 0 0 0 1.98-.36l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.8 1.8 0 0 0-.36 1.98v.01a1.8 1.8 0 0 0 1.65 1.08h.09a2 2 0 1 1 0 4h-.09A1.8 1.8 0 0 0 19.4 15z"></path></svg>
                        <div><small>System Status</small><strong>Database Backup: Healthy</strong></div>
                        <span>Synced</span>
                    </article>
                </section>
            </main>

            <?php include '../includes/admin_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/admin.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/admin.js'); ?>"></script>
</body>
</html>

