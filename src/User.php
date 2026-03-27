<?php
//src/User.php
namespace App;

//use PDO;

class User
{
    private \PDO $pdo;
    private ?int $id = null;
    private ?string $username = null;
    private ?string $email = null;
    private ?string $password = null;
    private ?string $role = null;
    public function __construct(?\PDO $pdo = null)
    {
        if ($pdo) {
            $this->pdo = $pdo;
        } else {
            $config = include __DIR__ . '/../config/database.php';
            $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
            try {
                $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $config['options']);
            } catch (\PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
    }
    public function login(string $usernameOrEmail, string $password): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $this->id = $user['id'];
            $this->username = $user['username'];
            $this->email = $user['email'];
            $this->role = $user['role'];

            $_SESSION['user_id'] = $this->id;
            $_SESSION['username'] = $this->username;
            $_SESSION['email'] = $this->email;
            $_SESSION['role'] = $this->role;
            return true;
        }
        return false;
    }
    public function register(string $username, string $email, string $password): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
        return $stmt->execute([$username, $email, $hash]);
    }

    public function getAllUsers(): array
    {
        $stmt = $this->pdo->query("SELECT id, username, email, role, membership_tier, membership_id, created_at FROM users ORDER BY created_at DESC");
        return $stmt->fetchAll();
    }

    public function getUserById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, role, membership_tier, membership_id, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function updateUserRole(int $id, string $role): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        return $stmt->execute([$role, $id]);
    }

    public function updateMembershipTier(int $id, string $tier): bool
    {
        $stmt = $this->pdo->prepare("UPDATE users SET membership_tier = ? WHERE id = ?");
        return $stmt->execute([$tier, $id]);
    }

    public function deleteUser(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public static function isLoggedIn(): bool
    {
        return isset($_SESSION['user_id']);
    }
    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] === 'admin');
    }
    public static function getCurrentUserId(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }
    public static function logout(): void
    {
        session_unset();
        session_destroy();
    }
}
