<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../includes/env_loader.php';
$c = require __DIR__ . '/../config/database.php';
$p = new PDO("mysql:host={$c['host']};dbname={$c['dbname']};charset={$c['charset']}", $c['username'], $c['password'], $c['options']);

$s = $p->query("
    SELECT us.user_id, u.username, us.tier, COUNT(*) as c 
    FROM user_subscriptions us 
    LEFT JOIN user_subscriptions parent ON us.parent_id = parent.id 
    JOIN users u ON us.user_id = u.id 
    WHERE (us.parent_id IS NULL AND us.expires_at > NOW()) 
       OR (us.parent_id IS NOT NULL AND parent.expires_at > NOW()) 
    GROUP BY us.user_id, us.tier 
    HAVING c > 1
");
$r = $s->fetchAll(PDO::FETCH_ASSOC);
echo empty($r) ? "NO DUPLICATES - Database is clean!\n" : "DUPLICATES FOUND:\n" . print_r($r, true);
