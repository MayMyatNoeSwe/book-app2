<?php
// admin/books.php
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/includes/sessions.php';
require_once dirname(__DIR__) . '/includes/env_loader.php';
require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/views/admin/layout.php';

// Use the Library system
use App\Library;
use App\Book;

$library = new Library();

// Handle Deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    if ($library->deleteBook($_GET['id'])) {
        setFlashMessage('Book successfully deleted!', 'success');
    } else {
        setFlashMessage('Error deleting book.', 'danger');
    }
    redirect(baseUrl() . '/admin/books.php');
}

// Handle Form Submission (Add or Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category = $_POST['category'] ?? 'Fiction';
    $price = (int)($_POST['price'] ?? 15000);
    $borrowPrice = (int)($_POST['borrow_price'] ?? 5000);
    $year = (int)($_POST['year'] ?? date('Y'));
    $totalCopies = (int)($_POST['total_copies'] ?? 1);
    
    if ($title && $author) {
        if ($id) {
            // Edit existing book
            $book = $library->getBookById($id);
            if ($book) {
                $book->setTitle($title);
                $book->setAuthor($author);
                $book->setCategory($category);
                $book->setPrice($price);
                $book->setBorrowPrice($borrowPrice);
                $book->setYear($year);
                $book->setTotalCopies($totalCopies);
                
                // Handle Cover Upload
                if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                    $library->handleCoverUpload($book, ['cover_image' => $_FILES['cover']]);
                }
                
                try {
                    $library->updateBook($book);
                    setFlashMessage('Book successfully updated!', 'success');
                } catch (Exception $e) {
                    setFlashMessage('Error updating book: ' . $e->getMessage(), 'danger');
                }
            }
        } else {
            // Add new book
            $book = new Book($title, $author, $year, $totalCopies, null, $category, null, $price, $borrowPrice);
            
            // Handle Cover Upload
            if (isset($_FILES['cover']) && $_FILES['cover']['error'] === UPLOAD_ERR_OK) {
                $library->handleCoverUpload($book, ['cover_image' => $_FILES['cover']]);
            }
            
            try {
                $library->addBook($book);
                setFlashMessage('Book successfully added to the collection!', 'success');
            } catch (Exception $e) {
                setFlashMessage('Error adding book: ' . $e->getMessage(), 'danger');
            }
        }
        
        redirect(baseUrl() . '/admin/books.php');
    }
}

// Fetch actual books from database
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$books = $library->getBooksPaginated($limit, $offset);
$totalBooks = $library->countBooks();

// Fetch real data for stats
$pdo = $library->getPdo();

// Categories Count
$stmt = $pdo->query("SELECT COUNT(DISTINCT category) FROM books");
$totalCategories = $stmt->fetchColumn();

// Status Counts
$stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE available_copies > 0");
$availableBooksCount = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM books WHERE available_copies = 0");
$outOfStockCount = $stmt->fetchColumn();

