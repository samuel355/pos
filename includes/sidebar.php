<?php
$currentPage = basename($_SERVER['PHP_SELF']);

function isActiveMenu($page, $currentPage)
{
    return $page === $currentPage ? 'active' : '';
}

function ariaCurrent($page, $currentPage)
{
    return $page === $currentPage ? 'aria-current="page"' : '';
}
?>

<aside class="main-sidebar" id="main-sidebar">
    <div class="sidebar-logo px-5 py-4 border-bottom">
        <a href="dashboard.php" class="kt-logo">
            <span class="kt-logo-badge">KT</span>
            <span class="kt-logo-text">POS</span>
        </a>
    </div>

    <div class="navbar-menu h-100">
        <div data-simplebar class="h-100">
            <ul class="navbar-nav-menu list-unstyled mb-0 px-4 py-4">
                <li class="nav-menu-title">
                    <span>Main</span>
                </li>

                <li class="nav-item">
                    <a href="dashboard.php"
                        class="nav-link <?php echo isActiveMenu('dashboard.php', $currentPage); ?>"
                        <?php echo ariaCurrent('dashboard.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-dashboard-line"></i>
                        </span>
                        <span class="menu-text">Dashboard</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="pos.php"
                        class="nav-link <?php echo isActiveMenu('pos.php', $currentPage); ?>"
                        <?php echo ariaCurrent('pos.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-shopping-cart-2-line"></i>
                        </span>
                        <span class="menu-text">POS System</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="sales.php"
                        class="nav-link <?php echo isActiveMenu('sales.php', $currentPage); ?>"
                        <?php echo ariaCurrent('sales.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-receipt-line"></i>
                        </span>
                        <span class="menu-text">Sales</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="receipts.php"
                        class="nav-link <?php echo isActiveMenu('receipts.php', $currentPage); ?>"
                        <?php echo ariaCurrent('receipts.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-file-list-3-line"></i>
                        </span>
                        <span class="menu-text">Receipts</span>
                    </a>
                </li>

                <li class="nav-menu-title">
                    <span>Management</span>
                </li>

                <li class="nav-item">
                    <a href="products.php"
                        class="nav-link <?php echo isActiveMenu('products.php', $currentPage); ?>"
                        <?php echo ariaCurrent('products.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-shopping-bag-line"></i>
                        </span>
                        <span class="menu-text">Products</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="categories.php"
                        class="nav-link <?php echo isActiveMenu('categories.php', $currentPage); ?>"
                        <?php echo ariaCurrent('categories.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-list-check"></i>
                        </span>
                        <span class="menu-text">Categories</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="tables.php"
                        class="nav-link <?php echo isActiveMenu('tables.php', $currentPage); ?>"
                        <?php echo ariaCurrent('tables.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-vip-crown-line"></i>
                        </span>
                        <span class="menu-text">Club Tables</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="packages.php"
                        class="nav-link <?php echo isActiveMenu('packages.php', $currentPage); ?>"
                        <?php echo ariaCurrent('packages.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-gift-line"></i>
                        </span>
                        <span class="menu-text">Packages</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a href="staff.php"
                        class="nav-link <?php echo isActiveMenu('staff.php', $currentPage); ?>"
                        <?php echo ariaCurrent('staff.php', $currentPage); ?>>
                        <span class="icons">
                            <i class="ri-team-line"></i>
                        </span>
                        <span class="menu-text">Staff Management</span>
                    </a>
                </li>

                <li class="nav-menu-title">
                    <span>System</span>
                </li>

                <li class="nav-item">
                    <a href="logout.php" class="nav-link text-danger">
                        <span class="icons">
                            <i class="ri-logout-circle-line"></i>
                        </span>
                        <span class="menu-text">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</aside>

<div id="sidebar-backdrop" class="sidebar-backdrop"></div>

<div class="min-vh-100 position-relative">
    <div class="page-wrapper">
        <div class="container-fluid">
