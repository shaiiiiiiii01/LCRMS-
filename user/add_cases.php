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
    <link rel="stylesheet" href="../assets/css/user.css?v=<?php echo filemtime(__DIR__ . '/../assets/css/user.css'); ?>">
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
                    <form class="case-form" action="cases_api.php?action=create" method="post" data-case-form novalidate>
                        <div class="case-form-grid">
                            <section class="form-section case-id-section">
                                <div class="form-section-title">
                                    <div class="section-title-copy">
                                        <span class="section-heading-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
                                                <path d="M14 2v5h5M9 13h6M9 17h4"></path>
                                            </svg>
                                        </span>
                                        <div>
                                            <h3>Case Identification</h3>
                                            <p>Basic filing details used to classify and locate the case record.</p>
                                        </div>
                                    </div>
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
                                        <input id="caseTitle" name="case_title" type="text" placeholder="COMPLAINANT VS RESPONDENT" maxlength="255" data-letters-uppercase>
                                    </div>

                                    <div class="form-group">
                                        <label for="complainantTitle">Complainant Title</label>
                                        <input id="complainantTitle" name="complainant_title" type="text" placeholder="EJECTION" maxlength="255" data-letters-uppercase>
                                    </div>

                                    <div class="form-group">
                                        <span class="case-choice-label" id="natureOfCaseLabel">Nature of Case</span>
                                        <div class="case-choice-boxes" role="radiogroup" aria-labelledby="natureOfCaseLabel" data-choice-boxes>
                                            <label class="case-choice-box" for="natureCivil">
                                                <input id="natureCivil" name="nature_of_case" type="radio" value="Civil">
                                                <span>Civil</span>
                                            </label>
                                            <label class="case-choice-box" for="natureCriminal">
                                                <input id="natureCriminal" name="nature_of_case" type="radio" value="Criminal">
                                                <span>Criminal</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="form-section case-party-section complainant-section is-collapsed" data-case-collapsible>
                                <div class="form-section-title">
                                    <div class="section-title-copy">
                                        <span class="section-heading-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                                <circle cx="12" cy="8" r="4"></circle>
                                            </svg>
                                        </span>
                                        <div>
                                            <h3>Complainant Information</h3>
                                            <p>Additional personal details for the complainant record.</p>
                                        </div>
                                    </div>
                                    <button class="section-toggle-button" type="button" data-case-collapsible-toggle aria-expanded="false" aria-controls="complainantInfoFields">
                                        <span>Show fields</span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div id="complainantInfoFields" class="collapsible-section-body" data-case-collapsible-body aria-hidden="true">
                                    <div class="section-grid">
                                        <div class="form-group">
                                            <label for="complainantFullName">Full Name</label>
                                            <input id="complainantFullName" name="complainant_full_name" type="text" placeholder="Enter full name" maxlength="255" data-letters-uppercase>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantStatus">Status</label>
                                            <select id="complainantStatus" name="complainant_status">
                                                <option value="" selected>Select status</option>
                                                <option>Single</option>
                                                <option>Married</option>
                                                <option>Widowed</option>
                                                <option>Separated</option>
                                            </select>
                                        </div>

                                        <div class="form-group wide">
                                            <label for="complainantAddress">Address</label>
                                            <input id="complainantAddress" name="complainant_address" type="text" placeholder="Enter complete address" maxlength="255">
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantReligion">Religion</label>
                                            <input id="complainantReligion" name="complainant_religion" type="text" placeholder="Enter religion" maxlength="100" data-letters-uppercase>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantBirthdate">Birthdate</label>
                                            <div class="date-field">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                    <path d="M16 2v4M8 2v4M3 10h18"></path>
                                                </svg>
                                                <input id="complainantBirthdate" name="complainant_birthdate" type="date" data-age-source="complainant_age">
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantAge">Age</label>
                                            <input id="complainantAge" name="complainant_age" type="number" min="0" max="130" readonly placeholder="Auto-calculated">
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantGovernmentId">Government ID</label>
                                            <input id="complainantGovernmentId" name="complainant_government_id" type="text" placeholder="Enter ID details" maxlength="150">
                                        </div>

                                        <div class="form-group">
                                            <label for="complainantContactNumber">Contact Number</label>
                                            <input id="complainantContactNumber" name="complainant_contact_number" type="tel" placeholder="Enter 11-digit contact number" inputmode="numeric" maxlength="11" data-numeric-only data-exact-digits="11">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="form-section case-party-section respondent-section is-collapsed" data-case-collapsible>
                                <div class="form-section-title">
                                    <div class="section-title-copy">
                                        <span class="section-heading-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                                <circle cx="12" cy="8" r="4"></circle>
                                            </svg>
                                        </span>
                                        <div>
                                            <h3>Respondent Information</h3>
                                            <p>Additional contact details for the respondent record.</p>
                                        </div>
                                    </div>
                                    <button class="section-toggle-button" type="button" data-case-collapsible-toggle aria-expanded="false" aria-controls="respondentInfoFields">
                                        <span>Show fields</span>
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M6 9l6 6 6-6"></path>
                                        </svg>
                                    </button>
                                </div>

                                <div id="respondentInfoFields" class="collapsible-section-body" data-case-collapsible-body aria-hidden="true">
                                    <div class="section-grid">
                                        <div class="form-group">
                                            <label for="respondentFullName">Full Name</label>
                                            <input id="respondentFullName" name="respondent_full_name" type="text" placeholder="Enter full name" maxlength="255" data-letters-uppercase>
                                        </div>

                                        <div class="form-group">
                                            <label for="respondentContactNumber">Contact Number</label>
                                            <input id="respondentContactNumber" name="respondent_contact_number" type="tel" placeholder="Enter 11-digit contact number" inputmode="numeric" maxlength="11" data-numeric-only data-exact-digits="11">
                                        </div>

                                        <div class="form-group wide">
                                            <label for="respondentAddress">Address</label>
                                            <input id="respondentAddress" name="respondent_address" type="text" placeholder="Enter complete address" maxlength="255">
                                        </div>
                                    </div>
                                </div>
                            </section>

                            <section class="form-section schedule-status-section">
                                <div class="form-section-title">
                                    <div class="section-title-copy">
                                        <span class="section-heading-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                                <path d="M16 2v4M8 2v4M3 10h18"></path>
                                            </svg>
                                        </span>
                                        <div>
                                            <h3>Schedule and Status</h3>
                                            <p>Track filing dates, case movement, and the current case status.</p>
                                        </div>
                                    </div>
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
                                            <option>Mediation</option>
                                            <option>Conciliation</option>
                                            <option>CFA (Certificate of File Action)</option>
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

                            <section class="form-section narrative-section">
                                <div class="form-section-title">
                                    <div class="section-title-copy">
                                        <span class="section-heading-icon" aria-hidden="true">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M14 2H7a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7z"></path>
                                                <path d="M14 2v5h5M9 13h6M9 17h6"></path>
                                            </svg>
                                        </span>
                                        <div>
                                            <h3>Case Narrative</h3>
                                            <p>Document the incident details and any agreement reached during proceedings.</p>
                                        </div>
                                    </div>
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

                                <div class="form-note">
                                    <p>Maximum 2500 characters required for detailed documentation and the main point of agreement</p>
                                </div>

                                <div class="case-form-actions">
                                    <div class="text-actions">
                                        <button type="reset">Clear Form</button>
                                        <a href="dashboard.php">Cancel</a>
                                    </div>
                                    <button class="primary-button compact" type="submit">
                                        <svg viewBox="0 0 24 24" aria-hidden="true">
                                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                                            <path d="M17 21v-8H7v8M7 3v5h8"></path>
                                        </svg>
                                        <span>Save Record</span>
                                    </button>
                                </div>
                            </section>
                        </div>
                    </form>
                </section>

                <p class="privacy-note">All saved records are encrypted and compliant with the Data Privacy Act of 2012. Need help? <a href="#">Contact System Administrator.</a></p>
            </main>

            <?php include '../includes/user_footer.php'; ?>
        </div>
    </div>

    <script src="../assets/js/user.js?v=<?php echo filemtime(__DIR__ . '/../assets/js/user.js'); ?>"></script>
</body>
</html>

