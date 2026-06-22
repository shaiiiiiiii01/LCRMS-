<?php
declare(strict_types=1);

function lcrms_case_docx_text(?string $value): string
{
    $value = trim((string) ($value ?? ''));

    return $value === '' ? 'Not set' : $value;
}

function lcrms_case_docx_status(?string $status): string
{
    $status = trim((string) ($status ?? ''));

    return match (strtolower($status)) {
        'cfa', 'cfa (call for action)', 'call for action', 'cfa (certificate to file action)', 'certificate to file action', 'cfa (certificate of file action)', 'certificate of file action' => 'CFA',
        'm', 'mediation' => 'M',
        'c', 'conciliation', 'for conciliation stage' => 'C',
        default => $status,
    };
}

function lcrms_case_docx_xml(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
}

function lcrms_case_docx_paragraph(string $text, string $style = ''): string
{
    $styleXml = $style !== '' ? '<w:pStyle w:val="' . lcrms_case_docx_xml($style) . '"/>' : '';
    $lines = preg_split('/\R/', $text) ?: [$text];
    $runs = [];

    foreach ($lines as $index => $line) {
        if ($index > 0) {
            $runs[] = '<w:r><w:br/></w:r>';
        }

        $runs[] = '<w:r><w:t xml:space="preserve">' . lcrms_case_docx_xml($line) . '</w:t></w:r>';
    }

    return '<w:p><w:pPr>' . $styleXml . '</w:pPr>' . implode('', $runs) . '</w:p>';
}

function lcrms_case_docx_cell(string $text, bool $isLabel = false): string
{
    $bold = $isLabel ? '<w:rPr><w:b/></w:rPr>' : '';

    return '<w:tc><w:tcPr><w:tcW w:w="' . ($isLabel ? '2800' : '6200') . '" w:type="dxa"/></w:tcPr><w:p><w:r>' . $bold . '<w:t xml:space="preserve">' . lcrms_case_docx_xml($text) . '</w:t></w:r></w:p></w:tc>';
}

function lcrms_case_docx_table(array $rows): string
{
    $xml = '<w:tbl><w:tblPr><w:tblW w:w="0" w:type="auto"/><w:tblBorders><w:top w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/><w:left w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/><w:bottom w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/><w:right w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/><w:insideH w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/><w:insideV w:val="single" w:sz="6" w:space="0" w:color="D9E0E9"/></w:tblBorders></w:tblPr>';

    foreach ($rows as $row) {
        $xml .= '<w:tr>' . lcrms_case_docx_cell((string) $row[0], true) . lcrms_case_docx_cell((string) $row[1]) . '</w:tr>';
    }

    return $xml . '</w:tbl>';
}

function lcrms_case_docx_document_xml(array $case): string
{
    $caseNumber = lcrms_case_docx_text($case['case_number'] ?? '');
    $status = lcrms_case_docx_status($case['case_status'] ?? '');
    $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
    $xml .= '<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">';
    $xml .= '<w:body>';
    $xml .= lcrms_case_docx_paragraph('Case Details', 'Title');
    $xml .= lcrms_case_docx_paragraph('LCRMS Case Record - ' . $caseNumber, 'Subtitle');
    $xml .= lcrms_case_docx_paragraph('Case Identification', 'Heading1');
    $xml .= lcrms_case_docx_table([
        ['Case Number', $caseNumber],
        ['Case Title', lcrms_case_docx_text($case['case_title'] ?? '')],
        ['Complainant Title', lcrms_case_docx_text($case['complainant_title'] ?? '')],
        ['Nature of Case', lcrms_case_docx_text($case['nature_of_case'] ?? '')],
    ]);
    $xml .= lcrms_case_docx_paragraph('', '');
    $xml .= lcrms_case_docx_paragraph('Complainant Information', 'Heading1');
    $xml .= lcrms_case_docx_table([
        ['Full Name', lcrms_case_docx_text($case['complainant_full_name'] ?? '')],
        ['Address', lcrms_case_docx_text($case['complainant_address'] ?? '')],
        ['Status', lcrms_case_docx_text($case['complainant_status'] ?? '')],
        ['Religion', lcrms_case_docx_text($case['complainant_religion'] ?? '')],
        ['Birthdate', lcrms_case_docx_text($case['complainant_birthdate'] ?? '')],
        ['Age', lcrms_case_docx_text((string) ($case['complainant_age'] ?? ''))],
        ['Government ID', lcrms_case_docx_text($case['complainant_government_id'] ?? '')],
        ['Contact Number', lcrms_case_docx_text($case['complainant_contact_number'] ?? '')],
    ]);
    $xml .= lcrms_case_docx_paragraph('', '');
    $xml .= lcrms_case_docx_paragraph('Respondent Information', 'Heading1');
    $xml .= lcrms_case_docx_table([
        ['Full Name', lcrms_case_docx_text($case['respondent_full_name'] ?? '')],
        ['Address', lcrms_case_docx_text($case['respondent_address'] ?? '')],
        ['Contact Number', lcrms_case_docx_text($case['respondent_contact_number'] ?? '')],
    ]);
    $xml .= lcrms_case_docx_paragraph('', '');
    $xml .= lcrms_case_docx_paragraph('Schedule and Status', 'Heading1');
    $xml .= lcrms_case_docx_table([
        ['Date Filed', lcrms_case_docx_text($case['date_filed'] ?? '')],
        ['Date of Initial Confrontation', lcrms_case_docx_text($case['date_initial_confrontation'] ?? '')],
        ['Case Status', lcrms_case_docx_text($status)],
        ['Date of Settlement / Award', lcrms_case_docx_text($case['date_settlement_award'] ?? '')],
        ['Date of Execution', lcrms_case_docx_text($case['date_execution'] ?? '')],
    ]);
    $xml .= lcrms_case_docx_paragraph('', '');
    $xml .= lcrms_case_docx_paragraph('Case Narrative', 'Heading1');
    $xml .= lcrms_case_docx_paragraph('Detailed Case Description', 'Heading2');
    $xml .= lcrms_case_docx_paragraph(lcrms_case_docx_text($case['detailed_case_description'] ?? ''));
    $xml .= lcrms_case_docx_paragraph('Main Point of Agreement', 'Heading2');
    $xml .= lcrms_case_docx_paragraph(lcrms_case_docx_text($case['main_point_of_agreement'] ?? ''));
    $xml .= '<w:sectPr><w:pgSz w:w="12240" w:h="15840"/><w:pgMar w:top="1080" w:right="1080" w:bottom="1080" w:left="1080" w:header="720" w:footer="720" w:gutter="0"/></w:sectPr>';
    $xml .= '</w:body></w:document>';

    return $xml;
}