renderAdminLayout('Manage Books', function() use ($books, $totalBooks, $totalCategories, $availableBooksCount, $outOfStockCount) {
    ?>
    <section class="premium-hero mb-5 rounded-5 overflow-hidden position-relative p-5 border border-light-subtle shadow-sm bg-lightest">
        <div class="hero-pattern position-absolute top-0 start-0 w-100 h-100 opacity-5" style="background-image: radial-gradient(#4e73df 1px, transparent 1px); background-size: 30px 30px; z-index: 2;"></div>
        
        <div class="position-relative" style="z-index: 3;">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h3 class="text-dark fw-800 mb-2">Book Inventory Catalog</h3>
                    <p class="text-muted mb-4 fs-6 fw-500">Analyze your global collection, monitor real-time availability, and optimize stock efficiency from a centralized command center.</p>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm py-2" data-bs-toggle="modal" data-bs-target="#bookModal">
                            <i class="fas fa-plus-circle me-2"></i>New Catalog Entry
                        </button>
                    </div>
                </div>
                <div class="col-lg-4 d-none d-lg-block text-end">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center" style="width: 140px; height: 140px;">
                        <i class="fas fa-book-sparkles text-primary" style="font-size: 3.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="inventory-stats mb-5">
        <div class="row g-4 text-center">
            <div class="col-xl-3 col-md-6">
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
...
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
...
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
...
                <div class="card card-premium-stat h-100 border-0 shadow-sm rounded-4 overflow-hidden bg-white">
                    <div class="card-body p-4 position-relative">
                        <div class="stat-icon-premium bg-danger-soft text-danger shadow-sm mb-3 mx-auto">
                            <i class="fas fa-exclamation-circle"></i>
                        </div>
                        <h3 class="fw-800 mb-1 text-dark"><?= $outOfStockCount ?></h3>
                        <p class="text-muted small text-uppercase fw-bold mb-0">OUT OF STOCK</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="row g-4 align-items-center mb-4">
        <div class="col-lg-12">
            <div class="input-group input-group-lg border-0 shadow-sm rounded-4 overflow-hidden bg-white px-3 py-1">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted opacity-50"></i></span>
                <input type="text" id="bookSearch" class="form-control border-0 shadow-none ps-1 fs-6" placeholder="Execute Deep search across titles, authors, and classification metadata...">
                <button class="btn btn-soft-primary rounded-pill px-4 ms-2 fw-bold btn-sm my-1" type="button" id="applyFilterBtn">Apply Filter</button>
            </div>
        </div>
    </div>

    <div class="card card-admin border-0 shadow-sm overflow-hidden">
        <div class="card-header bg-white py-4 border-0 d-flex justify-content-between align-items-center px-4">
            <div>
                <h5 class="mb-0 fw-800 text-dark">Library Inventory</h5>
                <p class="text-muted smallest fw-bold mb-0 text-uppercase tracking-wider">SECURE CATALOG REGISTRY</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-white border border-light-subtle rounded-pill btn-sm px-4 fw-bold text-muted small"><i class="fas fa-file-csv me-2 text-success"></i>Export</button>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead>
                        <tr class="bg-lightest border-bottom">
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Book Identity</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider">Classification</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-center">Valuation</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-center">Availability</th>
                            <th class="py-3 px-4 border-0 text-muted smallest fw-800 text-uppercase tracking-wider text-end">Control</th>
                        </tr>
                    </thead>
                        <tbody id="inventoryTableBody" class="border-top-0">
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td class="px-4 py-3 fw-bold text-muted small">#<?= substr($book->getId(), -4) ?></td>
                                <td class="px-4 py-3">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="book-cover-mini rounded shadow-sm overflow-hidden d-flex align-items-center justify-content-center" style="width: 45px; height: 65px; background: rgba(0,0,0,0.05);">
                                            <img src="<?= getBookCoverUrl($book) ?>" class="w-100 h-100 object-fit-cover" 
                                                 onerror="this.src='<?= getDummyBookCover($book->getTitle(), $book->getAuthor(), 45, 65) ?>'">
                                        </div>
                                        <div class="book-meta-info">
                                            <h6 class="mb-0 fw-bold text-dark"><?= e($book->getTitle()) ?></h6>
                                            <p class="mb-0 text-muted small"><?= e($book->getAuthor()) ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="badge bg-light text-dark rounded-pill border px-3 fw-normal"><?= e($book->getCategory()) ?></span>
                                </td>
                                <td class="px-4 py-3 text-center fw-bold">
                                    <?= number_format($book->getPrice()) ?> KS
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php 
                                    $statusClass = $book->isAvailable() ? 'success' : 'warning';
                                    echo renderStatusBadge($book->isAvailable() ? 'available' : 'borrowed', $statusClass);
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="action-btn-group d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-icon-only rounded-pill btn-soft-primary edit-book-btn" 
                                                title="Edit Book"
                                                data-id="<?= $book->getId() ?>"
                                                data-title="<?= e($book->getTitle()) ?>"
                                                data-author="<?= e($book->getAuthor()) ?>"
                                                data-category="<?= e($book->getCategory()) ?>"
                                                data-price="<?= $book->getPrice() ?>"
                                                data-borrow-price="<?= $book->getBorrowPrice() ?>"
                                                data-year="<?= $book->getYear() ?>"
                                                data-total-copies="<?= $book->getTotalCopies() ?>"
                                                data-cover="<?= getBookCoverUrl($book) ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon-only rounded-pill btn-soft-danger delete-book-btn" 
                                                title="Delete Book"
                                                data-id="<?= $book->getId() ?>"
                                                data-title="<?= e($book->getTitle()) ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="p-4 border-top d-flex justify-content-between align-items-center">
                    <p class="mb-0 text-muted small">Showing <?= count($books) ?> of <?= $totalBooks ?> books</p>
                    <nav aria-label="Book pagination">
                        <ul class="pagination pagination-sm mb-0">
                            <li class="page-item disabled"><a class="page-item link px-3 py-1 border rounded me-1 text-muted text-decoration-none small" href="#">Prev</a></li>
                            <li class="page-item active"><a class="page-item link px-3 py-1 border rounded me-1 active bg-primary text-white text-decoration-none small" href="#">1</a></li>
                            <li class="page-item"><a class="page-item link px-3 py-1 border rounded me-1 text-dark text-decoration-none small" href="#">2</a></li>
                            <li class="page-item"><a class="page-item link px-3 py-1 border rounded text-dark text-decoration-none small" href="#">Next</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </section>

    <!-- Modal for adding/editing a book -->
    <div class="modal fade" id="bookModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-50">
            <div class="modal-content overflow-hidden border-0 shadow-2xl rounded-5 bg-glass">
                <div class="modal-header border-0 pt-4 px-5 pb-0">
                    <div class="header-content">
                        <h4 class="modal-title fw-800 text-gradient mb-1" id="bookModalTitle">Add New Book to Collection</h4>
                        <p class="text-muted small mb-0">Fill in the details below to update your library inventory.</p>
                    </div>
                    <button type="button" class="btn-close-premium shadow-sm" data-bs-dismiss="modal" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <form id="bookForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="bookId">
                        <div class="row g-4">
                            <div class="col-lg-8">
                                <div class="form-section mb-3">
                                    <h6 class="section-label mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Book Information</h6>
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="premium-field">
                                                <label class="field-label">Book Title</label>
                                                <div class="input-with-icon">
                                                    <i class="fas fa-book icon-muted"></i>
                                                    <input type="text" name="title" id="bookTitle" class="premium-input" placeholder="e.g. The Great Gatsby" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="premium-field">
                                                <label class="field-label">Author Name</label>
                                                <div class="input-with-icon">
                                                    <i class="fas fa-user-edit icon-muted"></i>
                                                    <input type="text" name="author" id="bookAuthor" class="premium-input" placeholder="e.g. F. Scott Fitzgerald" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-7">
                                            <div class="premium-field">
                                                <label class="field-label">Category</label>
                                                <div class="input-with-icon">
                                                    <i class="fas fa-tags icon-muted"></i>
                                                    <select name="category" id="bookCategory" class="premium-select">
                                                        <?php 
                                                        $categories = ['Fiction', 'Non-Fiction', 'Romance', 'Sci-Fi', 'Fantasy', 'Horror', 'Mystery', 'Biography', 'History', 'Self-Help', 'Education'];
                                                        foreach ($categories as $cat): ?>
                                                            <option value="<?= $cat ?>"><?= $cat ?></option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="premium-field">
                                                <label class="field-label">Publication Year</label>
                                                <div class="input-with-icon">
                                                    <i class="fas fa-calendar-alt icon-muted"></i>
                                                    <input type="number" name="year" id="bookYear" class="premium-input" placeholder="2024" required>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-section">
                                    <h6 class="section-label mb-2 mt-2"><i class="fas fa-dollar-sign me-2 text-success"></i>Inventory Logs</h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <div class="premium-field">
                                                <label class="field-label">Price (KS)</label>
                                                <input type="number" name="price" id="bookPrice" class="premium-input" placeholder="15000" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="premium-field">
                                                <label class="field-label">Borrow (KS)</label>
                                                <input type="number" name="borrow_price" id="bookBorrowPrice" class="premium-input" placeholder="5000" required>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="premium-field">
                                                <label class="field-label">Total Copies</label>
                                                <input type="number" name="total_copies" id="bookTotalCopies" class="premium-input" placeholder="1" min="1" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-4">
                                <div class="cover-upload-section text-center h-100 p-2">
                                    <label class="field-label text-center d-block mb-3">Cover Art</label>
                                    <div id="coverUploadPlaceholder" class="premium-upload-box mb-3" style="height: 220px;">
                                        <input type="file" id="bookCoverInput" name="cover" accept="image/*" class="d-none">
                                        
                                        <div id="uploadUI" class="upload-ui-content">
                                            <div class="upload-circle mb-2" style="width: 48px; height: 48px; font-size: 1.2rem;">
                                                <i class="fas fa-cloud-upload-alt text-primary"></i>
                                            </div>
                                            <p class="text-muted smallest shadow-text mb-0">PNG, JPG up to 2MB</p>
                                        </div>

                                        <div id="previewContainer" class="preview-container d-none">
                                            <img id="coverPreview" src="#" alt="Preview" class="cover-preview-img">
                                            <div class="preview-overlay">
                                                <button type="button" id="removeCoverBtn" class="btn btn-danger btn-sm rounded-circle shadow">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    <p class="smallest text-muted opacity-75">Upload a cover to make your book stand out in the catalog.</p>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 p-4 pt-0 d-flex justify-content-between">
                    <button type="button" class="btn btn-link text-muted text-decoration-none fw-600 px-0" data-bs-dismiss="modal">Discard Changes</button>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-soft-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" form="bookForm" id="bookSubmitBtn" class="btn btn-primary btn-premium-gradient rounded-pill px-5 shadow-lg fw-bold">Add Book</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
    .modal-50 {
        max-width: 50% !important;
        width: 50%;
    }

    @media (max-width: 1200px) {
        .modal-50 {
            max-width: 80% !important;
            width: 80%;
        }
    }

    @media (max-width: 768px) {
        .modal-50 {
            max-width: 95% !important;
            width: 95%;
        }
    }

    :root {
        --premium-primary: #4e73df;
        --premium-secondary: #858796;
        --premium-bg: #f8f9fc;
        --input-bg: #fdfdfe;
        --field-border: #e3e6f0;
        --text-main: #2e3b5e;
    }

    [data-bs-theme="dark"] .modal-content {
        --premium-bg: #1a1c23;
        --input-bg: #242633;
        --field-border: #303348;
        --text-main: #f1f5f9;
    }

    .bg-glass {
        background: var(--premium-bg) !important;
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
    }

    .text-gradient {
        background: linear-gradient(90deg, #4e73df, #224abe);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
    }

    .fw-800 { font-weight: 800; }
    .rounded-5 { border-radius: 1.5rem !important; }

    .btn-close-premium {
        background: rgba(0,0,0,0.05);
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--premium-secondary);
        transition: all 0.2s;
    }
    .btn-close-premium:hover { background: rgba(231, 74, 59, 0.1); color: #e74a3b; transform: rotate(90deg); }

    .section-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: var(--premium-secondary);
        border-bottom: 2px solid rgba(78, 115, 223, 0.1);
        padding-bottom: 8px;
    }

    .premium-field { margin-bottom: 0; }
    .field-label {
        font-size: 0.7rem;
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 8px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .premium-input, .premium-select {
        width: 100%;
        padding: 12px 16px;
        background: var(--input-bg);
        border: 1.5px solid var(--field-border);
        border-radius: 12px;
        color: var(--text-main);
        font-size: 0.95rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        outline: none;
    }
    .premium-input:focus, .premium-select:focus {
        border-color: var(--premium-primary);
        box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
    }

    .input-with-icon { position: relative; }
    .input-with-icon .premium-input, 
    .input-with-icon .premium-select { padding-left: 42px; }
    .input-with-icon .icon-muted {
        position: absolute;
        left: 16px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--premium-secondary);
        opacity: 0.5;
        font-size: 0.9rem;
    }

    .premium-upload-box {
        background: var(--input-bg);
        border: 2px dashed var(--field-border);
        border-radius: 20px;
        height: 280px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        position: relative;
        overflow: hidden;
        transition: all 0.3s cubic-bezier(0.165, 0.84, 0.44, 1);
    }
    .premium-upload-box:hover {
        border-color: var(--premium-primary);
        background: rgba(78, 115, 223, 0.02);
        transform: translateY(-4px);
    }

    .upload-circle {
        width: 64px;
        height: 64px;
        background: rgba(78, 115, 223, 0.1);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin: 0 auto;
    }

    .preview-container {
        width: 100%;
        height: 100%;
        position: relative;
    }
    .cover-preview-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    .preview-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.4);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        opacity: 0;
        transition: opacity 0.3s;
    }
    .preview-container:hover .preview-overlay { opacity: 1; }

    .smallest { font-size: 0.75rem; line-height: 1.4; }
    .shadow-text { text-shadow: 0 1px 1px rgba(255,255,255,0.8); }
    .bg-light-hint { background: rgba(0,0,0,0.02); }

    .btn-premium-gradient {
        background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
        border: none;
        transition: all 0.3s;
    }
    .btn-premium-gradient:hover {
        transform: scale(1.05);
        box-shadow: 0 8px 25px rgba(78, 115, 223, 0.4) !important;
    }

    .btn-soft-secondary {
        background: rgba(133, 135, 150, 0.1);
        color: var(--premium-secondary);
        border: none;
    }
    .btn-soft-secondary:hover { background: rgba(133, 135, 150, 0.2); }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Modal references
        const bookModal = new bootstrap.Modal(document.getElementById('bookModal'));
        const bookForm = document.getElementById('bookForm');
        const bookModalTitle = document.getElementById('bookModalTitle');
        const bookSubmitBtn = document.getElementById('bookSubmitBtn');
        
        // Input references
        const bookId = document.getElementById('bookId');
        const bookTitle = document.getElementById('bookTitle');
        const bookAuthor = document.getElementById('bookAuthor');
        const bookCategory = document.getElementById('bookCategory');
        const bookYear = document.getElementById('bookYear');
        const bookPrice = document.getElementById('bookPrice');
        const bookBorrowPrice = document.getElementById('bookBorrowPrice');
        const bookTotalCopies = document.getElementById('bookTotalCopies');
        
        // Image upload references
        const placeholder = document.getElementById('coverUploadPlaceholder');
        const input = document.getElementById('bookCoverInput');
        const preview = document.getElementById('coverPreview');
        const previewContainer = document.getElementById('previewContainer');
        const uploadUI = document.getElementById('uploadUI');
        const removeBtn = document.getElementById('removeCoverBtn');

        // Add New Book clicked
        document.querySelectorAll('[data-bs-target="#bookModal"]').forEach(btn => {
            btn.addEventListener('click', function() {
                bookForm.reset();
                bookId.value = '';
                bookModalTitle.textContent = 'Add New Book to Collection';
                bookSubmitBtn.textContent = 'Add Book';
                resetPreview();
            });
        });

        // Edit Book clicked
        document.querySelectorAll('.edit-book-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const data = this.dataset;
                
                bookId.value = data.id;
                bookTitle.value = data.title;
                bookAuthor.value = data.author;
                bookCategory.value = data.category;
                bookYear.value = data.year;
                bookPrice.value = data.price;
                bookBorrowPrice.value = data.borrowPrice;
                bookTotalCopies.value = data.totalCopies;
                
                // Set preview if image exists
                if (data.cover && !data.cover.includes('dummy') && !data.cover.includes('data:image/svg')) {
                    preview.src = data.cover;
                    preview.classList.remove('d-none');
                    previewContainer.classList.remove('d-none');
                    uploadUI.classList.add('d-none');
                    placeholder.style.border = 'none';
                } else {
                    resetPreview();
                }
                
                bookModalTitle.textContent = 'Edit Book Details';
                bookSubmitBtn.textContent = 'Update Book';
                bookModal.show();
            });
        });

        // Delete Book clicked
        document.querySelectorAll('.delete-book-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const title = this.dataset.title;
                
                Swal.fire({
                    title: 'Are you sure?',
                    text: `You are about to delete "${title}". This action cannot be undone!`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#e74a3b',
                    cancelButtonColor: '#858796',
                    confirmButtonText: 'Yes, delete it!',
                    borderRadius: '1rem',
                    background: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#1a1c23' : '#fff',
                    color: document.documentElement.getAttribute('data-bs-theme') === 'dark' ? '#f1f5f9' : '#2e3b5e'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = `books.php?action=delete&id=${id}`;
                    }
                });
            });
        });

        // Cover upload logic
        placeholder.addEventListener('click', () => {
            input.click();
        });

        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    previewContainer.classList.remove('d-none');
                    uploadUI.classList.add('d-none');
                    placeholder.style.border = 'none';
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            resetPreview();
            input.value = '';
        });

        function resetPreview() {
            preview.src = '#';
            preview.classList.add('d-none');
            previewContainer.classList.add('d-none');
            uploadUI.classList.remove('d-none');
            placeholder.style.border = '';
        }

        // Live filtering logic
        const bookSearch = document.getElementById('bookSearch');
        if (bookSearch) {
            bookSearch.addEventListener('input', function(e) {
                const term = e.target.value.toLowerCase();
                const rows = document.querySelectorAll('#inventoryTableBody tr');
                
                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(term)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        }
    });
    </script>
    <?php
});
