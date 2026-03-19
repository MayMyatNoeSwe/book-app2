<?php
/**
 * Debug Delete Review Functionality
 */

require_once 'includes/sessions.php';
require_once 'vendor/autoload.php';
require_once 'includes/env_loader.php';

use App\Auth;
use App\Library;

if (!Auth::check()) {
    die("Please login first");
}

$library = new Library();
$userId = Auth::id();

// Get a book with user's review
$stmt = $library->getPdo()->prepare("
    SELECT r.*, b.title, b.author 
    FROM reviews r
    JOIN books b ON r.book_id = b.id
    WHERE r.user_id = ?
    LIMIT 1
");
$stmt->execute([$userId]);
$review = $stmt->fetch();

if (!$review) {
    die("You don't have any reviews yet. Please add a review first.");
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Debug Delete Review</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
</head>
<body>
    <div class="container my-5">
        <h1>Debug Delete Review</h1>
        <hr>
        
        <div class="alert alert-info">
            <h5>Your Review:</h5>
            <p><strong>Book:</strong> <?= htmlspecialchars($review['title']) ?> by <?= htmlspecialchars($review['author']) ?></p>
            <p><strong>Rating:</strong> <?= $review['rating'] ?> stars</p>
            <p><strong>Review:</strong> <?= htmlspecialchars($review['review_text']) ?></p>
            <p><strong>Review ID:</strong> <?= $review['id'] ?></p>
        </div>

        <h3>Test Buttons:</h3>
        
        <div class="mb-3">
            <h5>1. Test if JavaScript function exists:</h5>
            <button class="btn btn-primary" onclick="testFunctionExists()">
                Test Function Exists
            </button>
        </div>

        <div class="mb-3">
            <h5>2. Test SweetAlert2:</h5>
            <button class="btn btn-info" onclick="testSweetAlert()">
                Test SweetAlert
            </button>
        </div>

        <div class="mb-3">
            <h5>3. Test Delete API (without confirmation):</h5>
            <button class="btn btn-warning" onclick="testDeleteAPI()">
                Test Delete API
            </button>
        </div>

        <div class="mb-3">
            <h5>4. Test Full Delete Flow:</h5>
            <button class="btn btn-danger" onclick="deleteReview(<?= $review['id'] ?>)">
                <i class="fas fa-trash"></i> Delete Review (Full Flow)
            </button>
        </div>

        <div class="mb-3">
            <h5>5. Console Log:</h5>
            <div id="console" class="border p-3 bg-light" style="min-height: 200px; font-family: monospace; white-space: pre-wrap;"></div>
        </div>

        <hr>
        <a href="book-details.php?id=<?= $review['book_id'] ?>" class="btn btn-secondary">
            Go to Book Details Page
        </a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
    // Console logger
    function log(message) {
        const consoleDiv = document.getElementById('console');
        const timestamp = new Date().toLocaleTimeString();
        consoleDiv.textContent += `[${timestamp}] ${message}\n`;
        console.log(message);
    }

    // Test 1: Check if function exists
    function testFunctionExists() {
        if (typeof deleteReview === 'function') {
            log('✓ deleteReview function exists');
            alert('✓ deleteReview function exists');
        } else {
            log('✗ deleteReview function NOT found');
            alert('✗ deleteReview function NOT found');
        }
    }

    // Test 2: Test SweetAlert2
    function testSweetAlert() {
        log('Testing SweetAlert2...');
        if (typeof Swal !== 'undefined') {
            log('✓ SweetAlert2 is loaded');
            Swal.fire({
                title: 'Success!',
                text: 'SweetAlert2 is working correctly',
                icon: 'success'
            });
        } else {
            log('✗ SweetAlert2 NOT loaded');
            alert('✗ SweetAlert2 NOT loaded');
        }
    }

    // Test 3: Test Delete API directly
    function testDeleteAPI() {
        log('Testing Delete API...');
        const reviewId = <?= $review['id'] ?>;
        
        fetch('api/delete_review.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ review_id: reviewId })
        })
        .then(response => {
            log('Response status: ' + response.status);
            return response.json();
        })
        .then(data => {
            log('Response data: ' + JSON.stringify(data));
            if (data.success) {
                alert('✓ API call successful! Review deleted.');
            } else {
                alert('✗ API call failed: ' + data.message);
            }
        })
        .catch(error => {
            log('Error: ' + error.message);
            alert('✗ Error: ' + error.message);
        });
    }

    // Test 4: Full delete flow (same as book-details.php)
    function deleteReview(reviewId) {
        log('Starting delete review flow for ID: ' + reviewId);
        
        Swal.fire({
            title: 'Delete Review?',
            text: 'Are you sure you want to delete this review? This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            log('User clicked: ' + (result.isConfirmed ? 'Confirm' : 'Cancel'));
            
            if (result.isConfirmed) {
                log('Sending delete request...');
                
                // Send delete request
                fetch('api/delete_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ review_id: reviewId })
                })
                .then(response => {
                    log('Response status: ' + response.status);
                    return response.json();
                })
                .then(data => {
                    log('Response: ' + JSON.stringify(data));
                    
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            text: 'Your review has been deleted.',
                            confirmButtonColor: '#2e8a40',
                            timer: 1500,
                            showConfirmButton: false
                        }).then(() => {
                            log('Reloading page...');
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: data.message || 'Failed to delete review',
                            confirmButtonColor: '#2e8a40'
                        });
                    }
                })
                .catch(error => {
                    log('Fetch error: ' + error.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'An error occurred while deleting the review',
                        confirmButtonColor: '#2e8a40'
                    });
                });
            }
        });
    }

    // Log page load
    log('Page loaded successfully');
    log('Review ID: <?= $review['id'] ?>');
    log('User ID: <?= $userId ?>');
    </script>
</body>
</html>
