<?php
namespace App;

use PDO;

class OAuth
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Find or create user from OAuth provider
     */
    public function findOrCreateUser(string $provider, string $oauthId, string $email, string $name, ?string $avatarUrl = null): ?array
    {
        // Check if user exists with this OAuth provider
        $stmt = $this->pdo->prepare("
            SELECT * FROM users 
            WHERE oauth_provider = ? AND oauth_id = ?
        ");
        $stmt->execute([$provider, $oauthId]);
        $user = $stmt->fetch();

        if ($user) {
            // Update avatar if changed
            if ($avatarUrl && $avatarUrl !== $user['avatar_url']) {
                $this->updateAvatar($user['id'], $avatarUrl);
            }
            return $user;
        }

        // Check if user exists with this email
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $existingUser = $stmt->fetch();

        if ($existingUser) {
            // Link OAuth to existing account
            $this->linkOAuthToUser($existingUser['id'], $provider, $oauthId, $avatarUrl);
            return $existingUser;
        }

        // Create new user
        return $this->createOAuthUser($provider, $oauthId, $email, $name, $avatarUrl);
    }

    /**
     * Create new user from OAuth
     */
    private function createOAuthUser(string $provider, string $oauthId, string $email, string $name, ?string $avatarUrl): array
    {
        // Generate unique username from name
        $username = $this->generateUsername($name);

        $stmt = $this->pdo->prepare("
            INSERT INTO users (username, email, password, oauth_provider, oauth_id, avatar_url, email_verified, role, membership_tier, created_at)
            VALUES (?, ?, '', ?, ?, ?, TRUE, 'user', 'bronze', NOW())
        ");
        
        $stmt->execute([$username, $email, $provider, $oauthId, $avatarUrl]);
        
        $userId = $this->pdo->lastInsertId();
        
        // Generate Membership ID
        $mid = 'LIB-' . str_pad($userId, 6, '0', STR_PAD_LEFT);
        $stmt = $this->pdo->prepare("UPDATE users SET membership_id = ? WHERE id = ?");
        $stmt->execute([$mid, $userId]);

        // Fetch and return the new user
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Link OAuth provider to existing user
     */
    private function linkOAuthToUser(int $userId, string $provider, string $oauthId, ?string $avatarUrl): void
    {
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET oauth_provider = ?, oauth_id = ?, avatar_url = ?, email_verified = TRUE
            WHERE id = ?
        ");
        $stmt->execute([$provider, $oauthId, $avatarUrl, $userId]);
    }

    /**
     * Update user avatar
     */
    private function updateAvatar(int $userId, string $avatarUrl): void
    {
        $stmt = $this->pdo->prepare("UPDATE users SET avatar_url = ? WHERE id = ?");
        $stmt->execute([$avatarUrl, $userId]);
    }

    /**
     * Generate unique username from name
     */
    private function generateUsername(string $name): string
    {
        // Clean the name
        $username = strtolower(trim($name));
        $username = preg_replace('/[^a-z0-9_]/', '', $username);
        
        // Check if username exists
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        
        if ($stmt->fetchColumn() > 0) {
            // Add random number if username exists
            $username .= rand(100, 999);
        }

        return $username;
    }

    /**
     * Login user via OAuth
     */
    public function loginUser(array $user): void
    {
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['avatar_url'] = $user['avatar_url'];
        $_SESSION['logged_in'] = true; // Important: Set this flag for Auth::check()
    }
}
