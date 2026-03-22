<?php
// includes/EBook.php

namespace App;

class EBook extends Book
{
    private string $fileSize;
    private ?string $downloadLink = null;
    public function __construct(
        string $title,
        string $author,
        int $year,
        string $fileSize,
        int $totalCopies = 1,
        ?string $coverImage = null,
        string $category = 'Uncategorized',
        ?string $downloadLink = null,
        ?string $id = null,
        ?int $price = 15000,
        ?int $borrowPrice = 5000
    ) {
        parent::__construct($title, $author, $year, $totalCopies, $coverImage, $category, $id, $price, $borrowPrice);
        $this->fileSize = $fileSize;
        $this->downloadLink = $downloadLink;
    }
    public function getFileSize(): string
    {
        return $this->fileSize;
    }
    public function getDownloadLink(): ?string
    {
        return $this->downloadLink;
    }
    public function setDownloadLink(?string $link): void
    {
        $this->downloadLink = $link;
    }
    public function toArray(): array
    {
        return parent::toArray() + [
            'type' => 'ebook',
            'fileSize' => $this->fileSize,
            'downloadLink' => $this->downloadLink,
        ];
    }
    public static function fromArray(array $data): self
    {
        $ebook = new self(
            $data['title'],
            $data['author'],
            $data['year'],
            $data['fileSize'] ?? "Unknown",
            $data['totalCopies'] ?? 1,
            $data['cover_image'] ?? null,
            $data['category'] ?? 'Uncategorized',
            $data['download_link'] ?? null,
            $data['id'] ?? null,
            $data['price'] ?? 15000,
            $data['borrow_price'] ?? 5000
        );
        $ebook->availableCopies = $data['available_copies'] ?? $ebook->totalCopies;
        return $ebook;
    }
}
