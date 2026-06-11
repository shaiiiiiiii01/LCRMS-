<?php
declare(strict_types=1);

class CaseModel
{
    private const STATUS_CFA = 'CFA (Call for Action)';
    private const STATUS_SETTLED = 'Settled';

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
        $caseTitle = trim((string) ($payload['case_title'] ?? ''));
        $complainantTitle = trim((string) ($payload['complainant_title'] ?? ''));
        $natureOfCase = trim((string) ($payload['nature_of_case'] ?? ''));
        $dateFiledInput = trim((string) ($payload['date_filed'] ?? ''));
        $details = trim((string) ($payload['detailed_case_description'] ?? ''));
        $agreement = trim((string) ($payload['main_point_of_agreement'] ?? ''));
        $errors = [];

        if ($caseTitle === '') {
            $errors[] = 'Case Title is required.';
        }

        if ($complainantTitle === '') {
            $errors[] = 'Complainant Title is required.';
        }

        if ($natureOfCase === '') {
            $errors[] = 'Nature of Case is required.';
        }

        if ($dateFiledInput === '') {
            $errors[] = 'Date Filed is required.';
        }

        if ($details === '') {
            $errors[] = 'Detailed Case Description is required.';
        }

        $dateFiled = $this->parseRequiredDate($dateFiledInput, 'Date Filed', $errors);
        $initialConfrontation = $this->parseOptionalDate((string) ($payload['date_initial_confrontation'] ?? ''), 'Date of Initial Confrontation', $errors);
        $settlementAward = $this->parseOptionalDate((string) ($payload['date_settlement_award'] ?? ''), 'Date of Settlement / Award', $errors);
        $executionDate = null;

        if ($settlementAward !== null) {
            $executionDate = $this->parseOptionalDate((string) ($payload['date_execution'] ?? ''), 'Date of Execution', $errors);
        }

        $statusResult = $this->determineStatus(
            (string) ($payload['case_status'] ?? $payload['status'] ?? ''),
            $initialConfrontation,
            $settlementAward,
            $agreement,
            $this->isAuthorizedForRestrictedStatus($account)
        );

        if (!empty($statusResult['error'])) {
            $errors[] = $statusResult['error'];
        }

        $caseStatus = (string) ($statusResult['status'] ?? self::STATUS_CFA);

