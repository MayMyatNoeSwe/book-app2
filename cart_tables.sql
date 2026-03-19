-- ============================================
-- Shopping Cart Tables for Book Library
-- ============================================
-- Run this SQL file to create cart, orders, and order_items tables
-- Also adds price column to books table

-- ============================================
-- 1. Create Cart Table
-- ============================================
CREATE TABLE IF NOT EXISTS `cart` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `quantity` INT NOT NULL DEFAULT 1,
  `added_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_user_book` (`user_id`, `book_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_book_id` (`book_id`),
  CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cart_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. Create Orders Table
-- ============================================
CREATE TABLE IF NOT EXISTS `orders` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `order_number` VARCHAR(50) NOT NULL,
  `total_amount` DECIMAL(10, 2) NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `shipping_address` TEXT DEFAULT NULL,
  `notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_order_number` (`order_number`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_order_number` (`order_number`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`),
  CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. Create Order Items Table
-- ============================================
CREATE TABLE IF NOT EXISTS `order_items` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `order_id` INT NOT NULL,
  `book_id` INT NOT NULL,
  `quantity` INT NOT NULL,
  `price` DECIMAL(10, 2) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_order_id` (`order_id`),
  KEY `idx_book_id` (`book_id`),
  CONSTRAINT `fk_order_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_order_items_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. Add Price Column to Books Table
-- ============================================
-- Check if price column exists, if not add it
SET @dbname = DATABASE();
SET @tablename = 'books';
SET @columnname = 'price';
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  'SELECT 1',
  'ALTER TABLE books ADD COLUMN price DECIMAL(10, 2) DEFAULT 9.99 AFTER year'
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- ============================================
-- 5. Set Random Prices for Existing Books
-- ============================================
-- Update books that don't have prices yet
UPDATE `books` 
SET `price` = ROUND(5 + (RAND() * 20), 2) 
WHERE `price` IS NULL OR `price` = 0;

-- ============================================
-- Verification Queries (Optional - Run to Check)
-- ============================================
-- Check if tables were created
-- SELECT 'cart' as table_name, COUNT(*) as row_count FROM cart
-- UNION ALL
-- SELECT 'orders', COUNT(*) FROM orders
-- UNION ALL
-- SELECT 'order_items', COUNT(*) FROM order_items;

-- Check books with prices
-- SELECT id, title, author, price FROM books LIMIT 10;

-- ============================================
-- Sample Data (Optional - Uncomment to add test data)
-- ============================================
/*
-- Add sample cart items (replace user_id and book_id with actual values)
INSERT INTO cart (user_id, book_id, quantity) VALUES
(1, 1, 2),
(1, 2, 1),
(1, 3, 1);

-- Add sample order
INSERT INTO orders (user_id, order_number, total_amount, status, payment_method) VALUES
(1, 'ORD-20260217-ABC123', 49.99, 'completed', 'credit_card');

-- Add sample order items
INSERT INTO order_items (order_id, book_id, quantity, price) VALUES
(1, 1, 2, 15.99),
(1, 2, 1, 18.01);
*/

-- ============================================
-- Success Message
-- ============================================
SELECT 'Shopping cart tables created successfully!' as Status;