function lcrms_case_docx_styles_xml(): string
{
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">
    <w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/><w:rPr><w:sz w:val="22"/><w:szCs w:val="22"/></w:rPr><w:pPr><w:spacing w:after="120"/></w:pPr></w:style>
    <w:style w:type="paragraph" w:styleId="Title"><w:name w:val="Title"/><w:rPr><w:b/><w:color w:val="151A23"/><w:sz w:val="34"/></w:rPr><w:pPr><w:spacing w:after="120"/></w:pPr></w:style>
    <w:style w:type="paragraph" w:styleId="Subtitle"><w:name w:val="Subtitle"/><w:rPr><w:color w:val="596579"/><w:sz w:val="22"/></w:rPr><w:pPr><w:spacing w:after="260"/></w:pPr></w:style>
    <w:style w:type="paragraph" w:styleId="Heading1"><w:name w:val="heading 1"/><w:rPr><w:b/><w:color w:val="125CA8"/><w:sz w:val="26"/></w:rPr><w:pPr><w:spacing w:before="180" w:after="120"/></w:pPr></w:style>
    <w:style w:type="paragraph" w:styleId="Heading2"><w:name w:val="heading 2"/><w:rPr><w:b/><w:color w:val="151A23"/><w:sz w:val="22"/></w:rPr><w:pPr><w:spacing w:before="120" w:after="80"/></w:pPr></w:style>
</w:styles>';
}

function lcrms_case_docx_entries(array $case): array
{
    return [
        '[Content_Types].xml' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/></Types>',
        '_rels/.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>',
        'word/_rels/document.xml.rels' => '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>',
        'word/document.xml' => lcrms_case_docx_document_xml($case),
        'word/styles.xml' => lcrms_case_docx_styles_xml(),
    ];
}

function lcrms_case_docx_zip_with_extension(array $entries): ?string
{
    if (!class_exists('ZipArchive')) {
        return null;
    }

    $path = tempnam(sys_get_temp_dir(), 'lcrms-case-');

    if ($path === false) {
        return null;
    }

    $zip = new ZipArchive();

    if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
        @unlink($path);
        return null;
    }

    foreach ($entries as $name => $content) {
        $zip->addFromString($name, $content);
    }

    $zip->close();
    $data = file_get_contents($path);
    @unlink($path);

    return $data === false ? null : $data;
}

function lcrms_case_docx_zip_stored(array $entries): string
{
    $local = '';
    $central = '';
    $offset = 0;

    foreach ($entries as $name => $content) {
        $crc = crc32($content);
        $size = strlen($content);
        $nameLength = strlen($name);
        $localHeader = pack('VvvvvvVVVvv', 0x04034b50, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0) . $name;
        $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0, 0, 0, 0, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset) . $name;
        $local .= $localHeader . $content;
        $offset += strlen($localHeader) + $size;
    }

    return $local . $central . pack('VvvvvVVv', 0x06054b50, 0, 0, count($entries), count($entries), strlen($central), strlen($local), 0);
}

function lcrms_case_docx_build(array $case): string
{
    $entries = lcrms_case_docx_entries($case);
    $zip = lcrms_case_docx_zip_with_extension($entries);

    return $zip ?? lcrms_case_docx_zip_stored($entries);
}

function lcrms_case_docx_filename(array $case): string
{
    $caseNumber = preg_replace('/[^A-Za-z0-9_-]+/', '-', lcrms_case_docx_text($case['case_number'] ?? 'case'));
    $caseNumber = trim((string) $caseNumber, '-');

    return ($caseNumber === '' ? 'case-details' : $caseNumber) . '.docx';
}

