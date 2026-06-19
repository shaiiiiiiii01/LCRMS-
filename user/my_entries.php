<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_user_login();
require_once __DIR__ . '/models/CaseModel.php';

$userActive = 'my_entries';
$pageTitle = 'My Entries';
$assetBase = '../';
$account = lcrms_current_account();
$search = trim((string) ($_GET['search'] ?? ''));
$cases = [];

if ($account) {
    $cases = (new CaseModel(lcrms_db()))->listForAccount($account, $search);
}

function entry_date_label(?string $date): string
{
    $date = trim((string) $date);

    if ($date === '') {
        return 'Not set';
    }

    $timestamp = strtotime($date);

    return $timestamp === false ? $date : date('M j, Y', $timestamp);
}

function entry_status_class(string $status): string
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

function entry_status_label(string $status): string
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
    <title>My Entries | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/user.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/user.css'); ?>">
</head>
<body class="user-dashboard-page">
    <div class="user-layout">
        <?php include '../includes/user_nav.php'; ?>
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="user-content">
            <?php include '../includes/user_header.php'; ?>

            <main class="dashboard-main">
                <section class="entries-card full-card">
                    <div class="entries-head">
                        <div>
                            <h2>My Case Entries</h2>
                            <p>Your saved case records from the database.</p>
                        </div>
                        <a href="add_cases.php">
                            Add Case
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M12 5v14M5 12h14"></path>
                            </svg>
                        </a>
                    </div>

                    <form class="entries-search-form" action="my_entries.php" method="get" role="search">
                        <label class="entries-search-field" for="caseSearch">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="M16 16l4 4"></path>
                            </svg>
                            <input id="caseSearch" name="search" type="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by Case Title">
                        </label>
                        <button class="entries-search-button" type="submit">Search</button>
                        <?php if ($search !== ''): ?>
                            <a class="entries-clear-search" href="my_entries.php">Clear</a>
                        <?php endif; ?>
                    </form>

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
                                <?php if ($cases === []): ?>
                                    <tr>
                                        <td class="entries-empty" colspan="6">
                                            <?php echo $search === '' ? 'No case records found.' : 'No case records found for your search.'; ?>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($cases as $case): ?>
                                        <tr class="entry-row" data-entry-row data-case-detail-row data-case-id="<?php echo htmlspecialchars((string) $case['id']); ?>" tabindex="0" role="button">
                                            <td><a href="#" data-case-detail-link><?php echo htmlspecialchars((string) $case['case_number']); ?></a></td>
                                            <td><?php echo htmlspecialchars((string) $case['case_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) $case['complainant_title']); ?></td>
                                            <td><?php echo htmlspecialchars((string) ($case['nature_of_case'] ?? '')); ?></td>
                                            <td><span class="status-pill <?php echo entry_status_class((string) $case['case_status']); ?>"><?php echo htmlspecialchars(entry_status_label((string) $case['case_status'])); ?></span></td>
                                            <td><?php echo htmlspecialchars(entry_date_label($case['date_filed'] ?? null)); ?></td>
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

