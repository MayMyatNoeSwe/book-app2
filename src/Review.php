<?php
namespace App;

use PDO;

class Review
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Add or update a review
     */
    public function addReview(int $bookId, int $userId, int $rating, ?string $comment = null): bool
    {
        try {
            // Check if review already exists
            $stmt = $this->pdo->prepare("SELECT id FROM reviews WHERE book_id = ? AND user_id = ?");
            $stmt->execute([$bookId, $userId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update existing review
                $stmt = $this->pdo->prepare("
                    UPDATE reviews 
                    SET rating = ?, comment = ?, updated_at = NOW() 
                    WHERE book_id = ? AND user_id = ?
                ");
                $stmt->execute([$rating, $comment, $bookId, $userId]);
            } else {
                // Insert new review
                $stmt = $this->pdo->prepare("
                    INSERT INTO reviews (book_id, user_id, rating, comment) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$bookId, $userId, $rating, $comment]);
            }

            // Update book's average rating and review count
            $this->updateBookRating($bookId);

            return true;
        } catch (\PDOException $e) {
            error_log("Review error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete a review
     */
    public function deleteReview(int $reviewId, int $userId): bool
    {
        try {
            // Delete review (only if it belongs to the user)
            $stmt = $this->pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$reviewId, $userId]);

            return $result && $stmt->rowCount() > 0;
        } catch (\PDOException $e) {
            error_log("Delete review error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get reviews for a book
     */
    public function getBookReviews(int $bookId, int $limit = 10, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare("
            SELECT r.*, u.username, u.avatar_url 
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.book_id = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$bookId, $limit, $offset]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user's review for a book
     */
    public function getUserReview(int $bookId, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM reviews 
            WHERE book_id = ? AND user_id = ?
        ");
        $stmt->execute([$bookId, $userId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        return $review ?: null;
    }

    /**
     * Count reviews for a book
     */
    public function countBookReviews(int $bookId): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM reviews WHERE book_id = ?");
        $stmt->execute([$bookId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Update book's average rating and review count
     */
    private function updateBookRating(int $bookId): void
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                COALESCE(AVG(rating), 0) as avg_rating,
                COUNT(*) as review_count
            FROM reviews 
            WHERE book_id = ?
        ");
        $stmt->execute([$bookId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare("
            UPDATE books 
            SET average_rating = ?, review_count = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            round($stats['avg_rating'], 2),
            $stats['review_count'],
            $bookId
        ]);
    }

    /**
     * Get rating distribution for a book
     */
    public function getRatingDistribution(int $bookId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT rating, COUNT(*) as count
            FROM reviews
            WHERE book_id = ?
            GROUP BY rating
            ORDER BY rating DESC
        ");
        $stmt->execute([$bookId]);
        
        $distribution = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $distribution[$row['rating']] = (int) $row['count'];
        }
        
        return $distribution;
    }
}
