<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$message = "";

// Handle Add Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {
  $name = $_POST["name"] ?? "";
  $category_id = $_POST["category_id"] ?? null;
  $price = $_POST["price"] ?? 0;
  $stock = $_POST["stock"] ?? 0;
  $image_path = "";

  if (isset($_POST["image_path"])) {
    $image_path = $_POST["image_path"];
  }

  if ($name && $price) {
    $stmt = $pdo->prepare(
      "INSERT INTO products (name, category_id, price, stock_quantity, image_path) VALUES (?, ?, ?, ?, ?)",
    );
    $stmt->execute([$name, $category_id, $price, $stock, $image_path]);
    $message = "Product added successfully!";
  }
}

// Handle Delete Product
if (isset($_GET["delete"])) {
  $id = $_GET["delete"];
  $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
  $stmt->execute([$id]);
  $message = "Product deleted successfully!";
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$stmt = $pdo->query(
  "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.id DESC",
);
$products = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="row">
    <div class="col-12">
        <div class="page-title-box d-sm-flex align-items-center justify-content-between">
            <h4 class="mb-sm-0">Products Management</h4>
        </div>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo $message; ?></div>
<?php endif; ?>

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Add New Product</h5>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" class="form-control">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat[
                                  "id"
                                ]; ?>"><?php echo htmlspecialchars($cat["name"]); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Price</label>
                        <input type="number" step="0.01" name="price" class="form-control" required>
                    </div>
                    <div class="col-md-1">
                        <label class="form-label">Stock</label>
                        <input type="number" name="stock" class="form-control" value="0">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label">Image Path</label>
                        <input type="text" name="image_path" class="form-control" placeholder="assets/img-01.png">
                    </div>
                    <div class="col-12 mt-3">
                        <button type="submit" name="add_product" class="btn btn-primary">Add Product</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Product List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Image</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $prod): ?>
                                <tr>
                                    <td><?php echo $prod["id"]; ?></td>
                                    <td>
                                        <?php if ($prod["image_path"]): ?>
                                            <img src="<?php echo htmlspecialchars(
                                              $prod["image_path"],
                                            ); ?>" alt="" height="40">
                                        <?php else: ?>
                                            <span class="text-muted">No Image</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($prod["name"]); ?></td>
                                    <td><?php echo htmlspecialchars(
                                      $prod["category_name"] ?? "N/A",
                                    ); ?></td>
                                    <td>$<?php echo number_format($prod["price"], 2); ?></td>
                                    <td><?php echo $prod["stock_quantity"]; ?></td>
                                    <td>
                                        <a href="products.php?delete=<?php echo $prod[
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
