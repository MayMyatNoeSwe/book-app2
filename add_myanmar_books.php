<?php
require_once 'includes/env_loader.php';
require_once 'config/database.php';

try {
    // Get database configuration
    $config = require 'config/database.php';
    
    // Create PDO connection
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
    $pdo = new PDO($dsn, $config['username'], $config['password'], $config['options']);
    
    // Clear existing data
    echo "Clearing existing data...\n";
    $pdo->exec("DELETE FROM reviews");
    $pdo->exec("DELETE FROM reservations");
    $pdo->exec("DELETE FROM borrowing_history");
    $pdo->exec("DELETE FROM books");
    $pdo->exec("DELETE FROM authors");
    
    // Insert Myanmar Authors
    echo "Adding Myanmar authors...\n";
    $authors = [
        ['name' => 'မင်းလု', 'bio' => 'မြန်မာစာပေ၏ ထင်ရှားသော စာရေးဆရာတစ်ဦး။ ဝတ္ထုတိုများနှင့် ကဗျာများကို ရေးသားခဲ့သည်။'],
        ['name' => 'ဇော်ဂျီ', 'bio' => 'ခေတ်သစ်မြန်မာစာပေ၏ ရှေ့ဆောင်စာရေးဆရာ။ လူမှုရေးဝတ္ထုများကို အထူးရေးသားသည်။'],
        ['name' => 'သခင်ကျော်စွာ', 'bio' => 'မြန်မာ့ရိုးရာစာပေနှင့် ခေတ်သစ်စာပေကို ပေါင်းစပ်ရေးသားသော စာရေးဆရာ။'],
        ['name' => 'မောင်သိန်းတင်', 'bio' => 'သမိုင်းဝတ္ထုများနှင့် စွန့်စားခန်းဝတ္ထုများကို ရေးသားသော စာရေးဆရာ။'],
        ['name' => 'ဒေါ်အမာ', 'bio' => 'အမျိုးသမီးစာရေးဆရာ။ မိသားစုဘဝနှင့် လူမှုရေးဝတ္ထုများကို ရေးသားသည်။'],
        ['name' => 'ဂျာနယ်ကျော်', 'bio' => 'သတင်းစာဆရာနှင့် ဝတ္ထုရှင်။ လက်ရှိလူမှုရေးပြဿနာများကို ရေးသားသည်။'],
        ['name' => 'မိုးမခ', 'bio' => 'ကဗျာဆရာမ။ ခံစားချက်ပြင်းထန်သော ကဗျာများကို ရေးသားသည်။'],
        ['name' => 'သက်ဆွေ', 'bio' => 'ဝတ္ထုတိုများနှင့် အတ္ထုပ္ပတ္တိများကို ရေးသားသော စာရေးဆရာ။'],
        ['name' => 'နု-ဝေ', 'bio' => 'ခေတ်သစ်ဝတ္ထုရှင်။ လူငယ်များအတွက် စာပေများကို ရေးသားသည်။'],
        ['name' => 'မြသန္တာ', 'bio' => 'အမျိုးသမီးစာရေးဆရာ။ အမျိုးသမီးများ၏ ဘဝနှင့် အခွင့်အရေးများကို ရေးသားသည်။']
    ];
    
    $stmt = $pdo->prepare("INSERT INTO authors (name, bio) VALUES (?, ?)");
    foreach ($authors as $author) {
        $stmt->execute([$author['name'], $author['bio']]);
    }
    
    // Insert Myanmar Books
    echo "Adding Myanmar books...\n";
    $books = [
        // Fiction Books
        ['id' => 'book_001', 'title' => 'လူသားဖြစ်တည်မှု', 'author' => 'မင်းလု', 'year' => 2018, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://covers.openlibrary.org/b/id/8739161-L.jpg'],
        ['id' => 'book_002', 'title' => 'မြို့တော်၏ အရိပ်', 'author' => 'ဇော်ဂျီ', 'year' => 2019, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm002/400/600'],
        ['id' => 'book_003', 'title' => 'ရန်ကုန်မြို့၏ ညများ', 'author' => 'မင်းလု', 'year' => 2020, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm003/400/600'],
        ['id' => 'book_004', 'title' => 'လမ်းဆုံလမ်းခွ', 'author' => 'ဂျာနယ်ကျော်', 'year' => 2021, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm004/400/600'],
        ['id' => 'book_005', 'title' => 'အတိတ်နှင့် ပစ္စုပ္ပန်', 'author' => 'သက်ဆွေ', 'year' => 2019, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm005/400/600'],
        ['id' => 'book_006', 'title' => 'နောင်တရမှု', 'author' => 'နု-ဝေ', 'year' => 2021, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm006/400/600'],
        ['id' => 'book_007', 'title' => 'လူငယ်များ၏ အိပ်မက်', 'author' => 'နု-ဝေ', 'year' => 2022, 'category' => 'Fiction', 'copies' => 7, 'cover' => 'https://picsum.photos/seed/mm007/400/600'],
        ['id' => 'book_008', 'title' => 'ဘဝ၏ အဓိပ္ပာယ်', 'author' => 'မင်းလု', 'year' => 2017, 'category' => 'Fiction', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm008/400/600'],
        ['id' => 'book_009', 'title' => 'မုန်းတီးမှု၏ အဆုံး', 'author' => 'ဇော်ဂျီ', 'year' => 2020, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm009/400/600'],
        ['id' => 'book_010', 'title' => 'ရွာသူရွာသား', 'author' => 'သက်ဆွေ', 'year' => 2018, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm010/400/600'],
        
        // History Books
        ['id' => 'book_011', 'title' => 'ရွှေတိဂုံ', 'author' => 'သခင်ကျော်စွာ', 'year' => 2015, 'category' => 'History', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm011/400/600'],
        ['id' => 'book_012', 'title' => 'ဘုရင့်နောင်ခေတ်', 'author' => 'မောင်သိန်းတင်', 'year' => 2017, 'category' => 'History', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm012/400/600'],
        ['id' => 'book_013', 'title' => 'မြန်မာ့ယဉ်ကျေးမှု', 'author' => 'သခင်ကျော်စွာ', 'year' => 2016, 'category' => 'History', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm013/400/600'],
        ['id' => 'book_014', 'title' => 'စစ်ကိုင်းခေတ်', 'author' => 'မောင်သိန်းတင်', 'year' => 2018, 'category' => 'History', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm014/400/600'],
        ['id' => 'book_015', 'title' => 'ပုဂံမြို့ရှေး', 'author' => 'သခင်ကျော်စွာ', 'year' => 2019, 'category' => 'History', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm015/400/600'],
        ['id' => 'book_016', 'title' => 'မြန်မာ့သမိုင်း', 'author' => 'မောင်သိန်းတင်', 'year' => 2020, 'category' => 'History', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm016/400/600'],
        
        // Romance Books
        ['id' => 'book_017', 'title' => 'အမေ့ချစ်ခြင်း', 'author' => 'ဒေါ်အမာ', 'year' => 2020, 'category' => 'Romance', 'copies' => 8, 'cover' => 'https://picsum.photos/seed/mm017/400/600'],
        ['id' => 'book_018', 'title' => 'ချစ်ခြင်းမေတ္တာ', 'author' => 'ဒေါ်အမာ', 'year' => 2021, 'category' => 'Romance', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm018/400/600'],
        ['id' => 'book_019', 'title' => 'နှလုံးသား၏ အသံ', 'author' => 'ဒေါ်အမာ', 'year' => 2019, 'category' => 'Romance', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm019/400/600'],
        ['id' => 'book_020', 'title' => 'ပထမဆုံး ချစ်သူ', 'author' => 'မိုးမခ', 'year' => 2021, 'category' => 'Romance', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm020/400/600'],
        
        // Poetry Books
        ['id' => 'book_021', 'title' => 'မိုးရွာသံ', 'author' => 'မိုးမခ', 'year' => 2019, 'category' => 'Poetry', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm021/400/600'],
        ['id' => 'book_022', 'title' => 'နွေဦးရာသီ', 'author' => 'မိုးမခ', 'year' => 2020, 'category' => 'Poetry', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm022/400/600'],
        ['id' => 'book_023', 'title' => 'ညဥ့်ကဗျာ', 'author' => 'မိုးမခ', 'year' => 2018, 'category' => 'Poetry', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm023/400/600'],
        ['id' => 'book_024', 'title' => 'ခံစားချက်များ', 'author' => 'မိုးမခ', 'year' => 2022, 'category' => 'Poetry', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm024/400/600'],
        
        // Biography Books
        ['id' => 'book_025', 'title' => 'ဘဝခရီး', 'author' => 'သက်ဆွေ', 'year' => 2018, 'category' => 'Biography', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm025/400/600'],
        ['id' => 'book_026', 'title' => 'သတင်းစာဆရာ၏ ဘဝ', 'author' => 'ဂျာနယ်ကျော်', 'year' => 2020, 'category' => 'Biography', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm026/400/600'],
        ['id' => 'book_027', 'title' => 'အမျိုးသမီးခေါင်းဆောင်များ', 'author' => 'မြသန္တာ', 'year' => 2022, 'category' => 'Biography', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm027/400/600'],
        ['id' => 'book_028', 'title' => 'စာရေးဆရာ၏ ဘဝ', 'author' => 'သက်ဆွေ', 'year' => 2021, 'category' => 'Biography', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm028/400/600'],
        
        // Non-Fiction Books
        ['id' => 'book_029', 'title' => 'ပညာရေးနှင့် လူ့အဖွဲ့အစည်း', 'author' => 'ဇော်ဂျီ', 'year' => 2019, 'category' => 'Non-Fiction', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm029/400/600'],
        ['id' => 'book_030', 'title' => 'အမျိုးသမီးတို့၏ အသံ', 'author' => 'မြသန္တာ', 'year' => 2021, 'category' => 'Non-Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm030/400/600'],
        ['id' => 'book_031', 'title' => 'လူ့အခွင့်အရေး', 'author' => 'မြသန္တာ', 'year' => 2020, 'category' => 'Non-Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm031/400/600'],
        ['id' => 'book_032', 'title' => 'စီးပွားရေးဖွံ့ဖြိုးတိုးတက်မှု', 'author' => 'ဇော်ဂျီ', 'year' => 2021, 'category' => 'Non-Fiction', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm032/400/600'],
        ['id' => 'book_033', 'title' => 'နည်းပညာနှင့် လူ့ဘဝ', 'author' => 'ဂျာနယ်ကျော်', 'year' => 2022, 'category' => 'Non-Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm033/400/600'],
        
        // More Fiction
        ['id' => 'book_034', 'title' => 'သစ်တောကြီး', 'author' => 'နု-ဝေ', 'year' => 2020, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm034/400/600'],
        ['id' => 'book_035', 'title' => 'ပင်လယ်ကမ်းခြေ', 'author' => 'မင်းလု', 'year' => 2021, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm035/400/600'],
        ['id' => 'book_036', 'title' => 'တောင်ကြီးမြို့', 'author' => 'သက်ဆွေ', 'year' => 2022, 'category' => 'Fiction', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm036/400/600'],
        ['id' => 'book_037', 'title' => 'မန္တလေးညများ', 'author' => 'ဇော်ဂျီ', 'year' => 2018, 'category' => 'Fiction', 'copies' => 3, 'cover' => 'https://picsum.photos/seed/mm037/400/600'],
        ['id' => 'book_038', 'title' => 'ရေစက်ဝိုင်း', 'author' => 'နု-ဝေ', 'year' => 2019, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm038/400/600'],
        ['id' => 'book_039', 'title' => 'အိမ်ပြန်လမ်း', 'author' => 'ဒေါ်အမာ', 'year' => 2022, 'category' => 'Fiction', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm039/400/600'],
        ['id' => 'book_040', 'title' => 'မြစ်ကမ်းပါး', 'author' => 'သက်ဆွေ', 'year' => 2020, 'category' => 'Fiction', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm040/400/600'],
        
        // Mystery/Thriller
        ['id' => 'book_041', 'title' => 'လျှို့ဝှက်ချက်', 'author' => 'ဂျာနယ်ကျော်', 'year' => 2019, 'category' => 'Mystery', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm041/400/600'],
        ['id' => 'book_042', 'title' => 'မထင်မှတ်သော အဖြစ်အပျက်', 'author' => 'မောင်သိန်းတင်', 'year' => 2021, 'category' => 'Mystery', 'copies' => 4, 'cover' => 'https://picsum.photos/seed/mm042/400/600'],
        ['id' => 'book_043', 'title' => 'ညမှောင်ထဲက', 'author' => 'ဂျာနယ်ကျော်', 'year' => 2020, 'category' => 'Thriller', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm043/400/600'],
        ['id' => 'book_044', 'title' => 'အန္တရာယ်ကြီး', 'author' => 'မောင်သိန်းတင်', 'year' => 2022, 'category' => 'Thriller', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm044/400/600'],
        
        // Children's Books
        ['id' => 'book_045', 'title' => 'ကလေးများအတွက် ပုံပြင်များ', 'author' => 'နု-ဝေ', 'year' => 2020, 'category' => 'Children', 'copies' => 8, 'cover' => 'https://picsum.photos/seed/mm045/400/600'],
        ['id' => 'book_046', 'title' => 'စိတ်ကူးယဉ် ခရီးစဉ်', 'author' => 'နု-ဝေ', 'year' => 2021, 'category' => 'Children', 'copies' => 7, 'cover' => 'https://picsum.photos/seed/mm046/400/600'],
        ['id' => 'book_047', 'title' => 'သတ္တဝါများ၏ ကမ္ဘာ', 'author' => 'ဒေါ်အမာ', 'year' => 2019, 'category' => 'Children', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm047/400/600'],
        ['id' => 'book_048', 'title' => 'ကောင်းသော အကျင့်များ', 'author' => 'ဒေါ်အမာ', 'year' => 2022, 'category' => 'Children', 'copies' => 9, 'cover' => 'https://picsum.photos/seed/mm048/400/600'],
        
        // Self-Help
        ['id' => 'book_049', 'title' => 'အောင်မြင်မှု၏ လမ်းစဉ်', 'author' => 'မြသန္တာ', 'year' => 2021, 'category' => 'Self-Help', 'copies' => 5, 'cover' => 'https://picsum.photos/seed/mm049/400/600'],
        ['id' => 'book_050', 'title' => 'စိတ်ခွန်အား', 'author' => 'ဇော်ဂျီ', 'year' => 2022, 'category' => 'Self-Help', 'copies' => 6, 'cover' => 'https://picsum.photos/seed/mm050/400/600'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO books (id, title, author, year, category, total_copies, available_copies, cover_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($books as $book) {
        $stmt->execute([
            $book['id'],
            $book['title'],
            $book['author'],
            $book['year'],
            $book['category'],
            $book['copies'],
            $book['copies'],
            $book['cover']
        ]);
    }
    
    // Add some borrowing history
    echo "Adding borrowing history...\n";
    $borrowHistory = [
        ['user_id' => 2, 'book_id' => 'book_001', 'borrowed_at' => '2024-01-10 14:30:00', 'returned_at' => '2024-01-20 10:15:00', 'due_date' => '2024-01-24'],
        ['user_id' => 2, 'book_id' => 'book_003', 'borrowed_at' => '2024-01-15 09:00:00', 'returned_at' => NULL, 'due_date' => '2024-01-29'],
        ['user_id' => 3, 'book_id' => 'book_002', 'borrowed_at' => '2024-01-18 16:45:00', 'returned_at' => NULL, 'due_date' => '2024-02-01'],
        ['user_id' => 2, 'book_id' => 'book_005', 'borrowed_at' => '2024-01-05 10:00:00', 'returned_at' => '2024-01-15 14:30:00', 'due_date' => '2024-01-19'],
        ['user_id' => 3, 'book_id' => 'book_006', 'borrowed_at' => '2024-01-20 11:00:00', 'returned_at' => NULL, 'due_date' => '2024-02-03'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO borrowing_history (user_id, book_id, borrowed_at, returned_at, due_date) VALUES (?, ?, ?, ?, ?)");
    foreach ($borrowHistory as $borrow) {
        $stmt->execute([
            $borrow['user_id'],
            $borrow['book_id'],
            $borrow['borrowed_at'],
            $borrow['returned_at'],
            $borrow['due_date']
        ]);
    }
    
    // Add some reviews
    echo "Adding reviews...\n";
    $reviews = [
        ['user_id' => 2, 'book_id' => 'book_001', 'rating' => 5, 'review_text' => 'အလွန်ကောင်းမွန်သော စာအုပ်ဖြစ်ပါသည်။ စာရေးဟန်က အရမ်းကောင်းပါတယ်။'],
        ['user_id' => 3, 'book_id' => 'book_002', 'rating' => 4, 'review_text' => 'စိတ်ဝင်စားဖွယ်ကောင်းသော ဇာတ်လမ်းဖြစ်ပါသည်။'],
        ['user_id' => 2, 'book_id' => 'book_005', 'rating' => 5, 'review_text' => 'ခံစားချက်ပြင်းထန်သော အချစ်ဇာတ်လမ်း။ အလွန်ကြိုက်နှစ်သက်ပါတယ်။'],
        ['user_id' => 3, 'book_id' => 'book_007', 'rating' => 5, 'review_text' => 'လှပသော ကဗျာများ။ စိတ်ကူးစိတ်သန်းကောင်းပါတယ်။'],
    ];
    
    $stmt = $pdo->prepare("INSERT INTO reviews (user_id, book_id, rating, review_text) VALUES (?, ?, ?, ?)");
    foreach ($reviews as $review) {
        $stmt->execute([
            $review['user_id'],
            $review['book_id'],
            $review['rating'],
            $review['review_text']
        ]);
    }
    
    echo "\n✅ Successfully added Myanmar books and authors!\n";
    echo "Total Authors: " . count($authors) . "\n";
    echo "Total Books: " . count($books) . "\n";
    
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
