<?php
// src/Auth.php
// Authentication Helper Class
// Handles login, logout, registration, password reset (basic), and guards

namespace App;
use PDO;
class Auth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

 
    public function attempt(string $usernameOrEmail, string $password): bool
    {
        $stmt = $this->pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate session ID on successful login (prevents session fixation)
            session_regenerate_id(true);

            $_SESSION['user_id']   = $user['id'];
            $_SESSION['username']  = $user['username'];
            $_SESSION['email']     = $user['email'];
            $_SESSION['role']      = $user['role'];
            $_SESSION['logged_in'] = true;

            return true;
        }

        return false;
    }


    public function register(string $username, string $email, string $password, string $role = 'user'): bool
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            return $stmt->execute([$username, $email, $hash, $role]);
        } catch (\PDOException $e) {
            // Duplicate username/email will throw exception
            return false;
        }
    }

 
    public static function logout(): void
    {
        // Clear all session data
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Finally destroy the session
        session_destroy();
    }

    /**
     * Check if user is logged in
     */
    public static function check(): bool
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Get current user ID
     */
    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current username
     */
    public static function user(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    /**
     * Check if current user is admin
     */
    public static function isAdmin(): bool
    {
        return ($_SESSION['role'] ?? '') === 'admin';
    }

    /**
     * Redirect if not authenticated
     */
    public static function guard(string $redirectTo = 'login.php'): void
    {
        if (!self::check()) {
            header("Location: $redirectTo");
            exit;
        }
    }

    /**
     * Redirect if not admin
     */
    public static function guardAdmin(string $redirectTo = '../login.php'): void
    {
        if (!self::isAdmin()) {
            $_SESSION['flash_message'] = ['text' => 'Access denied. Administrators only.', 'type' => 'danger'];
            header("Location: $redirectTo");
            exit;
        }
    }
}