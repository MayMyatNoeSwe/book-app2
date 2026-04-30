<?php
$p=new PDO('mysql:host=localhost;dbname=book_library','root','742001');
$s=$p->query("SELECT bh.id, bh.user_id, bh.subscription_id, bh.borrow_fee, bh.borrowed_at, b.title 
              FROM borrowing_history bh 
              JOIN books b ON bh.book_id = b.id 
              ORDER BY bh.user_id, bh.borrowed_at ASC");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) {
    echo "ID: {$r['id']}, User: {$r['user_id']}, Sub: " . ($r['subscription_id'] ?? 'NULL') . ", Fee: {$r['borrow_fee']}, Date: {$r['borrowed_at']}, Book: {$r['title']}\n";
}
