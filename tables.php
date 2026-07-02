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

function redirectTables($query = "") {
    header("Location: tables.php" . ($query ? "?" . $query : ""));
    exit;
}

function inferPackageItemType(string $name): string
{
    $upper = mb_strtoupper($name);

    foreach (["HENNESSY", "MOËT", "MOET", "CHAMPAGNE", "VEUVE", "TEQUILA"] as $keyword) {
        if (strpos($upper, $keyword) !== false) {
            return "premium";
        }
    }

    foreach (["ENERGY", "WATER", "AQUA"] as $keyword) {
        if (strpos($upper, $keyword) !== false) {
            return "regular";
        }
    }

    return strpos($upper, "PASS") !== false ? "other" : "regular";
}

function parsePackageItems(string $rawItems): array
{
    $items = [];
    $lines = preg_split("/\r\n|\r|\n/", $rawItems);

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === "") {
            continue;
        }

        $quantity = 1;
        $type = inferPackageItemType($line);
        $name = $line;

        $parts = array_map("trim", explode("|", $line));

        if (count($parts) >= 3) {
            $quantity = max(1, (int)$parts[0]);
            $type = strtolower($parts[1]);
            $name = implode(" ", array_slice($parts, 2));
            $name = trim($quantity . " " . $name);
        } elseif (preg_match("/^(\d+)\s+(.+)$/", $line, $matches)) {
            $quantity = max(1, (int)$matches[1]);
        }

        if (!in_array($type, ["premium", "regular", "other"], true)) {
            $type = inferPackageItemType($name);
        }

        $items[] = [
            "name" => $name,
            "type" => $type,
            "quantity" => $quantity,
        ];
    }

    if (empty($items)) {
        throw new Exception("At least one package item is required.");
    }

    return $items;
}

