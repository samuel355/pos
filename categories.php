<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$errors = [];
$success = '';

function e($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function normalizeCategoryImagePath($path)
{
    $placeholder = './assets/uploads/placeholder.png';
    $path = trim((string)$path);

    if ($path === '') {
        return $placeholder;
    }

    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, './') === 0) {
        return $path;
    }

    return './' . ltrim($path, '/');
}

function uploadCategoryImage($file)
{
    if (!isset($file) || !isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Category image upload failed.');
    }

    $allowedTypes = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp'
    ];

    $tmpName = $file['tmp_name'];
    $mimeType = mime_content_type($tmpName);

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception('Only JPG, PNG, GIF, and WEBP category images are allowed.');
    }

    if ($file['size'] > 3 * 1024 * 1024) {
        throw new Exception('Category image size must not be more than 3MB.');
    }

    $uploadDir = __DIR__ . '/assets/uploads/categories';

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = 'category_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $allowedTypes[$mimeType];
    $destination = $uploadDir . '/' . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new Exception('Unable to save category image.');
    }

    return 'assets/uploads/categories/' . $fileName;
}

function generateCategoryCode($pdo)
{
    $stmt = $pdo->query("SELECT COALESCE(MAX(id), 0) + 1 FROM categories");
    $nextId = (int)$stmt->fetchColumn();

    return 'CAT-' . str_pad((string)$nextId, 4, '0', STR_PAD_LEFT);
}

function validateStatus($status)
{
    return in_array($status, ['Active', 'Inactive'], true) ? $status : 'Active';
}

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    try {
        $name = trim($_POST['name'] ?? '');
        $code = trim($_POST['code'] ?? '');
        $status = validateStatus($_POST['status'] ?? 'Active');

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        if (mb_strlen($name) > 100) {
            $errors[] = 'Category name must be 100 characters or fewer.';
        }

        if ($code === '') {
            $code = generateCategoryCode($pdo);
        }

        if (mb_strlen($code) > 50) {
            $errors[] = 'Category code must be 50 characters or fewer.';
        }

        $imagePath = uploadCategoryImage($_FILES['category_image'] ?? null);

        if ($imagePath === '') {
            $imagePath = 'assets/uploads/placeholder.png';
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? OR code = ? LIMIT 1');
            $check->execute([$name, $code]);

            if ($check->fetch()) {
                $errors[] = 'Category name or code already exists.';
            } else {
                $insert = $pdo->prepare('INSERT INTO categories (name, code, image_path, status) VALUES (?, ?, ?, ?)');
                $insert->execute([$name, $code, $imagePath, $status]);

                header('Location: categories.php?created=1');
                exit;
            }
        }
    } catch (Exception $ex) {
        $errors[] = $ex->getMessage();
    }
}

