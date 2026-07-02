<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$message = "";
$error = "";

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function normalizeImagePath($path) {
    $placeholder = "./assets/uploads/placeholder.png";
    $path = trim((string)$path);

    if ($path === "") {
        return "";
        return $placeholder;
    }

    if (strpos($path, "http://") === 0 || strpos($path, "https://") === 0 || strpos($path, "./") === 0) {
        return $path;
    }

    return "./" . ltrim($path, "/");
}

function uploadProductImage($file) {
    if (!isset($file) || !isset($file["error"]) || $file["error"] === UPLOAD_ERR_NO_FILE) {
        return "";
    }

    if ($file["error"] !== UPLOAD_ERR_OK) {
        throw new Exception("Image upload failed.");
    }

    $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/gif" => "gif",
            "image/webp" => "webp"
    ];

    $tmpName = $file["tmp_name"];
    $mimeType = mime_content_type($tmpName);

    if (!isset($allowedTypes[$mimeType])) {
        throw new Exception("Only JPG, PNG, GIF, and WEBP images are allowed.");
    }

    if ($file["size"] > 3 * 1024 * 1024) {
        throw new Exception("Image size must not be more than 3MB.");
    }

    $uploadDir = __DIR__ . "/assets/uploads/products";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $fileName = "product_" . date("YmdHis") . "_" . bin2hex(random_bytes(4)) . "." . $allowedTypes[$mimeType];
    $destination = $uploadDir . "/" . $fileName;

    if (!move_uploaded_file($tmpName, $destination)) {
        throw new Exception("Unable to save uploaded image.");
    }

    return "assets/uploads/products/" . $fileName;
}

function deleteUploadedProductImage($imagePath) {
    $imagePath = (string)$imagePath;

    if ($imagePath === "" || strpos($imagePath, "assets/uploads/products/") !== 0) {
        return;
    }

    $fullPath = __DIR__ . "/" . $imagePath;

    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}

// Handle Add Category
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_category"])) {
    $categoryName = trim($_POST["category_name"] ?? "");

    if ($categoryName === "") {
        $error = "Category name is required.";
    } else {
        $check = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
        $check->execute([$categoryName]);

        if ($check->fetch()) {
            $error = "Category already exists.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$categoryName]);

            $message = "Category added successfully.";
        }
    }
}

