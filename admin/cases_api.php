<?php
declare(strict_types=1);

ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

function lcrms_import_normalized_header(string $header): string
{
    $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
    $header = strtolower(trim((string) $header));

    return (string) preg_replace('/[^a-z0-9]+/', '', $header);
}

function lcrms_import_column_aliases(): array
{
    return [
        'case_number' => ['case number', 'case no', 'case no.', 'case #'],
        'case_title' => ['case title'],
        'complainant_title' => ['complainant title', 'complaint title'],
        'nature_of_case' => ['nature of case', 'nature of case,'],
        'date_filed' => ['date filed'],
        'date_initial_confrontation' => ['date of initial confrontation', 'initial confrontation'],
        'case_status' => ['case status', 'status'],
        'date_settlement_award' => ['date of settlement / award', 'date of settlement/award', 'settlement / award', 'settlement award'],
        'date_execution' => ['date of execution', 'execution'],
        'detailed_case_description' => ['detailed case description', 'case description'],
        'main_point_of_agreement' => ['main point of agreement'],
        '_created_by' => ['submitted by', 'created by', 'encoder', 'submitted by user', 'user', 'username'],
    ];
}

function lcrms_import_column_map(array $headerRow): array
{
    $lookup = [];

    foreach (lcrms_import_column_aliases() as $field => $aliases) {
        foreach ($aliases as $alias) {
            $lookup[lcrms_import_normalized_header($alias)] = $field;
        }
    }

    $map = [];

    foreach ($headerRow as $index => $label) {
        $key = lcrms_import_normalized_header((string) $label);

        if ($key !== '' && isset($lookup[$key]) && !isset($map[$lookup[$key]])) {
            $map[$lookup[$key]] = $index;
        }
    }

    return $map;
}

function lcrms_import_validate_columns(array $headerRow): array
{
    $required = [
        'case_number' => 'Case Number',
        'case_title' => 'Case Title',
        'complainant_title' => 'Complainant Title',
        'nature_of_case' => 'Nature of Case',
        'date_filed' => 'Date Filed',
        'date_initial_confrontation' => 'Date of Initial Confrontation',
        'case_status' => 'Case Status',
        'date_settlement_award' => 'Date of Settlement / Award',
        'date_execution' => 'Date of Execution',
        'detailed_case_description' => 'Detailed Case Description',
        'main_point_of_agreement' => 'Main Point of Agreement',
    ];
    $map = lcrms_import_column_map($headerRow);
    $missing = [];

    foreach ($required as $field => $label) {
        if (!isset($map[$field])) {
            $missing[] = $label;
        }
    }

    return [$map, $missing];
}

function lcrms_import_unpack_uint16(string $data, int $offset): int
{
    $value = unpack('v', substr($data, $offset, 2));

    return (int) ($value[1] ?? 0);
}

function lcrms_import_unpack_uint32(string $data, int $offset): int
{
    $value = unpack('V', substr($data, $offset, 4));

    return (int) ($value[1] ?? 0);
}

function lcrms_import_zip_entry(string $path, string $entryName): ?string
{
    $data = file_get_contents($path);

    if ($data === false || strlen($data) < 22) {
        return null;
    }

    $eocd = strrpos($data, "PK\x05\x06");

    if ($eocd === false) {
        return null;
    }

    $centralOffset = lcrms_import_unpack_uint32($data, $eocd + 16);
    $centralSize = lcrms_import_unpack_uint32($data, $eocd + 12);
    $position = $centralOffset;
    $end = $centralOffset + $centralSize;

    while ($position + 46 <= $end && substr($data, $position, 4) === "PK\x01\x02") {
        $flags = lcrms_import_unpack_uint16($data, $position + 8);
        $method = lcrms_import_unpack_uint16($data, $position + 10);
        $compressedSize = lcrms_import_unpack_uint32($data, $position + 20);
        $nameLength = lcrms_import_unpack_uint16($data, $position + 28);
        $extraLength = lcrms_import_unpack_uint16($data, $position + 30);
        $commentLength = lcrms_import_unpack_uint16($data, $position + 32);
        $localOffset = lcrms_import_unpack_uint32($data, $position + 42);
        $name = substr($data, $position + 46, $nameLength);
        $position += 46 + $nameLength + $extraLength + $commentLength;

        if ($name !== $entryName) {
            continue;
        }

        if (($flags & 1) === 1) {
            throw new RuntimeException('Password-protected Excel files cannot be imported.');
        }

        if (substr($data, $localOffset, 4) !== "PK\x03\x04") {
            throw new RuntimeException('The uploaded Excel file is not readable.');
        }

        $localNameLength = lcrms_import_unpack_uint16($data, $localOffset + 26);
        $localExtraLength = lcrms_import_unpack_uint16($data, $localOffset + 28);
        $contentOffset = $localOffset + 30 + $localNameLength + $localExtraLength;
        $compressed = substr($data, $contentOffset, $compressedSize);

        if ($method === 0) {
            return $compressed;
        }

        if ($method === 8) {
            $inflated = gzinflate($compressed);

            if ($inflated === false) {
                throw new RuntimeException('The uploaded Excel worksheet could not be decompressed.');
            }

            return $inflated;
        }

        throw new RuntimeException('The uploaded Excel compression method is not supported.');
    }

    return null;
}

