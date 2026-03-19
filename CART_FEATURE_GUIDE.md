# Shopping Cart Feature - Complete Guide

## Overview
A full-featured shopping cart system for your book library application with add to cart, quantity management, and checkout functionality.

## Features Implemented

### 1. Database Tables
- `cart` - Stores cart items for each user
- `orders` - Stores completed orders
- `order_items` - Stores items in each order
- `books.price` - Added price column to books table

### 2. Backend (PHP Classes)
- `src/Cart.php` - Complete cart management class
  - Add items to cart
  - Update quantities
  - Remove items
  - Get cart total
  - Create orders
  - View order history

### 3. API Endpoints
- `api/cart_add.php` - Add book to cart
- `api/cart_update.php` - Update item quantity
- `api/cart_remove.php` - Remove item from cart
- `api/cart_count.php` - Get cart item count

### 4. Frontend Pages
- `cart.php` - Shopping cart page with full UI
- `book-details.php` - Updated with "Add to Cart" button
- `views/navbar.php` - Added cart icon with count badge

### 5. JavaScript Functions
- `addToCart(bookId, quantity)` - Add book to cart (global function)
- `updateQuantity(cartId, quantity)` - Update cart item quantity
- `removeItem(cartId)` - Remove item from cart
- `loadCartCount()` - Load and display cart count in navbar

## Installation Steps

### Step 1: Run Database Setup
```bash
# Open in browser
http://localhost/book-app/setup_cart.php
```

This will:
- Create `cart` table
- Create `orders` table
- Create `order_items` table
- Add `price` column to `books` table
- Set random prices for existing books ($5-$25)

### Step 2: Test the Features
1. Login to your account
2. Browse books (book-list.php or book-details.php)
3. Click "Add to Cart" button
4. See cart count update in navbar
5. Click cart icon to view cart
6. Update quantities or remove items
7. Proceed to checkout (checkout.php - to be implemented)

## File Structure

```
book-app/
├── api/
│   ├── cart_add.php          # Add to cart API
│   ├── cart_update.php       # Update quantity API
│   ├── cart_remove.php       # Remove item API
│   └── cart_count.php        # Get cart count API
├── src/
│   └── Cart.php              # Cart management class
├── views/
│   ├── navbar.php            # Updated with cart icon
│   └── footer.php            # Updated with cart scripts
├── cart.php                  # Shopping cart page
├── setup_cart.php            # Database setup script
└── CART_FEATURE_GUIDE.md     # This file
```

## Usage Examples

### Add to Cart from Any Page
```html
<button onclick="addToCart(123)" class="btn btn-primary">
    <i class="fas fa-shopping-cart"></i> Add to Cart
</button>
```

### Add Multiple Quantities
```javascript
addToCart(123, 3); // Add 3 copies of book ID 123
```

### Check Cart Count
The cart count is automatically loaded and displayed in the navbar badge when user is logged in.

## Database Schema

### Cart Table
```sql
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (book_id) REFERENCES books(id),
    UNIQUE KEY unique_user_book (user_id, book_id)
);
```

### Orders Table
```sql
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    order_number VARCHAR(50) UNIQUE NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled'),
    payment_method VARCHAR(50),
    shipping_address TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### Order Items Table
```sql
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    book_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

## API Documentation

### POST /api/cart_add.php
Add a book to cart

**Request:**
```json
{
    "book_id": 123,
    "quantity": 1
}
```

**Response:**
```json
{
    "success": true,
    "message": "Book added to cart!",
    "cart_count": 3
}
```

### POST /api/cart_update.php
Update item quantity

**Request:**
```json
{
    "cart_id": 45,
    "quantity": 3
}
```

**Response:**
```json
{
    "success": true,
    "message": "Cart updated",
    "cart_count": 5,
    "cart_total": "89.95"
}
```

### POST /api/cart_remove.php
Remove item from cart

**Request:**
```json
{
    "cart_id": 45
}
```

**Response:**
```json
{
    "success": true,
    "message": "Item removed from cart",
    "cart_count": 2,
    "cart_total": "39.98"
}
```

### GET /api/cart_count.php
Get cart item count

**Response:**
```json
{
    "success": true,
    "count": 3
}
```

## Cart Class Methods

### addItem($userId, $bookId, $quantity)
Adds a book to the user's cart. If the book already exists, increases quantity.

### getItems($userId)
Returns all cart items for a user with book details.

### getCount($userId)
Returns total number of items in cart (sum of quantities).

### getTotal($userId)
Returns total price of all items in cart.