// Handle Update Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_category'])) {
    try {
        $id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $name = trim($_POST['edit_name'] ?? '');
        $code = trim($_POST['edit_code'] ?? '');
        $status = validateStatus($_POST['edit_status'] ?? 'Active');

        if ($id <= 0) {
            $errors[] = 'Invalid category selected.';
        }

        if ($name === '') {
            $errors[] = 'Category name is required.';
        }

        if (mb_strlen($name) > 100) {
            $errors[] = 'Category name must be 100 characters or fewer.';
        }

        if ($code === '') {
            $errors[] = 'Category code is required.';
        }

        if (mb_strlen($code) > 50) {
            $errors[] = 'Category code must be 50 characters or fewer.';
        }

        $stmt = $pdo->prepare('SELECT image_path FROM categories WHERE id = ?');
        $stmt->execute([$id]);
        $existingCategory = $stmt->fetch();

        if (!$existingCategory) {
            $errors[] = 'Category not found.';
        }

        $imagePathToSave = $existingCategory['image_path'] ?? 'assets/uploads/placeholder.png';
        $newImagePath = uploadCategoryImage($_FILES['edit_category_image'] ?? null);

        if ($newImagePath !== '') {
            $imagePathToSave = $newImagePath;

            if (!empty($existingCategory['image_path']) && strpos($existingCategory['image_path'], 'assets/uploads/categories/') === 0) {
                $oldImagePath = __DIR__ . '/' . $existingCategory['image_path'];

                if (is_file($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        if (!$errors) {
            $check = $pdo->prepare('SELECT id FROM categories WHERE (name = ? OR code = ?) AND id <> ? LIMIT 1');
            $check->execute([$name, $code, $id]);

            if ($check->fetch()) {
                $errors[] = 'Another category already uses this name or code.';
            } else {
                $update = $pdo->prepare('
                    UPDATE categories 
                    SET name = ?, code = ?, image_path = ?, status = ? 
                    WHERE id = ?
                ');
                $update->execute([$name, $code, $imagePathToSave, $status, $id]);

                header('Location: categories.php?updated=1');
                exit;
            }
        }
    } catch (Exception $ex) {
        $errors[] = $ex->getMessage();
    }
}

// Handle Delete Category
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
    $countStmt->execute([$id]);
    $productCount = (int)$countStmt->fetchColumn();

    if ($productCount > 0) {
        header('Location: categories.php?delete_blocked=1');
        exit;
    }

    $delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $delete->execute([$id]);

    header('Location: categories.php?deleted=1');
    exit;
}

if (isset($_GET['created']) && $_GET['created'] === '1') {
    $success = 'Category created successfully.';
}

if (isset($_GET['updated']) && $_GET['updated'] === '1') {
    $success = 'Category updated successfully.';
}

if (isset($_GET['deleted']) && $_GET['deleted'] === '1') {
    $success = 'Category deleted successfully.';
}

if (isset($_GET['delete_blocked']) && $_GET['delete_blocked'] === '1') {
    $errors[] = 'This category cannot be deleted because products are assigned to it.';
}

$categories = $pdo->query("
    SELECT 
        c.id,
        c.name,
        c.code,
        c.image_path,
        c.status,
        c.created_at,
        c.updated_at,
        COUNT(p.id) AS product_count
    FROM categories c
    LEFT JOIN products p ON p.category_id = c.id
    GROUP BY c.id, c.name, c.code, c.image_path, c.status, c.created_at, c.updated_at
    ORDER BY c.name ASC
")->fetchAll();

$nextCategoryCode = generateCategoryCode($pdo);
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
    <div>
        <h5 class="mb-1">Categories</h5>
        <p class="text-muted mb-0">Create, edit, and manage product categories.</p>
    </div>

    <a href="products.php" class="btn btn-primary">
        <i class="ri-shopping-bag-line me-1"></i>
        Products
    </a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo e($success); ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?php echo e($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0">Add Category</h6>
            </div>

            <div class="card-body">
                <form method="post" action="categories.php" enctype="multipart/form-data" class="vstack gap-3" novalidate>
                    <div>
                        <label class="form-label" for="category_name">Category Name <span class="text-danger">*</span></label>
                        <input
                            id="category_name"
                            type="text"
                            name="name"
                            class="form-control"
                            maxlength="100"
                            required
                            placeholder="e.g. Drinks"
                            value="<?php echo e($_POST['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label class="form-label" for="category_code">Category Code</label>
                        <input
                            id="category_code"
                            type="text"
                            name="code"
                            class="form-control"
                            maxlength="50"
                            placeholder="<?php echo e($nextCategoryCode); ?>"
                            value="<?php echo e($_POST['code'] ?? $nextCategoryCode); ?>">
                        <small class="text-muted">Unique category code.</small>
                    </div>

                    <div>
                        <label class="form-label" for="category_image">Category Image</label>
                        <input
                            id="category_image"
                            type="file"
                            name="category_image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/gif,image/webp">
                        <small class="text-muted">JPG, PNG, GIF, WEBP. Max 3MB.</small>
                    </div>

                    <div>
                        <label class="form-label" for="category_status">Status</label>
                        <select id="category_status" name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <button type="submit" name="add_category" class="btn btn-primary">
                        <i class="ri-add-line me-1"></i>
                        Save Category
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between gap-3">
                <h6 class="card-title mb-0">Category List</h6>
                <span class="badge bg-light text-muted border">
                    <?php echo count($categories); ?> Categories
                </span>
            </div>

            <div class="card-body">
                <?php if (!$categories): ?>
                    <div class="text-center py-5">
                        <div class="avatar size-14 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                            <i class="ri-list-check fs-3"></i>
                        </div>
                        <h6 class="mb-1">No categories found</h6>
                        <p class="text-muted mb-0">Create your first category using the form.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 70px;">Image</th>
                                    <th>Category</th>
                                    <th>Code</th>
                                    <th>Products</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th style="width: 190px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($categories as $category): ?>
                                    <?php $categoryImage = normalizeCategoryImagePath($category['image_path'] ?? ''); ?>
                                    <tr>
                                        <td>
                                            <img src="<?php echo e($categoryImage); ?>"
                                                alt="<?php echo e($category['name']); ?>"
                                                style="width: 48px; height: 48px; object-fit: cover; border-radius: 10px;">
                                        </td>
                                        <td>
                                            <h6 class="mb-1 fs-14"><?php echo e($category['name']); ?></h6>
                                            <span class="text-muted fs-sm">ID: <?php echo (int)$category['id']; ?></span>
                                        </td>

                                        <td><?php echo e($category['code']); ?></td>

                                        <td>
                                            <span class="badge bg-light text-body border">
                                                <?php echo (int)$category['product_count']; ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?php if ($category['status'] === 'Active'): ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php endif; ?>
                                        </td>

                                        <td><?php echo e($category['created_at']); ?></td>

                                        <td>
                                            <div class="d-flex gap-2">
                                                <button type="button"
                                                    class="btn btn-sm btn-light border view-category-btn"
                                                    data-category='<?php echo e(json_encode([
                                                                        'id' => (int)$category['id'],
                                                                        'name' => $category['name'],
                                                                        'code' => $category['code'],
                                                                        'image_path' => $categoryImage,
                                                                        'status' => $category['status'],
                                                                        'product_count' => (int)$category['product_count'],
                                                                        'created_at' => $category['created_at'],
                                                                        'updated_at' => $category['updated_at'],
                                                                    ])); ?>'>
                                                    View
                                                </button>

                                                <button type="button"
                                                    class="btn btn-sm btn-primary edit-category-btn"
                                                    data-category='<?php echo e(json_encode([
                                                                        'id' => (int)$category['id'],
                                                                        'name' => $category['name'],
                                                                        'code' => $category['code'],
                                                                        'image_path' => $categoryImage,
                                                                        'status' => $category['status'],
                                                                    ])); ?>'>
                                                    Edit
                                                </button>

                                                <?php if ((int)$category['product_count'] === 0): ?>
                                                    <a href="categories.php?delete=<?php echo (int)$category['id']; ?>"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Delete this category?')">
                                                        Delete
                                                    </a>
                                                <?php else: ?>
                                                    <button type="button" class="btn btn-sm btn-danger" disabled title="Category has products">
                                                        Delete
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="viewCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Category Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <div class="text-center mb-4">
                    <img id="viewCategoryImage"
                        src="./assets/uploads/placeholder.png"
                        alt="Category"
                        style="width: 120px; height: 120px; object-fit: cover; border-radius: 16px;">
                </div>
                <div class="vstack gap-3">
                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Name</span>
                        <strong id="viewCategoryName"></strong>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Code</span>
                        <strong id="viewCategoryCode"></strong>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Status</span>
                        <strong id="viewCategoryStatus"></strong>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Products</span>
                        <strong id="viewCategoryProducts"></strong>
                    </div>

                    <div class="d-flex justify-content-between border-bottom pb-2">
                        <span class="text-muted">Created</span>
                        <strong id="viewCategoryCreated"></strong>
                    </div>

                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Updated</span>
                        <strong id="viewCategoryUpdated"></strong>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="categories.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                <input type="hidden" name="category_id" id="editCategoryId">

                <div class="modal-header">
                    <h6 class="modal-title">Edit Category</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="vstack gap-3">
                        <div>
                            <label class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" name="edit_name" id="editCategoryName" class="form-control" maxlength="100" required>
                        </div>

                        <div>
                            <label class="form-label">Category Code <span class="text-danger">*</span></label>
                            <input type="text" name="edit_code" id="editCategoryCode" class="form-control" maxlength="50" required>
                        </div>

                        <div>
                            <label class="form-label">Status</label>
                            <select name="edit_status" id="editCategoryStatus" class="form-control">
                                <option value="Active">Active</option>
                                <option value="Inactive">Inactive</option>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Change Category Image</label>
                            <input type="file"
                                name="edit_category_image"
                                class="form-control"
                                accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">Leave empty to keep current image.</small>
                        </div>

                        <div>
                            <label class="form-label">Current Image</label>
                            <div>
                                <img id="editCategoryCurrentImage"
                                    src="./assets/uploads/placeholder.png"
                                    alt="Current Category"
                                    style="width: 90px; height: 90px; object-fit: cover; border-radius: 12px;">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_category" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.view-category-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const category = JSON.parse(this.dataset.category);

                document.getElementById('viewCategoryName').innerText = category.name || '';
                document.getElementById('viewCategoryCode').innerText = category.code || '';
                document.getElementById('viewCategoryStatus').innerText = category.status || '';
                document.getElementById('viewCategoryProducts').innerText = category.product_count || 0;
                document.getElementById('viewCategoryCreated').innerText = category.created_at || 'N/A';
                document.getElementById('viewCategoryUpdated').innerText = category.updated_at || 'N/A';
                document.getElementById('viewCategoryImage').src = category.image_path || './assets/uploads/placeholder.png';

                new bootstrap.Modal(document.getElementById('viewCategoryModal')).show();
            });
        });

        document.querySelectorAll('.edit-category-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                const category = JSON.parse(this.dataset.category);

                document.getElementById('editCategoryId').value = category.id;
                document.getElementById('editCategoryName').value = category.name || '';
                document.getElementById('editCategoryCode').value = category.code || '';
                document.getElementById('editCategoryStatus').value = category.status || 'Active';

                new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>