function lcrms_import_xml_text(SimpleXMLElement $node): string
{
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $text = '';

    foreach ($node->children($ns) as $child) {
        if ($child->getName() === 't') {
            $text .= (string) $child;
        } elseif ($child->getName() === 'r') {
            $text .= lcrms_import_xml_text($child);
        }
    }

    return $text;
}

function lcrms_import_xlsx_rows(string $path): array
{
    $sharedStrings = [];
    $sharedXml = lcrms_import_zip_entry($path, 'xl/sharedStrings.xml');

    if ($sharedXml !== null) {
        $shared = simplexml_load_string($sharedXml);

        if ($shared instanceof SimpleXMLElement) {
            $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

            foreach ($shared->children($ns)->si as $si) {
                $sharedStrings[] = lcrms_import_xml_text($si);
            }
        }
    }

    $sheetXml = lcrms_import_zip_entry($path, 'xl/worksheets/sheet1.xml');

    if ($sheetXml === null) {
        throw new RuntimeException('The uploaded workbook does not contain a readable first worksheet.');
    }

    $sheet = simplexml_load_string($sheetXml);

    if (!$sheet instanceof SimpleXMLElement) {
        throw new RuntimeException('The uploaded worksheet is not valid XML.');
    }

    $rows = [];
    $ns = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
    $sheetData = $sheet->children($ns)->sheetData;

    foreach ($sheetData->children($ns)->row as $row) {
        $cells = [];
        $nextColumn = 0;

        foreach ($row->children($ns)->c as $cell) {
            $attributes = $cell->attributes();
            $reference = (string) ($attributes['r'] ?? '');
            $type = (string) ($attributes['t'] ?? '');
            $column = $nextColumn;

            if (preg_match('/^([A-Z]+)/i', $reference, $matches)) {
                $column = 0;

                foreach (str_split(strtoupper($matches[1])) as $letter) {
                    $column = ($column * 26) + (ord($letter) - 64);
                }

                $column--;
            }

            if ($type === 's') {
                $value = $sharedStrings[(int) $cell->children($ns)->v] ?? '';
            } elseif ($type === 'inlineStr') {
                $value = lcrms_import_xml_text($cell->children($ns)->is);
            } else {
                $value = (string) $cell->children($ns)->v;
            }

            $cells[$column] = trim($value);
            $nextColumn = $column + 1;
        }

        if ($cells !== []) {
            ksort($cells);
            $rows[] = $cells;
        } else {
            $rows[] = [];
        }
    }

    return $rows;
}

function lcrms_import_xml_spreadsheet_rows(string $path): array
{
    $content = file_get_contents($path);

    if ($content === false) {
        throw new RuntimeException('The uploaded Excel file could not be read.');
    }

    if (str_starts_with($content, "\xD0\xCF\x11\xE0")) {
        throw new RuntimeException('Legacy binary .xls files are not supported. Please save the file as .xlsx and upload it again.');
    }

    $trimmed = ltrim($content);
    $rows = [];

    if (stripos($trimmed, '<html') === 0 || stripos($trimmed, '<table') !== false) {
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($content);
        libxml_clear_errors();

        foreach ($dom->getElementsByTagName('tr') as $tr) {
            $cells = [];

            foreach ($tr->childNodes as $cell) {
                if ($cell instanceof DOMElement && in_array(strtolower($cell->tagName), ['td', 'th'], true)) {
                    $cells[] = trim($cell->textContent);
                }
            }

            $rows[] = $cells;
        }

        return $rows;
    }

    $xml = simplexml_load_string($content);

    if (!$xml instanceof SimpleXMLElement) {
        throw new RuntimeException('The uploaded .xls file is not a supported Excel XML file.');
    }

    $xml->registerXPathNamespace('ss', 'urn:schemas-microsoft-com:office:spreadsheet');
    $xmlRows = $xml->xpath('//ss:Worksheet[1]//ss:Table/ss:Row') ?: [];

    foreach ($xmlRows as $xmlRow) {
        $cells = [];
        $column = 0;

        foreach ($xmlRow->xpath('ss:Cell') ?: [] as $cell) {
            $attributes = $cell->attributes('urn:schemas-microsoft-com:office:spreadsheet');
            $index = (int) ($attributes['Index'] ?? 0);

            if ($index > 0) {
                $column = $index - 1;
            }

            $data = $cell->xpath('ss:Data');
            $cells[$column] = isset($data[0]) ? trim((string) $data[0]) : '';
            $column++;
        }

        $rows[] = $cells;
    }

    return $rows;
}

