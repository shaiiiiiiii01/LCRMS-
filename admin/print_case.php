<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/auth_admin.php';
require_admin_login();
require_once __DIR__ . '/../user/models/CaseModel.php';
require_once __DIR__ . '/../includes/case_docx.php';

$caseId = (int) ($_GET['id'] ?? 0);
$case = null;

if ($caseId > 0) {
    $case = (new CaseModel(lcrms_db()))->findByIdForAdmin($caseId);
}

if (!$case) {
    http_response_code(404);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Case record not found.';
    exit;
}

$document = lcrms_case_docx_build($case);
$filename = lcrms_case_docx_filename($case);

while (ob_get_level() > 0) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
header('Content-Disposition: attachment; filename="' . addcslashes($filename, "\\\"") . '"');
header('Content-Length: ' . strlen($document));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

echo $document;
exit;

