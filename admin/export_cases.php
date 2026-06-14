<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_admin_login();
require_once __DIR__ . '/../user/models/CaseModel.php';
require_once __DIR__ . '/case_helpers.php';

$caseModel = new CaseModel(lcrms_db());
$search = trim((string) ($_GET['search'] ?? ''));
$status = trim((string) ($_GET['status'] ?? ''));
$dateFilter = trim((string) ($_GET['date_filter'] ?? ''));
$dateValue = trim((string) ($_GET['date_value'] ?? ''));
$cases = $caseModel->exportForAdmin($search, $status, $dateFilter, $dateValue);

function xlsx_xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function xlsx_cell_ref(int $columnIndex, int $rowIndex): string
{
    $column = '';
    $number = $columnIndex;

    while ($number > 0) {
        $mod = ($number - 1) % 26;
        $column = chr(65 + $mod) . $column;
        $number = intdiv($number - $mod, 26);
    }

    return $column . $rowIndex;
}

function xlsx_inline_cell(int $columnIndex, int $rowIndex, string $value, int $style = 0): string
{
    $styleAttribute = $style > 0 ? ' s="' . $style . '"' : '';

    return '<c r="' . xlsx_cell_ref($columnIndex, $rowIndex) . '" t="inlineStr"' . $styleAttribute . '><is><t xml:space="preserve">' . xlsx_xml($value) . '</t></is></c>';
}

function xlsx_dos_time(): array
{
    $time = getdate();
    $dosTime = (($time['hours'] & 0x1f) << 11) | (($time['minutes'] & 0x3f) << 5) | ((int) floor($time['seconds'] / 2) & 0x1f);
    $dosDate = (($time['year'] - 1980) << 9) | (($time['mon'] & 0xf) << 5) | ($time['mday'] & 0x1f);

    return [$dosTime, $dosDate];
}

function xlsx_zip(array $files): string
{
    [$dosTime, $dosDate] = xlsx_dos_time();
    $zip = '';
    $centralDirectory = '';
    $offset = 0;

    foreach ($files as $name => $data) {
        $crc = crc32($data);
        $size = strlen($data);
        $nameLength = strlen($name);
        $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0) . $name;
        $zip .= $localHeader . $data;
        $centralDirectory .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset) . $name;
        $offset += strlen($localHeader) + $size;
    }

    $centralOffset = strlen($zip);
    $centralSize = strlen($centralDirectory);
    $count = count($files);
    $zip .= $centralDirectory;
    $zip .= pack('VvvvvVVv', 0x06054b50, 0, 0, $count, $count, $centralSize, $centralOffset, 0);

    return $zip;
}

$headers = [
    'Case Number',
    'Case Title',
    'Complainant Title',
    'Nature of Case',
    'Date Filed',
    'Initial Confrontation',
    'Case Status',
    'Settlement / Award',
    'Execution',
    'Detailed Case Description',
    'Main Point of Agreement',
    'Submitted By',
    'Date Created',
];
$rows = [$headers];

foreach ($cases as $case) {
    $rows[] = [
        (string) $case['case_number'],
        (string) $case['case_title'],
        (string) $case['complainant_title'],
        (string) $case['nature_of_case'],
        admin_case_date_label($case['date_filed'] ?? null),
        admin_case_date_label($case['date_initial_confrontation'] ?? null),
        (string) $case['case_status'],
        admin_case_date_label($case['date_settlement_award'] ?? null),
        admin_case_date_label($case['date_execution'] ?? null),
        (string) $case['detailed_case_description'],
        (string) $case['main_point_of_agreement'],
        (string) $case['created_by'],
        admin_case_datetime_label($case['date_created'] ?? null),
    ];
}

$sheetRows = [];

foreach ($rows as $rowIndex => $row) {
    $cells = [];
    $style = $rowIndex === 0 ? 1 : 2;

    foreach ($row as $columnIndex => $value) {
        $cells[] = xlsx_inline_cell($columnIndex + 1, $rowIndex + 1, (string) $value, $style);
    }

    $sheetRows[] = '<row r="' . ($rowIndex + 1) . '">' . implode('', $cells) . '</row>';
}

$lastColumn = xlsx_cell_ref(count($headers), 1);
$lastColumn = preg_replace('/\d+$/', '', $lastColumn);
$lastRow = max(1, count($rows));
$sheetXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
    <sheetViews>
        <sheetView workbookViewId="0">
            <pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>
            <selection pane="bottomLeft" activeCell="A2" sqref="A2"/>
        </sheetView>
    </sheetViews>
    <cols>
        <col min="1" max="1" width="18" customWidth="1"/>
        <col min="2" max="3" width="32" customWidth="1"/>
        <col min="4" max="4" width="20" customWidth="1"/>
        <col min="5" max="9" width="18" customWidth="1"/>
        <col min="10" max="11" width="48" customWidth="1"/>
        <col min="12" max="12" width="24" customWidth="1"/>
        <col min="13" max="13" width="24" customWidth="1"/>
    </cols>
    <sheetData>' . implode('', $sheetRows) . '</sheetData>
    <autoFilter ref="A1:' . $lastColumn . $lastRow . '"/>
</worksheet>';

$files = [
    '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/docProps/app.xml" ContentType="application/vnd.openxmlformats-officedocument.extended-properties+xml"/><Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>',
    '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/><Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/extended-properties" Target="docProps/app.xml"/></Relationships>',
    'docProps/app.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Properties xmlns="http://schemas.openxmlformats.org/officeDocument/2006/extended-properties" xmlns:vt="http://schemas.openxmlformats.org/officeDocument/2006/docPropsVTypes"><Application>LCRMS</Application></Properties>',
    'docProps/core.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:dcmitype="http://purl.org/dc/dcmitype/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><dc:title>LCRMS Case Export</dc:title><dc:creator>LCRMS</dc:creator><dcterms:created xsi:type="dcterms:W3CDTF">' . gmdate('c') . '</dcterms:created></cp:coreProperties>',
    'xl/workbook.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Cases" sheetId="1" r:id="rId1"/></sheets></workbook>',
    'xl/_rels/workbook.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
    'xl/styles.xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><color rgb="FF16395F"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF16477F"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="2"><border><left/><right/><top/><bottom/><diagonal/></border><border><left style="thin"><color rgb="FFD9E6F5"/></left><right style="thin"><color rgb="FFD9E6F5"/></right><top style="thin"><color rgb="FFD9E6F5"/></top><bottom style="thin"><color rgb="FFD9E6F5"/></bottom><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="1" xfId="0" applyFill="1" applyFont="1" applyBorder="1" applyAlignment="1"><alignment horizontal="center" vertical="center" wrapText="1"/></xf><xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyBorder="1" applyAlignment="1"><alignment vertical="top" wrapText="1"/></xf></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>',
    'xl/worksheets/sheet1.xml' => $sheetXml,
];

$xlsx = xlsx_zip($files);
$filename = 'lcrms-cases-' . date('Y-m-d-His') . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($xlsx));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
echo $xlsx;
