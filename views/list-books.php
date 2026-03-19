<?php
$showActions = $showActions ?? false;
$showCopies = $showCopies ?? true;
?>

<h2 class="mb-4">📚 All Books (<?= count($books) ?>)</h2>

<?php if (empty($books)): ?>
<div class="alert alert-info text-center">
    No books found. <?php if ($showActions): ?><a href="admin/add-book.php">Add the first one!</a><?php endif; ?>
</div>
<?php else: ?>
<div class="table-responsive">
    <table class="table table-hover align-middle">
        <thead class="table-dark">
            <tr>
                <th>Cover</th>
                <th>Title</th>
                <th>Author</th>
                <th>Year</th>
                <th>Category</th>
                <?php if ($showCopies): ?>
                <th>Copies</th>
                <?php endif; ?>
                <th>Status</th>
                <?php if ($showActions): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($books as $book): ?>
            <tr>
                <td>
                    <?php if ($book->getCoverImage()): ?>
                    <img src="public/uploads/covers/<?= e($book->getCoverImage()) ?>" alt="Cover"
                        class="rounded shadow-sm" style="width:60px; height:90px; object-fit:cover;">
                    <?php else: ?>
                    <div class="bg-light border rounded d-flex align-items-center justify-content-center"
                        style="width:60px; height:90px;">
                        <small class="text-muted">No cover</small>
                    </div>
                    <?php endif; ?>
                </td>
                <td class="fw-bold"><?= e($book->getTitle()) ?></td>
                <td><?= e($book->getAuthor()) ?></td>
                <td><?= $book->getYear() ?></td>
                <td>
                    <span class="badge bg-secondary"><?= e($book->getCategory()) ?></span>
                </td>
                <?php if ($showCopies): ?>
                <td>
                    <span class="<?= $book->isAvailable() ? 'text-success' : 'text-danger' ?>">
                        <?= $book->getAvailableCopies() ?> / <?= $book->getTotalCopies() ?>
                    </span>
                </td>
                <?php endif; ?>
                <td>
                    <?php if ($book->isAvailable()): ?>
                    <span class="badge bg-success">Available</span>
                    <?php else: ?>
                    <span class="badge bg-warning">Borrowed Out</span>
                    <?php endif; ?>
                </td>
                <?php if ($showActions): ?>
                <td>
                    <a href="admin/add-book.php?id=<?= $book->getId() ?>"
                        class="btn btn-sm btn-outline-warning">Edit</a>
                    <a href="admin/delete-book.php?id=<?= $book->getId() ?>" class="btn btn-sm btn-outline-danger"
                        onclick="return confirm('Delete forever?')">Delete</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>