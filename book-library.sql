-- =============================================
-- Book Library Management System - Complete Database
-- Pure PHP OOP Project
-- Created: December 26, 2025
-- =============================================

CREATE DATABASE IF NOT EXISTS book_library CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE book_library;

-- Users Table (with authentication and roles)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Authors Table (for author photos and bios)
CREATE TABLE authors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    photo VARCHAR(255) NULL,
    bio TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Books Table (with covers, categories, multiple copies)
CREATE TABLE books (
    id VARCHAR(50) PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(255) NOT NULL,
    year INT NOT NULL,
    cover_image VARCHAR(255) NULL,
    category VARCHAR(50) DEFAULT 'Uncategorized',
    total_copies INT DEFAULT 1,
    available_copies INT DEFAULT 1,
    type ENUM('physical', 'ebook') DEFAULT 'physical',
    file_size VARCHAR(20) NULL,
    download_link TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Borrowing History (with due dates for fines)
CREATE TABLE borrowing_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(50) NOT NULL,
    borrowed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    returned_at TIMESTAMP NULL,
    due_date DATE NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE,
    INDEX idx_user_book (user_id, book_id)
);

-- Reviews & Ratings
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(50) NOT NULL,
    rating TINYINT(1) CHECK (rating BETWEEN 1 AND 5),
    review_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_review (user_id, book_id)
);

-- Reservations / Waitlist
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    book_id VARCHAR(50) NOT NULL,
    reserved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM(
        'waiting',
        'available',
        'cancelled',
        'fulfilled'
    ) DEFAULT 'waiting',
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES books (id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_book_reservation (user_id, book_id)
);

-- =============================================
-- Sample Data
-- =============================================

-- Default Admin User
-- Username: admin
-- Password: admin123 (hashed)
INSERT INTO
    users (
        username,
        email,
        password,
        role
    )
VALUES (
        'admin',
        'admin@library.com',
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
        'admin'
    );

-- Sample Regular Users
INSERT INTO
    users (
        username,
        email,
        password,
        role
    )
VALUES (
        'john_doe',
        'john@example.com',
        '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        'user'
    ), -- password: secret123
    (
        'jane_smith',
        'jane@example.com',
        '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm',
        'user'
    );

-- Sample Authors with photos
INSERT INTO authors (name, photo, bio) VALUES
    ('J.K. Rowling', 'jk_rowling.jpg', 'British author best known for the Harry Potter series.'),
    ('J.R.R. Tolkien', 'tolkien.jpg', 'English author and philologist, creator of Middle-earth.'),
    ('George Orwell', 'orwell.jpg', 'English novelist and essayist, known for dystopian fiction.'),
    ('Jane Austen', 'austen.jpg', 'English novelist known for her social commentary and wit.'),
    ('F. Scott Fitzgerald', 'fitzgerald.jpg', 'American novelist of the Jazz Age.'),
    ('Harper Lee', 'harper_lee.jpg', 'American novelist, author of To Kill a Mockingbird.'),
    ('Frank Herbert', 'herbert.jpg', 'American science fiction author, creator of Dune.'),
    ('Douglas Crockford', 'crockford.jpg', 'American computer programmer and author.'),
    ('Robert C. Martin', 'martin.jpg', 'American software engineer and author.'),
    ('J.D. Salinger', 'salinger.jpg', 'American writer known for The Catcher in the Rye.');

-- Sample Books
INSERT INTO
    books (
        id,
        title,
        author,
        year,
        cover_image,
        category,
        total_copies,
        available_copies
    )
