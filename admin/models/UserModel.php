<?php
declare(strict_types=1);

class UserModel
{
    public function __construct(private mysqli $conn)
    {
        mysqli_set_charset($this->conn, 'utf8mb4');
        mysqli_report(MYSQLI_REPORT_OFF);
        $this->ensureTable();
        $this->ensureActivityLogTable();
    }

    public function list(string $search = ''): array
    {
        $users = [];

        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = mysqli_prepare($this->conn, "SELECT id, user_code, fullname, username, role, created_at FROM users WHERE role = 'USER' AND (fullname LIKE ? OR username LIKE ?) ORDER BY id ASC");
            mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
        } else {
            $stmt = mysqli_prepare($this->conn, "SELECT id, user_code, fullname, username, role, created_at FROM users WHERE role = 'USER' ORDER BY id ASC");
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $users[] = $row;
        }

        return $users;
    }

    public function create(array $payload, string $adminUsername = 'System'): array
    {
        $fullname = trim($payload['fullname'] ?? '');
        $username = trim($payload['username'] ?? '');
        $password = trim($payload['password'] ?? '');
        $requestedRole = $this->sanitizeRole($payload['role'] ?? 'USER');
        $role = $requestedRole === 'ADMIN' && !$this->adminExists() ? 'ADMIN' : 'USER';

        if ($fullname === '' || $username === '' || $password === '') {
            return ['success' => false, 'message' => 'Full name, username, and password are required.', 'status' => 422];
        }

        if ($this->usernameExists($username)) {
            return ['success' => false, 'message' => 'Username already exists.', 'status' => 409];
        }

        $userCode = $this->generateUserCode();
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($this->conn, 'INSERT INTO users (user_code, fullname, username, password, role) VALUES (?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'sssss', $userCode, $fullname, $username, $passwordHash, $role);

        if (!mysqli_stmt_execute($stmt)) {
            return ['success' => false, 'message' => 'Username already exists or could not be saved.', 'status' => 409];
        }

        $this->logActivity('CREATE_USER', $username, $adminUsername);

        return ['success' => true, 'message' => 'User added successfully.', 'id' => mysqli_insert_id($this->conn), 'user_code' => $userCode];
    }

    public function update(array $payload, string $adminUsername = 'System'): array
    {
        $id = (int) ($payload['id'] ?? 0);
        $fullname = trim($payload['fullname'] ?? '');
        $username = trim($payload['username'] ?? '');
        $password = trim($payload['password'] ?? '');
        $role = 'USER';

        if ($id <= 0 || $fullname === '' || $username === '') {
            return ['success' => false, 'message' => 'Full name and username are required.', 'status' => 422];
        }

        if (!$this->find($id)) {
            return ['success' => false, 'message' => 'User account was not found.', 'status' => 404];
        }

        $existingUser = $this->find($id);

        if ($existingUser && $this->sanitizeRole((string) $existingUser['role']) === 'ADMIN') {
            return ['success' => false, 'message' => 'Admin profile must be updated from the Admin Profile section.', 'status' => 422];
        }

        if ($this->usernameExists($username, $id)) {
            return ['success' => false, 'message' => 'Username already exists.', 'status' => 409];
        }

        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($this->conn, 'UPDATE users SET fullname = ?, username = ?, password = ?, role = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'ssssi', $fullname, $username, $passwordHash, $role, $id);
        } else {
            $stmt = mysqli_prepare($this->conn, 'UPDATE users SET fullname = ?, username = ?, role = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'sssi', $fullname, $username, $role, $id);
        }

        if (!mysqli_stmt_execute($stmt)) {
            return ['success' => false, 'message' => 'User could not be updated.', 'status' => 409];
        }

        $this->logActivity('UPDATE_USER', $username, $adminUsername);

        return ['success' => true, 'message' => 'User updated successfully.'];
    }

    public function delete(array $payload, string $adminUsername = 'System'): array
    {
        $id = (int) ($payload['id'] ?? 0);

        if ($id <= 0) {
            return ['success' => false, 'message' => 'A valid user is required.', 'status' => 422];
        }

        $user = $this->find($id);

        if (!$user) {
            return ['success' => false, 'message' => 'User account was not found.', 'status' => 404];
        }

        if ($this->sanitizeRole((string) $user['role']) === 'ADMIN') {
            return ['success' => false, 'message' => 'Admin profile cannot be deleted from User Management.', 'status' => 422];
        }

        $stmt = mysqli_prepare($this->conn, 'DELETE FROM users WHERE id = ?');
        mysqli_stmt_bind_param($stmt, 'i', $id);

        if (!mysqli_stmt_execute($stmt)) {
            return ['success' => false, 'message' => 'User could not be deleted.', 'status' => 409];
        }

        $this->logActivity('DELETE_USER', (string) $user['username'], $adminUsername);

        return ['success' => true, 'message' => 'User deleted successfully.'];
    }

    public function find(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $stmt = mysqli_prepare($this->conn, 'SELECT id, user_code, fullname, username, password, role, created_at FROM users WHERE id = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 'i', $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $user ?: null;
    }

    public function findByUsername(string $username): ?array
    {
        $username = trim($username);

        if ($username === '') {
            return null;
        }

        $stmt = mysqli_prepare($this->conn, 'SELECT id, user_code, fullname, username, password, role, created_at FROM users WHERE username = ? LIMIT 1');
        mysqli_stmt_bind_param($stmt, 's', $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $user ?: null;
    }

    public function adminProfile(int $adminId = 0): ?array
    {
        if ($adminId > 0) {
            $stmt = mysqli_prepare($this->conn, "SELECT id, user_code, fullname, username, role, created_at FROM users WHERE id = ? AND role = 'ADMIN' LIMIT 1");
            mysqli_stmt_bind_param($stmt, 'i', $adminId);
        } else {
            $stmt = mysqli_prepare($this->conn, "SELECT id, user_code, fullname, username, role, created_at FROM users WHERE role = 'ADMIN' ORDER BY id ASC LIMIT 1");
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $admin = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        return $admin ?: null;
    }

    public function updateAdminProfile(array $payload, int $adminId): array
    {
        $fullname = trim($payload['fullname'] ?? '');
        $username = trim($payload['username'] ?? '');
        $password = trim($payload['password'] ?? '');

        if ($adminId <= 0 || $fullname === '' || $username === '') {
            return ['success' => false, 'message' => 'Full name and username are required.', 'status' => 422];
        }

        $admin = $this->adminProfile($adminId);

        if (!$admin) {
            return ['success' => false, 'message' => 'Admin profile was not found.', 'status' => 404];
        }

        if ($this->usernameExists($username, $adminId)) {
            return ['success' => false, 'message' => 'Username already exists.', 'status' => 409];
        }

        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($this->conn, "UPDATE users SET fullname = ?, username = ?, password = ?, role = 'ADMIN' WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'sssi', $fullname, $username, $passwordHash, $adminId);
        } else {
            $stmt = mysqli_prepare($this->conn, "UPDATE users SET fullname = ?, username = ?, role = 'ADMIN' WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'ssi', $fullname, $username, $adminId);
        }

        if (!mysqli_stmt_execute($stmt)) {
            return ['success' => false, 'message' => 'Admin profile could not be updated.', 'status' => 409];
        }

        $this->syncLegacyAdminProfile($username, $password);
        $this->logActivity('UPDATE_ADMIN_PROFILE', $username, $username);

        return [
            'success' => true,
            'message' => 'Admin profile updated successfully.',
            'admin' => $this->adminProfile($adminId),
        ];
    }

    public function adminExists(): bool
    {
        $result = mysqli_query($this->conn, "SELECT id FROM users WHERE role = 'ADMIN' LIMIT 1");

        return (bool) ($result && mysqli_fetch_assoc($result));
    }

    public function ensureDefaultAdmin(string $fullname = 'System Administrator', string $username = 'admin', string $password = 'admin123'): void
    {
        $result = mysqli_query($this->conn, "SELECT id FROM users WHERE role = 'ADMIN' LIMIT 1");

        if ($result && mysqli_fetch_assoc($result)) {
            return;
        }

        if ($this->usernameExists($username)) {
            $stmt = mysqli_prepare($this->conn, "UPDATE users SET role = 'ADMIN', fullname = ? WHERE username = ?");
            mysqli_stmt_bind_param($stmt, 'ss', $fullname, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return;
        }

        $this->create([
            'fullname' => $fullname,
            'username' => $username,
            'password' => $password,
            'role' => 'ADMIN',
        ]);
    }

    private function ensureTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_code VARCHAR(32) NOT NULL UNIQUE,
            fullname VARCHAR(150) NOT NULL,
            username VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) NOT NULL DEFAULT 'USER',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql);
        $this->migrateTable();
        $this->seedIfEmpty();
    }

    private function ensureActivityLogTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            action_type VARCHAR(50) NOT NULL,
            username_affected VARCHAR(150) NOT NULL,
            admin_username VARCHAR(150) NOT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            INDEX activity_logs_created_at_index (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

        mysqli_query($this->conn, $sql);

        $columns = [
            'action_type' => "ALTER TABLE activity_logs ADD COLUMN action_type VARCHAR(50) NOT NULL DEFAULT 'USER_ACTION'",
            'username_affected' => "ALTER TABLE activity_logs ADD COLUMN username_affected VARCHAR(150) NOT NULL DEFAULT ''",
            'admin_username' => "ALTER TABLE activity_logs ADD COLUMN admin_username VARCHAR(150) NOT NULL DEFAULT ''",
            'created_at' => "ALTER TABLE activity_logs ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];

        foreach ($columns as $column => $alterSql) {
            if (!$this->columnExists($column, 'activity_logs')) {
                mysqli_query($this->conn, $alterSql);
            }
        }
    }

    private function logActivity(string $actionType, string $usernameAffected, string $adminUsername): void
    {
        $stmt = mysqli_prepare($this->conn, 'INSERT INTO activity_logs (action_type, username_affected, admin_username) VALUES (?, ?, ?)');

        if (!$stmt) {
            return;
        }

        mysqli_stmt_bind_param($stmt, 'sss', $actionType, $usernameAffected, $adminUsername);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function syncLegacyAdminProfile(string $username, string $password): void
    {
        $tableCheck = mysqli_query($this->conn, "SHOW TABLES LIKE 'admin'");

        if (!$tableCheck || !mysqli_fetch_row($tableCheck)) {
            return;
        }

        if ($password !== '') {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($this->conn, 'UPDATE admin SET username = ?, password = ? ORDER BY admin_id ASC LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'ss', $username, $passwordHash);
        } else {
            $stmt = mysqli_prepare($this->conn, 'UPDATE admin SET username = ? ORDER BY admin_id ASC LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $username);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    private function seedIfEmpty(): void
    {
        $result = mysqli_query($this->conn, 'SELECT COUNT(*) AS total FROM users');
        $row = $result ? mysqli_fetch_assoc($result) : ['total' => 0];

        if ((int) $row['total'] !== 0) {
            return;
        }

        $seed = [
            ['LCRMS-20260001', 'System Administrator', 'admin', 'ADMIN'],
            ['LCRMS-20263201', 'MARK JONAS', 'user1@gmail.com', 'USER'],
            ['LCRMS-20264569', 'HANZ ROBERT', 'user2@gmail.com', 'USER'],
        ];

        $stmt = mysqli_prepare($this->conn, 'INSERT INTO users (user_code, fullname, username, password, role) VALUES (?, ?, ?, ?, ?)');

        foreach ($seed as $user) {
            $password = password_hash('lcrms123', PASSWORD_DEFAULT);
            mysqli_stmt_bind_param($stmt, 'sssss', $user[0], $user[1], $user[2], $password, $user[3]);
            mysqli_stmt_execute($stmt);
        }
    }

    private function migrateTable(): void
    {
        if (!$this->columnExists('id')) {
            if ($this->columnExists('user_id')) {
                mysqli_query($this->conn, 'ALTER TABLE users CHANGE user_id id INT UNSIGNED NOT NULL AUTO_INCREMENT');
            } else {
                mysqli_query($this->conn, 'ALTER TABLE users ADD COLUMN id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST');
            }
        }

        $columns = [
            'user_code' => "ALTER TABLE users ADD COLUMN user_code VARCHAR(32) NULL AFTER id",
            'fullname' => "ALTER TABLE users ADD COLUMN fullname VARCHAR(150) NULL AFTER user_code",
            'username' => "ALTER TABLE users ADD COLUMN username VARCHAR(150) NULL AFTER fullname",
            'password' => "ALTER TABLE users ADD COLUMN password VARCHAR(255) NULL AFTER username",
            'role' => "ALTER TABLE users ADD COLUMN role VARCHAR(50) NOT NULL DEFAULT 'USER' AFTER password",
            'created_at' => "ALTER TABLE users ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER role",
        ];

        foreach ($columns as $column => $alterSql) {
            if (!$this->columnExists($column) && !mysqli_query($this->conn, $alterSql)) {
                $fallbackSql = preg_replace('/\s+AFTER\s+[a-zA-Z0-9_]+$/', '', $alterSql);
                mysqli_query($this->conn, $fallbackSql);
            }
        }

        if ($this->columnExists('name')) {
            mysqli_query($this->conn, "UPDATE users SET fullname = UPPER(name) WHERE fullname IS NULL OR fullname = ''");
        }

        mysqli_query($this->conn, "UPDATE users SET fullname = UPPER(username) WHERE (fullname IS NULL OR fullname = '') AND username IS NOT NULL AND username <> ''");
        mysqli_query($this->conn, "UPDATE users SET role = 'USER' WHERE role IS NULL OR role = ''");
        mysqli_query($this->conn, "UPDATE users SET role = 'ADMIN' WHERE UPPER(role) = 'SUPER ADMINISTRATOR'");
        mysqli_query($this->conn, "UPDATE users SET role = 'USER' WHERE UPPER(role) NOT IN ('USER', 'ADMIN')");

        $passwordHash = password_hash('lcrms123', PASSWORD_DEFAULT);
        $stmt = mysqli_prepare($this->conn, "UPDATE users SET password = ? WHERE password IS NULL OR password = ''");
        mysqli_stmt_bind_param($stmt, 's', $passwordHash);
        mysqli_stmt_execute($stmt);

        $result = mysqli_query($this->conn, "SELECT id FROM users WHERE user_code IS NULL OR user_code = '' ORDER BY id ASC");

        while ($result && $row = mysqli_fetch_assoc($result)) {
            $code = $this->generateUserCode();
            $stmt = mysqli_prepare($this->conn, 'UPDATE users SET user_code = ? WHERE id = ?');
            mysqli_stmt_bind_param($stmt, 'si', $code, $row['id']);
            mysqli_stmt_execute($stmt);
        }

        mysqli_query($this->conn, 'ALTER TABLE users MODIFY user_code VARCHAR(32) NOT NULL');
        mysqli_query($this->conn, 'ALTER TABLE users MODIFY fullname VARCHAR(150) NOT NULL');
        mysqli_query($this->conn, 'ALTER TABLE users MODIFY username VARCHAR(150) NOT NULL');
        mysqli_query($this->conn, 'ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL');
        mysqli_query($this->conn, 'ALTER TABLE users ADD UNIQUE KEY user_code_unique (user_code)');
        mysqli_query($this->conn, 'ALTER TABLE users ADD UNIQUE KEY username_unique (username)');
    }

    private function columnExists(string $column, string $table = 'users'): bool
    {
        $column = mysqli_real_escape_string($this->conn, $column);
        $table = mysqli_real_escape_string($this->conn, $table);
        $result = mysqli_query($this->conn, "SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

        return (bool) mysqli_fetch_assoc($result);
    }

    private function sanitizeRole(string $role): string
    {
        $role = strtoupper(trim($role));
        $allowed = ['USER', 'ADMIN'];
        return in_array($role, $allowed, true) ? $role : 'USER';
    }

    private function usernameExists(string $username, int $excludeId = 0): bool
    {
        if ($excludeId > 0) {
            $stmt = mysqli_prepare($this->conn, 'SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 'si', $username, $excludeId);
        } else {
            $stmt = mysqli_prepare($this->conn, 'SELECT id FROM users WHERE username = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $username);
        }

        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $exists = (bool) ($result && mysqli_fetch_assoc($result));
        mysqli_stmt_close($stmt);

        return $exists;
    }

    private function generateUserCode(): string
    {
        do {
            $code = 'LCRMS-' . date('Y') . random_int(1000, 9999);
            $stmt = mysqli_prepare($this->conn, 'SELECT id FROM users WHERE user_code = ? LIMIT 1');
            mysqli_stmt_bind_param($stmt, 's', $code);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
        } while ($result && mysqli_fetch_assoc($result));

        return $code;
    }
}
