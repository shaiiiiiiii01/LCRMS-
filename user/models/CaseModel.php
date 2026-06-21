<?php
declare(strict_types=1);

class CaseModel
{
    private const STATUS_MEDIATION = 'Mediation';
    private const STATUS_CONCILIATION = 'Conciliation';
    private const STATUS_CFA = 'CFA (Certificate of File Action)';
    private const NATURE_OPTIONS = ['Civil', 'Criminal'];
    private const COMPLAINANT_STATUS_OPTIONS = ['Single', 'Married', 'Widowed', 'Separated'];
    private const DISMISSAL_REASONS = [
        'Complainant withdrew/dropped the case (inurong ang kaso)',
        'Respondent failed to appear (di dumating ang respondent)',
    ];

    public function __construct(private mysqli $conn)
    {
        mysqli_set_charset($this->conn, 'utf8mb4');
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->ensureTable();
        $this->ensureActivityLogTable();
    }

    public function create(array $payload, array $account): array
    {
        error_log('LCRMS CaseModel create reached.');
        $caseTitleInput = trim((string) ($payload['case_title'] ?? ''));
        $complainantTitleInput = trim((string) ($payload['complainant_title'] ?? ''));
        $caseTitle = $this->normalizeLettersUppercase($caseTitleInput);
        $complainantTitle = $this->normalizeLettersUppercase($complainantTitleInput);
        $natureOfCase = $this->normalizeNatureOfCase((string) ($payload['nature_of_case'] ?? ''));
        $dateFiledInput = trim((string) ($payload['date_filed'] ?? ''));
        $details = trim((string) ($payload['detailed_case_description'] ?? ''));
        $agreement = trim((string) ($payload['main_point_of_agreement'] ?? ''));
        $complainantFullNameInput = trim((string) ($payload['complainant_full_name'] ?? ''));
        $complainantReligionInput = trim((string) ($payload['complainant_religion'] ?? ''));
        $respondentFullNameInput = trim((string) ($payload['respondent_full_name'] ?? ''));
        $complainantFullName = $this->normalizeLettersUppercase($complainantFullNameInput);
        $complainantAddress = trim((string) ($payload['complainant_address'] ?? ''));
        $complainantStatus = $this->normalizeComplainantStatus((string) ($payload['complainant_status'] ?? ''));
        $complainantReligion = $this->normalizeLettersUppercase($complainantReligionInput);
        $complainantBirthdateInput = trim((string) ($payload['complainant_birthdate'] ?? ''));
        $complainantGovernmentId = trim((string) ($payload['complainant_government_id'] ?? ''));
        $complainantContactNumber = $this->normalizeDigits((string) ($payload['complainant_contact_number'] ?? ''));
        $respondentFullName = $this->normalizeLettersUppercase($respondentFullNameInput);
        $respondentAddress = trim((string) ($payload['respondent_address'] ?? ''));
        $respondentContactNumber = $this->normalizeDigits((string) ($payload['respondent_contact_number'] ?? ''));
        $errors = [];

        if ($caseTitle === '') {
            $errors[] = 'Case Title is required.';
        }

        if ($complainantTitle === '') {
            $errors[] = 'Complainant Title is required.';
        }

        if ($natureOfCase === '') {
            $errors[] = 'Nature of Case is required.';
        } elseif (!$this->isValidNatureOfCase($natureOfCase)) {
            $errors[] = 'Nature of Case must be Civil or Criminal.';
        }

        if ($dateFiledInput === '') {
            $errors[] = 'Date Filed is required.';
        }

        if ($details === '') {
            $errors[] = 'Detailed Case Description is required.';
        }

        $partyRequiredFields = [
            'Complainant Full Name' => $complainantFullName,
            'Complainant Address' => $complainantAddress,
            'Complainant Status' => $complainantStatus,
            'Complainant Religion' => $complainantReligion,
            'Complainant Birthdate' => $complainantBirthdateInput,
            'Complainant Government ID' => $complainantGovernmentId,
            'Complainant Contact Number' => $complainantContactNumber,
            'Respondent Full Name' => $respondentFullName,
            'Respondent Address' => $respondentAddress,
            'Respondent Contact Number' => $respondentContactNumber,
        ];

        foreach ($partyRequiredFields as $label => $value) {
            if ($value === '') {
                $errors[] = "{$label} is required.";
            }
        }

        $this->validateLettersOnly($caseTitleInput, 'Case Title', $errors);
        $this->validateLettersOnly($complainantTitleInput, 'Complainant Title', $errors);
        $this->validateLettersOnly($complainantFullNameInput, 'Complainant Full Name', $errors);
        $this->validateLettersOnly($complainantReligionInput, 'Complainant Religion', $errors);
        $this->validateLettersOnly($respondentFullNameInput, 'Respondent Full Name', $errors);
        $this->validateMaxLength($complainantAddress, 'Complainant Address', 255, $errors);
        $this->validateMaxLength($respondentAddress, 'Respondent Address', 255, $errors);
        $this->validateMaxLength($complainantGovernmentId, 'Complainant Government ID', 150, $errors);
        $this->validateExactDigits($complainantContactNumber, 'Complainant Contact Number', 11, $errors);
        $this->validateExactDigits($respondentContactNumber, 'Respondent Contact Number', 11, $errors);

        if ($complainantStatus !== '' && !in_array($complainantStatus, self::COMPLAINANT_STATUS_OPTIONS, true)) {
            $errors[] = 'Complainant Status must be Single, Married, Widowed, or Separated.';
        }

        if ($complainantBirthdateInput !== '' && !$this->hasFourDigitDateYear($complainantBirthdateInput)) {
            $errors[] = 'Complainant Birthdate year must be exactly 4 digits.';
        }

        $dateFiled = $this->parseRequiredDate($dateFiledInput, 'Date Filed', $errors);
        $complainantBirthdate = $this->parseRequiredDate($complainantBirthdateInput, 'Complainant Birthdate', $errors);
        $complainantAge = $this->calculateAge($complainantBirthdate);

        if ($complainantBirthdate !== null && $complainantAge === null) {
            $errors[] = 'Complainant Birthdate must be a valid past date.';
        }

        $initialConfrontation = $this->parseOptionalDate((string) ($payload['date_initial_confrontation'] ?? ''), 'Date of Initial Confrontation', $errors);
        $settlementAward = $this->parseOptionalDate((string) ($payload['date_settlement_award'] ?? ''), 'Date of Settlement / Award', $errors);
        $executionDate = $this->parseOptionalDate((string) ($payload['date_execution'] ?? ''), 'Date of Execution', $errors);
        $submittedStatus = $this->normalizeStatus((string) ($payload['case_status'] ?? $payload['status'] ?? ''));
        $caseStatus = self::STATUS_MEDIATION;

        if ($submittedStatus !== '' && $submittedStatus !== self::STATUS_MEDIATION) {
            $errors[] = 'New cases must start with M.';
        }

        $this->validateDateDependencies($dateFiled, $initialConfrontation, $settlementAward, $executionDate, $errors);
        $this->validateOutcomeRules($caseStatus, $settlementAward, $executionDate, $agreement, $errors);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
                'errors' => $errors,
                'status' => 422,
            ];
        }

        $createdBy = $this->currentFullname($account);
        $createdByUserId = $this->currentAccountId($account);
        $dateCreated = date('Y-m-d H:i:s');

        mysqli_begin_transaction($this->conn);

        try {
            $caseNumber = $this->generateCaseNumber();
            $stmt = mysqli_prepare(
                $this->conn,
                'INSERT INTO cases (
                    case_number,
                    case_title,
                    complainant_title,
                    nature_of_case,
                    date_filed,
                    date_initial_confrontation,
                    case_status,
                    date_settlement_award,
                    date_execution,
                    detailed_case_description,
                    main_point_of_agreement,
                    complainant_full_name,
                    complainant_address,
                    complainant_status,
                    complainant_religion,
                    complainant_birthdate,
                    complainant_age,
                    complainant_government_id,
                    complainant_contact_number,
                    respondent_full_name,
                    respondent_address,
                    respondent_contact_number,
                    created_by_user_id,
                    created_by,
                    date_created
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            if (!$stmt) {
                throw new RuntimeException('SQL insert prepare failed for cases table: ' . mysqli_error($this->conn));
            }

            mysqli_stmt_bind_param(
                $stmt,
                'ssssssssssssssssisssssiss',
                $caseNumber,
                $caseTitle,
                $complainantTitle,
                $natureOfCase,
                $dateFiled,
                $initialConfrontation,
                $caseStatus,
                $settlementAward,
                $executionDate,
                $details,
                $agreement,
                $complainantFullName,
                $complainantAddress,
                $complainantStatus,
                $complainantReligion,
                $complainantBirthdate,
                $complainantAge,
                $complainantGovernmentId,
                $complainantContactNumber,
                $respondentFullName,
                $respondentAddress,
                $respondentContactNumber,
                $createdByUserId,
                $createdBy,
                $dateCreated
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new RuntimeException('SQL insert failed for cases table: ' . mysqli_stmt_error($stmt));
            }

            mysqli_stmt_close($stmt);
            $this->logActivity($caseNumber, $caseTitle, $createdBy, $dateCreated);
            mysqli_commit($this->conn);

            return [
                'success' => true,
                'message' => 'Case saved successfully.',
                'case_number' => $caseNumber,
                'case_status' => $caseStatus,
                'next_case_number' => $this->previewNextCaseNumber(),
            ];
        } catch (Throwable $exception) {
            mysqli_rollback($this->conn);
            error_log('LCRMS case save failed: ' . $exception->getMessage());

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }
    }

    public function previewNextCaseNumber(): string
    {
        return $this->generateCaseNumber(false);
    }

    public function listByCreator(string $createdBy, int $limit = 0): array
    {
        $createdBy = trim($createdBy);
        $cases = [];

        if ($createdBy === '') {
            return $cases;
        }

        if ($limit > 0) {
            $stmt = mysqli_prepare($this->conn, 'SELECT case_number, case_title, complainant_title, nature_of_case, case_status, date_filed, created_by FROM cases WHERE created_by = ? ORDER BY date_created DESC, id DESC LIMIT ?');
            if (!$stmt) {
                return $cases;
            }
            mysqli_stmt_bind_param($stmt, 'si', $createdBy, $limit);
        } else {
            $stmt = mysqli_prepare($this->conn, 'SELECT case_number, case_title, complainant_title, nature_of_case, case_status, date_filed, created_by FROM cases WHERE created_by = ? ORDER BY date_created DESC, id DESC');
            if (!$stmt) {
                return $cases;
            }
            mysqli_stmt_bind_param($stmt, 's', $createdBy);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $cases[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $cases;
    }

    public function listForAccount(array $account, string $search = '', int $limit = 0): array
    {
        $ownerId = $this->currentAccountId($account);
        $createdBy = $this->currentFullname($account);
        $search = trim($search);
        $cases = [];

        if ($ownerId <= 0 && $createdBy === '') {
            return $cases;
        }

        $primaryKey = $this->casePrimaryKeyColumn();
        $orderColumn = $this->caseOrderColumn();
        $sql = "SELECT {$primaryKey} AS id, case_number, case_title, complainant_title, nature_of_case, case_status, date_filed
            FROM cases
            WHERE ((created_by_user_id > 0 AND created_by_user_id = ?) OR (created_by_user_id = 0 AND created_by = ?))";

        $types = 'is';
        $values = [$ownerId, $createdBy];

        if ($search !== '') {
            $sql .= ' AND case_title LIKE ?';
            $types .= 's';
            $values[] = '%' . $search . '%';
        }

        $sql .= " ORDER BY date_created DESC, {$orderColumn} DESC";

        if ($limit > 0) {
            $sql .= ' LIMIT ?';
            $types .= 'i';
            $values[] = $limit;
        }

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            return $cases;
        }

        $this->bindStatementParams($stmt, $types, $values);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $cases[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $cases;
    }

    public function findForAccountById(array $account, int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $ownerId = $this->currentAccountId($account);
        $createdBy = $this->currentFullname($account);
        $primaryKey = $this->casePrimaryKeyColumn();
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT {$primaryKey} AS id,
                case_number,
                case_title,
                complainant_title,
                nature_of_case,
                date_filed,
                date_initial_confrontation,
                case_status,
                date_settlement_award,
                date_execution,
                detailed_case_description,
                main_point_of_agreement,
                complainant_full_name,
                complainant_address,
                complainant_status,
                complainant_religion,
                complainant_birthdate,
                complainant_age,
                complainant_government_id,
                complainant_contact_number,
                respondent_full_name,
                respondent_address,
                respondent_contact_number
            FROM cases
            WHERE {$primaryKey} = ?
                AND ((created_by_user_id > 0 AND created_by_user_id = ?) OR (created_by_user_id = 0 AND created_by = ?))
            LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'iis', $id, $ownerId, $createdBy);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $case = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $case ?: null;
    }

    public function countTodayForAccount(array $account): int
    {
        $ownerId = $this->currentAccountId($account);
        $createdBy = $this->currentFullname($account);
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT COUNT(*) AS total
            FROM cases
            WHERE DATE(date_created) = CURDATE()
                AND ((created_by_user_id > 0 AND created_by_user_id = ?) OR (created_by_user_id = 0 AND created_by = ?))"
        );

        if (!$stmt) {
            return 0;
        }

        mysqli_stmt_bind_param($stmt, 'is', $ownerId, $createdBy);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0);
    }

    public function dashboardCountsForAccount(array $account): array
    {
        $counts = [
            'total' => 0,
            'new_today' => 0,
            'mediation' => 0,
            'conciliation' => 0,
            'dismissed' => 0,
            'cfa' => 0,
            'endorsed' => 0,
        ];
        $ownerId = $this->currentAccountId($account);
        $createdBy = $this->currentFullname($account);

        if ($ownerId <= 0 && $createdBy === '') {
            return $counts;
        }

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(date_created) = CURDATE() THEN 1 ELSE 0 END) AS new_today,
                SUM(CASE WHEN LOWER(case_status) IN ('m', 'mediation') THEN 1 ELSE 0 END) AS mediation,
                SUM(CASE WHEN LOWER(case_status) IN ('c', 'conciliation', 'for conciliation stage') THEN 1 ELSE 0 END) AS conciliation,
                SUM(CASE WHEN LOWER(case_status) = 'dismissed' THEN 1 ELSE 0 END) AS dismissed,
                SUM(CASE WHEN LOWER(case_status) IN ('cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS cfa,
                SUM(CASE WHEN LOWER(case_status) = 'endorsed' THEN 1 ELSE 0 END) AS endorsed
            FROM cases
            WHERE ((created_by_user_id > 0 AND created_by_user_id = ?) OR (created_by_user_id = 0 AND created_by = ?))"
        );

        if (!$stmt) {
            return $counts;
        }

        mysqli_stmt_bind_param($stmt, 'is', $ownerId, $createdBy);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            foreach ($counts as $key => $value) {
                $counts[$key] = (int) ($row[$key] ?? 0);
            }
        }

        return $counts;
    }

    public function listAll(int $limit = 50): array
    {
        $cases = [];
        $orderColumn = $this->columnExists('cases', 'id') ? 'id' : ($this->columnExists('cases', 'case_id') ? 'case_id' : 'case_number');
        $stmt = mysqli_prepare($this->conn, "SELECT case_number, case_title, complainant_title, nature_of_case, case_status, date_filed, created_by FROM cases ORDER BY date_created DESC, {$orderColumn} DESC LIMIT ?");

        if (!$stmt) {
            return $cases;
        }

        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $cases[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $cases;
    }

    public function listForAdmin(string $search = '', string $status = '', int $page = 1, int $perPage = 5, string $dateFilter = '', string $dateValue = ''): array
    {
        $search = trim($search);
        $page = max(1, $page);
        $perPage = max(1, $perPage);
        $offset = ($page - 1) * $perPage;
        $primaryKey = $this->casePrimaryKeyColumn();
        $orderColumn = $this->caseOrderColumn();
        $cases = [];
        $sql = "SELECT {$primaryKey} AS id,
                case_number,
                case_title,
                complainant_title,
                nature_of_case,
                case_status,
                date_filed,
                created_by,
                date_created
            FROM cases";
        $types = '';
        $values = [];
        $where = $this->adminCaseWhere($search, $status, $types, $values, $dateFilter, $dateValue);

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= " ORDER BY date_created DESC, {$orderColumn} DESC LIMIT ? OFFSET ?";
        $types .= 'ii';
        $values[] = $perPage;
        $values[] = $offset;
        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            return $cases;
        }

        $this->bindStatementParams($stmt, $types, $values);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $cases[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $cases;
    }

    public function countForAdmin(string $search = '', string $status = '', string $dateFilter = '', string $dateValue = ''): int
    {
        $types = '';
        $values = [];
        $sql = 'SELECT COUNT(*) AS total FROM cases';
        $where = $this->adminCaseWhere(trim($search), $status, $types, $values, $dateFilter, $dateValue);

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            return 0;
        }

        if ($types !== '') {
            $this->bindStatementParams($stmt, $types, $values);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0);
    }

    public function exportForAdmin(string $search = '', string $status = '', string $dateFilter = '', string $dateValue = ''): array
    {
        $search = trim($search);
        $primaryKey = $this->casePrimaryKeyColumn();
        $orderColumn = $this->caseOrderColumn();
        $cases = [];
        $sql = "SELECT {$primaryKey} AS id,
                case_number,
                case_title,
                complainant_title,
                nature_of_case,
                date_filed,
                date_initial_confrontation,
                case_status,
                date_settlement_award,
                date_execution,
                detailed_case_description,
                main_point_of_agreement,
                created_by,
                date_created
            FROM cases";
        $types = '';
        $values = [];
        $where = $this->adminCaseWhere($search, $status, $types, $values, $dateFilter, $dateValue);

        if ($where !== '') {
            $sql .= " WHERE {$where}";
        }

        $sql .= " ORDER BY date_created DESC, {$orderColumn} DESC";
        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            return $cases;
        }

        if ($types !== '') {
            $this->bindStatementParams($stmt, $types, $values);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $cases[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $cases;
    }

    public function adminCounts(): array
    {
        $counts = [
            'total' => 0,
            'new_today' => 0,
            'pending' => 0,
            'ongoing' => 0,
            'mediation' => 0,
            'conciliation' => 0,
            'cfa' => 0,
            'resolved' => 0,
            'endorsed' => 0,
            'dismissed' => 0,
        ];
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(date_created) = CURDATE() THEN 1 ELSE 0 END) AS new_today,
                SUM(CASE WHEN LOWER(case_status) IN ('m', 'mediation', 'c', 'conciliation', 'for conciliation stage') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(case_status) NOT IN ('settled', 'dismissed', 'endorsed', 'cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS ongoing,
                SUM(CASE WHEN LOWER(case_status) IN ('m', 'mediation') THEN 1 ELSE 0 END) AS mediation,
                SUM(CASE WHEN LOWER(case_status) IN ('c', 'conciliation', 'for conciliation stage') THEN 1 ELSE 0 END) AS conciliation,
                SUM(CASE WHEN LOWER(case_status) IN ('cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS cfa,
                SUM(CASE WHEN LOWER(case_status) IN ('settled', 'dismissed', 'endorsed', 'cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS resolved,
                SUM(CASE WHEN LOWER(case_status) = 'endorsed' THEN 1 ELSE 0 END) AS endorsed,
                SUM(CASE WHEN LOWER(case_status) = 'dismissed' THEN 1 ELSE 0 END) AS dismissed
            FROM cases"
        );

        if (!$stmt) {
            return $counts;
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            foreach ($counts as $key => $value) {
                $counts[$key] = (int) ($row[$key] ?? 0);
            }
        }

        return $counts;
    }

    public function findByIdForAdmin(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $primaryKey = $this->casePrimaryKeyColumn();
        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT {$primaryKey} AS id,
                case_number,
                case_title,
                complainant_title,
                nature_of_case,
                date_filed,
                date_initial_confrontation,
                case_status,
                date_settlement_award,
                date_execution,
                detailed_case_description,
                main_point_of_agreement,
                complainant_full_name,
                complainant_address,
                complainant_status,
                complainant_religion,
                complainant_birthdate,
                complainant_age,
                complainant_government_id,
                complainant_contact_number,
                respondent_full_name,
                respondent_address,
                respondent_contact_number,
                created_by,
                date_created
            FROM cases
            WHERE {$primaryKey} = ?
            LIMIT 1"
        );

        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $case = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $case ?: null;
    }

    public function updateForAdmin(int $id, array $payload, array $account): array
    {
        if ($id <= 0) {
            return [
                'success' => false,
                'message' => 'A valid case record is required.',
                'status' => 422,
            ];
        }

        $existingCase = $this->findByIdForAdmin($id);

        if (!$existingCase) {
            return [
                'success' => false,
                'message' => 'Case record not found.',
                'status' => 404,
            ];
        }

        $caseTitleInput = trim((string) ($payload['case_title'] ?? $existingCase['case_title'] ?? ''));
        $complainantTitleInput = trim((string) ($payload['complainant_title'] ?? $existingCase['complainant_title'] ?? ''));
        $caseTitle = $this->normalizeLettersUppercase($caseTitleInput);
        $complainantTitle = $this->normalizeLettersUppercase($complainantTitleInput);
        $natureOfCase = $this->normalizeNatureOfCase((string) ($payload['nature_of_case'] ?? $existingCase['nature_of_case'] ?? ''));
        $dateFiledInput = trim((string) ($payload['date_filed'] ?? $existingCase['date_filed'] ?? ''));
        $initialConfrontationInput = trim((string) ($payload['date_initial_confrontation'] ?? $existingCase['date_initial_confrontation'] ?? ''));
        $details = trim((string) ($payload['detailed_case_description'] ?? $existingCase['detailed_case_description'] ?? ''));
        $agreement = trim((string) ($payload['main_point_of_agreement'] ?? ''));
        $complainantFullNameInput = trim((string) ($payload['complainant_full_name'] ?? $existingCase['complainant_full_name'] ?? ''));
        $complainantReligionInput = trim((string) ($payload['complainant_religion'] ?? $existingCase['complainant_religion'] ?? ''));
        $respondentFullNameInput = trim((string) ($payload['respondent_full_name'] ?? $existingCase['respondent_full_name'] ?? ''));
        $complainantFullName = $this->normalizeLettersUppercase($complainantFullNameInput);
        $complainantAddress = trim((string) ($payload['complainant_address'] ?? $existingCase['complainant_address'] ?? ''));
        $complainantStatus = $this->normalizeComplainantStatus((string) ($payload['complainant_status'] ?? $existingCase['complainant_status'] ?? ''));
        $complainantReligion = $this->normalizeLettersUppercase($complainantReligionInput);
        $complainantBirthdateInput = trim((string) ($payload['complainant_birthdate'] ?? $existingCase['complainant_birthdate'] ?? ''));
        $complainantGovernmentId = trim((string) ($payload['complainant_government_id'] ?? $existingCase['complainant_government_id'] ?? ''));
        $complainantContactNumber = $this->normalizeDigits((string) ($payload['complainant_contact_number'] ?? $existingCase['complainant_contact_number'] ?? ''));
        $respondentFullName = $this->normalizeLettersUppercase($respondentFullNameInput);
        $respondentAddress = trim((string) ($payload['respondent_address'] ?? $existingCase['respondent_address'] ?? ''));
        $respondentContactNumber = $this->normalizeDigits((string) ($payload['respondent_contact_number'] ?? $existingCase['respondent_contact_number'] ?? ''));
        $caseStatus = $this->normalizeStatus((string) ($payload['case_status'] ?? ''));
        $currentStatus = $this->normalizeStatus((string) ($existingCase['case_status'] ?? ''));
        $errors = [];

        if ($caseTitle === '') {
            $errors[] = 'Case Title is required.';
        }

        if ($complainantTitle === '') {
            $errors[] = 'Complainant Title is required.';
        }

        if ($natureOfCase === '') {
            $errors[] = 'Nature of Case is required.';
        } elseif (!$this->isValidNatureOfCase($natureOfCase)) {
            $errors[] = 'Nature of Case must be Civil or Criminal.';
        }

        if ($dateFiledInput === '') {
            $errors[] = 'Date Filed is required.';
        }

        if ($details === '') {
            $errors[] = 'Detailed Case Description is required.';
        }

        $partyRequiredFields = [
            'Complainant Full Name' => $complainantFullName,
            'Complainant Address' => $complainantAddress,
            'Complainant Status' => $complainantStatus,
            'Complainant Religion' => $complainantReligion,
            'Complainant Birthdate' => $complainantBirthdateInput,
            'Complainant Government ID' => $complainantGovernmentId,
            'Complainant Contact Number' => $complainantContactNumber,
            'Respondent Full Name' => $respondentFullName,
            'Respondent Address' => $respondentAddress,
            'Respondent Contact Number' => $respondentContactNumber,
        ];

        foreach ($partyRequiredFields as $label => $value) {
            if ($value === '') {
                $errors[] = "{$label} is required.";
            }
        }

        $this->validateLettersOnly($caseTitleInput, 'Case Title', $errors);
        $this->validateLettersOnly($complainantTitleInput, 'Complainant Title', $errors);
        $this->validateLettersOnly($complainantFullNameInput, 'Complainant Full Name', $errors);
        $this->validateLettersOnly($complainantReligionInput, 'Complainant Religion', $errors);
        $this->validateLettersOnly($respondentFullNameInput, 'Respondent Full Name', $errors);
        $this->validateMaxLength($complainantAddress, 'Complainant Address', 255, $errors);
        $this->validateMaxLength($respondentAddress, 'Respondent Address', 255, $errors);
        $this->validateMaxLength($complainantGovernmentId, 'Complainant Government ID', 150, $errors);
        $this->validateExactDigits($complainantContactNumber, 'Complainant Contact Number', 11, $errors);
        $this->validateExactDigits($respondentContactNumber, 'Respondent Contact Number', 11, $errors);

        if ($complainantStatus !== '' && !in_array($complainantStatus, self::COMPLAINANT_STATUS_OPTIONS, true)) {
            $errors[] = 'Complainant Status must be Single, Married, Widowed, or Separated.';
        }

        if ($complainantBirthdateInput !== '' && !$this->hasFourDigitDateYear($complainantBirthdateInput)) {
            $errors[] = 'Complainant Birthdate year must be exactly 4 digits.';
        }

        if (!in_array($caseStatus, $this->workflowStatuses(), true)) {
            $errors[] = 'Select a valid case status.';
        }

        $dateFiled = $this->parseRequiredDate($dateFiledInput, 'Date Filed', $errors);
        $complainantBirthdate = $this->parseRequiredDate($complainantBirthdateInput, 'Complainant Birthdate', $errors);
        $complainantAge = $this->calculateAge($complainantBirthdate);

        if ($complainantBirthdate !== null && $complainantAge === null) {
            $errors[] = 'Complainant Birthdate must be a valid past date.';
        }

        $initialConfrontation = $this->parseOptionalDate($initialConfrontationInput, 'Date of Initial Confrontation', $errors);
        $settlementAward = $this->parseOptionalDate((string) ($payload['date_settlement_award'] ?? ''), 'Date of Settlement / Award', $errors);
        $executionDate = $this->parseOptionalDate((string) ($payload['date_execution'] ?? ''), 'Date of Execution', $errors);
        $this->validateStatusTransition($currentStatus, $caseStatus, $errors);
        $this->validateDateDependencies($dateFiled, $initialConfrontation, $settlementAward, $executionDate, $errors);
        $this->validateOutcomeRules($caseStatus, $settlementAward, $executionDate, $agreement, $errors);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
                'errors' => $errors,
                'status' => 422,
            ];
        }

        mysqli_begin_transaction($this->conn);

        $stmt = mysqli_prepare(
            $this->conn,
            'UPDATE cases SET
                case_title = ?,
                complainant_title = ?,
                nature_of_case = ?,
                date_filed = ?,
                date_initial_confrontation = ?,
                case_status = ?,
                date_settlement_award = ?,
                date_execution = ?,
                detailed_case_description = ?,
                main_point_of_agreement = ?,
                complainant_full_name = ?,
                complainant_address = ?,
                complainant_status = ?,
                complainant_religion = ?,
                complainant_birthdate = ?,
                complainant_age = ?,
                complainant_government_id = ?,
                complainant_contact_number = ?,
                respondent_full_name = ?,
                respondent_contact_number = ?,
                respondent_address = ?
            WHERE ' . $this->casePrimaryKeyColumn() . ' = ?
            LIMIT 1'
        );

        if (!$stmt) {
            mysqli_rollback($this->conn);

            return [
                'success' => false,
                'message' => 'Case record could not be prepared for update.',
                'status' => 500,
            ];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'sssssssssssssssisssssi',
            $caseTitle,
            $complainantTitle,
            $natureOfCase,
            $dateFiled,
            $initialConfrontation,
            $caseStatus,
            $settlementAward,
            $executionDate,
            $details,
            $agreement,
            $complainantFullName,
            $complainantAddress,
            $complainantStatus,
            $complainantReligion,
            $complainantBirthdate,
            $complainantAge,
            $complainantGovernmentId,
            $complainantContactNumber,
            $respondentFullName,
            $respondentContactNumber,
            $respondentAddress,
            $id
        );

        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_stmt_error($stmt) ?: 'Case record could not be updated.';
            mysqli_stmt_close($stmt);
            mysqli_rollback($this->conn);

            return [
                'success' => false,
                'message' => $message,
                'status' => 500,
            ];
        }

        mysqli_stmt_close($stmt);

        try {
            $this->logActivity(
                (string) ($existingCase['case_number'] ?? ''),
                $caseTitle,
                $this->currentFullname($account),
                date('Y-m-d H:i:s'),
                'Case Updated',
                'CASE_UPDATED'
            );
            mysqli_commit($this->conn);
        } catch (Throwable $exception) {
            mysqli_rollback($this->conn);

            return [
                'success' => false,
                'message' => $exception->getMessage(),
                'status' => 500,
            ];
        }

        return [
            'success' => true,
            'message' => 'Case record updated successfully.',
            'case' => $this->findByIdForAdmin($id),
        ];
    }

    public function importForAdmin(array $rows, array $account): array
    {
        $imported = 0;
        $skipped = [];
        $errors = [];
        $seenCaseNumbers = [];
        $adminName = $this->currentFullname($account);
        $adminId = $this->currentAccountId($account);
        $dateCreated = date('Y-m-d H:i:s');

        mysqli_begin_transaction($this->conn);

        try {
            foreach ($rows as $row) {
                $excelRow = (int) ($row['_row'] ?? 0);
                $rowErrors = [];
                $caseNumber = trim((string) ($row['case_number'] ?? ''));
                $caseTitleInput = trim((string) ($row['case_title'] ?? ''));
                $complainantTitleInput = trim((string) ($row['complainant_title'] ?? ''));
                $caseTitle = $this->normalizeLettersUppercase($caseTitleInput);
                $complainantTitle = $this->normalizeLettersUppercase($complainantTitleInput);
                $natureOfCase = $this->normalizeNatureOfCase((string) ($row['nature_of_case'] ?? ''));
                $dateFiledInput = trim((string) ($row['date_filed'] ?? ''));
                $caseStatus = $this->normalizeStatus((string) ($row['case_status'] ?? ''));
                $details = trim((string) ($row['detailed_case_description'] ?? ''));
                $agreement = trim((string) ($row['main_point_of_agreement'] ?? ''));

                if ($caseNumber === '') {
                    $rowErrors[] = 'Case Number is required.';
                } elseif (!preg_match('/^[A-Za-z0-9][A-Za-z0-9._\-\/]{1,19}$/', $caseNumber)) {
                    $rowErrors[] = 'Case Number contains invalid characters.';
                }

                if ($caseTitle === '') {
                    $rowErrors[] = 'Case Title is required.';
                }

                if ($complainantTitle === '') {
                    $rowErrors[] = 'Complainant Title is required.';
                }

                $this->validateLettersOnly($caseTitleInput, 'Case Title', $rowErrors);
                $this->validateLettersOnly($complainantTitleInput, 'Complainant Title', $rowErrors);

                if ($natureOfCase === '') {
                    $rowErrors[] = 'Nature of Case is required.';
                } elseif (!$this->isValidNatureOfCase($natureOfCase)) {
                    $rowErrors[] = 'Nature of Case must be Civil or Criminal.';
                }

                if ($dateFiledInput === '') {
                    $rowErrors[] = 'Date Filed is required.';
                }

                if (trim((string) ($row['case_status'] ?? '')) === '') {
                    $rowErrors[] = 'Case Status is required.';
                }

                if ($details === '') {
                    $rowErrors[] = 'Detailed Case Description is required.';
                }

                if ($caseStatus !== '' && !in_array($caseStatus, $this->workflowStatuses(), true)) {
                    $rowErrors[] = 'Case Status has an invalid value.';
                }

                if ($caseStatus !== '' && $caseStatus !== self::STATUS_MEDIATION) {
                    $rowErrors[] = 'New cases must start with M.';
                }

                $dateFiled = $this->parseImportDate($dateFiledInput, 'Date Filed', $rowErrors);
                $initialConfrontation = $this->parseImportDate((string) ($row['date_initial_confrontation'] ?? ''), 'Date of Initial Confrontation', $rowErrors);
                $settlementAward = $this->parseImportDate((string) ($row['date_settlement_award'] ?? ''), 'Date of Settlement / Award', $rowErrors);
                $executionDate = $this->parseImportDate((string) ($row['date_execution'] ?? ''), 'Date of Execution', $rowErrors);
                $this->validateDateDependencies($dateFiled, $initialConfrontation, $settlementAward, $executionDate, $rowErrors);
                $this->validateOutcomeRules($caseStatus, $settlementAward, $executionDate, $agreement, $rowErrors);

                if ($caseNumber !== '') {
                    $caseNumberKey = strtoupper($caseNumber);

                    if (isset($seenCaseNumbers[$caseNumberKey])) {
                        $skipped[] = [
                            'row' => $excelRow,
                            'case_number' => $caseNumber,
                            'reason' => 'Skipped duplicate case number in uploaded file: ' . $caseNumber,
                        ];
                        continue;
                    }

                    $seenCaseNumbers[$caseNumberKey] = true;

                    if ($this->caseNumberExists($caseNumber)) {
                        $skipped[] = [
                            'row' => $excelRow,
                            'case_number' => $caseNumber,
                            'reason' => 'Skipped duplicate case number: ' . $caseNumber,
                        ];
                        continue;
                    }
                }

                if ($rowErrors !== []) {
                    $errors[] = [
                        'row' => $excelRow,
                        'reason' => implode(' ', $rowErrors),
                    ];
                    continue;
                }

                $creator = $this->resolveImportCreator((string) ($row['_created_by'] ?? ''), $adminName, $adminId);
                $caseNumber = mb_substr($caseNumber, 0, 20);
                $caseTitle = mb_substr($caseTitle, 0, 255);
                $complainantTitle = mb_substr($complainantTitle, 0, 255);
                $natureOfCase = mb_substr($natureOfCase, 0, 150);
                $createdByUserId = (int) $creator['id'];
                $createdBy = (string) $creator['name'];

                $stmt = mysqli_prepare(
                    $this->conn,
                    'INSERT INTO cases (
                        case_number,
                        case_title,
                        complainant_title,
                        nature_of_case,
                        date_filed,
                        date_initial_confrontation,
                        case_status,
                        date_settlement_award,
                        date_execution,
                        detailed_case_description,
                        main_point_of_agreement,
                        created_by_user_id,
                        created_by,
                        date_created
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                if (!$stmt) {
                    throw new RuntimeException('SQL insert prepare failed for cases table: ' . mysqli_error($this->conn));
                }

                mysqli_stmt_bind_param(
                    $stmt,
                    'sssssssssssiss',
                    $caseNumber,
                    $caseTitle,
                    $complainantTitle,
                    $natureOfCase,
                    $dateFiled,
                    $initialConfrontation,
                    $caseStatus,
                    $settlementAward,
                    $executionDate,
                    $details,
                    $agreement,
                    $createdByUserId,
                    $createdBy,
                    $dateCreated
                );

                if (!mysqli_stmt_execute($stmt)) {
                    $error = mysqli_stmt_error($stmt);
                    mysqli_stmt_close($stmt);

                    if (str_contains(strtolower($error), 'duplicate')) {
                        $skipped[] = [
                            'row' => $excelRow,
                            'case_number' => $caseNumber,
                            'reason' => 'Skipped duplicate case number: ' . $caseNumber,
                        ];
                        continue;
                    }

                    throw new RuntimeException('SQL insert failed for cases table: ' . $error);
                }

                mysqli_stmt_close($stmt);
                $imported++;
            }

            if ($imported > 0) {
                $this->logActivity(
                    '',
                    'Imported ' . $imported . ' case' . ($imported === 1 ? '' : 's') . ' from Excel',
                    $adminName,
                    $dateCreated,
                    'Imported Cases from Excel',
                    'CASE_IMPORT'
                );
            }

            mysqli_commit($this->conn);

            return [
                'success' => true,
                'message' => $imported > 0 ? 'Import completed.' : 'Import failed. Please check the uploaded file.',
                'imported' => $imported,
                'skipped' => $skipped,
                'errors' => $errors,
            ];
        } catch (Throwable $exception) {
            mysqli_rollback($this->conn);
            error_log('LCRMS case import failed: ' . $exception->getMessage());

            return [
                'success' => false,
                'message' => 'Import failed. Please check the uploaded file.',
                'imported' => 0,
                'skipped' => $skipped,
                'errors' => array_merge($errors, [[
                    'row' => 0,
                    'reason' => $exception->getMessage(),
                ]]),
                'status' => 500,
            ];
        }
    }

    public function recentActivity(int $limit = 5): array
    {
        $activity = [];
        $limit = max(1, $limit);
        $orderColumn = $this->columnExists('activity_logs', 'id') ? 'id' : ($this->columnExists('activity_logs', 'log_id') ? 'log_id' : 'date_time');
        $stmt = mysqli_prepare(
            $this->conn,
            'SELECT action, case_number, performed_by, date_time
            FROM activity_logs
            ORDER BY date_time DESC, ' . $orderColumn . ' DESC
            LIMIT ?'
        );

        if (!$stmt) {
            return $activity;
        }

        mysqli_stmt_bind_param($stmt, 'i', $limit);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $activity[] = $row;
        }

        mysqli_stmt_close($stmt);

        return $activity;
    }

    public function counts(): array
    {
        $counts = [
            'total' => 0,
            'new_today' => 0,
            'ongoing' => 0,
            'resolved' => 0,
        ];

        $stmt = mysqli_prepare(
            $this->conn,
            "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN DATE(date_created) = CURDATE() THEN 1 ELSE 0 END) AS new_today,
                SUM(CASE WHEN LOWER(case_status) NOT IN ('settled', 'dismissed', 'endorsed', 'cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS ongoing,
                SUM(CASE WHEN LOWER(case_status) IN ('settled', 'dismissed', 'endorsed', 'cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)') THEN 1 ELSE 0 END) AS resolved
            FROM cases"
        );

        if (!$stmt) {
            return $counts;
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if ($row) {
            foreach ($counts as $key => $value) {
                $counts[$key] = (int) ($row[$key] ?? 0);
            }
        }

        return $counts;
    }

    private function workflowStatuses(): array
    {
        return [
            self::STATUS_MEDIATION,
            self::STATUS_CONCILIATION,
            self::STATUS_CFA,
            'Endorsed',
            'Dismissed',
        ];
    }

    private function finalWorkflowStatuses(): array
    {
        return [
            self::STATUS_CFA,
            'Endorsed',
            'Dismissed',
        ];
    }

    private function validateStatusTransition(string $currentStatus, string $nextStatus, array &$errors): void
    {
        if ($nextStatus === '' || !in_array($nextStatus, $this->workflowStatuses(), true)) {
            return;
        }

        if ($currentStatus === '') {
            $errors[] = 'Current case status is required before updating the workflow.';
            return;
        }

        if ($currentStatus === $nextStatus) {
            return;
        }

        if ($currentStatus === self::STATUS_MEDIATION && $nextStatus === self::STATUS_CONCILIATION) {
            return;
        }

        if ($currentStatus === self::STATUS_CONCILIATION && in_array($nextStatus, $this->finalWorkflowStatuses(), true)) {
            return;
        }

        if (in_array($currentStatus, $this->finalWorkflowStatuses(), true)) {
            $errors[] = 'Final outcome cases cannot move to another case status.';
            return;
        }

        if ($currentStatus === self::STATUS_MEDIATION) {
            $errors[] = 'Cases in M can only move to C.';
            return;
        }

        if ($currentStatus === self::STATUS_CONCILIATION) {
            $errors[] = 'Cases in C can only move to Dismissed, CFA, or Endorsed.';
            return;
        }

        $errors[] = 'Case status cannot be changed through that workflow step.';
    }

    private function validateDateDependencies(?string $dateFiled, ?string $initialConfrontation, ?string $settlementAward, ?string $executionDate, array &$errors): void
    {
        if ($dateFiled === null) {
            if ($initialConfrontation !== null || $settlementAward !== null || $executionDate !== null) {
                $errors[] = 'Date Filed is required before other dates can be entered.';
            }

            return;
        }

        if ($initialConfrontation === null && ($settlementAward !== null || $executionDate !== null)) {
            $errors[] = 'Date of Initial Confrontation is required before settlement or execution dates can be entered.';
        }

        if ($settlementAward !== null && $initialConfrontation === null) {
            $errors[] = 'Date of Settlement / Award can only be entered after Date Filed and Date of Initial Confrontation are set.';
        }

        if ($executionDate !== null && ($initialConfrontation === null || $settlementAward === null)) {
            $errors[] = 'Date of Execution can only be entered after Date Filed, Date of Initial Confrontation, and Date of Settlement / Award are set.';
        }
    }

    private function validateOutcomeRules(string $caseStatus, ?string $settlementAward, ?string $executionDate, string $agreement, array &$errors): void
    {
        $agreement = trim($agreement);

        if ($settlementAward !== null && $agreement === '') {
            $errors[] = 'Main Point of Agreement is required for settled cases.';
        }

        if ($caseStatus === 'Endorsed') {
            if ($settlementAward !== null || $executionDate !== null) {
                $errors[] = 'Endorsed cases must not have Date of Settlement / Award or Date of Execution.';
            }

            if ($agreement === '') {
                $errors[] = 'Main Point of Agreement is required for endorsed cases.';
            } elseif (!$this->endorsedAgreementIsValid($agreement)) {
                $errors[] = 'Main Point of Agreement for endorsed cases must explain that the case is being passed to the higher district and needs further action.';
            }
        }

        if ($caseStatus === 'Dismissed') {
            if ($settlementAward !== null || $executionDate !== null) {
                $errors[] = 'Dismissed cases must not have Date of Settlement / Award or Date of Execution.';
            }

            if ($agreement === '') {
                $errors[] = 'Dismissal reason is required.';
            } elseif (!$this->dismissalReasonIsValid($agreement)) {
                $errors[] = 'Dismissal reason must be one of: ' . implode('; ', self::DISMISSAL_REASONS) . '.';
            }
        }

        if ($caseStatus === self::STATUS_CFA) {
            if ($settlementAward !== null || $executionDate !== null) {
                $errors[] = 'CFA cases must not have Date of Settlement / Award or Date of Execution.';
            }

            if ($agreement === '') {
                $errors[] = 'Main Point of Agreement is required for CFA cases.';
            } elseif (!$this->cfaAgreementIsValid($agreement)) {
                $errors[] = 'Main Point of Agreement for CFA cases must explain why the case failed and why it needs to move forward.';
            }
        }
    }

    private function endorsedAgreementIsValid(string $agreement): bool
    {
        $lower = strtolower($agreement);

        return str_contains($lower, 'higher district')
            && (str_contains($lower, 'further action') || str_contains($lower, 'needs further action'));
    }

    private function cfaAgreementIsValid(string $agreement): bool
    {
        $lower = strtolower($agreement);
        $mentionsFailure = str_contains($lower, 'failed') || str_contains($lower, 'failure') || str_contains($lower, 'fail');
        $mentionsForward = str_contains($lower, 'move forward') || str_contains($lower, 'moving forward') || str_contains($lower, 'further action');

        return $mentionsFailure && $mentionsForward;
    }

    private function dismissalReasonIsValid(string $agreement): bool
    {
        $normalized = strtolower(trim($agreement));

        foreach (self::DISMISSAL_REASONS as $reason) {
            if ($normalized === strtolower($reason)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeLettersUppercase(string $value): string
    {
        $value = (string) preg_replace('/[^\p{L}\s]/u', '', $value);
        $value = (string) preg_replace('/\s+/', ' ', trim($value));

        return function_exists('mb_strtoupper') ? mb_strtoupper($value, 'UTF-8') : strtoupper($value);
    }

    private function normalizeDigits(string $value): string
    {
        return (string) preg_replace('/\D+/', '', $value);
    }

    private function normalizeComplainantStatus(string $status): string
    {
        $status = strtolower(trim($status));

        foreach (self::COMPLAINANT_STATUS_OPTIONS as $option) {
            if (strtolower($option) === $status) {
                return $option;
            }
        }

        return trim($status);
    }

    private function validateLettersOnly(string $value, string $label, array &$errors): void
    {
        if ($value !== '' && !preg_match('/^[\p{L}\s]+$/u', $value)) {
            $errors[] = "{$label} must contain letters only.";
        }
    }

    private function validateMaxLength(string $value, string $label, int $maxLength, array &$errors): void
    {
        $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);

        if ($length > $maxLength) {
            $errors[] = "{$label} must be {$maxLength} characters or fewer.";
        }
    }

    private function validateExactDigits(string $value, string $label, int $length, array &$errors): void
    {
        if ($value !== '' && !preg_match('/^\d{' . $length . '}$/', $value)) {
            $errors[] = "{$label} must be exactly {$length} digits.";
        }
    }

    private function hasFourDigitDateYear(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', trim($value));
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);
        $lower = strtolower($status);

        return match ($lower) {
            'cfa', 'cfa (call for action)', 'call for action', 'cfa (certificate to file action)', 'certificate to file action', 'cfa (certificate of file action)', 'certificate of file action' => self::STATUS_CFA,
            'endorsed' => 'Endorsed',
            'dismissed' => 'Dismissed',
            'm', 'mediation' => self::STATUS_MEDIATION,
            'c', 'conciliation', 'for conciliation stage' => self::STATUS_CONCILIATION,
            'settled' => self::STATUS_CONCILIATION,
            default => $status,
        };
    }

    private function normalizeNatureOfCase(string $natureOfCase): string
    {
        return match (strtolower(trim($natureOfCase))) {
            'civil' => 'Civil',
            'criminal' => 'Criminal',
            default => trim($natureOfCase),
        };
    }

    private function isValidNatureOfCase(string $natureOfCase): bool
    {
        return in_array($natureOfCase, self::NATURE_OPTIONS, true);
    }

    private function parseRequiredDate(string $value, string $label, array &$errors): ?string
    {
        $parsed = $this->parseOptionalDate($value, $label, $errors);

        if (trim($value) !== '' && $parsed === null) {
            return null;
        }

        return $parsed;
    }

    private function parseOptionalDate(string $value, string $label, array &$errors): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);

        if ($timestamp === false) {
            $errors[] = "{$label} must be a valid date.";
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    private function calculateAge(?string $birthdate): ?int
    {
        if ($birthdate === null || trim($birthdate) === '') {
            return null;
        }

        try {
            $birth = new DateTimeImmutable($birthdate);
            $today = new DateTimeImmutable('today');
        } catch (Throwable) {
            return null;
        }

        if ($birth > $today) {
            return null;
        }

        $age = (int) $birth->diff($today)->y;

        return $age <= 130 ? $age : null;
    }

    private function parseImportDate(string $value, string $label, array &$errors, bool $required = false): ?string
    {
        $value = trim($value);

        if ($value === '') {
            if ($required) {
                $errors[] = "{$label} is required.";
            }

            return null;
        }

        if (preg_match('/^\d+(\.\d+)?$/', $value)) {
            $serial = (float) $value;

            if ($serial >= 1 && $serial <= 60000) {
                $timestamp = (int) round(($serial - 25569) * 86400);
                return gmdate('Y-m-d', $timestamp);
            }
        }

        $formats = [
            'Y-m-d',
            'Y/m/d',
            'm/d/Y',
            'm-d-Y',
            'd/m/Y',
            'd-m-Y',
            'M d, Y',
            'F d, Y',
        ];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
            $lastErrors = DateTimeImmutable::getLastErrors();
            $hasErrors = is_array($lastErrors) && ((int) $lastErrors['warning_count'] > 0 || (int) $lastErrors['error_count'] > 0);

            if ($date instanceof DateTimeImmutable && !$hasErrors && $date->format($format) === $value) {
                return $date->format('Y-m-d');
            }
        }

        $errors[] = "{$label} must be a valid date.";

        return null;
    }

    private function caseNumberExists(string $caseNumber): bool
    {
        $stmt = mysqli_prepare($this->conn, 'SELECT 1 FROM cases WHERE case_number = ? LIMIT 1');

        if (!$stmt) {
            throw new RuntimeException('Case number duplicate check failed: ' . mysqli_error($this->conn));
        }

        mysqli_stmt_bind_param($stmt, 's', $caseNumber);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = (bool) ($result && mysqli_fetch_assoc($result));
        mysqli_stmt_close($stmt);

        return $exists;
    }

    private function resolveImportCreator(string $submittedBy, string $adminName, int $adminId): array
    {
        $submittedBy = trim($submittedBy);

        if ($submittedBy !== '') {
            $stmt = mysqli_prepare(
                $this->conn,
                "SELECT id, fullname
                FROM users
                WHERE role = 'USER' AND (fullname = ? OR username = ?)
                LIMIT 1"
            );

            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'ss', $submittedBy, $submittedBy);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $user = $result ? mysqli_fetch_assoc($result) : null;
                mysqli_stmt_close($stmt);

                if ($user) {
                    return [
                        'id' => (int) $user['id'],
                        'name' => (string) $user['fullname'],
                    ];
                }
            }
        }

        return [
            'id' => $adminId,
            'name' => $adminName,
        ];
    }

    private function generateCaseNumber(bool $lockRows = true): string
    {
        $year = date('Y');
        $like = 'L-' . $year . '-%';
        $orderColumn = $this->columnExists('cases', 'id') ? 'id' : ($this->columnExists('cases', 'case_id') ? 'case_id' : 'case_number');
        $sql = "SELECT case_number FROM cases WHERE case_number LIKE ? ORDER BY CAST(SUBSTRING(case_number, 8) AS UNSIGNED) DESC, {$orderColumn} DESC LIMIT 1";

        if ($lockRows) {
            $sql .= ' FOR UPDATE';
        }

        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('Case number could not be generated: ' . mysqli_error($this->conn));
        }

        mysqli_stmt_bind_param($stmt, 's', $like);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        $nextNumber = 1;

        if ($row && preg_match('/^L-' . preg_quote($year, '/') . '-(\d+)$/', (string) $row['case_number'], $matches)) {
            $nextNumber = ((int) $matches[1]) + 1;
        }

        return 'L-' . $year . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    private function logActivity(
        string $caseNumber,
        string $caseTitle,
        string $performedBy,
        string $dateTime,
        string $action = 'Case Created',
        string $actionType = 'CASE_CREATED'
    ): void
    {
        $usernameAffected = $caseNumber;
        $adminUsername = $performedBy;
        $stmt = mysqli_prepare(
            $this->conn,
            "INSERT INTO activity_logs (
                action,
                case_number,
                case_title,
                performed_by,
                date_time,
                action_type,
                username_affected,
                admin_username
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );

        if (!$stmt) {
            throw new RuntimeException('SQL insert prepare failed for activity_logs table: ' . mysqli_error($this->conn));
        }

        mysqli_stmt_bind_param($stmt, 'ssssssss', $action, $caseNumber, $caseTitle, $performedBy, $dateTime, $actionType, $usernameAffected, $adminUsername);

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('SQL insert failed for activity_logs table: ' . $error);
        }

        mysqli_stmt_close($stmt);
    }

    private function currentFullname(array $account): string
    {
        $fullname = trim((string) ($account['fullname'] ?? $_SESSION['fullname'] ?? $_SESSION['account_fullname'] ?? ''));

        return $fullname !== '' ? $fullname : 'System';
    }

    private function currentAccountId(array $account): int
    {
        return (int) ($account['id'] ?? $_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0);
    }

    private function casePrimaryKeyColumn(): string
    {
        return $this->columnExists('cases', 'id') ? 'id' : 'case_id';
    }

    private function caseOrderColumn(): string
    {
        return $this->columnExists('cases', 'id') ? 'id' : ($this->columnExists('cases', 'case_id') ? 'case_id' : 'case_number');
    }

    private function isAuthorizedForRestrictedStatus(array $account): bool
    {
        $role = strtoupper(trim((string) ($account['role'] ?? $_SESSION['account_role'] ?? $_SESSION['role'] ?? '')));

        return $role === 'ADMIN';
    }

    private function ensureTable(): void
    {
        $this->executeStatement("CREATE TABLE IF NOT EXISTS cases (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            case_number VARCHAR(20) NOT NULL,
            case_title VARCHAR(255) NOT NULL,
            complainant_title VARCHAR(255) NOT NULL,
            nature_of_case VARCHAR(150) NOT NULL,
            date_filed DATE NOT NULL,
            date_initial_confrontation DATE NULL,
            case_status VARCHAR(50) NOT NULL DEFAULT 'Mediation',
            date_settlement_award DATE NULL,
            date_execution DATE NULL,
            detailed_case_description TEXT NOT NULL,
            main_point_of_agreement TEXT NULL,
            complainant_full_name VARCHAR(255) NOT NULL DEFAULT '',
            complainant_address TEXT NULL,
            complainant_status VARCHAR(100) NOT NULL DEFAULT '',
            complainant_religion VARCHAR(100) NOT NULL DEFAULT '',
            complainant_birthdate DATE NULL,
            complainant_age INT UNSIGNED NULL,
            complainant_government_id VARCHAR(150) NOT NULL DEFAULT '',
            complainant_contact_number VARCHAR(50) NOT NULL DEFAULT '',
            respondent_full_name VARCHAR(255) NOT NULL DEFAULT '',
            respondent_address TEXT NULL,
            respondent_contact_number VARCHAR(50) NOT NULL DEFAULT '',
            created_by_user_id INT UNSIGNED NOT NULL DEFAULT 0,
            created_by VARCHAR(150) NOT NULL,
            date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY cases_case_number_unique (case_number),
            INDEX cases_created_by_user_id_index (created_by_user_id),
            INDEX cases_created_by_index (created_by),
            INDEX cases_date_created_index (date_created)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $columns = [
            'case_number' => "ALTER TABLE cases ADD COLUMN case_number VARCHAR(20) NOT NULL DEFAULT ''",
            'case_title' => "ALTER TABLE cases ADD COLUMN case_title VARCHAR(255) NOT NULL DEFAULT ''",
            'complainant_title' => "ALTER TABLE cases ADD COLUMN complainant_title VARCHAR(255) NOT NULL DEFAULT ''",
            'nature_of_case' => "ALTER TABLE cases ADD COLUMN nature_of_case VARCHAR(150) NOT NULL DEFAULT ''",
            'date_filed' => 'ALTER TABLE cases ADD COLUMN date_filed DATE NULL',
            'date_initial_confrontation' => 'ALTER TABLE cases ADD COLUMN date_initial_confrontation DATE NULL',
            'case_status' => 'ALTER TABLE cases ADD COLUMN case_status VARCHAR(50) NOT NULL DEFAULT \'Mediation\'',
            'date_settlement_award' => 'ALTER TABLE cases ADD COLUMN date_settlement_award DATE NULL',
            'date_execution' => 'ALTER TABLE cases ADD COLUMN date_execution DATE NULL',
            'detailed_case_description' => 'ALTER TABLE cases ADD COLUMN detailed_case_description TEXT NULL',
            'main_point_of_agreement' => 'ALTER TABLE cases ADD COLUMN main_point_of_agreement TEXT NULL',
            'complainant_full_name' => "ALTER TABLE cases ADD COLUMN complainant_full_name VARCHAR(255) NOT NULL DEFAULT ''",
            'complainant_address' => 'ALTER TABLE cases ADD COLUMN complainant_address TEXT NULL',
            'complainant_status' => "ALTER TABLE cases ADD COLUMN complainant_status VARCHAR(100) NOT NULL DEFAULT ''",
            'complainant_religion' => "ALTER TABLE cases ADD COLUMN complainant_religion VARCHAR(100) NOT NULL DEFAULT ''",
            'complainant_birthdate' => 'ALTER TABLE cases ADD COLUMN complainant_birthdate DATE NULL',
            'complainant_age' => 'ALTER TABLE cases ADD COLUMN complainant_age INT UNSIGNED NULL',
            'complainant_government_id' => "ALTER TABLE cases ADD COLUMN complainant_government_id VARCHAR(150) NOT NULL DEFAULT ''",
            'complainant_contact_number' => "ALTER TABLE cases ADD COLUMN complainant_contact_number VARCHAR(50) NOT NULL DEFAULT ''",
            'respondent_full_name' => "ALTER TABLE cases ADD COLUMN respondent_full_name VARCHAR(255) NOT NULL DEFAULT ''",
            'respondent_address' => 'ALTER TABLE cases ADD COLUMN respondent_address TEXT NULL',
            'respondent_contact_number' => "ALTER TABLE cases ADD COLUMN respondent_contact_number VARCHAR(50) NOT NULL DEFAULT ''",
            'created_by_user_id' => 'ALTER TABLE cases ADD COLUMN created_by_user_id INT UNSIGNED NOT NULL DEFAULT 0',
            'created_by' => 'ALTER TABLE cases ADD COLUMN created_by VARCHAR(150) NOT NULL DEFAULT \'System\'',
            'date_created' => 'ALTER TABLE cases ADD COLUMN date_created DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists('cases', $column)) {
                $this->executeStatement($sql);
            }
        }

        $this->migrateLegacyCasesTable();

        if (!$this->indexExists('cases', 'cases_case_number_unique')) {
            $this->executeStatement('ALTER TABLE cases ADD UNIQUE KEY cases_case_number_unique (case_number)');
        }

        if (!$this->indexExists('cases', 'cases_created_by_user_id_index')) {
            $this->executeStatement('ALTER TABLE cases ADD INDEX cases_created_by_user_id_index (created_by_user_id)');
        }
    }

    private function ensureActivityLogTable(): void
    {
        $this->executeStatement("CREATE TABLE IF NOT EXISTS activity_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            action VARCHAR(100) NOT NULL DEFAULT '',
            case_number VARCHAR(20) NOT NULL DEFAULT '',
            case_title VARCHAR(255) NOT NULL DEFAULT '',
            performed_by VARCHAR(150) NOT NULL DEFAULT '',
            date_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            action_type VARCHAR(50) NOT NULL DEFAULT 'USER_ACTION',
            username_affected VARCHAR(150) NOT NULL DEFAULT '',
            admin_username VARCHAR(150) NOT NULL DEFAULT '',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX activity_logs_date_time_index (date_time),
            INDEX activity_logs_created_at_index (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $columns = [
            'action' => "ALTER TABLE activity_logs ADD COLUMN action VARCHAR(100) NOT NULL DEFAULT ''",
            'case_number' => "ALTER TABLE activity_logs ADD COLUMN case_number VARCHAR(20) NOT NULL DEFAULT ''",
            'case_title' => "ALTER TABLE activity_logs ADD COLUMN case_title VARCHAR(255) NOT NULL DEFAULT ''",
            'performed_by' => "ALTER TABLE activity_logs ADD COLUMN performed_by VARCHAR(150) NOT NULL DEFAULT ''",
            'date_time' => 'ALTER TABLE activity_logs ADD COLUMN date_time DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP',
            'action_type' => "ALTER TABLE activity_logs ADD COLUMN action_type VARCHAR(50) NOT NULL DEFAULT 'USER_ACTION'",
            'username_affected' => "ALTER TABLE activity_logs ADD COLUMN username_affected VARCHAR(150) NOT NULL DEFAULT ''",
            'admin_username' => "ALTER TABLE activity_logs ADD COLUMN admin_username VARCHAR(150) NOT NULL DEFAULT ''",
            'created_at' => 'ALTER TABLE activity_logs ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP',
        ];

        foreach ($columns as $column => $sql) {
            if (!$this->columnExists('activity_logs', $column)) {
                $this->executeStatement($sql);
            }
        }

        $this->migrateLegacyActivityLogsTable();
    }

    private function migrateLegacyCasesTable(): void
    {
        if ($this->columnExists('cases', 'case_id')) {
            $this->executeStatement('ALTER TABLE cases MODIFY case_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if ($this->columnExists('cases', 'created_by')) {
            $this->executeStatement("ALTER TABLE cases MODIFY created_by VARCHAR(150) NOT NULL DEFAULT 'System'");
        }

        if ($this->columnExists('cases', 'created_at')) {
            $this->executeStatement('ALTER TABLE cases MODIFY created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }

        if ($this->columnExists('cases', 'updated_at')) {
            $this->executeStatement('ALTER TABLE cases MODIFY updated_at DATETIME NULL DEFAULT NULL');
        }

        if ($this->columnExists('cases', 'status')) {
            $this->executeStatement("ALTER TABLE cases MODIFY status VARCHAR(50) NOT NULL DEFAULT 'Mediation'");
        }

        if ($this->columnExists('cases', 'case_status')) {
            $this->executeStatement("ALTER TABLE cases MODIFY case_status VARCHAR(50) NOT NULL DEFAULT 'Mediation'");
        }

        if ($this->columnExists('cases', 'case_description')) {
            $this->executeStatement('ALTER TABLE cases MODIFY case_description TEXT NULL');
        }

        if ($this->columnExists('cases', 'main_point_of_agreement')) {
            $this->executeStatement('ALTER TABLE cases MODIFY main_point_of_agreement TEXT NULL');
        }

        if ($this->columnExists('cases', 'complainant_age')) {
            $this->executeStatement('ALTER TABLE cases MODIFY complainant_age INT UNSIGNED NULL');
        }
    }

    private function migrateLegacyActivityLogsTable(): void
    {
        if ($this->columnExists('activity_logs', 'log_id')) {
            $this->executeStatement('ALTER TABLE activity_logs MODIFY log_id INT UNSIGNED NOT NULL AUTO_INCREMENT');
        }

        if ($this->columnExists('activity_logs', 'user_id')) {
            $this->executeStatement('ALTER TABLE activity_logs MODIFY user_id INT UNSIGNED NOT NULL DEFAULT 0');
        }

        if ($this->columnExists('activity_logs', 'description')) {
            $this->executeStatement('ALTER TABLE activity_logs MODIFY description TEXT NULL');
        }

        if ($this->columnExists('activity_logs', 'log_date')) {
            $this->executeStatement('ALTER TABLE activity_logs MODIFY log_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            'SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ss', $table, $column);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function indexExists(string $table, string $index): bool
    {
        $stmt = mysqli_prepare(
            $this->conn,
            'SELECT COUNT(*) AS total FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
        );
        mysqli_stmt_bind_param($stmt, 'ss', $table, $index);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : ['total' => 0];
        mysqli_stmt_close($stmt);

        return (int) ($row['total'] ?? 0) > 0;
    }

    private function adminCaseWhere(string $search, string $status, string &$types, array &$values, string $dateFilter = '', string $dateValue = ''): string
    {
        $conditions = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $conditions[] = '(case_number LIKE ? OR case_title LIKE ? OR complainant_title LIKE ?)';
            $types .= 'sss';
            $values[] = $like;
            $values[] = $like;
            $values[] = $like;
        }

        $statusCondition = $this->adminStatusCondition($status);

        if ($statusCondition !== '') {
            $conditions[] = $statusCondition;
        }

        $dateColumn = $this->adminDateFilterColumn($dateFilter);
        $date = $this->parseAdminFilterDate($dateValue);

        if ($dateColumn !== '' && $date !== null) {
            $conditions[] = "{$dateColumn} = ?";
            $types .= 's';
            $values[] = $date;
        }

        return implode(' AND ', $conditions);
    }

    private function adminDateFilterColumn(string $dateFilter): string
    {
        return match (strtolower(trim($dateFilter))) {
            'date_filed' => 'date_filed',
            'date_initial_confrontation' => 'date_initial_confrontation',
            'date_settlement_award' => 'date_settlement_award',
            'date_execution' => 'date_execution',
            default => '',
        };
    }

    private function parseAdminFilterDate(string $dateValue): ?string
    {
        $dateValue = trim($dateValue);

        if ($dateValue === '') {
            return null;
        }

        $formats = ['Y-m-d', 'm/d/Y', 'm-d-Y'];

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!' . $format, $dateValue);
            $lastErrors = DateTimeImmutable::getLastErrors();
            $hasErrors = is_array($lastErrors) && ((int) $lastErrors['warning_count'] > 0 || (int) $lastErrors['error_count'] > 0);

            if ($date instanceof DateTimeImmutable && !$hasErrors && $date->format($format) === $dateValue) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function adminStatusCondition(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'new' => 'DATE(date_created) = CURDATE()',
            'cfa' => "LOWER(case_status) IN ('cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)')",
            'm' => "LOWER(case_status) IN ('m', 'mediation')",
            'c' => "LOWER(case_status) IN ('c', 'conciliation', 'for conciliation stage')",
            'resolved' => "LOWER(case_status) IN ('settled', 'dismissed', 'endorsed', 'cfa', 'cfa (call for action)', 'cfa (certificate to file action)', 'cfa (certificate of file action)')",
            'endorsed' => "LOWER(case_status) = 'endorsed'",
            'dismissed' => "LOWER(case_status) = 'dismissed'",
            default => '',
        };
    }

    private function executeStatement(string $sql): void
    {
        $stmt = mysqli_prepare($this->conn, $sql);

        if (!$stmt) {
            throw new RuntimeException('SQL prepare failed: ' . mysqli_error($this->conn) . ' SQL: ' . $sql);
        }

        if (!mysqli_stmt_execute($stmt)) {
            $error = mysqli_stmt_error($stmt);
            mysqli_stmt_close($stmt);
            throw new RuntimeException('SQL execute failed: ' . $error . ' SQL: ' . $sql);
        }

        mysqli_stmt_close($stmt);
    }

    private function bindStatementParams(mysqli_stmt $stmt, string $types, array &$values): void
    {
        $refs = [];

        foreach ($values as $key => &$value) {
            $refs[$key] = &$value;
        }

        mysqli_stmt_bind_param($stmt, $types, ...$refs);
    }
}

