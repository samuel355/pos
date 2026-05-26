<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
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

$defaultProductImage = './assets/pr-1-DpkbRlV7.png';

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function productImage($path, $fallback) {
    $path = trim((string)$path);

    if ($path === '') {
        return $fallback;
    }

    if (strpos($path, 'http://') === 0 || strpos($path, 'https://') === 0 || strpos($path, './') === 0) {
        return $path;
    }

    return './' . ltrim($path, '/');
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

        @media (max-width: 991px) {
            .product-cards,
            .pos-cart-items,
            .category-wrapper {
                max-height: none;
            }
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
                            <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3 category-filter"
                                    type="button"
                                    data-category="<?php echo (int)$category['id']; ?>">
                                <img src="./assets/img-01-BBWp8t8E.png" class="img-fluid size-8" alt="<?php echo e($category['name']); ?>">
                                <span class="fw-medium fs-13 mt-2"><?php echo e($category['name']); ?></span>
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

                            <a href="products.php" class="btn btn-primary d-none d-xl-block">Manage Products</a>
                        </div>

                        <div class="d-flex gap-2">
                            <div class="position-relative">
                                <input type="text" id="tableSearch" class="form-control ps-10" placeholder="Search product...">
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
                                    $stock = (int)$product['stock_quantity'];
                                    $image = productImage($product['image_path'], $defaultProductImage);
                                    ?>
                                    <div class="col-md-6 col-xl-4 col-xxl-3 product-item"
                                         data-id="<?php echo (int)$product['id']; ?>"
                                         data-name="<?php echo e(strtolower($product['name'])); ?>"
                                         data-category="<?php echo (int)$product['category_id']; ?>"
                                         data-price="<?php echo e($product['price']); ?>"
                                         data-stock="<?php echo $stock; ?>">
                                        <div class="card mb-0 pos-product-card h-100">
                                            <div class="card-body p-4 d-flex flex-column">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="<?php echo e($image); ?>" class="img-fluid p-4 d-block pos-product-img" alt="<?php echo e($product['name']); ?>">
                                                </div>

                                                <div class="mt-3 d-flex flex-column flex-grow-1">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">
                                                        <?php echo e($product['name']); ?>
                                                    </a>

                                                    <p class="text-muted small mb-1">
                                                        <?php echo e($product['category_name'] ?: 'Uncategorized'); ?>
                                                    </p>

                                                    <p class="<?php echo $stock > 0 ? 'text-success' : 'text-danger'; ?> small mb-2">
                                                        Stock: <?php echo $stock; ?>
                                                    </p>

                                                    <div class="d-flex justify-content-between align-items-end mt-auto">
                                                        <h6 class="fs-lg mb-0">GHS <?php echo number_format((float)$product['price'], 2); ?></h6>

                                                        <button type="button"
                                                                class="btn btn-primary btn-icon size-9 rounded-circle add-to-cart-btn"
                                                                <?php echo $stock <= 0 ? 'disabled' : ''; ?>
                                                                data-product='<?php echo e(json_encode([
                                                                        'id' => (int)$product['id'],
                                                                        'name' => $product['name'],
                                                                        'price' => (float)$product['price'],
                                                                        'stock' => $stock,
                                                                        'image_path' => $image,
                                                                ])); ?>'>
                                                            <i data-lucide="plus" class="size-4"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-xl-4 col-xxl-3 border-top border-top-lg-0 border-start-lg position-relative">
            <div class="p-5 h-100 d-flex flex-column bg-body">
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

