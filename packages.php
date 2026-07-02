<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "includes/table_packages.php";

$message = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "GHS " . number_format((float)$amount, 2);
}

function redirectPackages($query = "") {
    header("Location: packages.php" . ($query ? "?" . $query : ""));
    exit;
}

function syncPackageProduct(PDO $pdo, string $name, float $price, ?int $productId): int
{
    $categoryId = ensureCategory($pdo, "Table Packages");

    if ($productId && $productId > 0) {
        $stmt = $pdo->prepare("UPDATE products SET name = ?, category_id = ?, price = ?, stock_quantity = GREATEST(stock_quantity, 9999) WHERE id = ?");
        $stmt->execute([$name, $categoryId, $price, $productId]);
        return $productId;
    }

    return ensureProduct($pdo, $name, $categoryId, $price);
}

function savePackageBuilderItems(PDO $pdo, int $packageId, array $rows): void
{
    $productStmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ? LIMIT 1");

    $delete = $pdo->prepare("DELETE FROM table_package_items WHERE package_id = ?");
    $delete->execute([$packageId]);

    $insert = $pdo->prepare("
        INSERT INTO table_package_items
            (package_id, product_id, item_name, item_type, quantity, unit_cost, display_order)
        VALUES
            (?, ?, ?, ?, ?, ?, ?)
    ");

    $displayOrder = 1;

    foreach ($rows as $row) {
        $productId = isset($row["product_id"]) ? (int)$row["product_id"] : 0;
        $quantity = max(1, (int)($row["quantity"] ?? 1));
        $type = strtolower(trim((string)($row["item_type"] ?? "regular")));
        $customName = trim((string)($row["custom_name"] ?? ""));
        $customCost = isset($row["unit_cost"]) ? (float)$row["unit_cost"] : 0;

        if (!in_array($type, ["premium", "regular", "other"], true)) {
            $type = "regular";
        }

        if ($productId > 0) {
            $productStmt->execute([$productId]);
            $product = $productStmt->fetch();

            if (!$product) {
                throw new Exception("One selected product does not exist.");
            }

            $insert->execute([
                $packageId,
                (int)$product["id"],
                $product["name"],
                $type,
                $quantity,
                (float)$product["price"],
                $displayOrder,
            ]);
        } else {
            if ($customName === "") {
                continue;
            }

            $insert->execute([
                $packageId,
                null,
                $customName,
                $type,
                $quantity,
                max(0, $customCost),
                $displayOrder,
            ]);
        }

        $displayOrder++;
    }

    if ($displayOrder === 1) {
        throw new Exception("Add at least one product or custom package item.");
    }
}

function readPackageBuilderRows(): array
{
    $productIds = $_POST["item_product_id"] ?? [];
    $customNames = $_POST["item_custom_name"] ?? [];
    $types = $_POST["item_type"] ?? [];
    $quantities = $_POST["item_quantity"] ?? [];
    $unitCosts = $_POST["item_unit_cost"] ?? [];
    $count = max(count($productIds), count($customNames), count($types), count($quantities), count($unitCosts));
    $rows = [];

    for ($i = 0; $i < $count; $i++) {
        $rows[] = [
            "product_id" => $productIds[$i] ?? 0,
            "custom_name" => $customNames[$i] ?? "",
            "item_type" => $types[$i] ?? "regular",
            "quantity" => $quantities[$i] ?? 1,
            "unit_cost" => $unitCosts[$i] ?? 0,
        ];
    }

    return $rows;
}

function packageItemTotal(array $items): float
{
    $total = 0;

    foreach ($items as $item) {
        $total += (float)$item["unit_cost"] * (int)$item["quantity"];
    }

    return $total;
}

try {
    ensureTablePackageSchema($pdo);
} catch (Exception $ex) {
    $error = "Package setup failed: " . $ex->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "create_package" || $action === "update_package") {
            $packageId = (int)($_POST["package_id"] ?? 0);
            $name = trim($_POST["package_name"] ?? "");
            $price = (float)($_POST["package_price"] ?? 0);
            $capacity = max(1, (int)($_POST["package_capacity"] ?? 1));
            $tier = ($_POST["package_tier"] ?? "VIP") === "VVIP" ? "VVIP" : "VIP";
            $isActive = isset($_POST["package_active"]) ? 1 : 0;
            $rows = readPackageBuilderRows();

            if ($name === "") {
                throw new Exception("Package name is required.");
            }

            if ($price <= 0) {
                throw new Exception("Package selling price must be greater than zero.");
            }

            $duplicateSql = $action === "create_package"
                ? "SELECT id FROM table_packages WHERE name = ? LIMIT 1"
                : "SELECT id FROM table_packages WHERE name = ? AND id <> ? LIMIT 1";
            $duplicate = $pdo->prepare($duplicateSql);
            $duplicate->execute($action === "create_package" ? [$name] : [$name, $packageId]);

            if ($duplicate->fetch()) {
                throw new Exception("A package with this name already exists.");
            }

            $pdo->beginTransaction();

            if ($action === "create_package") {
                $productId = syncPackageProduct($pdo, $name, $price, null);
                $stmt = $pdo->prepare("
                    INSERT INTO table_packages (name, price, capacity, tier, product_id, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$name, $price, $capacity, $tier, $productId, $isActive]);
                $packageId = (int)$pdo->lastInsertId();
            } else {
                if ($packageId <= 0) {
                    throw new Exception("Invalid package selected.");
                }

                $stmt = $pdo->prepare("SELECT product_id FROM table_packages WHERE id = ? LIMIT 1");
                $stmt->execute([$packageId]);
                $existing = $stmt->fetch();

                if (!$existing) {
                    throw new Exception("Package not found.");
                }

                $productId = syncPackageProduct($pdo, $name, $price, $existing["product_id"] ? (int)$existing["product_id"] : null);
                $update = $pdo->prepare("
                    UPDATE table_packages
                    SET name = ?, price = ?, capacity = ?, tier = ?, product_id = ?, is_active = ?
                    WHERE id = ?
                ");
                $update->execute([$name, $price, $capacity, $tier, $productId, $isActive, $packageId]);
            }

            savePackageBuilderItems($pdo, $packageId, $rows);

            $pdo->commit();
            redirectPackages($action === "create_package" ? "created=1" : "updated=1");
        }

        if ($action === "delete_package") {
            $packageId = (int)($_POST["package_id"] ?? 0);

            if ($packageId <= 0) {
                throw new Exception("Invalid package selected.");
            }

            $usage = $pdo->prepare("SELECT COUNT(*) FROM table_bookings WHERE package_id = ?");
            $usage->execute([$packageId]);

            if ((int)$usage->fetchColumn() > 0) {
                $deactivate = $pdo->prepare("UPDATE table_packages SET is_active = 0 WHERE id = ?");
                $deactivate->execute([$packageId]);
                redirectPackages("deactivated=1");
            }

            $delete = $pdo->prepare("DELETE FROM table_packages WHERE id = ?");
            $delete->execute([$packageId]);
            redirectPackages("deleted=1");
        }
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $ex->getMessage();
    }
}

