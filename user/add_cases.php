<?php
require_once __DIR__ . '/../includes/auth_user.php';
require_user_login();

$userActive = 'add_cases';
$pageTitle = 'Add New Case';
$footerYear = '2026';
$assetBase = '../';
$caseNumberPreview = 'L-' . date('Y') . '-0001';

require_once __DIR__ . '/models/CaseModel.php';

try {
    $caseNumberPreview = (new CaseModel(lcrms_db()))->previewNextCaseNumber();
} catch (Throwable $exception) {
    $caseNumberPreview = 'L-' . date('Y') . '-0001';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Case | LCRMS</title>
    <link rel="stylesheet" href="../assets/css/user.css">
</head>
<body>
    <div class="user-layout">
        <?php include '../includes/user_nav.php'; ?>
        <div class="sidebar-backdrop" data-sidebar-close></div>

        <div class="user-content">
            <?php include '../includes/user_header.php'; ?>

            <main class="dashboard-main add-case-main">
                <section class="guidelines-panel" aria-label="Data entry guidelines">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <circle cx="12" cy="12" r="10"></circle>
                        <path d="M12 16v-4M12 8h.01"></path>
                    </svg>
                    <div>
                        <strong>Data Entry Guidelines</strong>
                        <p>Please review each entry carefully before saving. Use clear, complete details so the case record is easy to track and update.</p>
                    </div>
                </section>

                <section class="form-card add-case-form-card">
                    <div class="case-card-heading">
                        <div class="case-heading-title">
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
                                <path d="M14 2v5h5M12 11v6M9 14h6"></path>
                            </svg>
                            <div>
                                <h2>New Case Record</h2>
                                <p>Populate the fields below to initiate a formal case entry into the system.</p>
                            </div>
                        </div>
                    </div>

                    <form class="case-form" action="cases_api.php?action=create" method="post" data-case-form novalidate>
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
                                            <input id="caseNumber" name="case_number" type="text" value="<?php echo htmlspecialchars($caseNumberPreview); ?>" readonly>
                                            <span>AUTO-GEN</span>
                                        </div>
                                        <small>System-assigned unique identifier.</small>
                                    </div>

                                    <div class="form-group wide">
                                        <label for="caseTitle">Case Title</label>
                                        <input id="caseTitle" name="case_title" type="text" placeholder="e.g., MARITES TOLENTINO VS. JOHN PAUL BROWN">
                                    </div>

                                    <div class="form-group">
                                        <label for="complainantTitle">Complainant Title</label>
                                        <input id="complainantTitle" name="complainant_title" type="text" placeholder="e.g., Ejection">
                                    </div>

                                    <div class="form-group">
                                        <label for="natureOfCase">Nature of Case</label>
                                        <input id="natureOfCase" name="nature_of_case" type="text" placeholder="e.g., Civil">
                                    </div>
                                </div>
                            </section>

                            <section class="form-section">
                                <div class="form-section-title">
                                    <h3>Schedule and Status</h3>
                                    <p>Track filing dates, case movement, and the current case status.</p>
                                </div>

                                <div class="date-status-grid">
                                    <div class="form-group">
                                        <label for="dateFilled">Date Filed</label>
                                        <div class="date-field">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                            <input id="dateFilled" name="date_filed" type="date">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="initialConfrontation">Date of initial confrontation</label>
                                        <div class="date-field">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                            <input id="initialConfrontation" name="date_initial_confrontation" type="date">
                                        </div>
                                    </div>

                                    <div class="form-group status-field-group">
                                        <label for="status">Case Status</label>
                                        <select id="status" name="case_status">
                                            <option value="" selected>Select case status</option>
                                            <option>For Conciliation Stage</option>
                                            <option>Mediation</option>
                                            <option>Conciliation</option>
                                            <option>CFA (Call for action)</option>
                                            <option>Endorsed</option>
                                            <option>Dismissed</option>
                                        </select>
                                    </div>

                                    <div class="form-group">
                                        <label for="settlementAward">Date of Settlement / Award</label>
                                        <div class="date-field">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                            <input id="settlementAward" name="date_settlement_award" type="date">
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="executionDate">Date of execution</label>
                                        <div class="date-field">
                                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                            <input id="executionDate" name="date_execution" type="date">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="form-section">
                                <div class="form-section-title">
                                    <h3>Case Narrative</h3>
                                    <p>Document the incident details and any agreement reached during proceedings.</p>
                                </div>

                                <div class="section-grid narrative-grid">
                                    <div class="form-group">
                                        <label for="details">Detailed Case Description</label>
                                        <textarea id="details" name="detailed_case_description" rows="5" placeholder="Provide a comprehensive narrative of the incident, including specific dates, locations, and actions involved..."></textarea>
                                    </div>

                                    <div class="form-group">
                                        <label for="agreement">Main Point of Agreement</label>
                                        <textarea id="agreement" name="main_point_of_agreement" rows="5" placeholder="Provide the agreement of the case"></textarea>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="form-note">
                            <p>Maximum 2500 characters required for detailed documentation and the main point of agreement</p>
                        </div>

                        <div class="case-form-actions">
                            <div class="text-actions">
                                <button type="reset">Clear Form</button>
                                <a href="dashboard.php">Cancel</a>
                            </div>
                            <button class="primary-button compact" type="submit">Save Record</button>
                        </div>
                    </form>
                </section>

                <p class="privacy-note">All saved records are encrypted and compliant with the Data Privacy Act of 2012. Need help? <a href="#">Contact System Administrator.</a></p>
            </main>

            <?php include '../includes/user_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/user.js"></script>
</body>
</html>
