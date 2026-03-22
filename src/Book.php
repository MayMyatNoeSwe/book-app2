<?php
//src/Book.php
namespace App;

class Book
{
    protected string $id;
    protected string $title;
    protected string $author;
    protected int $year;
    protected ?string $coverImage = null;
    protected string $category = 'Uncategorized';
    protected int $totalCopies = 1;
    protected int $availableCopies = 1;
    protected float $averageRating = 0.0;

    public function __construct(
        string $title,
        string $author,
        int $year,
        int $totalCopies = 1,
        ?string $coverImage = null,
        string $category = 'Uncategorized',
        ?string $id = null
    ) {
        $this->id = $id ?? uniqid('book_', true);
        $this->title = trim($title);
        $this->author = trim($author);
        $this->year = $year;
        $this->coverImage = $coverImage;
        $this->category = $category;
        $this->totalCopies = max(1, $totalCopies); //minium 1 
        $this->availableCopies = $this->totalCopies; //Initially all available
    }
    // ==================== Getters ====================
    public function getId(): string
    {
        return $this->id;
    }
    public function getTitle(): string
    {
        return $this->title;
    }
    public function getAuthor(): string
    {
        return $this->author;
    }
    public function getYear(): int
    {
        return $this->year;
    }
    public function getCoverImage(): ?string
    {
        return $this->coverImage;
    }
    public function getCategory(): string
    {
        return $this->category;
    }
    public function getTotalCopies(): int
    {
        return $this->totalCopies;
    }
    public function getAvailableCopies(): int
    {
        return $this->availableCopies;
    }
    public function isAvailable(): bool
    {
        return $this->availableCopies > 0;
    }
    public function getAverageRating(): float
    {
        return $this->averageRating;
    }
    public function setAverageRating(float $rating): void
    {
        $this->averageRating = $rating;
    }
    // ==================== Setters ====================
    public function setCoverImage(?string $filename): void
    {
        $this->coverImage = $filename;
    }
    public function setCategory(string $category): void
    {
        $this->category = $category;
    }
    public function setTotalCopies(int $total): void
    {
        $this->totalCopies = max(1, $total);
        if ($this->availableCopies > $this->totalCopies) {
            $this->availableCopies = $this->totalCopies;
        }
    }
    // ======================== Inventory Actions =======================
    public function borrowCopy(): bool
    {
        if ($this->availableCopies > 0) {
            $this->availableCopies--;
            return true;
        }
        return false;
    }
    public function returnCopy(): void
    {
        if ($this->availableCopies < $this->totalCopies) {
            $this->availableCopies++;
        }
    }
    // ================== Serialization ==================
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'year' => $this->year,
            'cover_image' => $this->coverImage,
            'category' => $this->category,
            'total_copies' => $this->totalCopies,
            'available_copies' => $this->availableCopies,
        ];
    }
    public static function fromArray(array $data): self
    {
        $book = new self(
            $data['title'],
            $data['author'],
            $data['year'],
            $data['total_copies'] ?? 1,
            $data['cover_image'] ?? null,
            $data['category'] ?? 'Uncategorized',
            $data['id'] ?? null
        );
        $book->availableCopies = $data['available_copies'] ?? $book->totalCopies;
        $book->averageRating = (float)($data['average_rating'] ?? 0.0);
        return $book;
    }
}
