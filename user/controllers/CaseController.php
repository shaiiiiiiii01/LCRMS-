<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/CaseModel.php';

class CaseController
{
    public function __construct(private CaseModel $cases, private array $account)
    {
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'create';

        if ($method === 'GET' && $action === 'preview_number') {
            $this->respond([
                'success' => true,
                'case_number' => $this->cases->previewNextCaseNumber(),
            ]);
        }

        if ($method === 'GET' && $action === 'detail') {
            $case = $this->cases->findForAccountById($this->account, (int) ($_GET['id'] ?? 0));

            if (!$case) {
                $this->respond([
                    'success' => false,
                    'message' => 'Case record not found or access is restricted.',
                ], 404);
            }

            $this->respond([
                'success' => true,
                'case' => $case,
            ]);
        }

        if ($method === 'POST' && $action === 'create') {
            $payload = $this->readPayload();
            error_log('LCRMS case create payload keys: ' . implode(', ', array_keys($payload)));
            $this->respondModel($this->cases->create($payload, $this->account));
        }

        $this->respond(['success' => false, 'message' => 'Unsupported request.'], 404);
    }

    private function readPayload(): array
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            $payload = json_decode($raw ?: '{}', true);
            return is_array($payload) ? $payload : [];
        }

        return $_POST;
    }

    private function respondModel(array $payload): void
    {
        $status = (int) ($payload['status'] ?? 200);
        unset($payload['status']);
        $this->respond($payload, $status);
    }

    private function respond(array $payload, int $status = 200): void
    {
        http_response_code($status);
        echo json_encode($payload);
        exit;
    }
}