<script>
    let cart = [];
    let activeCategory = 'all';
    let activeFilter = 'all';
    let searchTerm = '';

    function money(amount) {
        return 'GHS ' + Number(amount || 0).toFixed(2);
    }

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function addToCart(product) {
        const existingItem = cart.find(item => Number(item.id) === Number(product.id));

        if (existingItem) {
            if (existingItem.quantity >= existingItem.stock) {
                alert('Not enough stock available.');
                return;
            }

            existingItem.quantity += 1;
        } else {
            if (product.stock <= 0) {
                alert('This product is out of stock.');
                return;
            }

            cart.push({
                id: Number(product.id),
                name: product.name,
                price: Number(product.price),
                stock: Number(product.stock),
                quantity: 1,
                image_path: product.image_path
            });
        }

        updateCartUI();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateCartUI();
    }

    function updateQuantity(index, delta) {
        if (!cart[index]) {
            return;
        }

        const nextQuantity = cart[index].quantity + delta;

        if (nextQuantity <= 0) {
            removeFromCart(index);
            return;
        }

        if (nextQuantity > cart[index].stock) {
            alert('Not enough stock available.');
            return;
        }

        cart[index].quantity = nextQuantity;
        updateCartUI();
    }

    function clearCart() {
        if (cart.length === 0) {
            return;
        }

        if (!confirm('Clear current order?')) {
            return;
        }

        cart = [];
        updateCartUI();
    }

    function updateCartUI() {
        const cartTbody = document.getElementById('cartItems');
        const emptyMsg = document.getElementById('emptyOrderMessage');

        cartTbody.innerHTML = '';

        let subtotal = 0;
        let totalQty = 0;

        cart.forEach((item, index) => {
            const lineTotal = item.price * item.quantity;
            subtotal += lineTotal;
            totalQty += item.quantity;

            const rowHtml = `
            <tr>
                <td class="ps-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar size-10 bg-light rounded p-1">
                            <img src="${escapeHtml(item.image_path)}" class="img-fluid" alt="${escapeHtml(item.name)}">
                        </div>
                        <div>
                            <h6 class="mb-1 fs-14 text-truncate cart-product-name">${escapeHtml(item.name)}</h6>
                            <p class="text-muted mb-0 fs-sm">${money(item.price)}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-light border qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
                        <span class="fw-medium">${item.quantity}</span>
                        <button type="button" class="btn btn-light border qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                    </div>
                </td>
                <td class="text-end pe-0">
                    <div class="fw-medium">${money(lineTotal)}</div>
                    <button type="button" class="btn btn-link text-danger p-0 fs-sm" onclick="removeFromCart(${index})">Remove</button>
                </td>
            </tr>
        `;

            cartTbody.insertAdjacentHTML('beforeend', rowHtml);
        });

        document.getElementById('subtotalAmount').innerText = money(subtotal);
        document.getElementById('taxAmount').innerText = money(0);
        document.getElementById('discountAmount').innerText = money(0);
        document.getElementById('totalPayableAmount').innerText = money(subtotal);
        document.getElementById('itemCount').innerText = 'Items: ' + totalQty;

        emptyMsg.style.display = cart.length > 0 ? 'none' : 'block';
    }

    function applyProductFilters() {
        const items = Array.from(document.querySelectorAll('.product-item'));

        items.forEach(item => {
            const productName = item.dataset.name || '';
            const productCategory = item.dataset.category || '';
            const productStock = Number(item.dataset.stock || 0);

            let visible = true;

            if (activeCategory !== 'all' && productCategory !== activeCategory) {
                visible = false;
            }

            if (searchTerm !== '' && productName.indexOf(searchTerm) === -1) {
                visible = false;
            }

            if (activeFilter === 'instock' && productStock <= 0) {
                visible = false;
            }

            if (activeFilter === 'outofstock' && productStock > 0) {
                visible = false;
            }

            if (activeFilter === 'lowstock' && !(productStock > 0 && productStock <= 5)) {
                visible = false;
            }

            item.style.display = visible ? 'block' : 'none';
        });

        if (activeFilter === 'price-low' || activeFilter === 'price-high') {
            const grid = document.getElementById('productsGrid');

            items.sort((a, b) => {
                const aPrice = Number(a.dataset.price || 0);
                const bPrice = Number(b.dataset.price || 0);

                return activeFilter === 'price-low' ? aPrice - bPrice : bPrice - aPrice;
            });

            items.forEach(item => grid.appendChild(item));
        }
    }

    async function processCheckout() {
        if (cart.length === 0) {
            alert('Cart is empty.');
            return;
        }

        if (!confirm('Process this payment?')) {
            return;
        }

        const checkoutBtn = document.getElementById('checkoutBtn');
        checkoutBtn.disabled = true;
        checkoutBtn.innerText = 'Processing...';

        try {
            const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

            const response = await fetch('api/save_sale.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    items: cart,
                    total: total,
                    payment_method: document.getElementById('paymentMethod').value
                })
            });

            const result = await response.json();

            if (result.success) {
                alert('Sale saved successfully. Receipt #' + result.sale_id);
                window.open('receipt.php?id=' + result.sale_id, '_blank');

                cart = [];
                updateCartUI();

                setTimeout(function () {
                    window.location.reload();
                }, 800);
            } else {
                alert(result.error || 'Unable to save sale.');
            }
        } catch (error) {
            alert('System error occurred while processing checkout.');
        } finally {
            checkoutBtn.disabled = false;
            checkoutBtn.innerText = 'Process Payment';
        }
    }

    function updateClock() {
        const now = new Date();

        const dateEl = document.getElementById('pos-date');
        const timeEl = document.getElementById('pos-time');

        if (dateEl) {
            dateEl.innerText = now.toLocaleDateString();
        }

        if (timeEl) {
            timeEl.innerText = now.toLocaleTimeString();
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('.add-to-cart-btn').forEach(button => {
            button.addEventListener('click', function () {
                addToCart(JSON.parse(this.dataset.product));
            });
        });

        document.querySelectorAll('.category-filter').forEach(button => {
            button.addEventListener('click', function () {
                document.querySelectorAll('.category-filter').forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');

                activeCategory = this.dataset.category;
                applyProductFilters();
            });
        });

        document.querySelectorAll('.product-filter').forEach(link => {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                activeFilter = this.dataset.filter;

                if (activeFilter === 'reset') {
                    activeFilter = 'all';
                    activeCategory = 'all';
                    searchTerm = '';
                    document.getElementById('tableSearch').value = '';

                    document.querySelectorAll('.category-filter').forEach(btn => btn.classList.remove('active'));
                    document.querySelector('.category-filter[data-category="all"]').classList.add('active');
                }

                applyProductFilters();
            });
        });

        document.getElementById('tableSearch').addEventListener('input', function () {
            searchTerm = this.value.toLowerCase().trim();
            applyProductFilters();
        });

        document.getElementById('clearCartBtn').addEventListener('click', clearCart);
        document.getElementById('checkoutBtn').addEventListener('click', processCheckout);

        updateCartUI();
        updateClock();
        setInterval(updateClock, 1000);
    });
</script>
</body>
</html>