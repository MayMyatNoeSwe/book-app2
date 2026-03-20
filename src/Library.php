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
        id,title,author,year,cover_image,category,total_copies,available_copies,type,file_size,download_link
        )VALUES(
        :id,:title,:author,:year,:cover_image,:category,:total_copies,:available_copies,:type,:file_size,:download_link
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
            download_link = :download_link
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
    public function borrowBook(string $bookId, int $userId): bool
    {
        $book = $this->getBookById($bookId);
        if (!$book || !$book->borrowCopy()) return false;

        $dueDate = date('Y-m-d', strtotime('+14days')); //14-day loan
        $stmt = $this->pdo->prepare("INSERT INTO borrowing_history(user_id,book_id,due_date) VALUES (?,?,?)");
        $stmt->execute([$userId, $bookId, $dueDate]);
        $this->updateBook($book);
        $this->sendNotification($_SESSION['email'], $book->getTitle(), 'borrowed', $dueDate);
        return true;
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
            WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
        $stmt->execute([$userId, $bookId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function returnBook(string $bookId, int $userId): bool
    {
        $book = $this->getBookById($bookId);
        if (!$book) return false;

        // Mark as returned in borrowing history
        $stmt = $this->pdo->prepare("UPDATE borrowing_history 
            SET returned_at = NOW() 
            WHERE user_id = ? AND book_id = ? AND returned_at IS NULL");
        $stmt->execute([$userId, $bookId]);

        // Return the copy to available stock
        $book->returnCopy();
        $this->updateBook($book);

        return true;
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
}