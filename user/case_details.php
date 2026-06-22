<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_user_login();
require_once __DIR__ . '/models/CaseModel.php';

$userActive = 'my_entries';
$pageTitle = 'Case Details';
$assetBase = '../';
$account = lcrms_current_account();
$caseId = (int) ($_GET['id'] ?? 0);
$case = null;

if ($account && $caseId > 0) {
    $case = (new CaseModel(lcrms_db()))->findForAccountById($account, $caseId);
}

if (!$case) {
    http_response_code(404);
}

function detail_value(?string $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function detail_case_status_value(string $status): string
{
    return match (strtolower(trim($status))) {
        'cfa', 'cfa (call for action)', 'call for action', 'cfa (certificate to file action)', 'certificate to file action', 'cfa (certificate of file action)', 'certificate of file action' => 'CFA',
        'm', 'mediation' => 'M',
        'c', 'conciliation', 'for conciliation stage' => 'C',
        default => $status,
    };
}

$statusOptions = [
    'M',
    'C',
    'CFA',
    'Endorsed',
    'Dismissed',
];

$rawCaseStatus = (string) ($case['case_status'] ?? '');
$caseStatus = detail_case_status_value($rawCaseStatus);
$removedStatusValues = ['settled'];

if ($caseStatus !== '' && !in_array(strtolower(trim($rawCaseStatus)), $removedStatusValues, true) && !in_array($caseStatus, $statusOptions, true)) {
    $statusOptions[] = $caseStatus;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Case Details | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/user.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/user.css'); ?>">
</head>
<body>
    <div class="user-layout">
        <?php include '../includes/user_nav.php'; ?>
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="user-content">
            <?php include '../includes/user_header.php'; ?>

            <main class="dashboard-main add-case-main">
                <?php if (!$case): ?>
                    <section class="entries-card full-card">
                        <div class="entries-head">
                            <div>
                                <h2>Case Details</h2>
                                <p>Case record not found or access is restricted.</p>
                            </div>
                            <a href="my_entries.php">
                                Back to My Entries
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M19 12H5M11 6l-6 6 6 6"></path>
                                </svg>
                            </a>
                        </div>
                    </section>
                <?php else: ?>
                    <section class="form-card add-case-form-card">
                        <div class="case-card-heading">
                            <div class="case-heading-title">
                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                    <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
                                    <path d="M14 2v5h5M9 15h6M9 11h6"></path>
                                </svg>
                                <div>
                                    <h2>Case Details</h2>
                                    <p>Read-only completed case record from the database.</p>
                                </div>
                            </div>
                        </div>

                        <form class="case-form readonly-case-form" aria-label="Read-only case details">
                            <div class="case-form-grid">
                                <section class="form-section">
                                    <div class="form-section-title">
                                        <h3>Case Identification</h3>
                                        <p>Basic filing details used to classify and locate the case record.</p>
                                    </div>

                                    <div class="section-grid">
                                        <div class="form-group case-number-group">
                                            <label for="caseNumber">Case Number</label>
                                            <div class="auto-gen-field">
                                                <input id="caseNumber" type="text" value="<?php echo detail_value($case['case_number'] ?? ''); ?>" readonly>
                                                <span>SAVED</span>
                                            </div>
                                            <small>System-assigned unique identifier.</small>
                                        </div>

                                        <div class="form-group wide">
                                            <label for="caseTitle">Case Title</label>
                                            <input id="caseTitle" type="text" value="<?php echo detail_value($case['case_title'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantTitle">Complainant Title</label>
                                            <input id="complainantTitle" type="text" value="<?php echo detail_value($case['complainant_title'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="natureOfCase">Nature of Case</label>
                                            <input id="natureOfCase" type="text" value="<?php echo detail_value($case['nature_of_case'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <div class="form-section-title">
                                        <h3>Complainant Information</h3>
                                        <p>Additional personal details for the complainant record.</p>
                                    </div>

                                    <div class="section-grid">
                                        <div class="form-group">
                                            <label for="complainantFullName">Full Name</label>
                                            <input id="complainantFullName" type="text" value="<?php echo detail_value($case['complainant_full_name'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantStatus">Status</label>
                                            <input id="complainantStatus" type="text" value="<?php echo detail_value($case['complainant_status'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group wide">
                                            <label for="complainantAddress">Address</label>
                                            <input id="complainantAddress" type="text" value="<?php echo detail_value($case['complainant_address'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantReligion">Religion</label>
                                            <input id="complainantReligion" type="text" value="<?php echo detail_value($case['complainant_religion'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantBirthdate">Birthdate</label>
                                            <input id="complainantBirthdate" type="date" value="<?php echo detail_value($case['complainant_birthdate'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantAge">Age</label>
                                            <input id="complainantAge" type="text" value="<?php echo detail_value((string) ($case['complainant_age'] ?? '')); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantGovernmentId">Government ID</label>
                                            <input id="complainantGovernmentId" type="text" value="<?php echo detail_value($case['complainant_government_id'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantContactNumber">Contact Number</label>
                                            <input id="complainantContactNumber" type="text" value="<?php echo detail_value($case['complainant_contact_number'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <div class="form-section-title">
                                        <h3>Respondent Information</h3>
                                        <p>Additional contact details for the respondent record.</p>
                                    </div>

                                    <div class="section-grid">
                                        <div class="form-group">
                                            <label for="respondentFullName">Full Name</label>
                                            <input id="respondentFullName" type="text" value="<?php echo detail_value($case['respondent_full_name'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group">
                                            <label for="respondentContactNumber">Contact Number</label>
                                            <input id="respondentContactNumber" type="text" value="<?php echo detail_value($case['respondent_contact_number'] ?? ''); ?>" readonly>
                                        </div>

                                        <div class="form-group wide">
                                            <label for="respondentAddress">Address</label>
                                            <input id="respondentAddress" type="text" value="<?php echo detail_value($case['respondent_address'] ?? ''); ?>" readonly>
                                        </div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <div class="form-section-title">
                                        <h3>Schedule and Status</h3>
                                        <p>Filing dates, case movement, and current case status.</p>
                                    </div>

                                    <div class="date-status-grid">
                                        <div class="form-group">
                                            <label for="dateFiled">Date Filed</label>
                                            <div class="date-field">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                                </svg>
                                                <input id="dateFiled" type="date" value="<?php echo detail_value($case['date_filed'] ?? ''); ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="initialConfrontation">Date of Initial Confrontation</label>
                                            <div class="date-field">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                                </svg>
                                                <input id="initialConfrontation" type="date" value="<?php echo detail_value($case['date_initial_confrontation'] ?? ''); ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="form-group status-field-group">
                                            <label for="status">Case Status</label>
                                            <select id="status" disabled>
                                                <?php if (in_array(strtolower(trim($rawCaseStatus)), $removedStatusValues, true)): ?>
                                                    <option value="" selected>Select case status</option>
                                                <?php endif; ?>
                                                <?php foreach ($statusOptions as $option): ?>
                                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $caseStatus === $option ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="form-group">
                                            <label for="settlementAward">Date of Settlement / Award</label>
                                            <div class="date-field">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                                </svg>
                                                <input id="settlementAward" type="date" value="<?php echo detail_value($case['date_settlement_award'] ?? ''); ?>" disabled>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="executionDate">Date of Execution</label>
                                            <div class="date-field">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                                </svg>
                                                <input id="executionDate" type="date" value="<?php echo detail_value($case['date_execution'] ?? ''); ?>" disabled>
                                            </div>
                                        </div>
                                    </div>
                                </section>

                                <section class="form-section">
                                    <div class="form-section-title">
                                        <h3>Case Narrative</h3>
                                        <p>Documented incident details and agreement reached during proceedings.</p>
                                    </div>

                                    <div class="section-grid narrative-grid">
                                        <div class="form-group">
                                            <label for="details">Detailed Case Description</label>
                                            <textarea id="details" rows="5" readonly><?php echo detail_value($case['detailed_case_description'] ?? ''); ?></textarea>
                                        </div>

                                        <div class="form-group">
                                            <label for="agreement">Main Point of Agreement</label>
                                            <textarea id="agreement" rows="5" readonly><?php echo detail_value($case['main_point_of_agreement'] ?? ''); ?></textarea>
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="case-form-actions">
                                <div class="text-actions">
                                    <a href="my_entries.php">Back to My Entries</a>
                                </div>
                                <a class="primary-button compact" href="print_case.php?id=<?php echo (int) ($case['id'] ?? 0); ?>">Print Case</a>
                            </div>
                        </form>
                    </section>
                <?php endif; ?>
            </main>

            <?php include '../includes/user_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/user.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/user.js'); ?>"></script>
</body>
</html>


