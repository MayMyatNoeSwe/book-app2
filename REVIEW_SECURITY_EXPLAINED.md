# Review Edit/Delete Security - How It Works

## Current Behavior (CORRECT & SECURE)

The edit and delete buttons **ONLY appear on YOUR OWN reviews**. This is intentional and secure.

### What You See:

1. **Your Own Review:**
   ```
   [Your Name] ⭐⭐⭐⭐⭐
   "Great book!"
   [Edit Button] [Delete Button]  ← YOU SEE THESE
   ```

2. **Other People's Reviews:**
   ```
   [Someone Else] ⭐⭐⭐
   "Good read"
   (No buttons visible)  ← NO BUTTONS FOR OTHER USERS' REVIEWS
   ```

## Why This Is Correct

This is a **security feature** to prevent:
- Users from deleting other people's reviews
- Users from editing other people's reviews
- Vandalism or abuse of the review system
- Unauthorized content modification

## Security Layers

### Layer 1: Frontend (UI)
**File:** `book-details.php` (line 504)

```php
<?php if (Auth::check() && Auth::id() == $review['user_id']): ?>
    <button onclick="editReview(...)">Edit</button>
    <button onclick="deleteReview(...)">Delete</button>
<?php endif; ?>
```

**What it does:**
- Checks if you're logged in
- Checks if the review belongs to you
- Only shows buttons if BOTH conditions are true

### Layer 2: API Authentication
**File:** `api/delete_review.php` (line 18)

```php
if (!Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in']);
    exit;
}
```

**What it does:**
- Verifies user is logged in before processing
- Returns 401 Unauthorized if not logged in

### Layer 3: Database Security
**File:** `src/Review.php` (line 61)

```php
$stmt = $this->pdo->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
$result = $stmt->execute([$reviewId, $userId]);
```

**What it does:**
- SQL query includes `AND user_id = ?`
- Even if someone bypasses frontend/API, database won't delete reviews that don't belong to them
- Returns false if no rows affected (review doesn't exist or doesn't belong to user)

## Testing the Security

### Test 1: Your Own Review
1. Go to a book where you wrote a review
2. You should see [Edit] and [Delete] buttons
3. Click delete → Confirmation appears → Review is deleted ✓

### Test 2: Other Users' Reviews
1. Go to a book with reviews from other users
2. You should NOT see [Edit] or [Delete] buttons on their reviews
3. This is CORRECT behavior ✓

### Test 3: Direct API Call (Advanced)
Even if someone tries to call the API directly:

```javascript
// Trying to delete someone else's review (ID: 999)
fetch('api/delete_review.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({ review_id: 999 })
})
```

**Result:** 
- If review 999 doesn't belong to you: `{"success": false, "message": "You can only delete your own reviews"}`
- Database query returns 0 rows affected
- Review is NOT deleted ✓

## Expected Behavior Summary

| Scenario | Edit Button | Delete Button | Can Delete? |
|----------|-------------|---------------|-------------|
| Your own review | ✓ Visible | ✓ Visible | ✓ Yes |
| Other user's review | ✗ Hidden | ✗ Hidden | ✗ No |
| Not logged in | ✗ Hidden | ✗ Hidden | ✗ No |
| Admin user* | ✗ Hidden | ✗ Hidden | ✗ No |

*Note: Currently there's no admin override. If you want admins to delete any review, that would require additional code.

## Common Misunderstandings

### ❌ "Delete button not working for other comments"
**This is CORRECT!** You should NOT be able to delete other people's comments.

### ❌ "I can only delete my own reviews"
**This is CORRECT!** This is how it should work for security.

### ❌ "Buttons don't appear on some reviews"
**This is CORRECT!** Buttons only appear on YOUR reviews.

## If You Want Different Behavior

### Option 1: Admin Can Delete Any Review
If you want administrators to delete any review:

1. Add admin role check
2. Show delete button for admins on all reviews
3. Update API to allow admin deletions
4. Update database query to allow admin override

### Option 2: Report Inappropriate Reviews
Instead of allowing users to delete others' reviews:

1. Add "Report" button on all reviews
2. Admins review reported content
3. Admins can then delete if inappropriate

### Option 3: Moderation System
Implement a full moderation system:

1. Users can flag reviews
2. Moderators review flagged content
3. Moderators can hide/delete reviews
4. Users get notified of moderation actions

## Troubleshooting

### "I can't delete my own review"
Check:
1. Are you logged in?
2. Is the review actually yours? (Check username)
3. Check browser console for errors (F12)
4. Try the debug page: `debug_delete_review.php`

### "Buttons don't appear at all"
Check:
1. Are you logged in?
2. Does the book have YOUR reviews?
3. Check if `Auth::check()` is working
4. Check session is active

### "Delete works but page doesn't update"
This was fixed - page now reloads after successful deletion.

## Code References

- **Frontend UI:** `book-details.php` lines 504-511
- **Delete Function:** `book-details.php` lines 680-740
- **API Endpoint:** `api/delete_review.php`
- **Database Layer:** `src/Review.php` method `deleteReview()`
- **Edit Function:** `book-details.php` lines 653-678

## Conclusion

The current behavior is **CORRECT and SECURE**. You can only edit/delete your own reviews, which is standard practice for review systems. This prevents abuse and maintains data integrity.

If you need different behavior (like admin moderation), that would require additional features to be implemented.
