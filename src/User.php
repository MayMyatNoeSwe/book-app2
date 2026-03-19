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
    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?, 'user')");
        return $stmt->execute([$username, $email, $hash]);
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
