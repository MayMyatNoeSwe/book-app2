<?php
namespace App;

use PDO;

class Cart
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Add item to cart
     */
    public function addItem(int $userId, string $bookId, int $quantity = 1): bool
    {
        try {
            // Check if item already exists in cart
            $stmt = $this->pdo->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND book_id = ?");
            $stmt->execute([$userId, $bookId]);
            $existing = $stmt->fetch();

            if ($existing) {
                // Update quantity
                $newQuantity = $existing['quantity'] + $quantity;
                $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
                return $stmt->execute([$newQuantity, $existing['id']]);
            } else {
                // Insert new item
                $stmt = $this->pdo->prepare("INSERT INTO cart (user_id, book_id, quantity) VALUES (?, ?, ?)");
                return $stmt->execute([$userId, $bookId, $quantity]);
            }
        } catch (\PDOException $e) {
            error_log("Add to cart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get cart items for user
     */
    public function getItems(int $userId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT c.id, c.book_id, c.quantity, c.added_at,
                   b.title, b.author, b.price, b.year, b.category, b.available_copies, b.cover_image
            FROM cart c
            JOIN books b ON c.book_id = b.id
            WHERE c.user_id = ?
            ORDER BY c.added_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get cart count for user
     */
    public function getCount(int $userId): int
    {
        $stmt = $this->pdo->prepare("SELECT SUM(quantity) FROM cart WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * Get cart total
     */
    public function getTotal(int $userId): float
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(c.quantity * b.price) as total
            FROM cart c
            JOIN books b ON c.book_id = b.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        return (float) $stmt->fetchColumn();
    }

    /**
     * Update item quantity
     */
    public function updateQuantity(int $cartId, int $userId, int $quantity): bool
    {
        try {
            if ($quantity <= 0) {
                return $this->removeItem($cartId, $userId);
            }

            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
            return $stmt->execute([$quantity, $cartId, $userId]);
        } catch (\PDOException $e) {
            error_log("Update cart quantity error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove item from cart
     */
    public function removeItem(int $cartId, int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            return $stmt->execute([$cartId, $userId]);
        } catch (\PDOException $e) {
            error_log("Remove from cart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Clear cart for user
     */
    public function clearCart(int $userId): bool
    {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (\PDOException $e) {
            error_log("Clear cart error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Create order from cart
     */
    public function createOrder(int $userId, array $orderData): ?string
    {
        try {
            $this->pdo->beginTransaction();

            // Get cart items
            $items = $this->getItems($userId);
            if (empty($items)) {
                throw new \Exception("Cart is empty");
            }

            // Calculate subtotal
            $total = $this->getTotal($userId);

            $shippingCost = (float) ($orderData['shipping_cost'] ?? 0);
            $totalAmount = $total + $shippingCost;

            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 8));

            // Create order
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, shipping_cost, payment_method, delivery_method, delivery_location, shipping_address, notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $orderNumber,
                $totalAmount,
                $shippingCost,
                $orderData['payment_method'] ?? 'cash',
                $orderData['delivery_method'] ?? null,
                $orderData['delivery_location'] ?? null,
                $orderData['shipping_address'] ?? '',
                $orderData['notes'] ?? ''
            ]);

            $orderId = $this->pdo->lastInsertId();

            // Create order items
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, book_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($items as $item) {
                $stmt->execute([
                    $orderId,
                    $item['book_id'], // This is already a string from getItems
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Clear cart
            $this->clearCart($userId);

            $this->pdo->commit();
            return $orderNumber;

        } catch (\Exception $e) {
            $this->pdo->rollBack();
            error_log("Create order error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user orders
     */
    public function getUserOrders(int $userId, int $limit = 10): array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.id
            WHERE o.user_id = ?
            GROUP BY o.id
            ORDER BY o.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get order details
     */
    public function getOrderDetails(string $orderNumber, int $userId): ?array
    {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.username, u.email
            FROM orders o
            JOIN users u ON o.user_id = u.id
            WHERE o.order_number = ? AND o.user_id = ?
        ");
        $stmt->execute([$orderNumber, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Get order items
        $stmt = $this->pdo->prepare("
            SELECT oi.*, b.title, b.author, b.cover_image
            FROM order_items oi
            JOIN books b ON oi.book_id = b.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order['id']]);
        $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $order;
    }
}
