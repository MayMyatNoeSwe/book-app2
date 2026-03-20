-- =============================================
-- Categories Table Migration
-- =============================================

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    slug VARCHAR(50) UNIQUE NOT NULL,
    icon VARCHAR(50) NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Add category_id to books table
ALTER TABLE books ADD COLUMN category_id INT NULL AFTER category;

-- Add foreign key constraint
ALTER TABLE books
ADD CONSTRAINT fk_book_category FOREIGN KEY (category_id) REFERENCES categories (id) ON DELETE SET NULL;