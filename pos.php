<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$stmt = $pdo->query("SELECT * FROM categories WHERE status = 'Active' ORDER BY name ASC");
$categories = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT
        p.*,
        c.name AS category_name
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.name ASC
");
$products = $stmt->fetchAll();

$tables = [];
try {
  $stmt = $pdo->query("
    SELECT id, name, status
    FROM restaurant_tables
        WHERE status = 'Active'
        ORDER BY id ASC
    ");
  $tables = $stmt->fetchAll();
} catch (PDOException $e) {
  $tables = [];
}

$isAdmin = isset($_SESSION["role"]) && $_SESSION["role"] === "admin";
$currentUserId = (int) $_SESSION["user_id"];
$currentUsername = $_SESSION["username"] ?? "Staff";
$defaultProductImage = "./assets/uploads/placeholder.png";

function e($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function normalizeAssetPath($path, $fallback = "")
{
  $path = trim((string) $path);

  if ($path === "") {
    return $fallback;
  }

  if (
    strpos($path, "http://") === 0 ||
    strpos($path, "https://") === 0 ||
    strpos($path, "./") === 0
  ) {
    return $path;
  }

  return "./" . ltrim($path, "/");
}

function productImage($path, $fallback)
{
  return normalizeAssetPath($path, $fallback);
}

function categoryImage($path)
{
  return normalizeAssetPath($path, "./assets/uploads/placeholder.png");
}
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth group" data-layout="two-column" data-content-width="fluid" data-bs-theme="light" data-sidebar-colors="light" data-sidebar="large" data-nav-type="default" dir="ltr" data-colors="default">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS System</title>

    <link rel="shortcut icon" href="./assets/favicon-B-3ALmIB.ico">
    <link rel="stylesheet" crossorigin href="./assets/admin-Bly6avC4.css">
    <link rel="stylesheet" crossorigin href="./assets/air-datepicker-ByzRugGb.css">
    <link rel="stylesheet" crossorigin href="./assets/filepond-plugin-image-preview-BLtz_BYx.css">
    <link rel="stylesheet" crossorigin href="./assets/virtual-select-hfXZVdeB.css">

    <script type="module" crossorigin src="./assets/src/pos-yOGPzrsS.js"></script>

    <style>
        .pos-product-card {
            cursor: pointer;
            transition: all .2s ease;
        }

        .pos-product-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(15, 23, 42, .12);
        }

        .pos-product-img {
            height: 150px;
            width: 100%;
            object-fit: contain;
        }

        .pos-cart-items {
            max-height: calc(100vh - 420px);
            overflow-y: auto;
        }

        .category-wrapper {
            max-height: 100vh;
            overflow-y: auto;
        }

        .product-cards {
            max-height: calc(100vh - 140px);
            overflow-y: auto;
        }

        .cart-product-name {
            max-width: 140px;
        }

        .qty-btn {
            width: 28px;
            height: 28px;
            padding: 0;
            line-height: 1;
        }

        .empty-order-image {
            max-width: 130px;
            opacity: .8;
        }

        .pos-toast {
            position: fixed;
            top: 18px;
            right: 18px;
            z-index: 9999;
            min-width: 260px;
            max-width: 360px;
            padding: 14px 16px;
            border-radius: 12px;
            color: #ffffff;
            background: #111827;
            box-shadow: 0 14px 35px rgba(15, 23, 42, .22);
            display: none;
            font-size: 14px;
            font-weight: 600;
        }

        .pos-toast.show {
            display: block;
            animation: toastSlide .2s ease-out;
        }

        .pos-toast.success {
            background: #16a34a;
        }

        .pos-toast.error {
            background: #dc2626;
        }

        .receipt-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9998;
            background: rgba(15, 23, 42, .55);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }

        .receipt-modal-backdrop.show {
            display: flex;
        }

        .receipt-modal {
            width: min(960px, 100%);
            height: min(92vh, 900px);
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 24px 80px rgba(15, 23, 42, .35);
        }

        .receipt-modal-header {
            padding: 14px 18px;
            border-bottom: 1px solid #e5e7eb;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }

        .receipt-modal-header h6 {
            margin: 0;
            font-size: 16px;
        }

        .receipt-modal-actions {
            display: flex;
            gap: 8px;
        }

        .receipt-modal-body {
            flex: 1;
            background: #f3f4f6;
        }

        .receipt-modal-body iframe {
            width: 100%;
            height: 100%;
            border: 0;
            background: #ffffff;
        }

        @keyframes toastSlide {
            from {
                opacity: 0;
                transform: translateY(-8px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 991px) {

            .product-cards,
            .pos-cart-items,
            .category-wrapper {
                max-height: none;
            }
        }

        .table-card {
            position: relative;
            cursor: pointer;
            transition: all .3s cubic-bezier(0.34, 1.56, 0.64, 1);
            border: none;
            border-radius: 16px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 2px 8px rgba(15, 23, 42, .08);
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .table-card::before {
            content: '';
            position: absolute;
            inset: 0 auto auto 0;
            width: 100%;
            height: 5px;
            background: #22c55e;
            transition: height .3s ease;
        }

        .table-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 48px rgba(15, 23, 42, .15);
        }

        .table-card:hover::before {
            height: 6px;
        }

        .table-card.selected {
            border: 2px solid #6366f1;
            box-shadow: 0 0 0 4px rgba(99, 102, 241, .1), 0 20px 48px rgba(99, 102, 241, .2);
        }

        .table-card.state-reserved::before { background: linear-gradient(90deg, #a855f7, #d946ef); }
        .table-card.state-serving::before { background: linear-gradient(90deg, #3b82f6, #2563eb); }
        .table-card.state-ready::before { background: linear-gradient(90deg, #f59e0b, #d97706); }
        .table-card.state-occupied::before { background: linear-gradient(90deg, #f97316, #ea580c); }

        .table-card.state-reserved { background: linear-gradient(135deg, rgba(168, 85, 247, 0.05) 0%, rgba(217, 70, 239, 0.03) 100%); }
        .table-card.state-serving { background: linear-gradient(135deg, rgba(59, 130, 246, 0.05) 0%, rgba(37, 99, 235, 0.03) 100%); }
        .table-card.state-ready { background: linear-gradient(135deg, rgba(245, 158, 11, 0.05) 0%, rgba(217, 119, 6, 0.03) 100%); }
        .table-card.state-occupied { background: linear-gradient(135deg, rgba(249, 115, 22, 0.05) 0%, rgba(234, 88, 12, 0.03) 100%); }

        .table-card-icon {
            width: 56px;
            height: 56px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            background: rgba(99, 102, 241, .12);
            color: #6366f1;
            transition: all .3s ease;
        }

        .table-card:hover .table-card-icon {
            transform: scale(1.1) rotate(5deg);
            background: rgba(99, 102, 241, .18);
        }

        .table-card.state-reserved .table-card-icon { background: rgba(168, 85, 247, .15); color: #a855f7; }
        .table-card.state-serving .table-card-icon { background: rgba(59, 130, 246, .15); color: #3b82f6; }
        .table-card.state-ready .table-card-icon { background: rgba(245, 158, 11, .15); color: #f59e0b; }
        .table-card.state-occupied .table-card-icon { background: rgba(249, 115, 22, .15); color: #f97316; }

        .table-status-pill {
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            padding: 6px 12px;
            border-radius: 12px;
            backdrop-filter: blur(8px);
            transition: all .3s ease;
        }

        .table-status-pill.free { background: rgba(34, 197, 94, 0.15); color: #16a34a; }
        .table-status-pill.reserved { background: rgba(168, 85, 247, 0.15); color: #a855f7; }
        .table-status-pill.serving { background: rgba(59, 130, 246, 0.15); color: #3b82f6; }
        .table-status-pill.ready { background: rgba(245, 158, 11, 0.15); color: #f59e0b; animation: readyPulse 1.6s ease-in-out infinite; }
        .table-status-pill.occupied { background: rgba(249, 115, 22, 0.15); color: #f97316; }

        .table-card:hover .table-status-pill {
            transform: scale(1.05);
        }

        @keyframes readyPulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, .35); }
            50% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
        }

        .table-card-actions {
            display: flex;
            gap: 8px;
            margin-top: auto;
            padding-top: 12px;
        }

        .table-card-actions .btn {
            flex: 1;
            font-size: 12px;
            font-weight: 600;
            border-radius: 10px;
            padding: 8px 12px;
            transition: all .2s ease;
            border: none;
        }

        .table-card-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }

        .table-meta-line {
            font-size: 12px;
            color: #64748b;
            margin-bottom: 6px;
            font-weight: 500;
        }

        .tables-modal-backdrop {
            position: fixed;
            inset: 0;
            z-index: 9990;
            background: rgba(15, 23, 42, .65);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .tables-modal-backdrop.show {
            display: flex;
        }

        .tables-modal {
            width: min(1040px, 100%);
            max-height: 92vh;
            background: #ffffff;
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            box-shadow: 0 40px 100px rgba(15, 23, 42, .25);
        }

        .tables-modal-hero {
            padding: 22px 24px 18px;
            background: linear-gradient(135deg, #312e81 0%, #6366f1 55%, #818cf8 100%);
            color: #fff;
        }

        .tables-modal-hero h5 {
            margin: 0;
            font-weight: 700;
        }

        .tables-modal-toolbar {
            padding: 16px 24px;
            background: #fff;
            border-bottom: 1px solid #e8edf3;
        }

        .tables-filter-chip {
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #475569;
            border-radius: 999px;
            padding: 6px 14px;
            font-size: 13px;
            font-weight: 600;
            transition: all .15s ease;
        }

        .tables-filter-chip.active,
        .tables-filter-chip:hover {
            background: #6366f1;
            border-color: #6366f1;
            color: #fff;
        }

        .tables-modal-body {
            overflow-y: auto;
            max-height: calc(92vh - 220px);
            padding: 28px 24px 32px;
        }

        #tablesGrid {
            row-gap: 8px !important;
            column-gap: 12px !important;
        }

        #tablesGrid > div {
            margin-bottom: 0 !important;
        }

        .table-orders-panel {
            max-height: 180px;
            overflow-y: auto;
        }

        .selected-table-badge {
            background: linear-gradient(135deg, rgba(99, 102, 241, .14), rgba(99, 102, 241, .04));
            border: 1px solid rgba(99, 102, 241, .25);
        }

        .serving-indicator {
            display: none;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            border-radius: 999px;
            background: linear-gradient(135deg, #dbeafe, #eff6ff);
            border: 1px solid #93c5fd;
            color: #1e40af;
            font-size: 13px;
            font-weight: 600;
        }

        .serving-indicator.show {
            display: inline-flex;
        }

        .serving-indicator.ready {
            background: linear-gradient(135deg, #fef3c7, #fffbeb);
            border-color: #fcd34d;
            color: #b45309;
        }

        .serving-indicator-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2563eb;
            animation: serveBlink 1.4s ease-in-out infinite;
        }

        .serving-indicator.ready .serving-indicator-dot {
            background: #f59e0b;
        }

        @keyframes serveBlink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: .45; transform: scale(.85); }
        }

        .reserve-modal {
            width: min(420px, 100%);
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 24px 70px rgba(15, 23, 42, .3);
        }
    </style>
</head>

<body class="sidebar-hidden">
    <div class="container-fluid px-0">
        <div class="row g-0 pos-wrapper">

            <div class="col-lg-7 col-xl-8 col-xxl-9">
                <div class="position-relative">
                    <header class="main-topbar start-0 position-absolute" id="main-topbar">
                        <a href="dashboard.php" class="navbar-brand">
                            <div class="logo-lg">
                                <img src="./assets/main-logo-CWEU2RA-.png" loading="lazy" alt="Logo" height="20" class="mx-auto logo-dark">
                                <img src="./assets/logo-white-B_ImY8Qx.png" loading="lazy" alt="Logo" height="20" class="mx-auto logo-light">
                            </div>
                        </a>

                        <div class="d-none d-xl-flex align-items-center gap-2 ms-10">
                            <div class="border py-6px px-3 rounded">
                                <i class="bi bi-calendar2 me-2 text-primary"></i>
                                <span id="pos-date">--</span>
                            </div>
                            <span>-</span>
                            <div class="border py-6px px-3 rounded">
                                <i class="bi bi-clock me-2 fw-semibold text-primary"></i>
                                <span id="pos-time">--</span>
                            </div>
                        </div>

                        <div class="d-flex align-items-center gap-3 ms-auto">
                            <div id="servingIndicator" class="serving-indicator">
                                <span class="serving-indicator-dot"></span>
                                <span id="servingIndicatorText">Serving —</span>
                            </div>

                            <span class="py-6px ps-4 pe-6px bg-success-subtle rounded text-success d-none d-md-flex d-lg-none d-xxl-flex align-items-center">
                                <span class="size-1-5 d-block rounded-circle bg-success me-2"></span>Open Order
                            </span>

                            <a href="dashboard.php" class="btn h-9 px-3 btn-secondary py-6px d-none d-md-inline-flex align-items-center">
                                <i class="bi bi-globe pe-2 fs-sm"></i> Dashboard
                            </a>

                            <a href="products.php" class="btn btn-outline-light bg-body-secondary shadow-sm border size-8 btn-icon rounded-1" title="Products">
                                <i class="ri-box-3-line fs-17"></i>
                            </a>

                            <a href="logout.php" class="btn btn-outline-light bg-body-secondary shadow-sm border size-8 btn-icon rounded-1" title="Logout">
                                <i class="ri-logout-box-r-line fs-17"></i>
                            </a>
                        </div>
                    </header>
                </div>

                <div class="pos-left-side d-flex flex-wrap flex-md-nowrap">
                    <div class="p-6 border-end shadow-sm bg-body-secondary category-wrapper">
                        <div class="nav flex-md-column nav-pills gap-4" id="pos-category-tabs" role="tablist">
                            <button class="nav-link active text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3 category-filter"
                                type="button"
                                data-category="all">
                                <img src="./assets/img-14-Bq_mg9xG.png" class="img-fluid size-8" alt="All">
                                <span class="fw-medium fs-13 mt-2">All</span>
                            </button>

                            <?php foreach ($categories as $category): ?>
                                <?php $catImage = categoryImage($category["image_path"] ?? ""); ?>
                                <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3 category-filter"
                                    type="button"
                                    data-category="<?php echo (int) $category["id"]; ?>">
                                    <img src="<?php echo e(
                                      $catImage,
                                    ); ?>" class="img-fluid size-8" alt="<?php echo e(
  $category["name"],
); ?>">
                                    <span class="fw-medium fs-13 mt-2"><?php echo e(
                                      $category["name"],
                                    ); ?></span>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="p-5 items-section w-100">
                        <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
                            <div class="d-flex gap-2">
                                <div class="dropdown">
                                    <button type="button" class="btn bg-body-secondary text-muted border" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i data-lucide="filter" class="size-4 me-1"></i>
                                        Filter
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-start">
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="all">All Products</a></li>
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="instock">In Stock</a></li>
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="outofstock">Out of Stock</a></li>
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="lowstock">Low Stock</a></li>
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="price-low">Price: Low to High</a></li>
                                        <li><a class="dropdown-item product-filter" href="#!" data-filter="price-high">Price: High to Low</a></li>
                                        <li><a class="dropdown-item text-danger product-filter" href="#!" data-filter="reset">Reset Filters</a></li>
                                    </ul>
                                </div>

                                <button type="button" class="btn btn-primary d-none d-xl-block" id="posBtn">POS</button>
                                <button type="button" id="openTablesBtn" class="btn btn-outline-primary inline-flex align-items-center">
                                    <i class="ri-table-line me-1"></i> Tables
                                </button>
                            </div>

                            <div class="d-flex gap-2">
                                <div class="position-relative">
                                    <input type="text" id="productSearch" class="form-control ps-10" placeholder="Search product...">
                                    <i data-lucide="search" class="size-4 icon-dark position-absolute top-50 start-0 ms-4 translate-middle-y"></i>
                                </div>
                            </div>
                        </div>

                        <div class="px-5 mx-n5 product-cards">
                            <?php if (empty($products)): ?>
                                <div class="alert alert-warning">
                                    No products found. Please add products from the products page.
                                </div>
                            <?php else: ?>
                                <div class="row g-4" id="productsGrid">
                                    <?php foreach ($products as $product): ?>
                                        <?php
                                        $stock = (int) $product["stock_quantity"];
                                        $image = productImage(
                                          $product["image_path"],
                                          $defaultProductImage,
                                        );
                                        ?>
                                        <div class="col-md-6 col-xl-4 col-xxl-3 product-item"
                                            data-id="<?php echo (int) $product["id"]; ?>"
                                            data-name="<?php echo e(
                                              strtolower($product["name"]),
                                            ); ?>"
                                            data-category="<?php echo (int) $product[
                                              "category_id"
                                            ]; ?>"
                                            data-price="<?php echo e($product["price"]); ?>"
                                            data-stock="<?php echo $stock; ?>">
                                            <div class="card mb-0 pos-product-card h-100">
                                                <div class="card-body p-4 d-flex flex-column">
                                                    <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                        <img src="<?php echo e(
                                                          $image,
                                                        ); ?>" class="img-fluid p-4 d-block pos-product-img" alt="<?php echo e(
  $product["name"],
); ?>">
                                                    </div>

                                                    <div class="mt-3 d-flex flex-column flex-grow-1">
                                                        <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">
                                                            <?php echo e($product["name"]); ?>
                                                        </a>

                                                        <p class="text-muted small mb-1">
                                                            <?php echo e(
                                                              $product["category_name"] ?:
                                                              "Uncategorized",
                                                            ); ?>
                                                        </p>

                                                        <p class="<?php echo $stock > 0
                                                          ? "text-success"
                                                          : "text-danger"; ?> small mb-2">
                                                            Stock: <?php echo $stock; ?>
                                                        </p>

                                                        <div class="d-flex justify-content-between align-items-end mt-auto">
                                                            <h6 class="fs-lg mb-0">GHS <?php echo number_format(
                                                              (float) $product["price"],
                                                              2,
                                                            ); ?></h6>

                                                            <button type="button"
                                                                class="btn btn-primary btn-icon size-9 rounded-circle add-to-cart-btn"
                                                                <?php echo $stock <= 0
                                                                  ? "disabled"
                                                                  : ""; ?>
                                                                data-product='<?php echo e(
                                                                  json_encode([
                                                                    "id" => (int) $product["id"],
                                                                    "name" => $product["name"],
                                                                    "price" =>
                                                                      (float) $product["price"],
                                                                    "stock" => $stock,
                                                                    "image_path" => $image,
                                                                  ]),
                                                                ); ?>'>
                                                                <i data-lucide="plus" class="size-4"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div id="noProductsFound" class="text-center py-5 d-none">
                                    <img src="./assets/no-order-CCjZwO4J.svg" class="empty-order-image mb-3" alt="No products">
                                    <h6 class="mb-1">No products found</h6>
                                    <p class="text-muted mb-0">Try another search term, category, or filter.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5 col-xl-4 col-xxl-3 border-top border-top-lg-0 border-start-lg position-relative">
                <div class="p-5 h-100 d-flex flex-column bg-body">
                    <div id="selectedTableBanner" class="selected-table-badge rounded p-3 mb-3 d-none">
                        <div class="d-flex align-items-center justify-content-between gap-2">
                            <div>
                                <span class="badge bg-primary-subtle text-primary mb-1" id="selectedTableStatusBadge">Active Table</span>
                                <h6 class="mb-0" id="selectedTableLabel">—</h6>
                                <p class="text-muted mb-0 fs-sm" id="selectedTableMeta">New items will link to this table</p>
                            </div>
                            <div class="d-flex gap-1 flex-wrap justify-content-end">
                                <button type="button" id="serveSelectedTableBtn" class="btn btn-primary btn-sm">Serve</button>
                                <button type="button" id="changeTableBtn" class="btn btn-light border btn-sm">Change</button>
                                <button type="button" id="clearTableBtn" class="btn btn-light border btn-sm">Clear</button>
                            </div>
                        </div>
                    </div>

                    <div id="tableOrdersSection" class="card mb-4 d-none">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="mb-0">Table Orders Today</h6>
                            <span class="badge bg-warning-subtle text-warning" id="tableOrdersCount">0 orders</span>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-orders-panel" id="tableOrdersList">
                                <p class="text-muted text-center py-4 mb-0">No orders yet for this table.</p>
                            </div>
                            <div class="border-top px-3 py-3 d-flex justify-content-between align-items-center bg-light">
                                <span class="fw-semibold">Table Total</span>
                                <span class="fw-bold text-primary" id="tableGrandTotal">GHS 0.00</span>
                            </div>
                            <div class="border-top px-3 py-2">
                                <button type="button" id="viewOrdersBtn" class="btn btn-sm btn-outline-primary w-100 py-2">View All Orders</button>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between gap-3 mb-4">
                        <div>
                            <h5 class="mb-1">Current Order</h5>
                            <p class="text-muted mb-0">
                                <span id="itemCount">Items: 0</span>
                            </p>
                        </div>

                        <button type="button" id="clearCartBtn" class="btn btn-light border btn-sm">
                            Clear
                        </button>
                    </div>

                    <div class="card mb-4">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="avatar size-11 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center">
                                    <i class="ri-user-line fs-lg"></i>
                                </div>
                                <div>
                                    <a href="#!" class="fw-medium text-reset d-block">Walk-in Customer</a>
                                    <p class="text-muted mb-0 fs-sm">Default POS customer</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive pos-cart-items">
                        <table class="table table-borderless text-nowrap align-middle mb-0 orderTable">
                            <tbody id="cartItems"></tbody>
                        </table>

                        <div class="text-center py-5" id="emptyOrderMessage">
                            <img src="./assets/no-order-CCjZwO4J.svg" class="empty-order-image mb-3" alt="No order">
                            <h6 class="mb-1">No items added</h6>
                            <p class="text-muted mb-0">Select products to start a sale.</p>
                        </div>
                    </div>

                    <div class="bg-light bg-opacity-50 px-3 py-4 border rounded mt-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-medium" id="subtotalAmount">GHS 0.00</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Tax</span>
                            <span class="fw-medium" id="taxAmount">GHS 0.00</span>
                        </div>

                        <div class="d-flex justify-content-between mb-3">
                            <span class="text-muted">Discount</span>
                            <span class="fw-medium" id="discountAmount">GHS 0.00</span>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center fs-16">
                            <span class="fw-semibold">Total Payable</span>
                            <span class="fw-bold text-primary" id="totalPayableAmount">GHS 0.00</span>
                        </div>
                    </div>

                    <div class="mt-4">
                        <label class="form-label" for="cartTableSelect">Link to Table <span class="text-muted fw-normal">(optional)</span></label>
                        <select id="cartTableSelect" class="form-control">
                            <option value="">No table — walk-in order</option>
                            <?php foreach ($tables as $table): ?>
                                <option value="<?php echo (int) $table["id"]; ?>"><?php echo e(
  $table["name"],
); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mt-4">
                        <label class="form-label">Payment Method</label>
                        <select id="paymentMethod" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Card">Card</option>
                            <option value="Mobile Money">Mobile Money</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>

                    <button type="button" id="checkoutBtn" class="btn btn-primary w-100 mt-auto">
                        Process Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div id="posToast" class="pos-toast"></div>

    <div id="receiptModalBackdrop" class="receipt-modal-backdrop">
        <div class="receipt-modal">
            <div class="receipt-modal-header">
                <h6 id="receiptModalTitle">Receipt Preview</h6>
                <div class="receipt-modal-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="printReceiptBtn">
                        Print
                    </button>
                    <button type="button" class="btn btn-light border btn-sm" id="closeReceiptModalBtn">
                        Close
                    </button>
                </div>
            </div>

            <div class="receipt-modal-body">
                <iframe id="receiptPreviewFrame" src="about:blank"></iframe>
            </div>
        </div>
    </div>

    <div id="ordersModalBackdrop" class="receipt-modal-backdrop">
        <div class="receipt-modal">
            <div class="receipt-modal-header">
                <h6 id="ordersModalTitle">All Orders - <span id="ordersModalTableName">—</span></h6>
                <div class="receipt-modal-actions">
                    <button type="button" class="btn btn-primary btn-sm" id="printOrdersBtn">
                        Print
                    </button>
                    <button type="button" class="btn btn-light border btn-sm" id="closeOrdersModalBtn">
                        Close
                    </button>
                </div>
            </div>
            <div class="receipt-modal-body">
                <div id="ordersModalContent" style="overflow-y: auto; padding: 24px;">
                    <p class="text-center text-muted">Loading orders...</p>
                </div>
            </div>
        </div>
    </div>

    <div id="tablesModalBackdrop" class="tables-modal-backdrop">
        <div class="tables-modal">
            <div class="tables-modal-hero d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5>Table Floor</h5>
                    <p class="mb-0 opacity-75 fs-sm">Select, reserve, or start serving a table</p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="closeTablesModalBtn">Close</button>
            </div>

            <div class="tables-modal-toolbar">
                <div class="row g-3 align-items-center">
                    <div class="col-lg-5">
                        <div class="position-relative">
                            <input type="text" id="modalTableSearch" class="form-control ps-10 rounded-pill" placeholder="Search tables...">
                            <i class="ri-search-line position-absolute top-50 start-0 ms-3 translate-middle-y text-muted"></i>
                        </div>
                    </div>
                    <div class="col-lg-7">
                        <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                            <button type="button" class="tables-filter-chip active" data-table-filter="all">All</button>
                            <button type="button" class="tables-filter-chip" data-table-filter="free">Free</button>
                            <button type="button" class="tables-filter-chip" data-table-filter="reserved">Reserved</button>
                            <button type="button" class="tables-filter-chip" data-table-filter="serving">Serving</button>
                            <button type="button" class="tables-filter-chip" data-table-filter="ready">Ready</button>
                            <?php if ($isAdmin): ?>
                                <button type="button" id="showAddTableFormBtn" class="btn btn-primary btn-sm rounded-pill ms-lg-2">
                                    <i class="ri-add-line"></i> Add Table
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
                    <div id="addTableForm" class="mt-3 p-3 border rounded-4 bg-white d-none">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-6">
                                <label class="form-label fs-sm mb-1">Table Name</label>
                                <input type="text" id="newTableName" class="form-control form-control-sm" placeholder="e.g. Table 21">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fs-sm mb-1">Status</label>
                                <select id="newTableStatus" class="form-control form-control-sm">
                                    <option value="Active">Active</option>
                                    <option value="Inactive">Inactive</option>
                                </select>
                            </div>
                            <div class="col-md-2 d-flex gap-1">
                                <button type="button" id="saveNewTableBtn" class="btn btn-primary btn-sm w-100">Save</button>
                                <button type="button" id="cancelAddTableBtn" class="btn btn-light border btn-sm">Cancel</button>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="tables-modal-body">
                <div class="row g-3" id="tablesGrid">
                    <div class="col-12 text-center py-5 text-muted" id="tablesLoadingMsg">Loading tables...</div>
                </div>
            </div>
        </div>
    </div>

    <div id="reserveModalBackdrop" class="tables-modal-backdrop">
        <div class="reserve-modal">
            <div class="p-4 border-bottom">
                <h6 class="mb-1">Reserve Table</h6>
                <p class="text-muted mb-0 fs-sm" id="reserveModalTableName">—</p>
            </div>
            <div class="p-4">
                <label class="form-label" for="reserveGuestName">Guest Name</label>
                <input type="text" id="reserveGuestName" class="form-control" placeholder="Who is this reservation for?">
                <input type="hidden" id="reserveTableId">
                <div class="d-flex gap-2 mt-4">
                    <button type="button" id="confirmReserveBtn" class="btn btn-primary flex-fill">Confirm Reservation</button>
                    <button type="button" id="cancelReserveModalBtn" class="btn btn-light border">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <div id="editTableModalBackdrop" class="tables-modal-backdrop">
        <div class="reserve-modal">
            <div class="p-4 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Edit Table</h6>
                <button type="button" class="btn btn-light border btn-sm" id="closeEditTableModalBtn">Close</button>
            </div>
            <div class="p-4">
                <input type="hidden" id="editTableId">
                <div class="mb-3">
                    <label class="form-label">Table Name</label>
                    <input type="text" id="editTableName" class="form-control">
                </div>
                <div class="mb-4">
                    <label class="form-label">Status</label>
                    <select id="editTableStatus" class="form-control">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" id="saveEditTableBtn" class="btn btn-primary">Update Table</button>
                    <button type="button" id="deleteTableBtn" class="btn btn-outline-danger">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        window.POS_CONFIG = {
            isAdmin: <?php echo $isAdmin ? "true" : "false"; ?>,
            userId: <?php echo (int) $currentUserId; ?>,
            username: <?php echo json_encode($currentUsername); ?>
        };
    </script>
    <script src="./assets/js/pos-page.js?v=<?php echo (int) @filemtime(
      __DIR__ . "/assets/js/pos-page.js",
    ); ?>"></script>
</body>

</html>
