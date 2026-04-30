<?php
require_once 'includes/functions.php';
require_once 'includes/env_loader.php';

$p=new PDO('mysql:host=localhost;dbname=book_library','root','742001');

$freeLimit = (int)getSetting('borrow_limit', 3);

// Get all users who have borrowed books without subscription
$stmt = $p->query("SELECT DISTINCT user_id FROM borrowing_history WHERE subscription_id IS NULL");
$users = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($users as $userId) {
    // Get all borrows for this user without sub, ordered by time
    $stmt = $p->prepare("SELECT bh.id, b.borrow_price 
                         FROM borrowing_history bh 
                         JOIN books b ON bh.book_id = b.id 
                         WHERE bh.user_id = ? AND bh.subscription_id IS NULL 
                         ORDER BY bh.borrowed_at ASC");
    $stmt->execute([$userId]);
    $borrows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $count = 0;
    foreach ($borrows as $b) {
        $fee = ($count < $freeLimit) ? 0 : $b['borrow_price'];
        
        $upd = $p->prepare("UPDATE borrowing_history SET borrow_fee = ? WHERE id = ?");
        $upd->execute([$fee, $b['id']]);
        
        echo "User $userId: Borrow ID {$b['id']} set fee to $fee\n";
        $count++;
    }
}

echo "Data fix complete.\n";
