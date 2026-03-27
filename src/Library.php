<?php

namespace App;
//use PDO;
use App\Book;
use App\EBook;

class Library
{
    private \PDO $pdo; //add backslash here
    private array $books = [];
    public function __construct()
    {
        $config = include __DIR__ . '/../config/database.php';
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        $options = $config['options'];
        try {
            $this->pdo = new \PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
        $this->loadAllBooks();
    }
    public function getPdo(): \PDO
    {
        return $this->pdo;
    }
    public function loadAllBooks(): void
    {
        $stmt = $this->pdo->query("SELECT * FROM books ORDER BY title");
        while ($row = $stmt->fetch()) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $book = EBook::fromArray($row);
            } else {
                $book = Book::fromArray($row);
            }
            $this->books[$book->getId()] = $book;
        }
    }
    // ===================== Book Management =====================
    public function getAllBooks(): array
    {
        return $this->books;
    }
    public function getBookById(string $id): ?Book
    {
        return $this->books[$id] ?? null;
    }
    //Dependency Injection
    public function handleCoverUpload(Book $book, array $files): bool
    {
        if (isset($files['cover_image']) && $files['cover_image']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($files['cover_image']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $allowed) && $files['cover_image']['size'] <= 2_000_000) {
                //remove old cover if exists
                if ($book->getCoverImage()) {
                    $oldPath = __DIR__ . '/../public/uploads/covers/' . $book->getCoverImage();
                    if (file_exists($oldPath)) unlink($oldPath);
                }
                $coverFilename = $book->getId() . '.' . $ext;
                $uploadPath = __DIR__ . '/../public/uploads/covers/' . $coverFilename;
                if (move_uploaded_file($files['cover_image']['tmp_name'], $uploadPath)) {
                    $book->setCoverImage($coverFilename);
                    return true;
                }
            }
        }
        return false;
    }

    public function addBook(Book $book): void
    {
        $sql = "INSERT INTO books(
        id,title,author,year,cover_image,category,total_copies,available_copies,type,file_size,download_link,price,borrow_price
        )VALUES(
        :id,:title,:author,:year,:cover_image,:category,:total_copies,:available_copies,:type,:file_size,:download_link,:price,:borrow_price
        )";
        $stmt = $this->pdo->prepare($sql);
        $data = $book->toArray();
        $stmt->execute([
            ':id' => $data['id'],
            ':title' => $data['title'],
            ':author' => $data['author'],
            ':year' => $data['year'],
            ':cover_image' => $data['cover_image'],
            ':category' => $data['category'],
            ':total_copies' => $data['total_copies'],
            ':available_copies' => $data['available_copies'],
            ':type' => $data['type'] ?? 'physical',
            ':file_size' => $data['file_size'] ?? null,
            ':download_link' => $data['download_link'] ?? null,
            ':price' => $data['price'] ?? 15000,
            ':borrow_price' => $data['borrow_price'] ?? 5000,
        ]);
        // getId() => it is used from Book.php
        // Store in books array with ID as key
        $this->books[$book->getId()] = $book;
    }
    public function updateBook(Book $book): void
    {
        $data = $book->toArray();
        $stmt = $this->pdo->prepare("UPDATE books SET 
            title = :title,
            author = :author,
            year = :year,
            cover_image = :cover_image,
            category = :category,
            total_copies = :total_copies,
            available_copies = :available_copies,
            type = :type,
            file_size = :file_size,
            download_link = :download_link,
            price = :price,
            borrow_price = :borrow_price
            WHERE id = :id");
        $stmt->execute([
            ':id'               => $data['id'],
            ':title'            => $data['title'],
            ':author'           => $data['author'],
            ':year'             => $data['year'],
            ':cover_image'      => $data['cover_image'],
            ':category'         => $data['category'],
            ':total_copies'     => $data['total_copies'],
            ':available_copies' => $data['available_copies'],
            ':type'             => $data['type'] ?? 'physical',
            ':file_size'        => $data['file_size'] ?? null,
            ':download_link'    => $data['download_link'] ?? null,
            ':price'            => $data['price'] ?? 15000,
            ':borrow_price'     => $data['borrow_price'] ?? 5000,
        ]);
        $this->books[$book->getId()] = $book;
    }

    public function deleteBook(string $id): bool
    {
        if (!isset($this->books[$id])) return false;

        $stmt = $this->pdo->prepare("DELETE FROM books WHERE id = :id");
        $stmt->execute([':id' => $id]);
        unset($this->books[$id]);
        return true;
    }
    // ====================== Borrowing & Due Dates =====================
    // ================ business logic ================
    /**
     * Get membership rules for a given user
     */
    private function getMembershipRules(int $userId): array
    {
        $stmt = $this->pdo->prepare("SELECT membership_tier FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $tier = $stmt->fetchColumn() ?: 'bronze';

        $rules = [
            'bronze'   => ['limit' => 3, 'days' => 14],
            'silver'   => ['limit' => 3, 'days' => 14],
            'gold'     => ['limit' => 5, 'days' => 14],
            'platinum' => ['limit' => 100, 'days' => 30]
        ];

        return $rules[strtolower($tier)] ?? $rules['bronze'];
    }

    public function borrowBook(string $bookId, int $userId): bool
    {
        $rules = $this->getMembershipRules($userId);
        
        // Check unreturned books against tier limit
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND returned_at IS NULL AND `status` IN ('pending','approved')");
        $stmt->execute([$userId]);
        $unreturnedBooks = (int)$stmt->fetchColumn();
        
        if ($unreturnedBooks >= $rules['limit']) {
            return false;
        }

        // Prevent borrowing the exact same book multiple times
        if ($this->isCurrentlyBorrowing($userId, $bookId)) {
            return false;
        }

        // Check if there's already a pending request for this book
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND book_id = ? AND `status` = 'pending'");
        $stmt->execute([$userId, $bookId]);
        if ((int)$stmt->fetchColumn() > 0) {
            return false;
        }

        $book = $this->getBookById($bookId);
        if (!$book || !$book->isAvailable()) return false;

        $dueDate = date('Y-m-d', strtotime('+' . $rules['days'] . ' days'));
        $stmt = $this->pdo->prepare("INSERT INTO borrowing_history(user_id, book_id, due_date, `status`) VALUES (?,?,?,'pending')");
        $stmt->execute([$userId, $bookId, $dueDate]);
        return true;
    }

    // Admin approves a borrow request
    public function approveBorrow(int $borrowId): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM borrowing_history WHERE id = ? AND `status` = 'pending'");
        $stmt->execute([$borrowId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) return false;

        $rules = $this->getMembershipRules($record['user_id']);
        $book = $this->getBookById($record['book_id']);
        if (!$book || !$book->borrowCopy()) return false;

        $dueDate = date('Y-m-d', strtotime('+' . $rules['days'] . ' days'));
        $stmt = $this->pdo->prepare("UPDATE borrowing_history SET status = 'approved', due_date = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$dueDate, $borrowId]);
        $this->updateBook($book);

        // Record transaction
        $this->addTransaction('income', 'borrow_fee', $book->getBorrowPrice(), "Borrow: {$book->getTitle()} by user #{$record['user_id']}", $borrowId, 'borrowing_history', $record['user_id']);

        return true;
    }

    // Admin rejects a borrow request
    public function rejectBorrow(int $borrowId, string $reason = ''): bool
    {
        $stmt = $this->pdo->prepare("UPDATE borrowing_history SET `status` = 'rejected', admin_notes = ? WHERE id = ? AND `status` = 'pending'");
        $stmt->execute([$reason, $borrowId]);
        return $stmt->rowCount() > 0;
    }

    // Admin approves a return request
    public function approveReturn(int $borrowId): bool
    {
        $stmt = $this->pdo->prepare("SELECT * FROM borrowing_history WHERE id = ? AND `status` = 'return_pending'");
        $stmt->execute([$borrowId]);
        $record = $stmt->fetch(\PDO::FETCH_ASSOC);
        if (!$record) return false;

        $book = $this->getBookById($record['book_id']);
        if (!$book) return false;

        $penalty = $this->calculatePenalty($record['due_date']);
        $sql = "UPDATE borrowing_history 
                SET `status` = 'returned', 
                    returned_at = NOW(), 
                    penalty_fee = ?,
                    penalty_paid = CASE WHEN return_screenshot IS NOT NULL THEN 1 ELSE penalty_paid END
                WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$penalty, $borrowId]);

        $book->returnCopy();
        $this->updateBook($book);

        // Record penalty if paid
        if ($penalty > 0 && ($record['return_screenshot'] ?? null)) {
            $this->addTransaction('income', 'penalty_fee', $penalty, "Overdue penalty for borrow #{$borrowId}", $borrowId, 'borrowing_history', $record['user_id']);
        }

        return true;
    }

    // Calculate penalty fee (500 Ks per day overdue)
    public function calculatePenalty(string $dueDate): int
    {
        $due = strtotime($dueDate);
        $now = time();
        if ($due >= $now) return 0;
        $overdueDays = (int)floor(($now - $due) / 86400);
        return $overdueDays * 500;
    }

    // Mark penalty as paid
    public function markPenaltyPaid(int $borrowId): bool
    {
        $stmt = $this->pdo->prepare("UPDATE borrowing_history SET penalty_paid = 1 WHERE id = ?");
        $stmt->execute([$borrowId]);
        return $stmt->rowCount() > 0;
    }

    // Get borrow requests for admin
    public function getBorrowRequests(string $status = 'all', int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT bh.*, u.username, u.email, b.title, b.author, b.cover_image, b.category, b.borrow_price
                FROM borrowing_history bh
                JOIN users u ON bh.user_id = u.id
                JOIN books b ON bh.book_id = b.id";
        $params = [];
        
        if ($status !== 'all') {
            $sql .= " WHERE bh.`status` = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY bh.borrowed_at DESC LIMIT ? OFFSET ?";
        
        // PDO needs limit/offset as ints to work in some modes with execute
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(count($params) + 1, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(count($params) + 2, (int)$offset, \PDO::PARAM_INT);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k + 1, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get borrow records for a specific user, filtered by status
     */
    public function getUserBorrows(int $userId, string $type = 'active'): array
    {
        $sql = "SELECT bh.*, b.title, b.author, b.cover_image, b.category, b.borrow_price
                FROM borrowing_history bh
                JOIN books b ON bh.book_id = b.id
                WHERE bh.user_id = ?";
        
        $params = [$userId];
        
        if ($type === 'active') {
            $sql .= " AND bh.returned_at IS NULL AND bh.status IN ('approved', 'return_pending')";
        } elseif ($type === 'pending') {
            $sql .= " AND bh.status = 'pending'";
        } elseif ($type === 'past') {
            $sql .= " AND bh.returned_at IS NOT NULL AND bh.status = 'returned'";
        } elseif ($type === 'rejected') {
            $sql .= " AND bh.status = 'rejected'";
        }
        
        $sql .= " ORDER BY bh.borrowed_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    // Count borrow requests by status
    public function countBorrowRequests(string $status = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM borrowing_history";
        $params = [];
        if ($status !== 'all') {
            $sql .= " WHERE `status` = ?";
            $params[] = $status;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ====================== Membership Management =====================
    public function countMembershipRequests(string $status = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM membership_requests";
        $params = [];
        if ($status !== 'all') {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getMembershipRequests(string $status = 'all', int $limit = 50): array
    {
        $sql = "SELECT mr.*, u.username, u.email, u.membership_tier as current_tier 
                FROM membership_requests mr
                JOIN users u ON mr.user_id = u.id";
        $params = [];
        if ($status !== 'all') {
            $sql .= " WHERE mr.status = ?";
            $params[] = $status;
        }
        $sql .= " ORDER BY mr.created_at DESC LIMIT ?";
        $params = array_merge($params, [(int)$limit]);
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function approveMembershipUpgrade(int $requestId): bool
    {
        try {
            if (!$this->pdo->inTransaction()) {
                $this->pdo->beginTransaction();
            }

            $stmt = $this->pdo->prepare("SELECT * FROM membership_requests WHERE id = ? AND status = 'pending'");
            $stmt->execute([$requestId]);
            $request = $stmt->fetch();

            if (!$request) return false;

            // Update user tier and set expiry to 1 month from now
            $stmt = $this->pdo->prepare("UPDATE users SET membership_tier = ?, membership_expires_at = DATE_ADD(NOW(), INTERVAL 1 MONTH) WHERE id = ?");
            $stmt->execute([$request['tier'], $request['user_id']]);

            // Mark request as approved
            $stmt = $this->pdo->prepare("UPDATE membership_requests SET status = 'approved' WHERE id = ?");
            $stmt->execute([$requestId]);

            // Record transaction
            $prices = ['silver' => 10000, 'gold' => 25000, 'platinum' => 50000];
            $amount = $prices[$request['tier']] ?? 0;
            if ($amount > 0) {
                $this->addTransaction('income', 'membership_fee', $amount, "Approved {$request['tier']} membership for user #" . $request['user_id'], $requestId, 'membership_requests', $request['user_id']);
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return false;
        }
    }

    public function rejectMembershipRequest(int $requestId, string $reason = ''): bool
    {
        $stmt = $this->pdo->prepare("UPDATE membership_requests SET status = 'rejected', admin_note = ? WHERE id = ?");
        return $stmt->execute([$reason, $requestId]);
    }

    // Check if user has a pending borrow for a book
    public function hasPendingBorrow(int $userId, string $bookId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM borrowing_history WHERE user_id = ? AND book_id = ? AND `status` = 'pending'");
        $stmt->execute([$userId, $bookId]);
        return (int)$stmt->fetchColumn() > 0;
    }
    public function addReview(int $userId, string $bookId, int $rating, ?string $text): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO reviews 
            (user_id, book_id, rating, review_text) 
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE rating = ?, review_text = ?");
        $stmt->execute([$userId, $bookId, $rating, $text, $rating, $text]);
    }

    public function updateReview(int $reviewId, int $userId, int $rating, ?string $text): bool
    {
        $stmt = $this->pdo->prepare("UPDATE reviews 
            SET rating = ?, review_text = ? 
            WHERE id = ? AND user_id = ?");
        return $stmt->execute([$rating, $text, $reviewId, $userId]);
    }

    public function getReviews(string $bookId): array
    {
        $stmt = $this->pdo->prepare("SELECT r.id, u.username, r.user_id, r.rating, r.review_text, r.created_at 
            FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.book_id = ? ORDER BY r.created_at DESC");
        $stmt->execute([$bookId]);
        return $stmt->fetchAll();
    }

    // ===================== Pagination Support =====================
    public function getBooksPaginated(int $limit, int $offset, ?string $category = null, ?string $search = null): array
    {
        $sql = "SELECT b.*, COALESCE(AVG(r.rating), 0) as average_rating 
                FROM books b
                LEFT JOIN reviews r ON b.id = r.book_id
                WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $sql .= " AND (title LIKE ? OR author LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }
        $sql .= " GROUP BY b.id ";
        $sql .= " ORDER BY title LIMIT ? OFFSET ?";

        $stmt = $this->pdo->prepare($sql);
        
        // Bind parameters manually to ensure LIMIT and OFFSET are treated as integers
        $i = 1;
        foreach ($params as $param) {
            $stmt->bindValue($i++, $param);
        }
        $stmt->bindValue($i++, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue($i++, (int)$offset, \PDO::PARAM_INT);
        
        $stmt->execute();

        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    public function countBooks(?string $category = null, ?string $search = null): int
    {
        $sql = "SELECT COUNT(*) FROM books WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $sql .= " AND (title LIKE ? OR author LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function reserveBook(int $userId, string $bookId): bool
    {
        $book = $this->getBookById($bookId);
        if ($book && !$book->isAvailable()) {
            $stmt = $this->pdo->prepare("INSERT INTO reservations
                (user_id, book_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $bookId]);
        }
        return false;
    }

    public function isCurrentlyBorrowing(int $userId, string $bookId): bool
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM borrowing_history 
            WHERE user_id = ? AND book_id = ? AND returned_at IS NULL AND `status` IN ('approved','return_pending','pending')");
        $stmt->execute([$userId, $bookId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    // User requests to return a book (goes to admin for approval)
    // User requests to return a book (goes to admin for approval)
    public function returnBook(string $bookId, int $userId, array $paymentData = []): bool
    {
        $method = $paymentData['method'] ?? null;
        $screenshot = $paymentData['screenshot'] ?? null;

        // Set status to return_pending (admin must approve)
        $sql = "UPDATE borrowing_history 
                SET `status` = 'return_pending', 
                    return_payment_method = ?, 
                    return_screenshot = ?
                WHERE user_id = ? AND book_id = ? AND returned_at IS NULL AND `status` IN ('approved','pending','return_pending')";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$method, $screenshot, $userId, $bookId]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Alias for returnBook to maintain compatibility with older borrow.php versions
     */
    public function requestReturn(int $userId, string $bookId, string $method, string $screenshotPath): bool
    {
        return $this->returnBook($bookId, $userId, [
            'method' => $method,
            'screenshot' => $screenshotPath
        ]);
    }

    // ======================== Notifications ========================
    private function sendNotification(string $email, string $bookTitle, string $action, ?string $extra = null): void
    {
        $subject = "Library Notification: Book {$action}";
        $message = "Dear user,\n\nYou have {$action} the book: \"{$bookTitle}\"\n";
        if ($extra) $message .= "Due Date: {$extra}\n";
        $message .= "\nThank you for using our library system.\n";
        //$message .= "Due Date: {$extra}\n";
        $headers = "From: no-reply@library.com";
        @mail($email, $subject, $message);
    }

    // ======================== Home Page Features ========================
    
    /**
     * Get best selling books based on borrowing frequency
     */
    public function getBestSellingBooks(int $limit = 6): array
    {
        $sql = "SELECT b.*, 
                       COUNT(bh.id) as borrow_count,
                       COALESCE(AVG(r.rating), 0) as average_rating
                FROM books b 
                LEFT JOIN borrowing_history bh ON b.id = bh.book_id 
                LEFT JOIN reviews r ON b.id = r.book_id
                GROUP BY b.id 
                ORDER BY borrow_count DESC, b.title ASC 
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, (int)$limit, \PDO::PARAM_INT);
        $stmt->execute();
        
        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    /**
     * Get top authors based on book popularity and reviews
     */
    public function getTopAuthors(int $limit = 6): array
    {
        // Check if authors table exists
        $tablesStmt = $this->pdo->query("SHOW TABLES LIKE 'authors'");
        $authorsTableExists = $tablesStmt->rowCount() > 0;
        
        if ($authorsTableExists) {
            // Query with authors table join
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        a.photo as author_photo,
                        a.bio as author_bio
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id
                    LEFT JOIN authors a ON b.author = a.name
                    GROUP BY b.author
                    HAVING book_count > 0
                    ORDER BY total_borrows DESC, avg_rating DESC, book_count DESC
                    LIMIT ?";
        } else {
            // Query without authors table (fallback)
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        NULL as author_photo,
                        NULL as author_bio
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id
                    GROUP BY b.author
                    HAVING book_count > 0
                    ORDER BY total_borrows DESC, avg_rating DESC, book_count DESC
                    LIMIT ?";
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recent book reviews with user and book details
     */
    public function getRecentReviews(int $limit = 6): array
    {
        $sql = "SELECT 
                    r.rating,
                    r.review_text,
                    r.created_at,
                    u.username,
                    b.title,
                    b.author,
                    b.id as book_id,
                    b.cover_image
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                JOIN books b ON r.book_id = b.id
                WHERE r.review_text IS NOT NULL AND r.review_text != ''
                ORDER BY r.created_at DESC
                LIMIT ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Get recently published books (based on year and creation date)
     */
    public function getRecentlyPublishedBooks(int $limit = 6, int $offset = 0): array
    {
        $sql = "SELECT b.*, COALESCE(AVG(r.rating), 0) as average_rating 
                FROM books b
                LEFT JOIN reviews r ON b.id = r.book_id
                GROUP BY b.id
                ORDER BY year DESC, created_at DESC 
                LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(1, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue(2, (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        
        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    /**
     * Count recently published books
     */
    public function countRecentlyPublishedBooks(): int
    {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM books");
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get book statistics for dashboard
     */
    public function getBookStats(): array
    {
        $stats = [];
        
        // Total books
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM books");
        $stats['total_books'] = $stmt->fetchColumn();
        
        // Total authors
        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT author) FROM books");
        $stats['total_authors'] = $stmt->fetchColumn();
        
        // Total reviews
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM reviews");
        $stats['total_reviews'] = $stmt->fetchColumn();
        
        // Books borrowed this month
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM borrowing_history WHERE borrowed_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
        $stats['monthly_borrows'] = $stmt->fetchColumn();
        
        return $stats;
    }

    /**
     * Advanced books pagination with sorting and availability filtering
     */
    public function getAdvancedBooksPaginated(int $limit, int $offset, ?string $category = null, ?string $search = null, string $sortBy = 'title', string $sortOrder = 'asc', string $availability = 'all'): array
    {
        $sql = "SELECT b.*, COALESCE(AVG(r.rating), 0) as average_rating 
                FROM books b
                LEFT JOIN reviews r ON b.id = r.book_id
                WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $sql .= " AND (title LIKE ? OR author LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }

        // Availability filter
        if ($availability === 'available') {
            $sql .= " AND available_copies > 0";
        } elseif ($availability === 'borrowed') {
            $sql .= " AND available_copies = 0";
        }

        $sql .= " GROUP BY b.id ";

        // Sorting
        $validSorts = ['title', 'author', 'year', 'category', 'created_at', 'borrowed', 'recent'];
        if (in_array($sortBy, $validSorts)) {
            if ($sortBy === 'borrowed') {
                // Sort by borrowing frequency (most borrowed first)
                $sql .= " ORDER BY (total_copies - available_copies) " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
            } elseif ($sortBy === 'recent') {
                // Sort by recently added/published (combination of year and created_at)
                $sql .= " ORDER BY year " . ($sortOrder === 'desc' ? 'DESC' : 'ASC') . ", created_at " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
            } else {
                $sql .= " ORDER BY " . $sortBy . " " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
            }
        } else {
            $sql .= " ORDER BY title ASC";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    /**
     * Count books with advanced filtering
     */
    public function countAdvancedBooks(?string $category = null, ?string $search = null, string $availability = 'all'): int
    {
        $sql = "SELECT COUNT(*) FROM books WHERE 1=1";
        $params = [];

        if ($category) {
            $sql .= " AND category = ?";
            $params[] = $category;
        }

        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $sql .= " AND (title LIKE ? OR author LIKE ?)";
            $params[] = "%$searchTerm%";
            $params[] = "%$searchTerm%";
        }

        // Availability filter
        if ($availability === 'available') {
            $sql .= " AND available_copies > 0";
        } elseif ($availability === 'borrowed') {
            $sql .= " AND available_copies = 0";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    // ======================== Author Management ========================

    /**
     * Get authors with advanced pagination and filtering
     */
    public function getAdvancedAuthorsPaginated(int $limit, int $offset, ?string $search = null, string $sortBy = 'name', string $sortOrder = 'asc', int $minBooks = 1): array
    {
        // Check if authors table exists
        $tablesStmt = $this->pdo->query("SHOW TABLES LIKE 'authors'");
        $authorsTableExists = $tablesStmt->rowCount() > 0;
        
        if ($authorsTableExists) {
            // Query with authors table join
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        a.photo as author_photo,
                        a.bio as author_bio,
                        MAX(b.year) as latest_book_year,
                        (SELECT title FROM books WHERE author = b.author ORDER BY year DESC, created_at DESC LIMIT 1) as latest_book_title
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id
                    LEFT JOIN authors a ON b.author = a.name";
        } else {
            // Query without authors table (fallback)
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        NULL as author_photo,
                        NULL as author_bio,
                        MAX(b.year) as latest_book_year,
                        (SELECT title FROM books WHERE author = b.author ORDER BY year DESC, created_at DESC LIMIT 1) as latest_book_title
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id";
        }

        $params = [];
        $sql .= " WHERE 1=1";

        // Search filter
        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $sql .= " AND b.author LIKE ?";
            $params[] = "%$searchTerm%";
        }

        $sql .= " GROUP BY b.author";

        // Minimum books filter
        if ($minBooks > 1) {
            $sql .= " HAVING book_count >= ?";
            $params[] = $minBooks;
        } else {
            $sql .= " HAVING book_count > 0";
        }

        // Sorting
        $validSorts = ['name', 'book_count', 'avg_rating', 'total_borrows', 'latest_book'];
        if (in_array($sortBy, $validSorts)) {
            if ($sortBy === 'name') {
                $sql .= " ORDER BY b.author " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
            } elseif ($sortBy === 'latest_book') {
                $sql .= " ORDER BY latest_book_year " . ($sortOrder === 'desc' ? 'DESC' : 'ASC') . ", latest_book_title ASC";
            } else {
                $sql .= " ORDER BY " . $sortBy . " " . ($sortOrder === 'desc' ? 'DESC' : 'ASC');
            }
        } else {
            $sql .= " ORDER BY b.author ASC";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Count authors with advanced filtering
     */
    public function countAdvancedAuthors(?string $search = null, int $minBooks = 1): int
    {
        $sql = "SELECT COUNT(DISTINCT b.author) as author_count
                FROM books b";

        $params = [];
        $whereConditions = [];

        // Search filter
        if ($search && trim($search) !== '') {
            $searchTerm = trim($search);
            $whereConditions[] = "b.author LIKE ?";
            $params[] = "%$searchTerm%";
        }

        if (!empty($whereConditions)) {
            $sql .= " WHERE " . implode(" AND ", $whereConditions);
        }

        // If minimum books filter is applied, we need to use a subquery
        if ($minBooks > 1) {
            $sql = "SELECT COUNT(*) FROM (
                        SELECT b.author, COUNT(DISTINCT b.id) as book_count
                        FROM books b";
            
            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }
            
            $sql .= " GROUP BY b.author
                      HAVING book_count >= ?
                    ) as author_counts";
            $params[] = $minBooks;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Get author statistics for dashboard
     */
    public function getAuthorStats(): array
    {
        $stats = [];
        
        // Total authors
        $stmt = $this->pdo->query("SELECT COUNT(DISTINCT author) FROM books");
        $stats['total_authors'] = $stmt->fetchColumn();
        
        // Total books by all authors
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM books");
        $stats['total_books'] = $stmt->fetchColumn();
        
        // Average rating across all authors
        $stmt = $this->pdo->query("SELECT COALESCE(AVG(rating), 0) FROM reviews");
        $stats['avg_rating'] = $stmt->fetchColumn();
        
        // Most prolific author
        $stmt = $this->pdo->query("SELECT author, COUNT(*) as book_count FROM books GROUP BY author ORDER BY book_count DESC LIMIT 1");
        $mostProlific = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stats['most_prolific_author'] = $mostProlific ? $mostProlific['author'] : 'N/A';
        $stats['most_prolific_count'] = $mostProlific ? $mostProlific['book_count'] : 0;
        
        return $stats;
    }

    /**
     * Get detailed author information by name
     */
    public function getAuthorDetails(string $authorName): ?array
    {
        // Check if authors table exists
        $tablesStmt = $this->pdo->query("SHOW TABLES LIKE 'authors'");
        $authorsTableExists = $tablesStmt->rowCount() > 0;
        
        if ($authorsTableExists) {
            // Query with authors table join
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        a.photo as author_photo,
                        a.bio as author_bio,
                        MIN(b.year) as first_book_year,
                        MAX(b.year) as latest_book_year,
                        (SELECT title FROM books WHERE author = ? ORDER BY year ASC, created_at ASC LIMIT 1) as first_book_title,
                        (SELECT title FROM books WHERE author = ? ORDER BY year DESC, created_at DESC LIMIT 1) as latest_book_title,
                        GROUP_CONCAT(DISTINCT b.category ORDER BY b.category) as categories
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id
                    LEFT JOIN authors a ON b.author = a.name
                    WHERE b.author = ?
                    GROUP BY b.author, a.photo, a.bio";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$authorName, $authorName, $authorName]);
        } else {
            // Query without authors table (fallback)
            $sql = "SELECT 
                        b.author,
                        COUNT(DISTINCT b.id) as book_count,
                        COUNT(bh.id) as total_borrows,
                        COALESCE(AVG(r.rating), 0) as avg_rating,
                        COUNT(r.id) as review_count,
                        NULL as author_photo,
                        NULL as author_bio,
                        MIN(b.year) as first_book_year,
                        MAX(b.year) as latest_book_year,
                        (SELECT title FROM books WHERE author = ? ORDER BY year ASC, created_at ASC LIMIT 1) as first_book_title,
                        (SELECT title FROM books WHERE author = ? ORDER BY year DESC, created_at DESC LIMIT 1) as latest_book_title,
                        GROUP_CONCAT(DISTINCT b.category ORDER BY b.category) as categories
                    FROM books b
                    LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                    LEFT JOIN reviews r ON b.id = r.book_id
                    WHERE b.author = ?
                    GROUP BY b.author";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$authorName, $authorName, $authorName]);
        }
        
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if (!$result || $result['book_count'] == 0) {
            return null;
        }
        
        // Convert categories string to array
        if ($result['categories']) {
            $result['categories_array'] = explode(',', $result['categories']);
        } else {
            $result['categories_array'] = [];
        }
        
        return $result;
    }

    /**
     * Get all books by a specific author
     */
    public function getBooksByAuthor(string $authorName, int $limit = 50, int $offset = 0, string $sortBy = 'year', string $sortOrder = 'desc'): array
    {
        $sql = "SELECT b.*, COALESCE(AVG(r.rating), 0) as average_rating 
                FROM books b
                LEFT JOIN reviews r ON b.id = r.book_id
                WHERE author = ?";
        $params = [$authorName];

        $sql .= " GROUP BY b.id ";

        // Sorting
        $validSorts = ['title', 'year', 'category', 'created_at'];
        if (in_array($sortBy, $validSorts)) {
            $sql .= " ORDER BY " . $sortBy . " " . ($sortOrder === 'asc' ? 'ASC' : 'DESC');
        } else {
            $sql .= " ORDER BY year DESC";
        }

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    /**
     * Get author's most popular books (by borrows and ratings)
     */
    public function getAuthorPopularBooks(string $authorName, int $limit = 6): array
    {
        $sql = "SELECT b.*, 
                       COUNT(bh.id) as borrow_count,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count
                FROM books b
                LEFT JOIN borrowing_history bh ON b.id = bh.book_id
                LEFT JOIN reviews r ON b.id = r.book_id
                WHERE b.author = ?
                GROUP BY b.id
                ORDER BY borrow_count DESC, avg_rating DESC, review_count DESC
                LIMIT ?";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$authorName, $limit]);

        $books = [];
        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            if (isset($row['type']) && $row['type'] === 'ebook') {
                $books[] = EBook::fromArray($row);
            } else {
                $books[] = Book::fromArray($row);
            }
        }
        return $books;
    }

    // ======================== Accounting Management ========================

    public function addTransaction(string $type, string $category, float $amount, ?string $description, ?string $refId = null, ?string $refTable = null, ?int $userId = null): bool
    {
        $sql = "INSERT INTO transactions (type, category, amount, description, reference_id, reference_table, user_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$type, $category, $amount, $description, $refId, $refTable, $userId]);
    }

    public function addExpense(string $title, float $amount, string $category, ?string $description, string $date): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("INSERT INTO expenses (title, amount, category, description, date) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$title, $amount, $category, $description, $date]);
            $expenseId = $this->pdo->lastInsertId();

            $this->addTransaction('expense', 'expense', $amount, "Expense: {$title} ({$category})", $expenseId, 'expenses');
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function deleteExpense(int $id): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("DELETE FROM transactions WHERE reference_id = ? AND reference_table = 'expenses'");
            $stmt->execute([$id]);
            
            $stmt = $this->pdo->prepare("DELETE FROM expenses WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }

    public function getExpenses(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = "SELECT * FROM expenses WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND date <= ?";
            $params[] = $filters['end_date'];
        }

        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest': $sql .= " ORDER BY date ASC, created_at ASC"; break;
            case 'amount_high': $sql .= " ORDER BY amount DESC"; break;
            case 'amount_low': $sql .= " ORDER BY amount ASC"; break;
            default: $sql .= " ORDER BY date DESC, created_at DESC"; break;
        }

        $sql .= " LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        foreach ($params as $p) $stmt->bindValue($i++, $p);
        $stmt->bindValue($i++, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue($i++, (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countExpenses(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM expenses WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (title LIKE ? OR description LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['category'])) {
            $sql .= " AND category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND date >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND date <= ?";
            $params[] = $filters['end_date'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getTransactions(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT t.*, u.username FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (t.description LIKE ? OR u.username LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['category'])) {
            $sql .= " AND t.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $sort = $filters['sort'] ?? 'newest';
        switch ($sort) {
            case 'oldest': $sql .= " ORDER BY t.created_at ASC"; break;
            case 'amount_high': $sql .= " ORDER BY t.amount DESC"; break;
            case 'amount_low': $sql .= " ORDER BY t.amount ASC"; break;
            default: $sql .= " ORDER BY t.created_at DESC"; break;
        }

        $sql .= " LIMIT ? OFFSET ?";
        
        $stmt = $this->pdo->prepare($sql);
        $i = 1;
        foreach ($params as $p) $stmt->bindValue($i++, $p);
        $stmt->bindValue($i++, (int)$limit, \PDO::PARAM_INT);
        $stmt->bindValue($i++, (int)$offset, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function countTransactions(array $filters = []): int
    {
        $sql = "SELECT COUNT(*) FROM transactions t LEFT JOIN users u ON t.user_id = u.id WHERE 1=1";
        $params = [];

        if (!empty($filters['search'])) {
            $sql .= " AND (t.description LIKE ? OR u.username LIKE ?)";
            $params[] = "%{$filters['search']}%";
            $params[] = "%{$filters['search']}%";
        }

        if (!empty($filters['category'])) {
            $sql .= " AND t.category = ?";
            $params[] = $filters['category'];
        }

        if (!empty($filters['type'])) {
            $sql .= " AND t.type = ?";
            $params[] = $filters['type'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= " AND DATE(t.created_at) >= ?";
            $params[] = $filters['start_date'];
        }

        if (!empty($filters['end_date'])) {
            $sql .= " AND DATE(t.created_at) <= ?";
            $params[] = $filters['end_date'];
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getAccountingStats(): array
    {
        $stats = [];
        // Income by category
        $stmt = $this->pdo->query("SELECT category, SUM(amount) as total FROM transactions WHERE type = 'income' GROUP BY category");
        $stats['income_by_category'] = $stmt->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Core stats
        $stats['total_income'] = (float)$this->pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'income'")->fetchColumn();
        $stats['total_expense'] = (float)$this->pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'expense'")->fetchColumn();
        $stats['total_profit'] = $stats['total_income'] - $stats['total_expense'];

        // Detailed Stats - Sales
        $stats['sales_detailed'] = $this->getSalesStats();

        // Detailed Stats - Borrows
        $stats['borrow_detailed'] = $this->getBorrowStats();

        // Detailed Stats - Memberships
        $stats['membership_detailed'] = $this->getMembershipStats();

        // Last 12 months income/expense for charts
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
                       SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END) as income,
                       SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END) as expense
                FROM transactions 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY month ORDER BY month ASC";
        $stats['monthly_history'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $stats;
    }

    private function getSalesStats(): array
    {
        $stats = [];
        // Sales over last 7 days
        $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as date, SUM(amount) as total 
                FROM transactions WHERE category = 'sale' AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) 
                GROUP BY date ORDER BY date ASC";
        $stats['last_7_days'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Top 5 books by sales revenue (joining transactions with order_items)
        $sql = "SELECT b.title, SUM(oi.price * oi.quantity) as total 
                FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.status = 'completed'
                GROUP BY b.id ORDER BY total DESC LIMIT 5";
        $stats['top_selling_books'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        // Average order value
        $stats['avg_order_value'] = (float)$this->pdo->query("SELECT COALESCE(AVG(total_amount), 0) FROM orders WHERE status = 'completed'")->fetchColumn();
        
        return $stats;
    }

    private function getBorrowStats(): array
    {
        $stats = [];
        // Borrow fees vs penalties
        $sql = "SELECT category, SUM(amount) as total FROM transactions WHERE category IN ('borrow_fee', 'penalty_fee') GROUP BY category";
        $stats['fee_distribution'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_KEY_PAIR);

        // Monthly borrow count
        $sql = "SELECT DATE_FORMAT(borrowed_at, '%Y-%m') as month, COUNT(*) as count 
                FROM borrowing_history 
                WHERE borrowed_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                GROUP BY month ORDER BY month ASC";
        $stats['borrow_count_trend'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $stats;
    }

    private function getMembershipStats(): array
    {
        $stats = [];
        // Income by tier
        $sql = "SELECT tier, COUNT(*) as count, 
                SUM(CASE 
                    WHEN tier = 'silver' THEN 10000
                    WHEN tier = 'gold' THEN 25000
                    WHEN tier = 'platinum' THEN 50000
                    ELSE 0
                END) as revenue
                FROM membership_requests WHERE status = 'approved' GROUP BY tier";
        $stats['tier_distribution'] = $this->pdo->query($sql)->fetchAll(\PDO::FETCH_ASSOC);

        return $stats;
    }

    public function updateOrderStatus(int $orderId, string $status): bool
    {
        try {
            $this->pdo->beginTransaction();
            $stmt = $this->pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
            if (!$stmt->execute([$status, $orderId])) {
                throw new \Exception("Failed to update status");
            }

            if ($status === 'completed') {
                $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ?");
                $stmt->execute([$orderId]);
                $order = $stmt->fetch();
                if ($order) {
                    $this->addTransaction('income', 'sale', $order['total_amount'], "Order #{$order['order_number']} completed", $orderId, 'orders', $order['user_id']);
                }
            }

            $this->pdo->commit();
            return true;
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            return false;
        }
    }
}
