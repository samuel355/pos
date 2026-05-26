<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "index_stats.php";
?>
<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="page-heading mb-3 gap-2 flex-column flex-md-row">
    <h6 class="flex-grow-1 mb-0">Main Dashboard</h6>
</div>

<div class="row g-5">
    <div class="col-xl-4 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <p class="mb-0 text-muted">Today Sales</p>
                    <div class="avatar size-8 bg-success-subtle rounded-circle text-success">
                        <i class="ri-shopping-cart-2-line"></i>
                    </div>
                </div>
                <h5 class="mb-0 fs-22">$<?php echo number_format($today_sales, 2); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <p class="mb-0 text-muted">Total Orders</p>
                    <div class="avatar size-8 bg-info-subtle rounded-circle text-info">
                        <i class="ri-file-list-3-line"></i>
                    </div>
                </div>
                <h5 class="mb-0 fs-22"><?php echo $total_orders; ?></h5>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-md-6">
        <div class="card card-h-100">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-4">
                    <p class="mb-0 text-muted">Total Products</p>
                    <div class="avatar size-8 bg-warning-subtle rounded-circle text-warning">
                        <i class="ri-shopping-bag-line"></i>
                    </div>
                </div>
                <h5 class="mb-0 fs-22"><?php echo $total_products; ?></h5>
            </div>
        </div>
    </div>
</div>

<?php include "includes/footer.php"; ?>