function savePackageItems(PDO $pdo, int $packageId, string $rawItems): void
{
    $items = parsePackageItems($rawItems);

    $delete = $pdo->prepare("DELETE FROM table_package_items WHERE package_id = ?");
    $delete->execute([$packageId]);

    $insert = $pdo->prepare("
        INSERT INTO table_package_items (package_id, item_name, item_type, quantity, display_order)
        VALUES (?, ?, ?, ?, ?)
    ");

    foreach ($items as $index => $item) {
        $insert->execute([
            $packageId,
            $item["name"],
            $item["type"],
            $item["quantity"],
            $index + 1,
        ]);
    }
}

function packageItemsToTextarea(array $items): string
{
    $lines = [];

    foreach ($items as $item) {
        $lines[] = (int)$item["quantity"] . "|" . $item["item_type"] . "|" . preg_replace("/^\d+\s+/", "", $item["item_name"]);
    }

    return implode("\n", $lines);
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

try {
    ensureTablePackageSchema($pdo);
} catch (Exception $ex) {
    $error = "Table package setup failed: " . $ex->getMessage();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";

    try {
        if ($action === "create_table") {
            $name = trim($_POST["name"] ?? "");
            $status = ($_POST["status"] ?? "Active") === "Inactive" ? "Inactive" : "Active";

            if ($name === "") {
                throw new Exception("Table name is required.");
            }

            $stmt = $pdo->prepare("INSERT INTO restaurant_tables (name, capacity, status) VALUES (?, ?, ?)");
            $stmt->execute([$name, 1, $status]);
            redirectTables("created=1");
        }

        if ($action === "update_table") {
            $id = (int)($_POST["table_id"] ?? 0);
            $name = trim($_POST["name"] ?? "");
            $status = ($_POST["status"] ?? "Active") === "Inactive" ? "Inactive" : "Active";

            if ($id <= 0 || $name === "") {
                throw new Exception("Valid table and name are required.");
            }

            $stmt = $pdo->prepare("UPDATE restaurant_tables SET name = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $status, $id]);
            redirectTables("updated=1");
        }

        if ($action === "delete_table") {
            $id = (int)($_POST["table_id"] ?? 0);

            if ($id <= 0) {
                throw new Exception("Invalid table selected.");
            }

            $activeBooking = $pdo->prepare("SELECT COUNT(*) FROM table_bookings WHERE table_id = ? AND status = 'open'");
            $activeBooking->execute([$id]);

            if ((int)$activeBooking->fetchColumn() > 0) {
                throw new Exception("This table has an open booking. Close or cancel it before deleting.");
            }

            $pdo->beginTransaction();

            $unlinkSales = $pdo->prepare("UPDATE sales SET table_id = NULL WHERE table_id = ?");
            $unlinkSales->execute([$id]);

            $stmt = $pdo->prepare("DELETE FROM restaurant_tables WHERE id = ?");
            $stmt->execute([$id]);

            if ($stmt->rowCount() === 0) {
                throw new Exception("Table not found.");
            }

            $pdo->commit();
            redirectTables("deleted=1");
        }

        if ($action === "create_package") {
            $name = trim($_POST["package_name"] ?? "");
            $price = (float)($_POST["package_price"] ?? 0);
            $capacity = max(1, (int)($_POST["package_capacity"] ?? 1));
            $tier = ($_POST["package_tier"] ?? "VIP") === "VVIP" ? "VVIP" : "VIP";
            $isActive = isset($_POST["package_active"]) ? 1 : 0;
            $rawItems = trim($_POST["package_items"] ?? "");

            if ($name === "") {
                throw new Exception("Package name is required.");
            }

            if ($price <= 0) {
                throw new Exception("Package price must be greater than zero.");
            }

            $duplicate = $pdo->prepare("SELECT id FROM table_packages WHERE name = ? LIMIT 1");
            $duplicate->execute([$name]);

            if ($duplicate->fetch()) {
                throw new Exception("A package with this name already exists.");
            }

            $pdo->beginTransaction();

            $productId = syncPackageProduct($pdo, $name, $price, null);

            $stmt = $pdo->prepare("
                INSERT INTO table_packages (name, price, capacity, tier, product_id, is_active)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$name, $price, $capacity, $tier, $productId, $isActive]);
            $packageId = (int)$pdo->lastInsertId();

            savePackageItems($pdo, $packageId, $rawItems);

            $pdo->commit();
            redirectTables("package_created=1");
        }

        if ($action === "update_package") {
            $packageId = (int)($_POST["package_id"] ?? 0);
            $name = trim($_POST["package_name"] ?? "");
            $price = (float)($_POST["package_price"] ?? 0);
            $capacity = max(1, (int)($_POST["package_capacity"] ?? 1));
            $tier = ($_POST["package_tier"] ?? "VIP") === "VVIP" ? "VVIP" : "VIP";
            $isActive = isset($_POST["package_active"]) ? 1 : 0;
            $rawItems = trim($_POST["package_items"] ?? "");

            if ($packageId <= 0 || $name === "") {
                throw new Exception("Valid package and package name are required.");
            }

            if ($price <= 0) {
                throw new Exception("Package price must be greater than zero.");
            }

            $stmt = $pdo->prepare("SELECT product_id FROM table_packages WHERE id = ? LIMIT 1");
            $stmt->execute([$packageId]);
            $package = $stmt->fetch();

            if (!$package) {
                throw new Exception("Package not found.");
            }

            $duplicate = $pdo->prepare("SELECT id FROM table_packages WHERE name = ? AND id <> ? LIMIT 1");
            $duplicate->execute([$name, $packageId]);

            if ($duplicate->fetch()) {
                throw new Exception("Another package with this name already exists.");
            }

            $pdo->beginTransaction();

            $productId = syncPackageProduct($pdo, $name, $price, $package["product_id"] ? (int)$package["product_id"] : null);

            $update = $pdo->prepare("
                UPDATE table_packages
                SET name = ?, price = ?, capacity = ?, tier = ?, product_id = ?, is_active = ?
                WHERE id = ?
            ");
            $update->execute([$name, $price, $capacity, $tier, $productId, $isActive, $packageId]);

            savePackageItems($pdo, $packageId, $rawItems);

            $pdo->commit();
            redirectTables("package_updated=1");
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
                redirectTables("package_deactivated=1");
            }

            $delete = $pdo->prepare("DELETE FROM table_packages WHERE id = ?");
            $delete->execute([$packageId]);
            redirectTables("package_deleted=1");
        }

        if ($action === "book_table") {
            $tableId = (int)($_POST["table_id"] ?? 0);
            $packageId = (int)($_POST["package_id"] ?? 0);
            $customerName = trim($_POST["customer_name"] ?? "");
            $customerContact = trim($_POST["customer_contact"] ?? "");

            if ($tableId <= 0 || $packageId <= 0) {
                throw new Exception("Select a valid table and package.");
            }

            if ($customerName === "") {
                throw new Exception("Customer name is required.");
            }

            $openCheck = $pdo->prepare("SELECT COUNT(*) FROM table_bookings WHERE table_id = ? AND status = 'open'");
            $openCheck->execute([$tableId]);

            if ((int)$openCheck->fetchColumn() > 0) {
                throw new Exception("This table already has an open booking.");
            }

            $pdo->beginTransaction();

            $saleId = createPackageSale($pdo, $tableId, $packageId, (int)$_SESSION["user_id"]);

            $bookingStmt = $pdo->prepare("
                INSERT INTO table_bookings
                    (table_id, package_id, sale_id, customer_name, customer_contact, created_by)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $bookingStmt->execute([
                $tableId,
                $packageId,
                $saleId,
                $customerName,
                $customerContact,
                (int)$_SESSION["user_id"]
            ]);

            $reserveStmt = $pdo->prepare("
                UPDATE restaurant_tables
                SET reserved_by = ?, customer_contact = ?, reserved_at = NOW(), serving_user_id = ?, serve_status = 'serving'
                WHERE id = ?
            ");
            $reserveStmt->execute([$customerName, $customerContact, (int)$_SESSION["user_id"], $tableId]);

            $pdo->commit();
            redirectTables("booked=1&receipt=" . $saleId);
        }

        if ($action === "close_booking") {
            $bookingId = (int)($_POST["booking_id"] ?? 0);

            if ($bookingId <= 0) {
                throw new Exception("Invalid booking selected.");
            }

            $stmt = $pdo->prepare("SELECT table_id, sale_id, booked_at FROM table_bookings WHERE id = ? AND status = 'open' LIMIT 1");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();

            if (!$booking) {
                throw new Exception("Open booking not found.");
            }

            $pdo->beginTransaction();

            // Snapshot every sale tied to this booking (the package sale plus every
            // drink round added while serving) before we unlink them, so the final
            // combined bill can still be printed after the table is closed.
            $saleIdsStmt = $pdo->prepare("
                SELECT id
                FROM sales
                WHERE table_id = ?
                  AND created_at >= ?
                ORDER BY created_at ASC
            ");
            $saleIdsStmt->execute([
                (int)$booking["table_id"],
                $booking["booked_at"]
            ]);
            $closedSaleIds = array_map("intval", array_column($saleIdsStmt->fetchAll(), "id"));

            $close = $pdo->prepare("UPDATE table_bookings SET status = 'closed', closed_at = NOW() WHERE id = ?");
            $close->execute([$bookingId]);

            $unlinkSales = $pdo->prepare("
                UPDATE sales
                SET table_id = NULL
                WHERE table_id = ?
                  AND created_at >= ?
            ");
            $unlinkSales->execute([
                (int)$booking["table_id"],
                $booking["booked_at"]
            ]);

            $table = $pdo->prepare("
                UPDATE restaurant_tables
                SET reserved_by = NULL,
                    customer_contact = NULL,
                    reserved_at = NULL,
                    serving_user_id = NULL,
                    serve_status = 'none'
                WHERE id = ?
            ");
            $table->execute([(int)$booking["table_id"]]);

            $pdo->commit();
            redirectTables("closed=1&receipt_ids=" . implode(",", $closedSaleIds));
        }
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error = $ex->getMessage();
    }
}

foreach ([
    "created" => "Table created.",
    "updated" => "Table updated.",
    "deleted" => "Table deleted.",
    "booked" => "Table booked and package sale created.",
    "closed" => "Booking closed and table released.",
    "package_created" => "Package created.",
    "package_updated" => "Package updated.",
    "package_deleted" => "Package deleted.",
    "package_deactivated" => "Package has booking history, so it was deactivated instead of permanently deleted.",
] as $key => $text) {
    if (isset($_GET[$key])) {
        $message = $text;
    }
}

$packages = $pdo->query("SELECT * FROM table_packages ORDER BY is_active DESC, price ASC")->fetchAll();
$activePackages = array_values(array_filter($packages, function ($package) {
    return (int)$package["is_active"] === 1;
}));
$packageItemsStmt = $pdo->query("SELECT * FROM table_package_items ORDER BY package_id ASC, display_order ASC");
$packageItems = [];
foreach ($packageItemsStmt->fetchAll() as $item) {
    $packageItems[(int)$item["package_id"]][] = $item;
}

$tablesStmt = $pdo->query("
    SELECT
        rt.*,
        tb.id AS booking_id,
        tb.package_id AS booking_package_id,
        tb.customer_name,
        tb.customer_contact AS booking_contact,
        tb.sale_id AS booking_sale_id,
        tb.booked_at,
        tp.name AS package_name,
        tp.price AS package_price,
        tp.capacity AS package_capacity,
        COALESCE(stats.order_count, 0) AS order_count,
        COALESCE(stats.total_amount, 0) AS total_amount,
        stats.sale_ids
    FROM restaurant_tables rt
    LEFT JOIN table_bookings tb ON tb.table_id = rt.id AND tb.status = 'open'
    LEFT JOIN table_packages tp ON tp.id = tb.package_id
    LEFT JOIN (
        SELECT
            s.table_id,
            COUNT(*) AS order_count,
            SUM(s.final_amount) AS total_amount,
            GROUP_CONCAT(s.id ORDER BY s.created_at ASC) AS sale_ids
        FROM sales s
        INNER JOIN table_bookings open_tb
            ON open_tb.table_id = s.table_id
           AND open_tb.status = 'open'
           AND s.created_at >= open_tb.booked_at
        WHERE s.table_id IS NOT NULL
        GROUP BY s.table_id
    ) stats ON stats.table_id = rt.id AND tb.id IS NOT NULL
    ORDER BY rt.id ASC
");
$tables = $tablesStmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<style>
    .receipt-preview-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 22px;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .24);
    }

    .receipt-preview-header {
        color: #ffffff;
        background: linear-gradient(135deg, #ff762d, #f59e0b);
        padding: 18px 22px;
    }

    .receipt-preview-body {
        position: relative;
        height: 78vh;
        background: #f3f4f6;
    }

    .receipt-preview-loader {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #6b7280;
        background: #f3f4f6;
        z-index: 2;
    }

    .receipt-preview-loader.d-none {
        display: none !important;
    }

    .receipt-preview-frame {
        width: 100%;
        height: 100%;
        border: 0;
        background: #ffffff;
    }
</style>

<div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
    <div>
        <h5 class="mb-1">Club Tables & Packages</h5>
        <p class="text-muted mb-0">Manage 20 tables, table bookings, package sales, and table bills.</p>
    </div>

    <div class="d-flex flex-wrap gap-2">
        <a href="packages.php" class="btn btn-light border">
            <i class="ri-gift-line me-1"></i>
            Manage Packages
        </a>
        <a href="pos.php" class="btn btn-primary">
            <i class="ri-shopping-cart-2-line me-1"></i>
            Open POS for Extra Drinks
        </a>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<?php if (isset($_GET["receipt"]) && (int)$_GET["receipt"] > 0): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Package receipt is ready.</span>
        <button type="button"
                class="btn btn-sm btn-primary open-receipt-modal"
                data-title="Package Receipt"
                data-url="receipt.php?id=<?php echo (int)$_GET["receipt"]; ?>">
            Print Package Receipt
        </button>
    </div>
<?php endif; ?>

<?php
    $closedReceiptIds = [];
    if (isset($_GET["receipt_ids"])) {
        foreach (explode(",", (string)$_GET["receipt_ids"]) as $rawId) {
            $id = (int)trim($rawId);
            if ($id > 0) {
                $closedReceiptIds[] = $id;
            }
        }
    }
?>
<?php if (!empty($closedReceiptIds)): ?>
    <div class="alert alert-info d-flex justify-content-between align-items-center">
        <span>Table closed. Final combined bill (package + all drinks added) is ready.</span>
        <button type="button"
                class="btn btn-sm btn-primary open-receipt-modal"
                data-title="Final Table Bill"
                data-url="receipt.php?ids=<?php echo e(implode(",", $closedReceiptIds)); ?>">
            Print Final Bill
        </button>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-xl-4">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0">Create Table</h6>
            </div>
            <div class="card-body">
                <form method="POST" class="vstack gap-3">
                    <input type="hidden" name="action" value="create_table">
                    <div>
                        <label class="form-label">Table Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Table 21" required>
                    </div>
                    <div>
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                    <button class="btn btn-primary" type="submit">Add Table</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-xl-8">
        <div class="card h-100">
            <div class="card-header">
                <h6 class="card-title mb-0">Book Customer Table</h6>
            </div>
            <div class="card-body">
                <form method="POST" class="row g-3">
                    <input type="hidden" name="action" value="book_table">
                    <div class="col-md-3">
                        <label class="form-label">Customer Name</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Contact <span class="text-muted fs-sm">(optional)</span></label>
                        <input type="text" name="customer_contact" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Table</label>
                        <select name="table_id" class="form-control" required>
                            <option value="">Select table</option>
                            <?php foreach ($tables as $table): ?>
                                <?php if (!$table["booking_id"] && $table["status"] === "Active"): ?>
                                    <option value="<?php echo (int)$table["id"]; ?>"><?php echo e($table["name"]); ?></option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Package</label>
                        <select name="package_id" class="form-control" required>
                            <option value="">Select package</option>
                            <?php foreach ($activePackages as $package): ?>
                                <option value="<?php echo (int)$package["id"]; ?>">
                                    <?php echo e($package["name"]); ?> — <?php echo money($package["price"]); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-primary" type="submit">Book Table & Create Package Sale</button>
                    </div>
                </form>
                <p class="text-muted fs-sm mb-0 mt-3">
                    After booking, use POS → select this table → add extra drinks → process payment. Final table bill can be printed from this page.
                </p>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h6 class="card-title mb-0">Tables</h6>
        <span class="badge bg-light text-muted border"><?php echo count($tables); ?> tables</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                <tr>
                    <th>Table</th>
                    <th>Status</th>
                    <th>Customer</th>
                    <th>Package</th>
                    <th>Orders Today</th>
                    <th style="width: 310px;">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($tables as $table): ?>
                    <?php
                        $updateFormId = "updateTableForm" . (int)$table["id"];
                        $tablePackageItems = $table["booking_package_id"] ? ($packageItems[(int)$table["booking_package_id"]] ?? []) : [];
                        $viewPayload = [
                            "name" => $table["name"],
                            "status" => $table["status"],
                            "customer_name" => $table["customer_name"] ?: "",
                            "customer_contact" => $table["booking_contact"] ?: "",
                            "package_name" => $table["package_name"] ?: "",
                            "package_price" => $table["package_price"] ? money($table["package_price"]) : "",
                            "orders" => (int)$table["order_count"],
                            "total" => money($table["total_amount"]),
                            "booked_at" => $table["booked_at"] ?: "",
                            "items" => array_map(function ($item) {
                                return [
                                    "name" => $item["item_name"],
                                    "quantity" => (int)$item["quantity"],
                                    "type" => $item["item_type"],
                                ];
                            }, $tablePackageItems),
                        ];
                    ?>
                    <tr>
                        <td>
                            <form method="POST" id="<?php echo e($updateFormId); ?>" class="d-none">
                                <input type="hidden" name="action" value="update_table">
                                <input type="hidden" name="table_id" value="<?php echo (int)$table["id"]; ?>">
                            </form>
                            <div class="row g-2 align-items-center">
                                <div class="col-7">
                                    <input type="text" name="name" form="<?php echo e($updateFormId); ?>" class="form-control form-control-sm" value="<?php echo e($table["name"]); ?>">
                                </div>
                                <?php /*
                                <div class="col-5">
                                    <select name="status" form="<?php echo e($updateFormId); ?>" class="form-control form-control-sm">
                                        <option value="Active" <?php echo $table["status"] === "Active" ? "selected" : ""; ?>>Active</option>
                                        <option value="Inactive" <?php echo $table["status"] === "Inactive" ? "selected" : ""; ?>>Inactive</option>
                                    </select>
                                </div>
                                */ ?>
                            </div>
                        </td>
                        <td>
                            <?php if ($table["booking_id"]): ?>
                                <span class="badge bg-warning">Booked</span>
                            <?php elseif ($table["status"] === "Active"): ?>
                                <span class="badge bg-success">Available</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($table["booking_id"]): ?>
                                <div class="fw-medium"><?php echo e($table["customer_name"]); ?></div>
                                <div class="text-muted fs-sm"><?php echo e($table["booking_contact"] ?: "No contact provided"); ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($table["booking_id"]): ?>
                                <div class="fw-medium"><?php echo e($table["package_name"]); ?></div>
                                <div class="text-muted fs-sm"><?php echo money($table["package_price"]); ?></div>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div><?php echo (int)$table["order_count"]; ?> order(s)</div>
                            <strong class="text-primary"><?php echo money($table["total_amount"]); ?></strong>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-2">
                                <button type="button"
                                        class="btn btn-sm btn-light border view-table-btn"
                                        data-table='<?php echo e(json_encode($viewPayload)); ?>'>
                                    View
                                </button>
                                <button type="submit" form="<?php echo e($updateFormId); ?>" class="btn btn-sm btn-light border">Save</button>

                            <?php if ($table["sale_ids"]): ?>
                                <button type="button"
                                        class="btn btn-sm btn-primary open-receipt-modal"
                                        data-title="<?php echo e($table["name"]); ?> Bill"
                                        data-url="receipt.php?ids=<?php echo e($table["sale_ids"]); ?>">
                                    Print Bill
                                </button>
                            <?php endif; ?>

                            <?php if ($table["booking_id"]): ?>
                                <form method="POST">
                                    <input type="hidden" name="action" value="close_booking">
                                    <input type="hidden" name="booking_id" value="<?php echo (int)$table["booking_id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-success">Close</button>
                                </form>
                                <a class="btn btn-sm btn-outline-primary" href="pos.php?table=<?php echo (int)$table["id"]; ?>">Add Drinks</a>
                            <?php else: ?>
                                <form method="POST" onsubmit="return confirm('Delete this table?');">
                                    <input type="hidden" name="action" value="delete_table">
                                    <input type="hidden" name="table_id" value="<?php echo (int)$table["id"]; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                </form>
                            <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="viewTableModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title mb-1" id="viewTableTitle">Table Details</h6>
                    <p class="text-muted mb-0 fs-sm" id="viewTableSubTitle">—</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <span class="text-muted fs-sm">Customer</span>
                            <h6 class="mb-0" id="viewCustomerName">—</h6>
                            <div class="text-muted fs-sm" id="viewCustomerContact">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <span class="text-muted fs-sm">Package</span>
                            <h6 class="mb-0" id="viewPackageName">—</h6>
                            <div class="text-muted fs-sm" id="viewPackagePrice">—</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="border rounded p-3 bg-light">
                            <span class="text-muted fs-sm">Orders Today</span>
                            <h6 class="mb-0" id="viewTableOrders">0 order(s)</h6>
                            <div class="text-primary fw-semibold fs-sm" id="viewTableTotal">GHS 0.00</div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">Package Items</h6>
                    <span class="badge bg-light text-muted border" id="viewPackageItemCount">0 items</span>
                </div>
                <div class="vstack gap-2" id="viewPackageItems">
                    <div class="text-muted text-center border rounded p-3">No package items.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade receipt-preview-modal" id="receiptModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="receipt-preview-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1" id="receiptModalTitle">Receipt Preview</h5>
                    <p class="mb-0 opacity-75 fs-sm">Review the bill here, then print when ready.</p>
                </div>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
            <div class="receipt-preview-body">
                <div class="receipt-preview-loader" id="receiptFrameLoader">
                    <div class="text-center">
                        <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div>
                        <div>Loading receipt preview...</div>
                    </div>
                </div>
                <iframe id="receiptFrame" class="receipt-preview-frame" src="about:blank"></iframe>
            </div>
            <div class="modal-footer bg-white">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="printReceiptFrameBtn">
                    <i class="ri-printer-line me-1"></i>
                    Print Bill
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function appendEmbeddedParam(url) {
        return url + (url.includes('?') ? '&' : '?') + 'embedded=1';
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.view-table-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const data = JSON.parse(this.dataset.table || '{}');
                const itemsBody = document.getElementById('viewPackageItems');

                document.getElementById('viewTableTitle').innerText = data.name || 'Table Details';
                document.getElementById('viewTableSubTitle').innerText = data.booked_at ? 'Booked at ' + data.booked_at : (data.status || '—');
                document.getElementById('viewCustomerName').innerText = data.customer_name || 'No customer';
                document.getElementById('viewCustomerContact').innerText = data.customer_contact || '—';
                document.getElementById('viewPackageName').innerText = data.package_name || 'No package';
                document.getElementById('viewPackagePrice').innerText = data.package_price || '—';
                document.getElementById('viewTableOrders').innerText = (data.orders || 0) + ' order(s)';
                document.getElementById('viewTableTotal').innerText = data.total || 'GHS 0.00';

                document.getElementById('viewPackageItemCount').innerText = (data.items ? data.items.length : 0) + ' item(s)';

                if (!data.items || data.items.length === 0) {
                    itemsBody.innerHTML = '<div class="text-muted text-center border rounded p-3">No package items.</div>';
                } else {
                    itemsBody.innerHTML = data.items.map(function (item) {
                        const itemName = String(item.name || '').replace(/[&<>"']/g, function (char) {
                                return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char];
                            });
                        return '<div class="d-flex justify-content-between align-items-center border rounded px-3 py-2 bg-light">' +
                            '<div class="fw-semibold">' + Number(item.quantity || 0) + ' × ' + itemName + '</div>' +
                            '</div>';
                    }).join('');
                }

                new bootstrap.Modal(document.getElementById('viewTableModal')).show();
            });
        });

        document.querySelectorAll('.open-receipt-modal').forEach(function (button) {
            button.addEventListener('click', function () {
                const modal = document.getElementById('receiptModal');
                const loader = document.getElementById('receiptFrameLoader');
                const frame = document.getElementById('receiptFrame');

                document.getElementById('receiptModalTitle').innerText = this.dataset.title || 'Receipt Preview';
                loader.classList.remove('d-none');
                frame.src = appendEmbeddedParam(this.dataset.url || 'about:blank');
                new bootstrap.Modal(modal).show();
            });
        });

        document.getElementById('receiptFrame').addEventListener('load', function () {
            document.getElementById('receiptFrameLoader').classList.add('d-none');
        });

        document.getElementById('printReceiptFrameBtn').addEventListener('click', function () {
            const frame = document.getElementById('receiptFrame');

            if (frame && frame.contentWindow) {
                frame.contentWindow.focus();
                frame.contentWindow.print();
            }
        });

        document.getElementById('receiptModal').addEventListener('hidden.bs.modal', function () {
            document.getElementById('receiptFrame').src = 'about:blank';
            document.getElementById('receiptFrameLoader').classList.remove('d-none');
        });
    });
</script>

<?php include "includes/footer.php"; ?>
