# Reviews and Rating Feature - Complete

## Overview
Logged-in users can now rate and comment on books. The system includes:
- Star rating (1-5 stars)
- Text comments/reviews
- Average rating calculation
- Review count tracking
- Edit/delete own reviews
- Beautiful UI with SweetAlert2

## What Was Created

### 1. Database Setup
**File:** `setup_reviews.php`
- Creates `reviews` table with ratings and comments
- Adds `average_rating` and `review_count` columns to books table
- Sets up foreign keys and indexes

**Run:** `php setup_reviews.php` ✓ (Already executed)

### 2. Review Class
**File:** `src/Review.php`
- `addReview()` - Add or update a review
- `deleteReview()` - Delete user's own review
- `getBookReviews()` - Get all reviews for a book
- `getUserReview()` - Get specific user's review
- `countBookReviews()` - Count total reviews
- `getRatingDistribution()` - Get rating breakdown (5★, 4★, etc.)
- Auto-updates book's average rating

### 3. API Endpoints
**File:** `api/submit_review.php`
- Handles review submission via AJAX
- Validates user login
- Validates rating (1-5)
- Returns JSON response

**File:** `api/delete_review.php`
- Handles review deletion
- Only allows users to delete their own reviews
- Returns JSON response

### 4. Frontend Integration
**File:** `book-details.php` (Already has reviews section!)
- Displays average rating and review count
- Shows all reviews with user info
- Review submission modal for logged-in users
- Star rating input with hover effects
- Edit/delete buttons for own reviews

## Features

### For All Users
- ✓ View book ratings and reviews
- ✓ See average rating (out of 5 stars)
- ✓ See total review count
- ✓ Read all user comments

### For Logged-In Users
- ✓ Submit ratings (1-5 stars)
- ✓ Write text reviews/comments
- ✓ Edit their own reviews
- ✓ Delete their own reviews
- ✓ One review per book per user

### For Non-Logged-In Users
- ✓ View all reviews
- ✓ See "Login to review" message
- ✓ Redirect to login when clicking review button

## How It Works

### Submitting a Review
1. User clicks "Write a Review" button on book details page
2. Modal opens with star rating and comment field
3. User selects rating (1-5 stars) - Required
4. User writes comment (optional)
5. Clicks "Submit Review"
6. AJAX sends data to `api/submit_review.php`
7. Review is saved to database
8. Book's average rating is updated
9. Page refreshes to show new review

### Editing a Review
1. User sees "Edit" button on their own review
2. Clicks edit, modal opens with current rating/comment
3. Makes changes and submits
4. Review is updated in database

### Deleting a Review
1. User clicks "Delete" button on their own review
2. Confirmation dialog appears
3. If confirmed, AJAX sends request to `api/delete_review.php`
4. Review is deleted
5. Book's average rating is recalculated

## Database Schema

### reviews table
```sql
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    book_id INT NOT NULL,
    user_id INT NOT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_book (user_id, book_id),
    FOREIGN KEY (book_id) REFERENCES books(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
)
```

### books table (added columns)
- `average_rating` DECIMAL(3,2) - Average of all ratings
- `review_count` INT - Total number of reviews

## UI Components

### Rating Display
- Large star display showing average rating
- Number format: "4.5 out of 5 (23 reviews)"
- Empty state: "No reviews yet"

### Review Cards
- User avatar (from OAuth or default)
- Username
- Star rating (colored stars)
- Review text
- Timestamp (e.g., "2 days ago")
- Edit/Delete buttons (only for own reviews)

### Review Modal
- Star rating input with hover effects
- Textarea for comment
- Character counter (optional)
- Submit button with loading state

## Security Features

1. **Authentication Required**
   - Only logged-in users can submit reviews
   - API endpoints check `Auth::check()`

2. **Authorization**
   - Users can only edit/delete their own reviews
   - Verified by user_id in database

3. **Input Validation**
   - Rating must be 1-5
   - SQL injection prevention (prepared statements)
   - XSS prevention (htmlspecialchars)

4. **Database Constraints**
   - One review per user per book (UNIQUE constraint)
   - Foreign keys ensure data integrity
   - CASCADE delete removes reviews when user/book deleted

## Testing

### Test the Feature
1. **View Reviews** (No login required)
   - Go to any book details page
   - Scroll to "Reader Reviews" section
   - See existing reviews

2. **Submit Review** (Login required)
   - Login to your account
   - Go to a book details page
   - Click "Write a Review" button
   - Select star rating (1-5)
   - Write a comment (optional)
   - Click "Submit Review"
   - See success message
   - See your review appear

3. **Edit Review**
   - Find your own review
   - Click "Edit" button
   - Change rating or comment
   - Submit changes

4. **Delete Review**
   - Find your own review
   - Click "Delete" button
   - Confirm deletion
   - Review disappears

## Files Modified/Created

### Created
- `setup_reviews.php` - Database setup script
- `src/Review.php` - Review management class
- `api/submit_review.php` - Submit review endpoint
- `api/delete_review.php` - Delete review endpoint
- `REVIEWS_RATING_FEATURE.md` - This documentation

### Already Exists (No changes needed!)
- `book-details.php` - Already has complete review UI

## Next Steps (Optional Enhancements)

1. **Email Notifications**
   - Notify book owner when reviewed
   - Notify user when someone replies

2. **Review Replies**
   - Allow users to reply to reviews
   - Threaded comments

3. **Helpful Votes**
   - "Was this review helpful?" buttons
   - Sort by most helpful

4. **Review Moderation**
   - Admin can hide/delete inappropriate reviews
   - Report review feature

5. **Review Images**
   - Allow users to upload photos with reviews

6. **Verified Purchase Badge**
   - Show badge if user borrowed the book

## Troubleshooting

### Reviews not showing
- Check if `setup_reviews.php` was run
- Verify reviews table exists in database
- Check browser console for JavaScript errors

### Can't submit review
- Verify user is logged in (`Auth::check()`)
- Check rating is between 1-5
- Check API endpoint permissions

### Average rating not updating
- Check `updateBookRating()` method in Review class
- Verify books table has `average_rating` column

## Status
✅ **COMPLETE AND READY TO USE!**

The feature is fully implemented and functional. Users can now rate and review books on your library application!