// Handle Add Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["add_product"])) {
    try {
        $name = trim($_POST["name"] ?? "");
        $category_id = isset($_POST["category_id"]) && $_POST["category_id"] !== "" ? (int)$_POST["category_id"] : null;
        $price = isset($_POST["price"]) ? (float)$_POST["price"] : 0;
        $stock = isset($_POST["stock"]) ? (int)$_POST["stock"] : 0;

        if ($name === "") {
            throw new Exception("Product name is required.");
        }
        if (mb_strlen($name) > 150) {
            throw new Exception("Product name must be 150 characters or fewer.");
        }

        if ($category_id <= 0) {
            throw new Exception("Please select a category for the product.");
        }
        $categoryCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
        $categoryCheck->execute([$category_id]);

        if (!$categoryCheck->fetch()) {
            throw new Exception("Selected category does not exist.");
        }

        if (!isset($_POST["price"]) || $_POST["price"] === "" || !is_numeric($_POST["price"])) {
            throw new Exception("Product price is required and must be a number.");
        }
        if ($price <= 0) {
            throw new Exception("Product price must be greater than zero.");
        }

        if ($stock < 0) {
            throw new Exception("Stock cannot be negative.");
        }
        $duplicate = $pdo->prepare("SELECT id FROM products WHERE name = ? AND category_id = ? LIMIT 1");
        $duplicate->execute([$name, $category_id]);

        if ($duplicate->fetch()) {
            throw new Exception("A product with this name already exists in the selected category.");
        }

        $image_path = uploadProductImage($_FILES["product_image"] ?? null);

        $stmt = $pdo->prepare("
            INSERT INTO products 
                (name, category_id, price, stock_quantity, image_path) 
            VALUES 
                (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $category_id, $price, $stock, $image_path]);

        $message = "Product added successfully.";
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// Handle Update Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_product"])) {
    try {
        $id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : 0;
        $name = trim($_POST["edit_name"] ?? "");
        $category_id = isset($_POST["edit_category_id"]) && $_POST["edit_category_id"] !== "" ? (int)$_POST["edit_category_id"] : null;
        $price = isset($_POST["edit_price"]) ? (float)$_POST["edit_price"] : 0;
        $stock = isset($_POST["edit_stock"]) ? (int)$_POST["edit_stock"] : 0;

        if ($id <= 0) {
            throw new Exception("Invalid product selected.");
        }

        if ($name === "") {
            throw new Exception("Product name is required.");
        }

        if (mb_strlen($name) > 150) {
            throw new Exception("Product name must be 150 characters or fewer.");
        }
        if ($category_id <= 0) {
            throw new Exception("Please select a category for the product.");
        }

        $categoryCheck = $pdo->prepare("SELECT id FROM categories WHERE id = ? LIMIT 1");
        $categoryCheck->execute([$category_id]);

        if (!$categoryCheck->fetch()) {
            throw new Exception("Selected category does not exist.");
        }

        if (!isset($_POST["edit_price"]) || $_POST["edit_price"] === "" || !is_numeric($_POST["edit_price"])) {
            throw new Exception("Product price is required and must be a number.");
        }

        if ($price <= 0) {
            throw new Exception("Product price must be greater than zero.");
        }

        if ($stock < 0) {
            throw new Exception("Stock cannot be negative.");
        }
        $duplicate = $pdo->prepare("SELECT id FROM products WHERE name = ? AND category_id = ? AND id <> ? LIMIT 1");
        $duplicate->execute([$name, $category_id, $id]);

        if ($duplicate->fetch()) {
            throw new Exception("Another product with this name already exists in the selected category.");
        }

        $stmt = $pdo->prepare("SELECT image_path FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $existingProduct = $stmt->fetch();

        if (!$existingProduct) {
            throw new Exception("Product not found.");
        }

        $newImagePath = uploadProductImage($_FILES["edit_product_image"] ?? null);
        $imagePathToSave = $existingProduct["image_path"];

        if ($newImagePath !== "") {
            $imagePathToSave = $newImagePath;

            if (!empty($existingProduct["image_path"]) && strpos($existingProduct["image_path"], "assets/uploads/products/") === 0) {
                $oldImagePath = __DIR__ . "/" . $existingProduct["image_path"];

                if (is_file($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
        }

        $stmt = $pdo->prepare("
            UPDATE products
            SET 
                name = ?,
                category_id = ?,
                price = ?,
                stock_quantity = ?,
                image_path = ?
            WHERE id = ?
        ");
        $stmt->execute([
                $name,
                $category_id,
                $price,
                $stock,
                $imagePathToSave,
                $id
        ]);

        $message = "Product updated successfully.";
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// Handle Delete Product
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_product"])) {
    try {
        $id = isset($_POST["product_id"]) ? (int)$_POST["product_id"] : 0;
        $csrfToken = $_POST["csrf_token"] ?? "";

        if (!hash_equals($_SESSION["csrf_token"], $csrfToken)) {
            throw new Exception("Invalid delete request. Please refresh the page and try again.");
        }

        if ($id <= 0) {
            throw new Exception("Invalid product selected.");
        }

        $stmt = $pdo->prepare("SELECT id, name, image_path FROM products WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $productToDelete = $stmt->fetch();

        if (!$productToDelete) {
            throw new Exception("Product not found.");
        }

        $saleItemCountStmt = $pdo->prepare("SELECT COUNT(*) FROM sale_items WHERE product_id = ?");
        $saleItemCountStmt->execute([$id]);

        if ((int)$saleItemCountStmt->fetchColumn() > 0) {
            throw new Exception("This product cannot be deleted because it already appears in sale history.");
        }

        $pdo->beginTransaction();

        $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        $pdo->commit();

        deleteUploadedProductImage($productToDelete["image_path"] ?? "");

        header("Location: products.php?deleted=1");
        exit;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex->getMessage();
    }
}

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {
    $message = "Product deleted successfully.";
}

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT 
        p.*, 
        c.name AS category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
");
$products = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>
<link rel="stylesheet" href="./assets/sweetalert2-BQV-BGv0.css">

    <div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h5 class="mb-1">Products Management</h5>
            <p class="text-muted mb-0">Add products, upload images, manage stock, and organize products by category.</p>
        </div>

        <a href="pos.php" class="btn btn-primary">
            <i class="ri-shopping-cart-2-line me-1"></i>
            Open POS
        </a>
    </div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Add New Product</h6>
                </div>

                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Product Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. Coca Cola 500ml" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Category</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo (int)$cat["id"]; ?>">
                                        <?php echo e($cat["name"]); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Price - GHS <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Stock Quantity</label>
                            <input type="number" min="0" name="stock" class="form-control" value="0" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Product Picture</label>
                            <input type="file" name="product_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                            <small class="text-muted">JPG, PNG, GIF, WEBP. Max 3MB.</small>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="add_product" class="btn btn-primary">
                                <i class="ri-add-line me-1"></i>
                                Add Product
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="card-title mb-0">Add Category</h6>
                </div>

                <div class="card-body">
                    <form method="POST" class="vstack gap-3">
                        <div>
                            <label class="form-label">Category Name</label>
                            <input type="text" name="category_name" class="form-control" placeholder="e.g. Drinks">
                        </div>

                        <button type="submit" name="add_category" class="btn btn-secondary">
                            <i class="ri-folder-add-line me-1"></i>
                            Save Category
                        </button>
                    </form>

                    <hr>

                    <h6 class="mb-3">Categories</h6>

                    <?php if (empty($categories)): ?>
                        <p class="text-muted mb-0">No categories yet.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($categories as $cat): ?>
                                <span class="badge bg-light text-body border">
                                <?php echo e($cat["name"]); ?>
                            </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card mt-4">
        <div class="card-header d-flex align-items-center justify-content-between gap-3">
            <h6 class="card-title mb-0">Product List</h6>

            <span class="badge bg-light text-muted border" id="productResultCount">
                <?php echo count($products); ?> Products
            </span>
        </div>

        <div class="card-body">
            <?php if (empty($products)): ?>
                <div class="text-center py-5">
                    <div class="avatar size-14 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="ri-shopping-bag-line fs-3"></i>
                    </div>
                    <h6 class="mb-1">No products yet</h6>
                    <p class="text-muted mb-0">Add your first product above.</p>
                </div>
            <?php else: ?>
                <div class="row g-3 align-items-end mb-4">
                    <div class="col-md-5">
                        <label for="productSearchInput" class="form-label">Search Products</label>
                        <div class="position-relative">
                            <i class="ri-search-line position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                            <input type="search"
                                   id="productSearchInput"
                                   class="form-control ps-10"
                                   placeholder="Search by name, category, ID, price, or stock...">
                        </div>
                    </div>

                    <div class="col-md-3">
                        <label for="productCategoryFilter" class="form-label">Category</label>
                        <select id="productCategoryFilter" class="form-control">
                            <option value="all">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo (int)$cat["id"]; ?>">
                                    <?php echo e($cat["name"]); ?>
                                </option>
                            <?php endforeach; ?>
                            <option value="uncategorized">Uncategorized</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="productStockFilter" class="form-label">Stock Status</label>
                        <select id="productStockFilter" class="form-control">
                            <option value="all">All Stock</option>
                            <option value="in_stock">In stock</option>
                            <option value="low_stock">Low stock</option>
                            <option value="out_of_stock">Out of stock</option>
                        </select>
                    </div>

                    <div class="col-md-1">
                        <button type="button" class="btn btn-light border w-100" id="resetProductFilters">
                            Reset
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                        <tr>
                            <th style="width: 70px;">Image</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th style="width: 180px;">Action</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($products as $prod): ?>
                            <?php
                                $imagePath = normalizeImagePath($prod["image_path"] ?? "");
                                $stockQuantity = (int)$prod["stock_quantity"];
                                $stockStatus = "in_stock";

                                if ($stockQuantity <= 0) {
                                    $stockStatus = "out_of_stock";
                                } elseif ($stockQuantity <= 5) {
                                    $stockStatus = "low_stock";
                                }
                            ?>
                            <tr class="product-row"
                                data-search="<?php echo e(strtolower(trim($prod["id"] . " " . $prod["name"] . " " . ($prod["category_name"] ?: "Uncategorized") . " " . $prod["price"] . " " . $prod["stock_quantity"]))); ?>"
                                data-category="<?php echo $prod["category_id"] ? (int)$prod["category_id"] : "uncategorized"; ?>"
                                data-stock="<?php echo e($stockStatus); ?>">
                                <td>
                                    <?php if ($imagePath): ?>
                                        <img src="<?php echo e($imagePath); ?>" alt="<?php echo e($prod["name"]); ?>" style="width: 48px; height: 48px; object-fit: cover; border-radius: 10px;">
                                    <?php else: ?>
                                        <div class="avatar size-12 bg-light rounded d-flex align-items-center justify-content-center">
                                            <i class="ri-image-line text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <h6 class="mb-1 fs-14"><?php echo e($prod["name"]); ?></h6>
                                    <span class="text-muted fs-sm">ID: <?php echo (int)$prod["id"]; ?></span>
                                </td>

                                <td>
                                    <?php echo e($prod["category_name"] ?: "Uncategorized"); ?>
                                </td>

                                <td class="fw-medium">
                                    GHS <?php echo number_format((float)$prod["price"], 2); ?>
                                </td>

                                <td>
                                    <?php if ($stockQuantity <= 0): ?>
                                        <span class="badge bg-danger">Out of stock</span>
                                    <?php elseif ($stockQuantity <= 5): ?>
                                        <span class="badge bg-warning"><?php echo $stockQuantity; ?> Low</span>
                                    <?php else: ?>
                                        <span class="badge bg-success"><?php echo $stockQuantity; ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <div class="d-flex gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-light border view-product-btn"
                                                data-product='<?php echo e(json_encode([
                                                        "id" => (int)$prod["id"],
                                                        "name" => $prod["name"],
                                                        "category" => $prod["category_name"] ?: "Uncategorized",
                                                        "category_id" => $prod["category_id"],
                                                        "price" => (float)$prod["price"],
                                                        "stock" => (int)$prod["stock_quantity"],
                                                        "image_path" => $imagePath
                                                ])); ?>'>
                                            View
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-primary edit-product-btn"
                                                data-product='<?php echo e(json_encode([
                                                        "id" => (int)$prod["id"],
                                                        "name" => $prod["name"],
                                                        "category_id" => $prod["category_id"],
                                                        "price" => (float)$prod["price"],
                                                        "stock" => (int)$prod["stock_quantity"],
                                                        "image_path" => $imagePath
                                                ])); ?>'>
                                            Edit
                                        </button>

                                        <form method="POST"
                                              class="delete-product-form"
                                              data-product-name="<?php echo e($prod["name"]); ?>">
                                            <input type="hidden" name="csrf_token" value="<?php echo e($_SESSION["csrf_token"]); ?>">
                                            <input type="hidden" name="product_id" value="<?php echo (int)$prod["id"]; ?>">
                                            <input type="hidden" name="delete_product" value="1">
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <div class="text-center py-5 d-none" id="noFilteredProducts">
                    <div class="avatar size-14 bg-light text-muted rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                        <i class="ri-filter-off-line fs-3"></i>
                    </div>
                    <h6 class="mb-1">No matching products</h6>
                    <p class="text-muted mb-0">Try changing your search term or filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="modal fade" id="viewProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h6 class="modal-title">Product Details</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="text-center mb-4">
                        <img id="viewProductImage" src="" alt="Product" style="width: 120px; height: 120px; object-fit: cover; border-radius: 16px; display: none;" class="mx-auto">
                        <div id="viewProductNoImage" class="avatar size-20 bg-light rounded d-flex align-items-center justify-content-center mx-auto">
                            <i class="ri-image-line text-muted fs-2"></i>
                        </div>
                    </div>

                    <div class="vstack gap-3">
                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Product Name</span>
                            <strong id="viewProductName"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Category</span>
                            <strong id="viewProductCategory"></strong>
                        </div>

                        <div class="d-flex justify-content-between border-bottom pb-2">
                            <span class="text-muted">Price</span>
                            <strong id="viewProductPrice"></strong>
                        </div>

                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Stock</span>
                            <strong id="viewProductStock"></strong>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="editProductModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="product_id" id="editProductId">

                    <div class="modal-header">
                        <h6 class="modal-title">Edit Product</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label">Product Name <span class="text-danger">*</span></label>
                                <input type="text" name="edit_name" id="editProductName" class="form-control" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Category</label>
                                <select name="edit_category_id" id="editProductCategory" class="form-control" required>
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat["id"]; ?>">
                                            <?php echo e($cat["name"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Price - GHS <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0.01" name="edit_price" id="editProductPrice" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Stock Quantity</label>
                                <input type="number" min="0" name="edit_stock" id="editProductStock" class="form-control" required>
                            </div>

                            <div class="col-md-4">
                                <label class="form-label">Change Picture</label>
                                <input type="file" name="edit_product_image" class="form-control" accept="image/jpeg,image/png,image/gif,image/webp">
                                <small class="text-muted">Leave empty to keep current image.</small>
                            </div>

                            <div class="col-12">
                                <label class="form-label">Current Picture</label>
                                <div>
                                    <img id="editProductCurrentImage" src="" alt="Current Product" style="width: 90px; height: 90px; object-fit: cover; border-radius: 12px; display: none;">
                                    <span id="editProductNoImage" class="text-muted">No image uploaded.</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_product" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="./assets/sweetalert2-BUm3ePnk.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const productSearchInput = document.getElementById('productSearchInput');
            const productCategoryFilter = document.getElementById('productCategoryFilter');
            const productStockFilter = document.getElementById('productStockFilter');
            const resetProductFilters = document.getElementById('resetProductFilters');
            const productResultCount = document.getElementById('productResultCount');
            const noFilteredProducts = document.getElementById('noFilteredProducts');
            const productRows = Array.from(document.querySelectorAll('.product-row'));

            function updateProductFilters() {
                const searchTerm = productSearchInput ? productSearchInput.value.trim().toLowerCase() : '';
                const categoryValue = productCategoryFilter ? productCategoryFilter.value : 'all';
                const stockValue = productStockFilter ? productStockFilter.value : 'all';
                let visibleCount = 0;

                productRows.forEach(function (row) {
                    const matchesSearch = searchTerm === '' || row.dataset.search.includes(searchTerm);
                    const matchesCategory = categoryValue === 'all' || row.dataset.category === categoryValue;
                    const matchesStock = stockValue === 'all' || row.dataset.stock === stockValue;
                    const isVisible = matchesSearch && matchesCategory && matchesStock;

                    row.classList.toggle('d-none', !isVisible);

                    if (isVisible) {
                        visibleCount++;
                    }
                });

                if (productResultCount) {
                    productResultCount.innerText = visibleCount + (visibleCount === 1 ? ' Product' : ' Products');
                }

                if (noFilteredProducts) {
                    noFilteredProducts.classList.toggle('d-none', visibleCount !== 0);
                }
            }

            [productSearchInput, productCategoryFilter, productStockFilter].forEach(function (control) {
                if (control) {
                    control.addEventListener('input', updateProductFilters);
                    control.addEventListener('change', updateProductFilters);
                }
            });

            if (resetProductFilters) {
                resetProductFilters.addEventListener('click', function () {
                    if (productSearchInput) {
                        productSearchInput.value = '';
                    }

                    if (productCategoryFilter) {
                        productCategoryFilter.value = 'all';
                    }

                    if (productStockFilter) {
                        productStockFilter.value = 'all';
                    }

                    updateProductFilters();
                    productSearchInput?.focus();
                });
            }

            document.querySelectorAll('.view-product-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const product = JSON.parse(this.dataset.product);

                    document.getElementById('viewProductName').innerText = product.name;
                    document.getElementById('viewProductCategory').innerText = product.category;
                    document.getElementById('viewProductPrice').innerText = 'GHS ' + Number(product.price).toFixed(2);
                    document.getElementById('viewProductStock').innerText = product.stock;

                    const image = document.getElementById('viewProductImage');
                    const noImage = document.getElementById('viewProductNoImage');

                    if (product.image_path) {
                        image.src = product.image_path || './assets/uploads/placeholder.png';
                        image.style.display = 'block';
                        noImage.style.display = 'none';
                    } else {
                        image.src = '';
                        image.style.display = 'none';
                        noImage.style.display = 'flex';
                    }

                    new bootstrap.Modal(document.getElementById('viewProductModal')).show();
                });
            });

            document.querySelectorAll('.edit-product-btn').forEach(function (button) {
                button.addEventListener('click', function () {
                    const product = JSON.parse(this.dataset.product);

                    document.getElementById('editProductId').value = product.id;
                    document.getElementById('editProductName').value = product.name;
                    document.getElementById('editProductCategory').value = product.category_id || '';
                    document.getElementById('editProductPrice').value = Number(product.price).toFixed(2);
                    document.getElementById('editProductStock').value = product.stock;

                    const image = document.getElementById('editProductCurrentImage');
                    const noImage = document.getElementById('editProductNoImage');

                    if (product.image_path) {
                        image.src = product.image_path || './assets/uploads/placeholder.png';
                        image.style.display = 'inline-block';
                        noImage.style.display = 'none';
                    } else {
                        image.src = '';
                        image.style.display = 'none';
                        noImage.style.display = 'inline';
                    }

                    new bootstrap.Modal(document.getElementById('editProductModal')).show();
                });
            });

            document.querySelectorAll('.delete-product-form').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();

                    const productName = this.dataset.productName || 'this product';

                    if (!window.Swal) {
                        if (window.confirm('Delete ' + productName + '?')) {
                            this.submit();
                        }

                        return;
                    }

                    Swal.fire({
                        title: 'Delete product?',
                        text: 'You are about to delete "' + productName + '". This cannot be undone.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete',
                        cancelButtonText: 'Cancel',
                        reverseButtons: true,
                        focusCancel: true,
                        customClass: {
                            confirmButton: 'btn btn-danger',
                            cancelButton: 'btn btn-light border me-2'
                        },
                        buttonsStyling: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>

<?php include "includes/footer.php"; ?>
