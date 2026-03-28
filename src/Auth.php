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
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password, role, membership_tier) VALUES (?, ?, ?, ?, 'bronze')");
            $success = $stmt->execute([$username, $email, $hash, $role]);

            if ($success) {
                $lastId = $this->pdo->lastInsertId();
                $mid = 'LIB-' . str_pad($lastId, 6, '0', STR_PAD_LEFT);
                $stmt = $this->pdo->prepare("UPDATE users SET membership_id = ? WHERE id = ?");
                $stmt->execute([$mid, $lastId]);
            }
            return $success;
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
     * Check if user is logged in and handle membership expiry
     */
    public static function check(): bool
    {
        $isLoggedIn = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
        
        if ($isLoggedIn && isset($_SESSION['user_id'])) {
            // Check expiry intermittently (once per session or every hour) to avoid DB load
            if (!isset($_SESSION['ms_expiry_checked']) || time() - $_SESSION['ms_expiry_checked'] > 3600) {
                try {
                    // Create a temporary PDO instance for static check
                    $config = require dirname(__DIR__) . '/config/database.php';
                    $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], $config['options']);
                    
                    // Cleanup expired subscriptions
                    $stmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ? AND expires_at < NOW()");
                    $stmt->execute([$_SESSION['user_id']]);
                    
                    if ($stmt->rowCount() > 0) {
                        // Recalculate main tier if something was deleted
                        $activeStmt = $pdo->prepare("SELECT tier, expires_at FROM user_subscriptions WHERE user_id = ? AND expires_at > NOW()");
                        $activeStmt->execute([$_SESSION['user_id']]);
                        $subs = $activeStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $bestTier = 'bronze';
                        $maxExpiry = null;
                        
                        $tiersFound = array_column($subs, 'tier');
                        if (in_array('platinum', $tiersFound)) $bestTier = 'platinum';
                        elseif (in_array('gold', $tiersFound)) $bestTier = 'gold';
                        elseif (in_array('silver', $tiersFound)) $bestTier = 'silver';
                        
                        if ($bestTier !== 'bronze') {
                            foreach ($subs as $s) {
                                if ($s['tier'] === $bestTier) {
                                    if (!$maxExpiry || strtotime($s['expires_at']) > strtotime($maxExpiry)) {
                                        $maxExpiry = $s['expires_at'];
                                    }
                                }
                            }
                        }

                        $updateStmt = $pdo->prepare("UPDATE users SET membership_tier = ?, membership_expires_at = ? WHERE id = ?");
                        $updateStmt->execute([$bestTier, $maxExpiry, $_SESSION['user_id']]);
                    }
                    
                    $_SESSION['ms_expiry_checked'] = time();
                } catch (\Exception $e) {}
            }
        }
        
        return $isLoggedIn;
    }

    /**
     * Get all active subscriptions for a user
     */
    public static function getSubscriptions(?int $userId = null): array
    {
        $userId = $userId ?: self::id();
        if (!$userId) return [];
        
        try {
            $config = require dirname(__DIR__) . '/config/database.php';
            $pdo = new PDO("mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}", $config['username'], $config['password'], $config['options']);
            
            $stmt = $pdo->prepare("SELECT tier, expires_at FROM user_subscriptions WHERE user_id = ? AND expires_at > NOW() ORDER BY expires_at ASC");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC); 
        } catch (\Exception $e) {
            return [];
        }
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
            $_SESSION['flash_message'] = ['text' => 'Please login to access this page.', 'type' => 'warning'];
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