        if ($caseStatus === self::STATUS_CFA) {
            $settlementAward = null;
            $executionDate = null;
        } elseif ($settlementAward === null) {
            $executionDate = null;
        }

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
            $stmt = mysqli_prepare($this->conn, 'SELECT case_number, case_title, complainant_title, case_status, date_filed, created_by FROM cases WHERE created_by = ? ORDER BY date_created DESC, id DESC LIMIT ?');
            if (!$stmt) {
                return $cases;
            }
            mysqli_stmt_bind_param($stmt, 'si', $createdBy, $limit);
        } else {
            $stmt = mysqli_prepare($this->conn, 'SELECT case_number, case_title, complainant_title, case_status, date_filed, created_by FROM cases WHERE created_by = ? ORDER BY date_created DESC, id DESC');
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
        $sql = "SELECT {$primaryKey} AS id, case_number, case_title, complainant_title, case_status, date_filed
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
                main_point_of_agreement
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

    public function listAll(int $limit = 50): array
    {
        $cases = [];
        $orderColumn = $this->columnExists('cases', 'id') ? 'id' : ($this->columnExists('cases', 'case_id') ? 'case_id' : 'case_number');
        $stmt = mysqli_prepare($this->conn, "SELECT case_number, case_title, complainant_title, case_status, date_filed, created_by FROM cases ORDER BY date_created DESC, {$orderColumn} DESC LIMIT ?");

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

    public function listForAdmin(string $search = '', string $status = '', int $page = 1, int $perPage = 5): array
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
                case_status,
                date_filed,
                created_by,
                date_created
            FROM cases";
        $types = '';
        $values = [];
        $where = $this->adminCaseWhere($search, $status, $types, $values);

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

    public function countForAdmin(string $search = '', string $status = ''): int
    {
        $types = '';
        $values = [];
        $sql = 'SELECT COUNT(*) AS total FROM cases';
        $where = $this->adminCaseWhere(trim($search), $status, $types, $values);

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

    public function exportForAdmin(string $search = '', string $status = ''): array
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
        $where = $this->adminCaseWhere($search, $status, $types, $values);

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
                SUM(CASE WHEN LOWER(case_status) IN ('cfa', 'cfa (call for action)', 'm', 'mediation', 'c', 'conciliation', 'for conciliation stage') THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN LOWER(case_status) NOT IN ('settled', 'dismissed', 'endorsed') THEN 1 ELSE 0 END) AS ongoing,
                SUM(CASE WHEN LOWER(case_status) IN ('cfa', 'cfa (call for action)') THEN 1 ELSE 0 END) AS cfa,
                SUM(CASE WHEN LOWER(case_status) IN ('settled', 'm', 'mediation', 'c', 'conciliation') THEN 1 ELSE 0 END) AS resolved,
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

        if (!$this->findByIdForAdmin($id)) {
            return [
                'success' => false,
                'message' => 'Case record not found.',
                'status' => 404,
            ];
        }

        $caseTitle = trim((string) ($payload['case_title'] ?? ''));
        $complainantTitle = trim((string) ($payload['complainant_title'] ?? ''));
        $natureOfCase = trim((string) ($payload['nature_of_case'] ?? ''));
        $dateFiledInput = trim((string) ($payload['date_filed'] ?? ''));
        $details = trim((string) ($payload['detailed_case_description'] ?? ''));
        $agreement = trim((string) ($payload['main_point_of_agreement'] ?? ''));
        $caseStatus = $this->normalizeStatus((string) ($payload['case_status'] ?? ''));
        $errors = [];

        if ($caseTitle === '') {
            $errors[] = 'Case Title is required.';
        }

        if ($complainantTitle === '') {
            $errors[] = 'Complainant Title is required.';
        }

        if ($natureOfCase === '') {
            $errors[] = 'Nature of Case is required.';
        }

        if ($dateFiledInput === '') {
            $errors[] = 'Date Filed is required.';
        }

        if ($details === '') {
            $errors[] = 'Detailed Case Description is required.';
        }

        $allowedStatuses = [
            'For Conciliation Stage',
            'Mediation',
            'Conciliation',
            self::STATUS_CFA,
            self::STATUS_SETTLED,
            'Endorsed',
            'Dismissed',
        ];

        if (!in_array($caseStatus, $allowedStatuses, true)) {
            $errors[] = 'Select a valid case status.';
        }

        $dateFiled = $this->parseRequiredDate($dateFiledInput, 'Date Filed', $errors);
        $initialConfrontation = $this->parseOptionalDate((string) ($payload['date_initial_confrontation'] ?? ''), 'Date of Initial Confrontation', $errors);
        $settlementAward = $this->parseOptionalDate((string) ($payload['date_settlement_award'] ?? ''), 'Date of Settlement / Award', $errors);
        $executionDate = $this->parseOptionalDate((string) ($payload['date_execution'] ?? ''), 'Date of Execution', $errors);

        if ($errors !== []) {
            return [
                'success' => false,
                'message' => implode(' ', $errors),
                'errors' => $errors,
                'status' => 422,
            ];
        }

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
                main_point_of_agreement = ?
            WHERE ' . $this->casePrimaryKeyColumn() . ' = ?
            LIMIT 1'
        );

        if (!$stmt) {
            return [
                'success' => false,
                'message' => 'Case record could not be prepared for update.',
                'status' => 500,
            ];
        }

        mysqli_stmt_bind_param(
            $stmt,
            'ssssssssssi',
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
            $id
        );

        if (!mysqli_stmt_execute($stmt)) {
            $message = mysqli_stmt_error($stmt) ?: 'Case record could not be updated.';
            mysqli_stmt_close($stmt);

            return [
                'success' => false,
                'message' => $message,
                'status' => 500,
            ];
        }

        mysqli_stmt_close($stmt);

        return [
            'success' => true,
            'message' => 'Case record updated successfully.',
            'case' => $this->findByIdForAdmin($id),
        ];
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
                SUM(CASE WHEN case_status NOT IN ('Settled', 'Dismissed', 'Endorsed') THEN 1 ELSE 0 END) AS ongoing,
                SUM(CASE WHEN case_status IN ('Settled', 'Dismissed', 'Endorsed') THEN 1 ELSE 0 END) AS resolved
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

    private function determineStatus(string $submittedStatus, ?string $initialConfrontation, ?string $settlementAward, string $agreement, bool $canUseRestrictedStatus): array
    {
        if ($initialConfrontation === null || trim($agreement) === '') {
            return ['status' => self::STATUS_CFA];
        }

        if ($settlementAward !== null) {
            return ['status' => self::STATUS_SETTLED];
        }

        $normalizedStatus = $this->normalizeStatus($submittedStatus);
        $restrictedStatuses = ['Endorsed', 'Dismissed'];

        if (in_array($normalizedStatus, $restrictedStatuses, true) && !$canUseRestrictedStatus) {
            return [
                'status' => self::STATUS_CFA,
                'error' => 'Endorsed and Dismissed statuses may only be assigned by authorized users.',
            ];
        }

        if (in_array($normalizedStatus, $restrictedStatuses, true)) {
            return ['status' => $normalizedStatus];
        }

        $allowedStatuses = [
            'For Conciliation Stage',
            'Mediation',
            'Conciliation',
            self::STATUS_CFA,
        ];

        return [
            'status' => in_array($normalizedStatus, $allowedStatuses, true)
                ? $normalizedStatus
                : 'For Conciliation Stage',
        ];
    }

    private function normalizeStatus(string $status): string
    {
        $status = trim($status);
        $lower = strtolower($status);

        return match ($lower) {
            'cfa', 'cfa (call for action)' => self::STATUS_CFA,
            'settled' => self::STATUS_SETTLED,
            'endorsed' => 'Endorsed',
            'dismissed' => 'Dismissed',
            'mediation' => 'Mediation',
            'conciliation' => 'Conciliation',
            'for conciliation stage' => 'For Conciliation Stage',
            default => $status,
        };
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
            case_status VARCHAR(50) NOT NULL,
            date_settlement_award DATE NULL,
            date_execution DATE NULL,
            detailed_case_description TEXT NOT NULL,
            main_point_of_agreement TEXT NULL,
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
            'case_status' => 'ALTER TABLE cases ADD COLUMN case_status VARCHAR(50) NOT NULL DEFAULT \'CFA (Call for Action)\'',
            'date_settlement_award' => 'ALTER TABLE cases ADD COLUMN date_settlement_award DATE NULL',
            'date_execution' => 'ALTER TABLE cases ADD COLUMN date_execution DATE NULL',
            'detailed_case_description' => 'ALTER TABLE cases ADD COLUMN detailed_case_description TEXT NULL',
            'main_point_of_agreement' => 'ALTER TABLE cases ADD COLUMN main_point_of_agreement TEXT NULL',
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
            $this->executeStatement("ALTER TABLE cases MODIFY status VARCHAR(50) NOT NULL DEFAULT 'CFA (Call for Action)'");
        }

        if ($this->columnExists('cases', 'case_description')) {
            $this->executeStatement('ALTER TABLE cases MODIFY case_description TEXT NULL');
        }

        if ($this->columnExists('cases', 'main_point_of_agreement')) {
            $this->executeStatement('ALTER TABLE cases MODIFY main_point_of_agreement TEXT NULL');
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

    private function adminCaseWhere(string $search, string $status, string &$types, array &$values): string
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

        return implode(' AND ', $conditions);
    }

    private function adminStatusCondition(string $status): string
    {
        $status = strtolower(trim($status));

        return match ($status) {
            'new' => 'DATE(date_created) = CURDATE()',
            'cfa' => "LOWER(case_status) IN ('cfa', 'cfa (call for action)')",
            'm' => "LOWER(case_status) IN ('m', 'mediation')",
            'c' => "LOWER(case_status) IN ('c', 'conciliation', 'for conciliation stage')",
            'settled' => "LOWER(case_status) = 'settled'",
            'resolved' => "LOWER(case_status) IN ('settled', 'm', 'mediation', 'c', 'conciliation')",
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
