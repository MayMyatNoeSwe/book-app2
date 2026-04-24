<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/env_loader.php';

$config = require __DIR__ . '/../config/database.php';
$dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
$pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);

echo "=== Users with multiple cards of same tier (shared + own) ===\n\n";

$sql = "SELECT us.user_id, u.username, us.tier, COUNT(*) as cnt, 
               GROUP_CONCAT(us.id, ':', CASE WHEN us.parent_id IS NULL THEN 'OWN' ELSE CONCAT('SHARED(p:',us.parent_id,')') END ORDER BY us.id) as details
        FROM user_subscriptions us
        JOIN users u ON us.user_id = u.id
        WHERE (us.expires_at > NOW() OR EXISTS (
            SELECT 1 FROM user_subscriptions p WHERE p.id = us.parent_id AND p.expires_at > NOW()
        ))
        GROUP BY us.user_id, us.tier
        HAVING COUNT(*) > 1";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($rows)) {
    echo "No users found with multiple same-tier cards.\n";
} else {
    foreach ($rows as $r) {
        echo "User: {$r['username']} (uid:{$r['user_id']}) | Tier: {$r['tier']} | Cards: {$r['details']}\n";
    }
}

echo "\n=== Full getSubscriptions() result for cho cho (uid:47) ===\n";
$sql2 = "SELECT us.*, parent.expires_at as parent_expires_at
         FROM user_subscriptions us
         LEFT JOIN user_subscriptions parent ON us.parent_id = parent.id
         WHERE us.user_id = 47 
         AND (
             (us.parent_id IS NULL AND us.expires_at > NOW()) 
             OR 
             (us.parent_id IS NOT NULL AND parent.expires_at > NOW())
         )
         ORDER BY us.expires_at DESC";
$stmt2 = $pdo->query($sql2);
$subs = $stmt2->fetchAll(PDO::FETCH_ASSOC);
foreach ($subs as $s) {
    echo "  ID: {$s['id']} | Tier: {$s['tier']} | Host: {$s['is_host']} | Parent: " . ($s['parent_id'] ?: 'NULL') . " | Expires: {$s['expires_at']}\n";
}

echo "\n=== Full getSubscriptions() for ALL users ===\n";
$allUsers = $pdo->query("SELECT DISTINCT user_id FROM user_subscriptions")->fetchAll(PDO::FETCH_COLUMN);
foreach ($allUsers as $uid) {
    $stmt3 = $pdo->prepare($sql2);
    // rewrite for each user
    $stmt3 = $pdo->prepare("SELECT us.*, parent.expires_at as parent_expires_at
         FROM user_subscriptions us
         LEFT JOIN user_subscriptions parent ON us.parent_id = parent.id
         WHERE us.user_id = ? 
         AND (
             (us.parent_id IS NULL AND us.expires_at > NOW()) 
             OR 
             (us.parent_id IS NOT NULL AND parent.expires_at > NOW())
         )
         ORDER BY us.expires_at DESC");
    $stmt3->execute([$uid]);
    $subs = $stmt3->fetchAll(PDO::FETCH_ASSOC);
    $uname = $pdo->prepare("SELECT username FROM users WHERE id = ?");
    $uname->execute([$uid]);
    $name = $uname->fetchColumn();
    
    // Check if there are duplicate tiers in getSubscriptions output
    $tierCounts = array_count_values(array_column($subs, 'tier'));
    $hasDupTier = false;
    foreach ($tierCounts as $t => $c) {
        if ($c > 1) $hasDupTier = true;
    }
    
    if ($hasDupTier) {
        echo "\n  ** USER: {$name} (uid:{$uid}) has DUPLICATE tier in getSubscriptions:\n";
        foreach ($subs as $s) {
            echo "     ID: {$s['id']} | Tier: {$s['tier']} | Host: {$s['is_host']} | Parent: " . ($s['parent_id'] ?: 'NULL') . "\n";
        }
    }
}

echo "\nDone.\n";
