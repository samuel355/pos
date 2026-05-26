<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'Category name is required.';
    } elseif (mb_strlen($name) > 100) {
        $errors[] = 'Category name must be 100 characters or fewer.';
    } else {
        $check = $pdo->prepare('SELECT id FROM categories WHERE name = ? LIMIT 1');
        $check->execute([$name]);

        if ($check->fetch()) {
            $errors[] = 'Category already exists.';
        } else {
            $insert = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
            $insert->execute([$name]);

            header('Location: categories.php?created=1');
            exit;
        }
    }
}

if (isset($_GET['created']) && $_GET['created'] === '1') {
    $success = 'Category created successfully.';
}

$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC')->fetchAll();
?>

<?php include 'includes/header.php'; ?>
<?php include 'includes/sidebar.php'; ?>

<div class="page-heading mb-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0">Categories</h6>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <?php foreach ($errors as $error): ?>
            <div><?php echo htmlspecialchars($error); ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Add Category</h6>
                <form method="post" action="categories.php" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="category_name">Name</label>
                        <input
                            id="category_name"
                            type="text"
                            name="name"
                            class="form-control"
                            maxlength="100"
                            required
                            value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>"
                        >
                    </div>
                    <button type="submit" class="btn btn-primary">Save Category</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h6 class="mb-3">Category List</h6>
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 100px;">ID</th>
                                <th>Name</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!$categories): ?>
                                <tr>
                                    <td colspan="2" class="text-muted">No categories found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td><?php echo (int)$category['id']; ?></td>
                                        <td><?php echo htmlspecialchars($category['name']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>