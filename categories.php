<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {
  $name = $_POST["name"] ?? "";
  if ($name) {
    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    $message = "Category added successfully!";
  }
}

if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
  $stmt->execute([$id]);
  $message = "Category deleted successfully!";
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY id DESC");
$categories = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Product Categories</h4>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add Category</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="Enter category name">
                    </div>
                    <button type="submit" name="add_category" class="btn btn-primary w-100">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Category List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Created At</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                                <tr>
                                    <td><?php echo $cat["id"]; ?></td>
                                    <td><?php echo htmlspecialchars($cat["name"]); ?></td>
                                    <td><?php echo $cat["created_at"]; ?></td>
                                    <td>
                                        <a href="categories.php?delete=<?php echo $cat[
                                          "id"
                                        ]; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