### updateQuantity($cartId, $userId, $quantity)
Updates the quantity of a cart item. If quantity is 0, removes the item.

### removeItem($cartId, $userId)
Removes an item from the cart.

### clearCart($userId)
Removes all items from user's cart.

### createOrder($userId, $orderData)
Creates an order from cart items and clears the cart.

## Security Features

1. **Authentication Required**: All cart operations require user login
2. **User Isolation**: Users can only access their own cart items
3. **SQL Injection Protection**: All queries use prepared statements
4. **CSRF Protection**: Can be added with tokens
5. **Input Validation**: All inputs are validated and sanitized

## UI Features

### Cart Page (cart.php)
- Responsive grid layout
- Book cover images with fallback
- Quantity controls (+/- buttons)
- Remove item button with confirmation
- Real-time total calculation
- Empty cart state
- Continue shopping link
- Checkout button
- Promo code input (placeholder)

### Navbar Cart Icon
- Shopping cart icon
- Badge showing item count
- Badge hidden when cart is empty
- Links to cart page
- Auto-updates after add/remove

### Book Details Page
- "Add to Cart" button
- Success notification with SweetAlert2
- Cart count updates automatically

## Styling

The cart uses Bootstrap 5 with custom CSS:
- Gradient backgrounds
- Card-based layout
- Hover effects
- Responsive design
- Dark mode support
- Smooth transitions

## Next Steps (Optional Enhancements)

### 1. Checkout Page
Create `checkout.php` with:
- Shipping address form
- Payment method selection
- Order summary
- Place order button

### 2. Order Confirmation
Create `order-confirmation.php` with:
- Order details
- Order number
- Estimated delivery
- Print receipt option

### 3. Order History
Create `orders.php` with:
- List of past orders
- Order status
- View order details
- Reorder functionality

### 4. Payment Integration
- Stripe integration
- PayPal integration
- Cash on delivery option

### 5. Email Notifications
- Order confirmation email
- Shipping notification
- Delivery confirmation

### 6. Admin Features
- View all orders
- Update order status
- Generate reports
- Manage inventory

### 7. Advanced Features
- Wishlist functionality
- Save for later
- Gift wrapping option
- Bulk discounts
- Coupon codes
- Stock management
- Low stock alerts

## Troubleshooting

### Cart count not showing
1. Check if user is logged in
2. Open browser console for errors
3. Verify `api/cart_count.php` is accessible
4. Check database connection

### Add to cart not working
1. Check browser console for errors
2. Verify user is logged in
3. Check `api/cart_add.php` for errors
4. Verify book ID is valid

### Prices not showing
1. Run `setup_cart.php` to add price column
2. Check if books have prices set
3. Update existing books with prices

### Cart page shows errors
1. Verify all database tables exist
2. Check PHP error logs
3. Verify Cart class is loaded
4. Check database connection

## Testing Checklist

- [ ] Run setup_cart.php successfully
- [ ] Login as a user
- [ ] Add book to cart from book-details page
- [ ] See cart count update in navbar
- [ ] Click cart icon to view cart
- [ ] Update quantity using +/- buttons
- [ ] Update quantity by typing in input
- [ ] Remove item from cart
- [ ] Add multiple different books
- [ ] Verify total calculation is correct
- [ ] Test with empty cart
- [ ] Test "Continue Shopping" link
- [ ] Test responsive design on mobile
- [ ] Test dark mode compatibility

## Performance Considerations

1. **Database Indexes**: Added indexes on user_id, book_id, order_number
2. **Query Optimization**: Uses JOINs to minimize queries
3. **Caching**: Cart count can be cached in session
4. **AJAX**: All cart operations use AJAX (no page reload)
5. **Lazy Loading**: Cart count loads after page load

## Browser Compatibility

- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+
- Mobile browsers (iOS Safari, Chrome Mobile)

## Dependencies

- PHP 7.4+
- MySQL 5.7+
- Bootstrap 5.3
- Font Awesome 6.4
- SweetAlert2 11
- jQuery (optional, not required)

## Support

For issues or questions:
1. Check this documentation
2. Review PHP error logs
3. Check browser console
4. Verify database tables exist
5. Test API endpoints directly

## Conclusion

The shopping cart feature is now fully functional with:
- ✓ Add to cart
- ✓ View cart
- ✓ Update quantities
- ✓ Remove items
- ✓ Cart count badge
- ✓ Responsive design
- ✓ Dark mode support
- ✓ Security features
- ✓ Error handling

Next step: Implement checkout and order processing!