VALUES (
        'book_001',
        'PHP for Beginners',
        'John Doe',
        2023,
        NULL,
        'Non-Fiction',
        5,
        4
    ),
    (
        'book_002',
        'The Great Gatsby',
        'F. Scott Fitzgerald',
        1925,
        NULL,
        'Fiction',
        3,
        2
    ),
    (
        'book_003',
        '1984',
        'George Orwell',
        1949,
        NULL,
        'Sci-Fi',
        4,
        0
    ),
    (
        'book_004',
        'To Kill a Mockingbird',
        'Harper Lee',
        1960,
        NULL,
        'Fiction',
        6,
        6
    ),
    (
        'book_005',
        'Clean Code',
        'Robert C. Martin',
        2008,
        NULL,
        'Non-Fiction',
        2,
        1
    ),
    (
        'book_006',
        'Harry Potter and the Sorcerer''s Stone',
        'J.K. Rowling',
        1997,
        NULL,
        'Fantasy',
        10,
        7
    ),
    (
        'book_007',
        'The Hobbit',
        'J.R.R. Tolkien',
        1937,
        NULL,
        'Fantasy',
        8,
        5
    ),
    (
        'book_008',
        'Pride and Prejudice',
        'Jane Austen',
        1813,
        NULL,
        'Romance',
        4,
        3
    ),
    (
        'book_009',
        'The Catcher in the Rye',
        'J.D. Salinger',
        1951,
        NULL,
        'Fiction',
        3,
        2
    ),
    (
        'book_010',
        'JavaScript: The Good Parts',
        'Douglas Crockford',
        2008,
        NULL,
        'Non-Fiction',
        3,
        2
    ),
    (
        'book_011',
        'Dune',
        'Frank Herbert',
        1965,
        NULL,
        'Sci-Fi',
        5,
        3
    ),
    (
        'book_012',
        'The Lord of the Rings',
        'J.R.R. Tolkien',
        1954,
        NULL,
        'Fantasy',
        6,
        4
    );

-- Sample Borrowing History
INSERT INTO
    borrowing_history (
        user_id,
        book_id,
        borrowed_at,
        returned_at,
        due_date
    )
VALUES (
        2,
        'book_001',
        '2025-12-10 14:30:00',
        '2025-12-20 10:15:00',
        '2025-12-24'
    ),
    (
        2,
        'book_003',
        '2025-12-15 09:00:00',
        NULL,
        '2025-12-29'
    ),
    (
        3,
        'book_002',
        '2025-12-18 16:45:00',
        NULL,
        '2026-01-01'
    ),
    (
        2,
        'book_006',
        '2025-12-05 10:00:00',
        '2025-12-15 14:30:00',
        '2025-12-19'
    ),
    (
        3,
        'book_006',
        '2025-12-20 11:00:00',
        NULL,
        '2026-01-03'
    ),
    (
        2,
        'book_007',
        '2025-12-12 16:00:00',
        '2025-12-22 09:00:00',
        '2025-12-26'
    ),
    (
        3,
        'book_008',
        '2025-12-25 14:00:00',
        NULL,
        '2026-01-08'
    ),
    (
        2,
        'book_010',
        '2025-12-08 13:00:00',
        '2025-12-18 10:00:00',
        '2025-12-22'
    );

-- Sample Reviews
INSERT INTO
    reviews (
        user_id,
        book_id,
        rating,
        review_text,
        created_at
    )
VALUES (
        2,
        'book_001',
        5,
        'Excellent introduction to PHP! Highly recommended for beginners.',
        '2025-12-21 12:00:00'
    ),
    (
        3,
        'book_002',
        4,
        'A classic. Beautiful writing, though a bit slow at times.',
        '2025-12-19 15:30:00'
    ),
    (
        2,
        'book_004',
        5,
        'One of the best books I''ve ever read. Timeless and powerful.',
        '2025-12-22 08:20:00'
    ),
    (
        3,
        'book_006',
        5,
        'Magical! Perfect for both children and adults. J.K. Rowling created an amazing world.',
        '2025-12-23 19:45:00'
    ),
    (
        2,
        'book_007',
        4,
        'A wonderful adventure story. Tolkien''s world-building is incredible.',
        '2025-12-24 11:30:00'
    ),
    (
        3,
        'book_008',
        4,
        'Jane Austen''s wit and social commentary are brilliant. A delightful read.',
        '2025-12-25 16:20:00'
    ),
    (
        2,
        'book_010',
        4,
        'Great insights into JavaScript. Essential for web developers.',
        '2025-12-26 10:15:00'
    ),
    (
        3,
        'book_011',
        5,
        'Epic science fiction at its finest. Complex and rewarding.',
        '2025-12-27 14:00:00'
    );

-- Sample Reservations (for unavailable books)
INSERT INTO
    reservations (user_id, book_id, status)
VALUES (3, 'book_003', 'waiting'),
    (2, 'book_005', 'waiting');

-- =============================================
-- End of SQL File
-- =============================================

SELECT 'Database setup complete! You can now run your PHP Library System.' AS status;