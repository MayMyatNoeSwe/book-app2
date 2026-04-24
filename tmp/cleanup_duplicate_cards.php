<?php
/**
 * Cleanup: Remove redundant same-tier cards.
 * - If user has both OWN + SHARED of same tier: keep OWN, remove SHARED
 * - If user has 2x SHARED of same tier: keep the newest, remove older
 */
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/env_loader.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

echo "=== Removing Redundant Same-Tier Cards ===\n\n";

// Find users with multiple active same-tier subscriptions
$sql = "SELECT us.user_id, us.tier, 
               GROUP_CONCAT(us.id ORDER BY us.is_host DESC, us.expires_at DESC) as ids,
               GROUP_CONCAT(CASE WHEN us.parent_id IS NULL THEN 'OWN' ELSE 'SHARED' END ORDER BY us.is_host DESC, us.expires_at DESC) as types
        FROM user_subscriptions us
        LEFT JOIN user_subscriptions parent ON us.parent_id = parent.id
        WHERE (
            (us.parent_id IS NULL AND us.expires_at > NOW()) 
            OR 
            (us.parent_id IS NOT NULL AND parent.expires_at > NOW())
        )
        GROUP BY us.user_id, us.tier
        HAVING COUNT(*) > 1";

$stmt = $pdo->query($sql);
$groups = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($groups)) {
    echo "No redundant same-tier cards found. Database is clean!\n";
    exit;
}

$totalRemoved = 0;

foreach ($groups as $g) {
    $ids = explode(',', $g['ids']);
    $types = explode(',', $g['types']);
    $keepId = $ids[0]; // First one: OWN preferred, then newest
    $removeIds = array_slice($ids, 1);
    
    $userStmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $userStmt->execute([$g['user_id']]);
    $username = $userStmt->fetchColumn();
    
    echo "User: {$username} (ID: {$g['user_id']}) | Tier: {$g['tier']}\n";
    echo "  Cards: " . implode(', ', array_map(fn($id, $type) => "#{$id}({$type})", $ids, $types)) . "\n";
    echo "  Keeping: #{$keepId} | Removing: " . implode(', ', array_map(fn($id) => "#{$id}", $removeIds)) . "\n";
    
    // Fix active_subscription_id if needed
    $activeStmt = $pdo->prepare("SELECT active_subscription_id FROM users WHERE id = ?");
    $activeStmt->execute([$g['user_id']]);
    $activeSubId = $activeStmt->fetchColumn();
    
    if (in_array($activeSubId, $removeIds)) {
        $switchStmt = $pdo->prepare("UPDATE users SET active_subscription_id = ? WHERE id = ?");
        $switchStmt->execute([$keepId, $g['user_id']]);
        echo "  -> Switched active card from #{$activeSubId} to #{$keepId}\n";
    }
    
    // Delete redundant cards
    $placeholders = implode(',', array_fill(0, count($removeIds), '?'));
    $delStmt = $pdo->prepare("DELETE FROM user_subscriptions WHERE id IN ($placeholders)");
    $delStmt->execute($removeIds);
    $removed = $delStmt->rowCount();
    $totalRemoved += $removed;
    echo "  -> Removed {$removed} redundant card(s)\n\n";
}

// Sync profiles
echo "Syncing user profiles...\n";
$lib = new \App\Library($pdo);
$affectedUsers = array_unique(array_column($groups, 'user_id'));
foreach ($affectedUsers as $uid) {
    $lib->syncUserProfileTier($uid);
    echo "  Synced user #{$uid}\n";
}

echo "\n=== Done! Removed {$totalRemoved} redundant card(s) total. ===\n";
