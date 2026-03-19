<?php
$pageTitle = "SweetAlert2 Test";
require_once 'includes/sessions.php';
require_once 'includes/functions.php';

// Test different alert types
if (isset($_GET['test'])) {
    switch ($_GET['test']) {
        case 'success':
            setFlashMessage('Successfully completed the operation!', 'success');
            break;
        case 'error':
            setFlashMessage('An error occurred while processing your request.', 'danger');
            break;
        case 'warning':
            setFlashMessage('Please be careful with this action.', 'warning');
            break;
        case 'info':
            setFlashMessage('Here is some important information for you.', 'info');
            break;
    }
    header('Location: test_sweetalert.php');
    exit;
}

include 'views/header.php';
?>

<div class="container my-5">
    <h1 class="mb-4">SweetAlert2 Test Page</h1>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Flash Messages (Server-side)</h5>
                    <p class="card-text">Test different types of flash messages that appear after page reload:</p>
                    <div class="d-grid gap-2">
                        <a href="?test=success" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Success Alert
                        </a>
                        <a href="?test=error" class="btn btn-danger">
                            <i class="fas fa-times-circle me-2"></i>Error Alert
                        </a>
                        <a href="?test=warning" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Warning Alert
                        </a>
                        <a href="?test=info" class="btn btn-info">
                            <i class="fas fa-info-circle me-2"></i>Info Alert
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title">Direct Alerts (Client-side)</h5>
                    <p class="card-text">Test SweetAlert2 directly without page reload:</p>
                    <div class="d-grid gap-2">
                        <button onclick="showSuccess()" class="btn btn-success">
                            <i class="fas fa-check-circle me-2"></i>Success Alert
                        </button>
                        <button onclick="showError()" class="btn btn-danger">
                            <i class="fas fa-times-circle me-2"></i>Error Alert
                        </button>
                        <button onclick="showWarning()" class="btn btn-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>Warning Alert
                        </button>
                        <button onclick="showInfo()" class="btn btn-info">
                            <i class="fas fa-info-circle me-2"></i>Info Alert
                        </button>
                        <button onclick="showConfirm()" class="btn btn-primary">
                            <i class="fas fa-question-circle me-2"></i>Confirmation Dialog
                        </button>
                        <button onclick="showCustom()" class="btn btn-secondary">
                            <i class="fas fa-star me-2"></i>Custom Styled Alert
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="alert alert-info">
        <h6><i class="fas fa-lightbulb me-2"></i>How to Use SweetAlert2 in Your Code:</h6>
        <p class="mb-2"><strong>Server-side (PHP):</strong></p>
        <code>setFlashMessage('Your message here', 'success');</code>
        
        <p class="mb-2 mt-3"><strong>Client-side (JavaScript):</strong></p>
        <code>Swal.fire('Title', 'Message', 'success');</code>
    </div>
</div>

<?php displayFlashMessage(); ?>

<script>
function showSuccess() {
    Swal.fire({
        icon: 'success',
        title: 'Success!',
        text: 'Operation completed successfully!',
        confirmButtonColor: '#2e8a40',
        timer: 3000,
        timerProgressBar: true
    });
}

function showError() {
    Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: 'Something went wrong!',
        confirmButtonColor: '#2e8a40'
    });
}

function showWarning() {
    Swal.fire({
        icon: 'warning',
        title: 'Warning!',
        text: 'Please proceed with caution.',
        confirmButtonColor: '#2e8a40'
    });
}

function showInfo() {
    Swal.fire({
        icon: 'info',
        title: 'Information',
        text: 'Here is some useful information for you.',
        confirmButtonColor: '#2e8a40'
    });
}

function showConfirm() {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2e8a40',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, do it!',
        cancelButtonText: 'No, cancel'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire({
                icon: 'success',
                title: 'Confirmed!',
                text: 'Your action has been confirmed.',
                confirmButtonColor: '#2e8a40'
            });
        }
    });
}

function showCustom() {
    Swal.fire({
        title: 'Custom Styled Alert',
        html: '<p>This is a <strong>custom</strong> alert with <em>HTML</em> content!</p>',
        imageUrl: 'https://placeholder.co/200x200/2e8a40/white?text=Myanmar+Library',
        imageWidth: 200,
        imageHeight: 200,
        imageAlt: 'Custom image',
        confirmButtonColor: '#2e8a40',
        background: '#f0f9ff',
        backdrop: `
            rgba(46, 138, 64, 0.2)
            left top
            no-repeat
        `
    });
}
</script>

<?php include 'views/footer.php'; ?>