function lcrms_import_rows_from_upload(array $file): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Select a valid Excel file to upload.');
    }

    $tmpName = (string) ($file['tmp_name'] ?? '');
    $originalName = (string) ($file['name'] ?? '');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $size = (int) ($file['size'] ?? 0);

    if (!is_uploaded_file($tmpName)) {
        throw new RuntimeException('The uploaded file could not be verified.');
    }

    if (!in_array($extension, ['xlsx', 'xls'], true)) {
        throw new RuntimeException('Only Excel files (.xlsx / .xls) can be imported.');
    }

    if ($size <= 0 || $size > 5 * 1024 * 1024) {
        throw new RuntimeException('Excel file size must be 5 MB or less.');
    }

    $rows = $extension === 'xlsx'
        ? lcrms_import_xlsx_rows($tmpName)
        : lcrms_import_xml_spreadsheet_rows($tmpName);

    $headerIndex = null;
    $headerRow = [];

    foreach ($rows as $index => $row) {
        if (array_filter($row, static fn($value) => trim((string) $value) !== '') !== []) {
            $headerIndex = $index;
            $headerRow = $row;
            break;
        }
    }

    if ($headerIndex === null) {
        throw new RuntimeException('The uploaded Excel file is empty.');
    }

    [$map, $missing] = lcrms_import_validate_columns($headerRow);

    if ($missing !== []) {
        throw new RuntimeException('Missing required columns: ' . implode(', ', $missing) . '.');
    }

    $records = [];

    for ($index = $headerIndex + 1; $index < count($rows); $index++) {
        $row = $rows[$index];

        if (array_filter($row, static fn($value) => trim((string) $value) !== '') === []) {
            continue;
        }

        $record = ['_row' => $index + 1];

        foreach ($map as $field => $columnIndex) {
            $record[$field] = trim((string) ($row[$columnIndex] ?? ''));
        }

        $records[] = $record;
    }

    if ($records === []) {
        throw new RuntimeException('The uploaded Excel file does not contain case records.');
    }

    return $records;
}

try {
    require_once __DIR__ . '/auth.php';
    require_admin_api_login();
    require_once __DIR__ . '/../db_connect.php';
    require_once __DIR__ . '/../user/models/CaseModel.php';

    if (!$conn instanceof mysqli || mysqli_connect_errno()) {
        throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
    }

    $caseModel = new CaseModel($conn);
    $action = $_GET['action'] ?? 'detail';

    if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'detail') {
        $case = $caseModel->findByIdForAdmin((int) ($_GET['id'] ?? 0));

        if (!$case) {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Case record not found.',
            ]);
            exit;
        }

        echo json_encode([
            'success' => true,
            'case' => $case,
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'update') {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $payload = $_POST;

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $decoded = json_decode($raw ?: '{}', true);
            $payload = is_array($decoded) ? $decoded : [];
        }

        $result = $caseModel->updateForAdmin((int) ($payload['id'] ?? 0), $payload, lcrms_current_account() ?? []);
        $status = (int) ($result['status'] ?? 200);
        unset($result['status']);
        http_response_code($status);
        echo json_encode($result);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'import') {
        try {
            $rows = lcrms_import_rows_from_upload($_FILES['excel_file'] ?? []);
            $result = $caseModel->importForAdmin($rows, lcrms_current_account() ?? []);
            $status = (int) ($result['status'] ?? 200);
            unset($result['status']);
            http_response_code($status);
            echo json_encode($result);
        } catch (Throwable $importException) {
            http_response_code(422);
            echo json_encode([
                'success' => false,
                'message' => 'Import failed. Please check the uploaded file.',
                'imported' => 0,
                'skipped' => [],
                'errors' => [[
                    'row' => 0,
                    'reason' => $importException->getMessage(),
                ]],
            ]);
        }
        exit;
    }

    http_response_code(404);
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported request.',
    ]);
} catch (Throwable $exception) {
    error_log('LCRMS admin case API error: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $exception->getMessage(),
    ]);
}
