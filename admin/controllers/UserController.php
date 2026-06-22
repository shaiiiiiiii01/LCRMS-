<?php
declare(strict_types=1);

require_once __DIR__ . '/../models/UserModel.php';

class UserController
{
    public function __construct(private UserModel $users)
    {
    }

    public function handle(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? 'list';

        if ($method === 'GET' && $action === 'list') {
            $this->respond(['success' => true, 'users' => $this->users->list(trim($_GET['search'] ?? ''))]);
        }

        if ($method === 'GET' && $action === 'admin_profile') {
            $admin = $this->users->adminProfile($this->currentAdminId());
            $this->respond(['success' => true, 'admin' => $admin]);
        }

        $payload = $this->readJson();
        $adminUsername = $this->currentAdminUsername();

        if ($method === 'POST' && $action === 'create') {
            $this->respondModel($this->users->create($payload, $adminUsername));
        }

        if ($method === 'POST' && $action === 'update') {
            $result = $this->users->update($payload, $adminUsername);

            if (($result['success'] ?? false) && (int) ($payload['id'] ?? 0) === (int) ($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0)) {
                $account = $this->users->find((int) $payload['id']);

                if ($account) {
                    lcrms_set_account_session($account);
                }
            }

            $this->respondModel($result);
        }

        if ($method === 'POST' && $action === 'update_admin_profile') {
            $result = $this->users->updateAdminProfile($payload, $this->currentAdminId());

            if (($result['success'] ?? false) && !empty($result['admin'])) {
                lcrms_set_account_session($result['admin']);
            }

            $this->respondModel($result);
        }

        if ($method === 'POST' && $action === 'delete') {
            if ((int) ($payload['id'] ?? 0) === (int) ($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0)) {
                $this->respond(['success' => false, 'message' => 'You cannot delete the account you are currently using.'], 422);
            }

            $this->respondModel($this->users->delete($payload, $adminUsername));
        }

        $this->respond(['success' => false, 'message' => 'Unsupported request.'], 404);
    }

    private function readJson(): array
    {
        $raw = file_get_contents('php://input');
        $payload = json_decode($raw ?: '{}', true);
        return is_array($payload) ? $payload : [];
    }

    private function currentAdminUsername(): string
    {
        return trim((string) ($_SESSION['username'] ?? $_SESSION['admin_username'] ?? 'Admin'));
    }

    private function currentAdminId(): int
    {
        return (int) ($_SESSION['admin_id'] ?? $_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0);
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

