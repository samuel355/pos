<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "index_stats.php";

$stmt = $pdo->query("
    SELECT 
        s.id,
        s.final_amount,
        s.payment_method,
        s.created_at,
        u.username
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
$recent_sales = $stmt->fetchAll();

$stmt = $pdo->query("
    SELECT 
        name,
        stock_quantity
    FROM products
    WHERE stock_quantity <= 5
    ORDER BY stock_quantity ASC, name ASC
    LIMIT 5
");
$low_stock_products = $stmt->fetchAll();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

    <div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
        <div>
            <h5 class="mb-1">Main Dashboard</h5>
            <p class="text-muted mb-0">Overview of sales, orders, products, and stock alerts.</p>
        </div>

        <a href="pos.php" class="btn btn-primary">
            <i class="ri-shopping-cart-2-line me-1"></i>
            Open POS
        </a>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="mb-1 text-muted">Today Sales</p>
                            <h5 class="mb-0 fs-22">GHS <?php echo number_format((float)$today_sales, 2); ?></h5>
                        </div>
                        <div class="avatar size-12 bg-success-subtle rounded-circle text-success d-flex align-items-center justify-content-center">
                            <i class="ri-shopping-cart-2-line fs-4"></i>
                        </div>
                    </div>
                    <a href="pos.php" class="link link-success fs-sm">Start selling</a>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="mb-1 text-muted">Total Orders</p>
                            <h5 class="mb-0 fs-22"><?php echo number_format((int)$total_orders); ?></h5>
                        </div>
                        <div class="avatar size-12 bg-info-subtle rounded-circle text-info d-flex align-items-center justify-content-center">
                            <i class="ri-file-list-3-line fs-4"></i>
                        </div>
                    </div>
                    <span class="text-muted fs-sm">All completed sales</span>
                </div>
            </div>
        </div>

        <div class="col-xl-4 col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div>
                            <p class="mb-1 text-muted">Total Products</p>
                            <h5 class="mb-0 fs-22"><?php echo number_format((int)$total_products); ?></h5>
                        </div>
                        <div class="avatar size-12 bg-warning-subtle rounded-circle text-warning d-flex align-items-center justify-content-center">
                            <i class="ri-shopping-bag-line fs-4"></i>
                        </div>
                    </div>
                    <a href="products.php" class="link link-warning fs-sm">Manage products</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-xl-8">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Recent Sales</h6>
                    <a href="pos.php" class="btn btn-sm btn-light border">New Sale</a>
                </div>

                <div class="card-body">
                    <?php if (empty($recent_sales)): ?>
                        <div class="text-center py-5">
                            <img src="./assets/no-order-CCjZwO4J.svg" alt="No sales" style="max-width: 120px;" class="mb-3">
                            <h6 class="mb-1">No sales yet</h6>
                            <p class="text-muted mb-0">Your recent sales will appear here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                <tr>
                                    <th>Receipt</th>
                                    <th>Cashier</th>
                                    <th>Payment</th>
                                    <th>Date</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($recent_sales as $sale): ?>
                                    <tr>
                                        <td>
                                            <a href="receipt.php?id=<?php echo (int)$sale['id']; ?>" target="_blank" class="fw-medium">
                                                #<?php echo (int)$sale['id']; ?>
                                            </a>
                                        </td>
                                        <td><?php echo htmlspecialchars($sale['username'] ?: 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['payment_method'] ?: 'Cash'); ?></td>
                                        <td><?php echo htmlspecialchars($sale['created_at']); ?></td>
                                        <td class="text-end fw-medium">
                                            GHS <?php echo number_format((float)$sale['final_amount'], 2); ?>
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

        <div class="col-xl-4">
            <div class="card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h6 class="card-title mb-0">Low Stock</h6>
                    <a href="products.php" class="btn btn-sm btn-light border">Products</a>
                </div>

                <div class="card-body">
                    <?php if (empty($low_stock_products)): ?>
                        <div class="text-center py-5">
                            <div class="avatar size-14 bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3">
                                <i class="ri-check-line fs-3"></i>
                            </div>
                            <h6 class="mb-1">Stock looks good</h6>
                            <p class="text-muted mb-0">No low-stock products found.</p>
                        </div>
                    <?php else: ?>
                        <div class="vstack gap-3">
                            <?php foreach ($low_stock_products as $product): ?>
                                <div class="d-flex align-items-center justify-content-between gap-3 border-bottom pb-3">
                                    <div>
                                        <h6 class="mb-1 fs-14"><?php echo htmlspecialchars($product['name']); ?></h6>
                                        <p class="text-muted mb-0 fs-sm">Remaining stock</p>
                                    </div>
                                    <span class="badge <?php echo (int)$product['stock_quantity'] <= 0 ? 'bg-danger' : 'bg-warning'; ?>">
                                    <?php echo (int)$product['stock_quantity']; ?>
                                </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

<?php include "includes/footer.php"; ?>