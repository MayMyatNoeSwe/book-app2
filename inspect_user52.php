<?php
$p=new PDO('mysql:host=localhost;dbname=book_library','root','742001');
$s=$p->query("SELECT bh.*, b.title 
              FROM borrowing_history bh 
              JOIN books b ON bh.book_id = b.id 
              WHERE user_id = 52
              ORDER BY bh.borrowed_at ASC");
$rows = $s->fetchAll(PDO::FETCH_ASSOC);
foreach($rows as $r) {
    echo "ID: {$r['id']}, Title: {$r['title']}, Fee: {$r['borrow_fee']}, Ret: " . ($r['returned_at'] ?? 'NULL') . "\n";
}
