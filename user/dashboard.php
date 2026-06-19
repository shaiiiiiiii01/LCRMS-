<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_user_login();
require_once __DIR__ . '/models/CaseModel.php';

$userActive = 'dashboard';
$pageTitle = 'Dashboard Overview';
$userName = $_SESSION['fullname'] ?? $_SESSION['username'] ?? 'User';
$assetBase = '../';
$account = lcrms_current_account();
$recentCases = [];
$entriesToday = 0;

if ($account) {
    $caseModel = new CaseModel(lcrms_db());
    $recentCases = $caseModel->listForAccount($account, '', 5);
    $entriesToday = $caseModel->countTodayForAccount($account);
}

function dashboard_entry_date_label(?string $date): string
{
    $date = trim((string) $date);

    if ($date === '') {
        return 'Not set';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y', $timestamp);
}

function dashboard_entry_status_class(string $status): string
{
    $status = strtolower($status);

    if (str_contains($status, 'dismiss')) {
        return 'status-dismissed';
    }

    if (str_contains($status, 'endors')) {
        return 'status-endoresed';
    }

    if (str_contains($status, 'cfa') || str_contains($status, 'call for action')) {
        return 'status-cfa';
    }

    if (str_contains($status, 'mediation')) {
        return 'status-m';
    }

    return 'status-c';
}

function dashboard_entry_status_label(string $status): string
{
    $normalized = strtolower(trim($status));

    return match ($normalized) {
        'cfa', 'cfa (call for action)', 'call for action', 'cfa (certificate to file action)', 'certificate to file action', 'cfa (certificate of file action)', 'certificate of file action' => 'CFA',
        'm', 'mediation' => 'M',
        'c', 'conciliation', 'for conciliation stage' => 'C',
        default => $status,
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/user.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/user.css'); ?>">
</head>
<body class="user-dashboard-page user-home-page">
    <div class="user-layout">
        <?php include '../includes/user_nav.php'; ?>

        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="user-content">
            <?php include '../includes/user_header.php'; ?>

            <main class="dashboard-main">
                <section class="welcome-banner">
                    <div>
                        <h2>Good Day, <?php echo htmlspecialchars($userName); ?>!</h2>
                        <p><?php echo htmlspecialchars($_SESSION['role'] ?? 'User'); ?> account</p>
                    </div>
                </section>

                <section class="quick-grid" aria-label="Dashboard shortcuts">
                    <a class="add-case-card" href="add_cases.php">
                        <span class="round-action-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="12" cy="12" r="9"></circle>
                                <path d="M12 8v8M8 12h8"></path>
                            </svg>
                        </span>
                        <span class="add-case-copy">
                            <strong>Add New Case Record</strong>
                            <span>Start a new filing for a complainant or respondent complaint.</span>
                        </span>
                        <svg class="chevron-icon" viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </a>

                    <article class="summary-card">
                        <div>
                            <h3>TODAY'S SUMMARY</h3>
                            <strong><?php echo $entriesToday; ?></strong>
                            <p>Total Entries Today</p>
                        </div>
                        <span class="summary-icon">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <path d="M14 2v6h6M9 15h6M9 11h2"></path>
                            </svg>
                        </span>
                    </article>
                </section>

                <section class="entries-card">
                    <div class="entries-head">
                        <div>
                            <h2>Recent Entries</h2>
                            <p>Your most recent case filings and updates.</p>
                        </div>
                        <a href="my_entries.php">
                            View All
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M5 12h14M13 6l6 6-6 6"></path>
                            </svg>
                        </a>
                    </div>

                    <div class="table-wrap">
                        <table class="entries-table">
                            <thead>
                                <tr>
                                    <th>Case Number</th>
                                    <th>Case Title</th>
                                    <th>Complainant Title</th>
                                    <th>Nature</th>
                                    <th>Case Status</th>
                                    <th>Date Filed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recentCases === []): ?>
                                    <tr>
                                        <td class="entries-empty" colspan="6">No case records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentCases as $case): ?>
                                        <tr class="entry-row" data-entry-row data-case-detail-row data-case-id="<?php echo htmlspecialchars((string) $case['id']); ?>" tabindex="0" role="button">
                                            <td><a href="#" data-case-detail-link><?php echo htmlspecialchars((string) $case['case_number']); ?></a></td>
                                            <td><?php echo htmlspecialchars((string) $case['case_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $case['complainant_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($case['nature_of_case'] ?? '')); ?></td>
                                            <td><span class="status-pill <?php echo dashboard_entry_status_class((string) $case['case_status']); ?>"><?php echo htmlspecialchars(dashboard_entry_status_label((string) $case['case_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(dashboard_entry_date_label($case['date_filed'] ?? null)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </main>

            <?php include '../includes/user_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/user.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/user.js'); ?>"></script>
</body>
</html>

