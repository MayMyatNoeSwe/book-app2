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

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = $_POST['title'] ?? '';
    $author = $_POST['author'] ?? '';
    $category = $_POST['category'] ?? 'Fiction';
    $price = (float)($_POST['price'] ?? 0);
    $year = (int)date('Y'); // Default to current year for now
    
    if ($title && $author) {
        $book = new Book($title, $author, $year, 1, null, $category);
        
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
        
        redirect(baseUrl() . '/admin/books.php');
    }
}

// Fetch actual books from database
$page = $_GET['page'] ?? 1;
$limit = 10;
$offset = ($page - 1) * $limit;
$books = $library->getBooksPaginated($limit, $offset);
$totalBooks = $library->countBooks();

renderAdminLayout('Manage Books', function() use ($books, $totalBooks) {
    ?>
    <section class="admin-book-list">
        <div class="row g-4 align-items-center mb-4">
            <div class="col-lg-8">
                <div class="input-group input-group-lg border shadow-sm rounded-4 overflow-hidden">
                    <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 shadow-none ps-0" placeholder="Search by title, author, or ISBN...">
                    <button class="btn btn-primary px-4 shadow-sm" type="button">Filter</button>
                </div>
            </div>
            <div class="col-lg-4 text-lg-end">
                <button class="btn btn-primary btn-lg rounded-pill px-4 shadow-primary d-flex align-items-center gap-2 justify-content-center" data-bs-toggle="modal" data-bs-target="#addBookModal">
                    <i class="fas fa-plus"></i>
                    <span>Add New Book</span>
                </button>
            </div>
        </div>

        <div class="card card-admin border-0 shadow-sm overflow-hidden">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark">Book Inventory List</h6>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light border rounded" data-bs-toggle="dropdown">
                        Actions <i class="fas fa-chevron-down ms-1 small"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-file-export me-2 text-muted"></i>Export CSV</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-print me-2 text-muted"></i>Print Report</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-lightest text-muted text-uppercase small" style="letter-spacing: 0.05rem;">
                            <tr>
                                <th class="py-3 px-4 border-0">ID</th>
                                <th class="py-3 px-4 border-0">Book Details</th>
                                <th class="py-3 px-4 border-0">Category</th>
                                <th class="py-3 px-4 border-0 text-center">Price</th>
                                <th class="py-3 px-4 border-0 text-center">Status</th>
                                <th class="py-3 px-4 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="border-top-0">
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
                                    $<?= number_format(19.99, 2) // Assuming a default price if not in DB ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php 
                                    $statusClass = $book->isAvailable() ? 'success' : 'warning';
                                    echo renderStatusBadge($book->isAvailable() ? 'available' : 'borrowed', $statusClass);
                                    ?>
                                </td>
                                <td class="px-4 py-3 text-end">
                                    <div class="action-btn-group d-flex justify-content-end gap-2">
                                        <button class="btn btn-sm btn-icon-only rounded-pill btn-soft-primary" title="Edit Book">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-icon-only rounded-pill btn-soft-danger" title="Delete Book">
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

    <!-- Modal for adding a book -->
    <div class="modal fade" id="addBookModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header bg-white border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-dark">Add New Book to Collection</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 pt-2">
                    <form id="addBookForm" method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Book Title</label>
                                    <input type="text" name="title" class="form-control form-control-admin" placeholder="e.g. The Great Gatsby" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Author Name</label>
                                    <input type="text" name="author" class="form-control form-control-admin" placeholder="e.g. F. Scott Fitzgerald" required>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Category</label>
                                        <select name="category" class="form-select form-control-admin">
                                            <option value="Fiction" selected>Fiction</option>
                                            <option value="Non-Fiction">Non-Fiction</option>
                                            <option value="Romance">Romance</option>
                                            <option value="Sci-Fi">Sci-Fi</option>
                                            <option value="Fantasy">Fantasy</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label fw-bold small text-muted text-uppercase" style="letter-spacing: 1px;">Price ($)</label>
                                        <input type="number" name="price" step="0.01" class="form-control form-control-admin" placeholder="0.00" required>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div id="coverUploadPlaceholder" class="book-cover-upload border-2 border-dashed rounded-4 p-4 text-center d-flex flex-column align-items-center justify-content-center h-100 bg-light-soft hover-bg-light transition-all cursor-pointer position-relative overflow-hidden">
                                    <input type="file" id="bookCoverInput" name="cover" accept="image/*" class="d-none">
                                    <div id="uploadUI">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary opacity-25 mb-3"></i>
                                        <h6 class="mb-1 fw-bold small">Upload Cover</h6>
                                        <p class="mb-0 text-muted" style="font-size: 0.7rem;">PNG, JPG up to 5MB</p>
                                    </div>
                                    <img id="coverPreview" src="#" alt="Preview" class="d-none w-100 h-100 object-fit-cover position-absolute top-0 start-0">
                                    <button type="button" id="removeCoverBtn" class="btn btn-sm btn-danger rounded-circle position-absolute top-0 end-0 m-2 d-none" style="z-index: 10;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer border-0 pb-4 px-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" form="addBookForm" class="btn btn-primary rounded-pill px-4 shadow-primary">Add Book</button>
                </div>
            </div>
        </div>
    </div>

    <style>
    .bg-lightest { background-color: #f8f9fc; }
    .btn-soft-primary { color: #4e73df; background-color: rgba(78, 115, 223, 0.1); border: none; }
    .btn-soft-primary:hover { color: #fff; background-color: #4e73df; }
    .btn-soft-danger { color: #e74a3b; background-color: rgba(231, 74, 59, 0.1); border: none; }
    .btn-soft-danger:hover { color: #fff; background-color: #e74a3b; }
    .btn-icon-only { width: 34px; height: 34px; padding: 0; display: flex; align-items: center; justify-content: center; }
    .last-child-mb-0:last-child { margin-bottom: 0 !important; }
    .cursor-pointer { cursor: pointer; }
    .bg-light-soft { background-color: #fbfbfd; border: 2px dashed #e1e4ed; }
    .hover-bg-light:hover { background-color: #f0f1f7; border-color: #bac8f3; }
    </style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const placeholder = document.getElementById('coverUploadPlaceholder');
        const input = document.getElementById('bookCoverInput');
        const preview = document.getElementById('coverPreview');
        const uploadUI = document.getElementById('uploadUI');
        const removeBtn = document.getElementById('removeCoverBtn');

        placeholder.addEventListener('click', () => {
            input.click();
        });

        input.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.classList.remove('d-none');
                    uploadUI.classList.add('d-none');
                    removeBtn.classList.remove('d-none');
                    placeholder.classList.remove('p-4');
                    placeholder.classList.add('p-0');
                    placeholder.style.border = 'none';
                }
                reader.readAsDataURL(this.files[0]);
            }
        });

        removeBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            input.value = '';
            preview.src = '#';
            preview.classList.add('d-none');
            uploadUI.classList.remove('d-none');
            removeBtn.classList.add('d-none');
            placeholder.classList.add('p-4');
            placeholder.classList.remove('p-0');
            placeholder.style.border = '';
        });
    });
    </script>
    <?php
});
