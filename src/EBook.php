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
        ?string $id = null
    ) {
        parent::__construct($title, $author, $year, $totalCopies, $coverImage, $category, $id);
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
            $data['coverImage'] ?? null,
            $data['category'] ?? 'Uncategorized',
            $data['downloadLink'] ?? null,
            $data['id'] ?? null
        );
        $ebook->availableCopies = $data['available_copies'] ?? $ebook->totalCopies;
        return $ebook;
    }
}