foreach ([
    "created" => "Package created.",
    "updated" => "Package updated.",
    "deleted" => "Package deleted.",
    "deactivated" => "Package has booking history, so it was deactivated instead of permanently deleted.",
] as $key => $text) {
    if (isset($_GET[$key])) {
        $message = $text;
    }
}

$products = $pdo->query("
    SELECT p.id, p.name, p.price, p.stock_quantity, c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON c.id = p.category_id
    WHERE c.name IS NULL OR c.name <> 'Table Packages'
    ORDER BY p.name ASC
")->fetchAll();

$packages = $pdo->query("SELECT * FROM table_packages ORDER BY is_active DESC, price ASC")->fetchAll();
$packageItemsStmt = $pdo->query("
    SELECT tpi.*, p.name AS product_name, p.price AS current_product_price
    FROM table_package_items tpi
    LEFT JOIN products p ON p.id = tpi.product_id
    ORDER BY tpi.package_id ASC, tpi.display_order ASC
");
$packageItems = [];
foreach ($packageItemsStmt->fetchAll() as $item) {
    $packageItems[(int)$item["package_id"]][] = $item;
}

$productsForJs = array_map(function ($product) {
    return [
        "id" => (int)$product["id"],
        "name" => $product["name"],
        "price" => (float)$product["price"],
        "category" => $product["category_name"] ?: "Uncategorized",
    ];
}, $products);
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<style>
    .package-card {
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #fff8f3 100%);
        box-shadow: 0 18px 45px rgba(15, 23, 42, .08);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .package-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 24px 60px rgba(15, 23, 42, .12);
    }

    .package-card::before {
        content: "";
        position: absolute;
        inset: 0 0 auto 0;
        height: 5px;
        background: linear-gradient(90deg, #ff762d, #f59e0b);
    }

    .package-card.inactive {
        background: #ffffff;
        opacity: .78;
    }

    .package-card-header {
        display: flex;
        justify-content: space-between;
        gap: 16px;
        padding: 20px 20px 14px;
    }

    .package-icon {
        width: 48px;
        height: 48px;
        border-radius: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ff762d;
        background: rgba(255, 118, 45, .12);
        font-size: 22px;
        flex: 0 0 auto;
    }

    .package-price {
        font-size: 24px;
        line-height: 1;
        font-weight: 800;
        color: #111827;
    }

    .package-metric {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 16px;
        padding: 12px;
        background: rgba(255, 255, 255, .75);
    }

    .package-metric span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .package-metric strong,
    .package-metric h6 {
        margin: 0;
        font-size: 15px;
    }

    .package-builder-panel {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        background: #ffffff;
        padding: 16px;
    }

    .package-row {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        padding: 10px;
        background: #fafafa;
    }

    .package-items-preview {
        border-top: 1px solid rgba(15, 23, 42, .08);
        padding: 0 20px 16px;
    }

    .package-preview-item {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 64px 96px 104px;
        gap: 10px;
        align-items: center;
        padding: 10px 0;
        border-bottom: 1px solid rgba(15, 23, 42, .06);
        font-size: 13px;
    }

    .package-preview-item:last-child {
        border-bottom: 0;
    }

    .package-preview-head {
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    @media (max-width: 767px) {
        .package-preview-item {
            grid-template-columns: 1fr 52px;
        }

        .package-preview-item .cost,
        .package-preview-item .total {
            display: none;
        }
    }
</style>

<div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
    <div>
        <h5 class="mb-1">Packages</h5>
        <p class="text-muted mb-0">Build table packages from database products, see item total, and set your selling price for profit.</p>
    </div>

    <a href="tables.php" class="btn btn-primary">
        <i class="ri-vip-crown-line me-1"></i>
        Back to Club Tables
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-header">
        <h6 class="card-title mb-0">Create Package</h6>
    </div>
    <div class="card-body">
        <form method="POST" class="package-form" data-package-form>
            <input type="hidden" name="action" value="create_package">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Package Name</label>
                    <input type="text" name="package_name" class="form-control" placeholder="e.g. 10K Package" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">People</label>
                    <input type="number" name="package_capacity" class="form-control" min="1" value="5" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Tier</label>
                    <select name="package_tier" class="form-control">
                        <option value="VIP">VIP</option>
                        <option value="VVIP">VVIP</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Selling Price</label>
                    <input type="number" name="package_price" class="form-control package-selling-price" min="1" step="0.01" placeholder="10000" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <div class="form-check mb-2">
                        <input type="checkbox" name="package_active" id="createPackageActive" class="form-check-input" checked>
                        <label for="createPackageActive" class="form-check-label">Active</label>
                    </div>
                </div>
            </div>

            <div class="border rounded-3 p-3 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Package Items</h6>
                    <button type="button" class="btn btn-sm btn-light border add-package-row">Add Item</button>
                </div>
                <div class="package-items-list"></div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light">
                        <span class="text-muted">Items Total</span>
                        <h6 class="mb-0 package-cost-total">GHS 0.00</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light">
                        <span class="text-muted">Package Price</span>
                        <h6 class="mb-0 package-selling-total">GHS 0.00</h6>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="border rounded p-3 bg-light">
                        <span class="text-muted">Expected Profit</span>
                        <h6 class="mb-0 package-profit-total">GHS 0.00</h6>
                    </div>
                </div>
            </div>

            <button type="submit" class="btn btn-primary mt-4">Create Package</button>
        </form>
    </div>
</div>

<div class="card mt-4 border-0 shadow-sm">
    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
        <div>
            <h6 class="card-title mb-0">Existing Packages</h6>
            <p class="text-muted mb-0 fs-sm">Review profitability and edit package contents from one clean view.</p>
        </div>
        <span class="badge bg-primary-subtle text-primary border"><?php echo count($packages); ?> packages</span>
    </div>
    <div class="card-body">
        <div class="row g-4">
            <?php foreach ($packages as $package): ?>
                <?php
                    $packageId = (int)$package["id"];
                    $items = $packageItems[$packageId] ?? [];
                    $itemTotal = packageItemTotal($items);
                    $profit = (float)$package["price"] - $itemTotal;
                    $updateFormId = "packageForm" . $packageId;
                    $deleteFormId = "deletePackageForm" . $packageId;
                ?>
                <div class="col-xl-6">
                    <div class="package-card h-100 <?php echo (int)$package["is_active"] === 0 ? "inactive" : ""; ?>">
                        <div class="package-card-header">
                                <div class="d-flex gap-3">
                                    <div class="package-icon">
                                        <i class="ri-gift-line"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-1"><?php echo e($package["name"]); ?></h5>
                                        <div class="d-flex flex-wrap gap-1">
                                            <span class="badge bg-light text-muted border"><?php echo (int)$package["capacity"]; ?> people</span>
                                            <?php if ($package["tier"] === "VVIP"): ?>
                                                <span class="badge bg-warning">VVIP</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary-subtle text-primary">VIP</span>
                                            <?php endif; ?>
                                            <?php if ((int)$package["is_active"] === 0): ?>
                                                <span class="badge bg-secondary">Inactive</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Active</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <div class="package-price"><?php echo money($package["price"]); ?></div>
                                    <div class="<?php echo $profit >= 0 ? "text-success" : "text-danger"; ?> fs-sm fw-semibold">
                                        Profit: <?php echo money($profit); ?>
                                    </div>
                                </div>
                        </div>

                        <div class="package-items-preview">
                                <div class="package-preview-item package-preview-head">
                                    <span>Product Name</span>
                                    <span>Qty</span>
                                    <span class="cost">Cost</span>
                                    <span class="total text-end">Total</span>
                                </div>
                                <?php if (empty($items)): ?>
                                    <p class="text-muted mb-0 py-3">No items added yet.</p>
                                <?php else: ?>
                                    <?php foreach ($items as $previewItem): ?>
                                        <?php $lineTotal = (float)$previewItem["unit_cost"] * (int)$previewItem["quantity"]; ?>
                                        <div class="package-preview-item">
                                            <span class="fw-medium text-truncate"><?php echo e($previewItem["item_name"]); ?></span>
                                            <span><?php echo (int)$previewItem["quantity"]; ?></span>
                                            <span class="cost"><?php echo money($previewItem["unit_cost"]); ?></span>
                                            <span class="total text-end fw-semibold"><?php echo money($lineTotal); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                        </div>

                        <div class="modal fade" id="editPackageModal<?php echo $packageId; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-xl modal-dialog-scrollable modal-dialog-centered">
                                <form method="POST" class="package-form modal-content" id="<?php echo e($updateFormId); ?>" data-package-form>
                                    <input type="hidden" name="action" value="update_package">
                                    <input type="hidden" name="package_id" value="<?php echo $packageId; ?>">
                                        <div class="modal-header">
                                            <div>
                                                <h6 class="modal-title mb-1">Edit <?php echo e($package["name"]); ?></h6>
                                                <p class="text-muted mb-0 fs-sm">Update package details, products, quantities, cost, and selling price.</p>
                                            </div>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fs-sm">Name</label>
                                    <input type="text" name="package_name" class="form-control form-control-sm" value="<?php echo e($package["name"]); ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">People</label>
                                    <input type="number" name="package_capacity" class="form-control form-control-sm" min="1" value="<?php echo (int)$package["capacity"]; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fs-sm">Tier</label>
                                    <select name="package_tier" class="form-control form-control-sm">
                                        <option value="VIP" <?php echo $package["tier"] === "VIP" ? "selected" : ""; ?>>VIP</option>
                                        <option value="VVIP" <?php echo $package["tier"] === "VVIP" ? "selected" : ""; ?>>VVIP</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fs-sm">Selling Price</label>
                                    <input type="number" name="package_price" class="form-control form-control-sm package-selling-price" min="1" step="0.01" value="<?php echo e($package["price"]); ?>" required>
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <div class="form-check mb-2">
                                        <input type="checkbox" name="package_active" id="activePackage<?php echo $packageId; ?>" class="form-check-input" <?php echo (int)$package["is_active"] === 1 ? "checked" : ""; ?>>
                                        <label for="activePackage<?php echo $packageId; ?>" class="form-check-label">Active for booking</label>
                                    </div>
                                </div>
                            </div>

                            <div class="package-builder-panel mt-3">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h6 class="mb-0">Package Items</h6>
                                        <span class="text-muted fs-sm"><?php echo count($items); ?> item row(s)</span>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-primary add-package-row">
                                        <i class="ri-add-line me-1"></i>Add Item
                                    </button>
                                </div>
                                <div class="package-items-list">
                                    <?php foreach ($items as $item): ?>
                                        <div class="package-row row g-2 align-items-end mb-2">
                                            <div class="col-md-5">
                                                <label class="form-label fs-sm">Product Name</label>
                                                <select name="item_product_id[]" class="form-control form-control-sm package-product-select">
                                                    <option value="">Custom / not in product list</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?php echo (int)$product["id"]; ?>"
                                                                data-price="<?php echo e($product["price"]); ?>"
                                                                <?php echo (int)$item["product_id"] === (int)$product["id"] ? "selected" : ""; ?>>
                                                            <?php echo e($product["name"]); ?> — <?php echo money($product["price"]); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                                <input type="text" name="item_custom_name[]" class="form-control form-control-sm package-custom-name mt-2" value="<?php echo e($item["product_id"] ? "" : $item["item_name"]); ?>" placeholder="Custom item name">
                                                <input type="hidden" name="item_type[]" value="<?php echo e($item["item_type"] ?: "regular"); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fs-sm">Qty</label>
                                                <input type="number" name="item_quantity[]" class="form-control form-control-sm package-quantity" min="1" value="<?php echo (int)$item["quantity"]; ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fs-sm">Cost</label>
                                                <input type="number" name="item_unit_cost[]" class="form-control form-control-sm package-unit-cost" min="0" step="0.01" value="<?php echo e($item["unit_cost"]); ?>">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label fs-sm">Total</label>
                                                <div class="form-control form-control-sm bg-light package-line-total">GHS 0.00</div>
                                            </div>
                                            <div class="col-md-1">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-package-row">×</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <div class="row g-3 mt-3">
                                <div class="col-md-4">
                                    <div class="package-metric">
                                        <span>Items Total</span>
                                        <h6 class="package-cost-total"><?php echo money($itemTotal); ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="package-metric">
                                        <span>Package Price</span>
                                        <h6 class="package-selling-total"><?php echo money($package["price"]); ?></h6>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="package-metric">
                                        <span>Expected Profit</span>
                                        <h6 class="package-profit-total <?php echo $profit >= 0 ? "text-success" : "text-danger"; ?>"><?php echo money($profit); ?></h6>
                                    </div>
                                </div>
                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary">Save Package</button>
                                        </div>
                                </form>
                            </div>
                        </div>

                        <form method="POST" id="<?php echo e($deleteFormId); ?>" onsubmit="return confirm('Delete <?php echo e($package["name"]); ?>? If it has booking history, it will be deactivated instead.');">
                            <input type="hidden" name="action" value="delete_package">
                            <input type="hidden" name="package_id" value="<?php echo $packageId; ?>">
                        </form>

                        <div class="d-flex flex-wrap gap-2 px-3 px-md-4 pb-4">
                            <button type="button" class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editPackageModal<?php echo $packageId; ?>">Edit</button>
                            <button type="submit" form="<?php echo e($deleteFormId); ?>" class="btn btn-sm btn-outline-danger">Delete</button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<template id="packageRowTemplate">
    <div class="package-row row g-2 align-items-end mb-2">
        <div class="col-md-5">
            <label class="form-label fs-sm">Product Name</label>
            <select name="item_product_id[]" class="form-control form-control-sm package-product-select">
                <option value="">Custom / not in product list</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?php echo (int)$product["id"]; ?>" data-price="<?php echo e($product["price"]); ?>">
                        <?php echo e($product["name"]); ?> — <?php echo money($product["price"]); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="text" name="item_custom_name[]" class="form-control form-control-sm package-custom-name mt-2" placeholder="Custom item name">
            <input type="hidden" name="item_type[]" value="regular">
        </div>
        <div class="col-md-2">
            <label class="form-label fs-sm">Qty</label>
            <input type="number" name="item_quantity[]" class="form-control form-control-sm package-quantity" min="1" value="1">
        </div>
        <div class="col-md-2">
            <label class="form-label fs-sm">Cost</label>
            <input type="number" name="item_unit_cost[]" class="form-control form-control-sm package-unit-cost" min="0" step="0.01" value="0">
        </div>
        <div class="col-md-2">
            <label class="form-label fs-sm">Total</label>
            <div class="form-control form-control-sm bg-light package-line-total">GHS 0.00</div>
        </div>
        <div class="col-md-1">
            <button type="button" class="btn btn-sm btn-outline-danger remove-package-row">×</button>
        </div>
    </div>
</template>

<script>
    window.PACKAGE_PRODUCTS = <?php echo json_encode($productsForJs); ?>;

    function packageMoney(amount) {
        return 'GHS ' + Number(amount || 0).toFixed(2);
    }

    function updatePackageTotals(form) {
        let itemTotal = 0;

        form.querySelectorAll('.package-row').forEach(function (row) {
            const productSelect = row.querySelector('.package-product-select');
            const quantityInput = row.querySelector('.package-quantity');
            const unitCostInput = row.querySelector('.package-unit-cost');
            const lineTotalEl = row.querySelector('.package-line-total');
            const quantity = Math.max(1, Number(quantityInput.value || 1));
            const selectedOption = productSelect.options[productSelect.selectedIndex];

            if (productSelect.value !== '' && selectedOption) {
                unitCostInput.value = Number(selectedOption.dataset.price || 0).toFixed(2);
                unitCostInput.readOnly = true;
            } else {
                unitCostInput.readOnly = false;
            }

            const lineTotal = quantity * Number(unitCostInput.value || 0);
            itemTotal += lineTotal;

            if (lineTotalEl) {
                lineTotalEl.innerText = packageMoney(lineTotal);
            }
        });

        const selling = Number(form.querySelector('.package-selling-price')?.value || 0);
        const profit = selling - itemTotal;

        form.querySelector('.package-cost-total').innerText = packageMoney(itemTotal);
        form.querySelector('.package-selling-total').innerText = packageMoney(selling);
        form.querySelector('.package-profit-total').innerText = packageMoney(profit);
        form.querySelector('.package-profit-total').classList.toggle('text-danger', profit < 0);
        form.querySelector('.package-profit-total').classList.toggle('text-success', profit >= 0);
    }

    function addPackageRow(form) {
        const template = document.getElementById('packageRowTemplate');
        const list = form.querySelector('.package-items-list');
        list.appendChild(template.content.cloneNode(true));
        updatePackageTotals(form);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.modal[id^="editPackageModal"]').forEach(function (modal) {
            document.body.appendChild(modal);
        });

        document.querySelectorAll('[data-package-form]').forEach(function (form) {
            if (form.querySelectorAll('.package-row').length === 0) {
                addPackageRow(form);
            }

            updatePackageTotals(form);

            form.addEventListener('input', function () {
                updatePackageTotals(form);
            });

            form.addEventListener('change', function () {
                updatePackageTotals(form);
            });

            form.querySelector('.add-package-row')?.addEventListener('click', function () {
                addPackageRow(form);
            });

            form.addEventListener('click', function (event) {
                if (!event.target.classList.contains('remove-package-row')) {
                    return;
                }

                const row = event.target.closest('.package-row');
                if (row) {
                    row.remove();
                    updatePackageTotals(form);
                }
            });
        });
    });
</script>

<?php include "includes/footer.php"; ?>
