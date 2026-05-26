<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

// Fetch Categories
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

// Fetch Products
$stmt = $pdo->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name ASC");
$products = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" class="scroll-smooth group" data-layout="two-column" data-content-width="fluid" data-bs-theme="light" data-sidebar-colors="light" data-sidebar="large" data-nav-type="default" dir="ltr" data-colors="default" data-profile-sidebar>
<head>

    <!-- Basic Meta Tags -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>POS System | GotPOS - Bootstrap Admin Dashboard Template</title>
    
    <!-- Primary Meta Tags -->
    <meta name="title" content="GotPOS | Bootstrap Admin Dashboard Template">
    <meta name="description" content="Get the best admin dashboard template with Bootstrap 5, multiple layouts, RTL/LTR support, dark/light mode, responsive design, and Sass support. Lightweight, easy to customize, and perfect for your next project.">
    <meta name="keywords" content="bootstrap dashboard, admin template, responsive dashboard, analytics dashboard, e-commerce dashboard, admin dashboard template, bootstrap 5, responsive design, RTL LTR support, dark light mode, sass supported, lightweight, easy to customize, bootstrap">
    <meta name="author" content="SRBThemes">
    <meta name="robots" content="index, follow">
    
    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="#!">
    <meta property="og:title" content="GotPOS | Bootstrap Admin Dashboard Template">
    <meta property="og:description" content="Get the best admin dashboard template with Bootstrap 5, multiple layouts, RTL/LTR support, dark/light mode, responsive design, and Sass support. Lightweight, easy to customize, and perfect for your next project.">
    <meta property="og:image" content="#!">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="#!">
    <meta property="twitter:title" content="GotPOS | Bootstrap Admin Dashboard Template">
    <meta property="twitter:description" content="Get the best admin dashboard template with Bootstrap 5, multiple layouts, RTL/LTR support, dark/light mode, responsive design, and Sass support. Lightweight, easy to customize, and perfect for your next project.">
    <meta property="twitter:image" content="#!">
    
    <!-- favicon -->
    <link rel="shortcut icon" href="./assets/favicon-B-3ALmIB.ico">

    <!-- Virtual Select -->

    <!-- Layout JS -->
    <!-- Bootstrap CSS -->
    <link href="./assets/bootstrap.rtl-B-QkKLS3.css" rel="stylesheet" type="text/css" disabled>
    <!-- Icons CSS -->
    <!-- App CSS -->
    <link href="./assets/app.rtl-Jqq4Msv0.css" rel="stylesheet" type="text/css" disabled>
    <!-- Admin Bundle JS -->
  <script type="module" crossorigin src="./assets/src\pos-yOGPzrsS.js"></script>
  <link rel="modulepreload" crossorigin href="./assets/admin.bundle-CBXoUBAg.js">
  <link rel="modulepreload" crossorigin href="./assets/air-datepicker-CyA5Zu09.js">
  <link rel="modulepreload" crossorigin href="./assets/en-Bpy3OoJA.js">
  <link rel="modulepreload" crossorigin href="./assets/filepond-plugin-image-preview-BcBSfvl1.js">
  <link rel="modulepreload" crossorigin href="./assets/form-input-spin.init-B8th2as5.js">
  
  
  
  
  <link rel="stylesheet" crossorigin href="./assets/admin-Bly6avC4.css">
  <link rel="stylesheet" crossorigin href="./assets/air-datepicker-ByzRugGb.css">
  <link rel="stylesheet" crossorigin href="./assets/filepond-plugin-image-preview-BLtz_BYx.css">
  <link rel="stylesheet" crossorigin href="./assets/virtual-select-hfXZVdeB.css">
</head>

<body class="sidebar-hidden">

<div class="container-fluid px-0">

    <div class="row g-0 pos-wrapper">
        <div class="col-lg-7 col-xl-8 col-xxl-9">
            <div class="position-relative">
                <header class="main-topbar start-0 position-absolute" id="main-topbar">
                    <a href="#!" class="navbar-brand">
                        <div class="logo-lg">
                            <img src="./assets/main-logo-CWEU2RA-.png" loading="lazy" aria-label="logo" alt="Logo" height="20" class="mx-auto logo-dark">
                            <img src="./assets/logo-white-B_ImY8Qx.png" loading="lazy" aria-label="logo" alt="Logo" height="20" class="mx-auto logo-light">
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
                            <a href="#!" class="bg-body-secondary rounded-circle size-6 avatar link link-success ms-4 flex-shrink-0"><i class="ri-shut-down-line"></i></a>
                        </span>
                        <a href="dashboard.php" class="btn h-9 px-3 btn-secondary py-6px d-none d-md-inline-flex align-items-center">
                            <i class="bi bi-globe pe-2 fs-sm"></i> Dashboard
                        </a>
                        <a href="dashboard.php" class="btn btn-outline-light bg-body-secondary shadow-sm border size-8 btn-icon rounded-1">
                            <i class="ri-settings-3-line fs-17"></i>
                        </a>
                        <!-- <button id="fullscreenBtn" type="button" class="btn btn-outline-light bg-body-secondary shadow-sm d-none d-md-block border size-8 btn-icon rounded-1">
                            <i data-lucide="maximize-2" class="size-4"></i>
                        </button> -->
                        <button type="button" class="topbar-link btn d-none d-md-block" id="fullScreenButton" aria-label="topbar-link">
                            <i data-lucide="maximize-2" class="size-4"></i>
                            <i data-lucide="minimize-2" class="size-4 d-none"></i>
                        </button>
                        <div class="dropdown">
                            <button class="btn topbar-link" type="button" aria-label="Notification-button" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="position-relative">
                                    <i data-lucide="store" class="size-4"></i>
                                    <span class="notification-animate position-absolute top-0 end-0 size-1-5 animate-ping bg-danger rounded-circle"></span>
                                    <span class="notification-animate position-absolute top-0 end-0 size-1-5 bg-danger rounded-circle"></span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end w-md p-0">
                                <div class="d-flex align-items-center border-bottom gap-2 p-4">
                                    <h6 class="flex-grow-1 mb-0">Cart</h6>
                                    <span class="badge bg-light border text-muted">4 Items</span>
                                </div>
                                <div class="vstack">
                                    <div class="cursor-pointer d-flex align-items-center gap-4 p-4 border-bottom">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar size-11 bg-light rounded-1 p-1">
                                                <img src="./assets/img-01-BBWp8t8E.png" loading="lazy" alt="Items" class="img-fluid size-10">
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="text-reset fw-medium">Cotton T-shirt</a>
                                                <p class="text-muted">Pink M</p>
                                            </div>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center gap-2">
                                            <div class="input-spin-group input-spin-primary">
                                                <button type="button" class="input-spin-minus size-9"><i data-lucide="minus" class="size-4"></i></button>
                                                <input type="text" class="input-spin form-control h-9 w-11" readonly="" value="2">
                                                <button type="button" class="input-spin-plus size-9"><i data-lucide="plus" class="size-4"></i></button>
                                            </div>
                                            <h6 class="mb-0 fw-medium">$1,256</h6>
                                        </div>
                                    </div>
                                    <div class="cursor-pointer d-flex align-items-center gap-4 p-4 border-bottom">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar size-11 bg-light rounded-1 p-1">
                                                <img src="./assets/img-02-ClVfz9I5.png" loading="lazy" alt="Items" class="img-fluid size-10">
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="text-reset fw-medium">Leather Shoulder Bag</a>
                                                <p class="text-muted">Brown</p>
                                            </div>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center gap-2">
                                            <div class="input-spin-group input-spin-primary">
                                                <button type="button" class="input-spin-minus size-9"><i data-lucide="minus" class="size-4"></i></button>
                                                <input type="text" class="input-spin form-control h-9 w-11" readonly="" value="1">
                                                <button type="button" class="input-spin-plus size-9"><i data-lucide="plus" class="size-4"></i></button>
                                            </div>
                                            <h6 class="mb-0 fw-medium">$2,890</h6>
                                        </div>
                                    </div>
                                    <div class="cursor-pointer d-flex align-items-center gap-4 p-4 border-bottom">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar size-11 bg-light rounded-1 p-1">
                                                <img src="./assets/img-03-oTTY_McP.png" loading="lazy" alt="Items" class="img-fluid size-10">
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="text-reset fw-medium">Wireless Headphones</a>
                                                <p class="text-muted">Black</p>
                                            </div>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center gap-2">
                                            <div class="input-spin-group input-spin-primary">
                                                <button type="button" class="input-spin-minus size-9"><i data-lucide="minus" class="size-4"></i></button>
                                                <input type="text" class="input-spin form-control h-9 w-11" readonly="" value="1">
                                                <button type="button" class="input-spin-plus size-9"><i data-lucide="plus" class="size-4"></i></button>
                                            </div>
                                            <h6 class="mb-0 fw-medium">$3,450</h6>
                                        </div>
                                    </div>
                                    <div class="cursor-pointer d-flex align-items-center gap-4 p-4 ">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="avatar size-11 bg-light rounded-1 p-1">
                                                <img src="./assets/img-04-DZ4OtBxS.png" loading="lazy" alt="Items" class="img-fluid size-10">
                                            </div>
                                            <div class="flex-grow-1">
                                                <a href="#!" class="text-reset fw-medium">Denim Jeans</a>
                                                <p class="text-muted">Blue 32</p>
                                            </div>
                                        </div>
                                        <div class="ms-auto d-flex align-items-center gap-2">
                                            <div class="input-spin-group input-spin-primary">
                                                <button type="button" class="input-spin-minus size-9"><i data-lucide="minus" class="size-4"></i></button>
                                                <input type="text" class="input-spin form-control h-9 w-11" readonly="" value="3">
                                                <button type="button" class="input-spin-plus size-9"><i data-lucide="plus" class="size-4"></i></button>
                                            </div>
                                            <h6 class="mb-0 fw-medium">$1,899</h6>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between gap-3 py-4 fs-16 px-5 border-top border-bottom">
                                        <p class="text-muted">Total :</p>
                                        <h6 class="mb-0">$14,549</h6>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown d-none d-md-block">
                            <button class="btn topbar-link" type="button" aria-label="Notification-button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i data-lucide="bell-ring" class="size-4"></i>
                                <span class="position-relative">
                                    <span class="notification-animate position-absolute top-0 end-0 size-1-5 animate-ping bg-success rounded-circle"></span>
                                    <span class="notification-animate position-absolute top-0 end-0 size-1-5 bg-success rounded-circle"></span>
                                </span>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg p-0">
                                <div class="d-flex align-items-center gap-2 p-4 border-bottom">
                                    <h6 class="flex-grow-1 mb-0">Notification (5)</h6>
                                    <ul class="nav nav-pills nav-light" id="notification-tab" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link py-1 px-3 fw-normal rounded-1 fs-13 active" id="tab-all" data-bs-toggle="pill" data-bs-target="#content-all" type="button" role="tab" aria-controls="content-all" aria-selected="true">All</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link py-1 px-3 fw-normal rounded-1 fs-13" id="tab-unread" data-bs-toggle="pill" data-bs-target="#content-unread" type="button" role="tab" aria-controls="content-unread" aria-selected="false">Unread</button>
                                        </li>
                                    </ul>
                                </div>
                                <div class="tab-content" id="notification-tabContent">
                                    <div class="tab-pane fade show active" id="content-all" role="tabpanel" aria-labelledby="tab-all">
                                        <div data-simplebar class="topbar-notification">
                                            <div class="vstack">
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-3-Bz6g7hsE.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Alex Morgan<span class="fw-normal text-muted ms-2">5m ago</span></h6>
                                                        <span class="text-muted">New order placed #ORD-4592</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-38-Do2kmZ3c.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">System Alert<span class="fw-normal text-muted ms-2">20m ago</span></h6>
                                                        <span class="mb-1">Product ID: #221 • Threshold crossed</span>
                                                        <span class="text-muted d-block">Low stock warning detected</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-51-CsAqIIgX.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Payment Received<span class="fw-normal text-muted ms-2">1h ago</span></h6>
                                                        <span class="text-muted">Invite you to GotPOS Admin & Dashboard</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom">
                                                    <img src="./assets/user-52-DJ5BGTMJ.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Inventory Update<span class="fw-normal text-muted ms-2">3h ago</span></h6>
                                                        <span class="mb-1">Category: Electronics • Qty: 15</span>
                                                        <span class="text-muted d-block">New items added to stock</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4">
                                                    <img src="./assets/user-53-SBhunRNA.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Staff Activity<span class="fw-normal text-muted ms-2">Yesterday</span></h6>
                                                        <span class="text-muted">Rahul closed shift with ₹32,180 cash</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="tab-pane fade" id="content-unread" role="tabpanel" aria-labelledby="tab-unread">
                                        <div data-simplebar class="topbar-notification">
                                            <div class="vstack">
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-3-Bz6g7hsE.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Alex Morgan<span class="fw-normal text-muted ms-2">5m ago</span></h6>
                                                        <span class="text-muted">New order placed #ORD-4592</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-38-Do2kmZ3c.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">System Alert<span class="fw-normal text-muted ms-2">20m ago</span></h6>
                                                        <span class="mb-1">Product ID: #221 • Threshold crossed</span>
                                                        <span class="text-muted d-block">Low stock warning detected</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-51-CsAqIIgX.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Payment Received<span class="fw-normal text-muted ms-2">1h ago</span></h6>
                                                        <span class="text-muted">Invite you to GotPOS Admin & Dashboard</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-22-C7Gl1Zmi.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Inventory Alert<span class="fw-normal text-muted ms-2">30m ago</span></h6>
                                                        <span class="text-muted d-block">Immediate restock recommended</span>
                                                    </span>
                                                </a>
                                                <a href="#!" class="notification-item position-relative d-flex gap-3 p-4 border-bottom unread">
                                                    <img src="./assets/user-17-Y_OiPHJx.png" loading="lazy" alt="User" class="rounded-circle size-9 flex-shrink-0">
                                                    <span class="flex-grow-1">
                                                        <h6 class="mb-1 fs-14">Shift Update<span class="fw-normal text-muted ms-2">45m ago</span></h6>
                                                        <span class="text-muted mb-1 d-block">Amit Patel has started a new shift</span>
                                                        <span class="text-muted d-block">Terminal: Counter #2</span>
                                                    </span>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown profile-dropdown">
                            <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <img src="./assets/user-71-RNjOCE17.png" loading="lazy" alt="en" class="object-fit-cover rounded size-8">
                            </button>
                            <div class="dropdown-menu w-64 profile-dropdown-menu p-0 dropdown-menu-end">
                                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                                    <img src="./assets/user-71-RNjOCE17.png" loading="lazy" alt="User" class="rounded size-9 flex-shrink-0">
                                    <div class="flex-grow-1 overflow-hidden">
                                        <h6 class="mb-0 text-truncate">Admin</h6>
                                        <p class="mb-0 text-truncate">
                                            <a href="#!" class="link link-custom-primary fs-sm">admin@pos.com</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="px-4 py-3">
                                    <ul class="list-unstyled mb-0">
                                        <li>
                                            <a class="profile-link rounded text-reset" href="pages-user.html">
                                                <i data-lucide="user" class="me-2 size-4 text-muted"></i> My Profile
                                            </a>
                                        </li>
                                        <li>
                                            <a class="profile-link rounded text-reset" href="dashboard.php">
                                                <i data-lucide="settings" class="me-2 size-4 text-muted"></i> POS Settings
                                            </a>
                                        </li>
                                        <li>
                                            <a class="profile-link rounded text-reset" href="staff.php">
                                                <i data-lucide="users" class="me-2 size-4 text-muted"></i> Staff Management
                                            </a>
                                        </li>
                                    </ul>
                                    <hr class="border my-2">
                                    <ul class="list-unstyled mb-0">
                                        <li class="d-flex profile-link rounded align-items-center justify-content-between">
                                            <a class="text-reset" href="#">
                                                <i data-lucide="sparkles" class="me-2 size-4 text-muted"></i> What's New?
                                            </a>
                                            <a href="#!"><i data-lucide="square-arrow-out-up-right" class="size-4 text-body-tertiary"></i></a>
                                        </li>
                                        <li class="d-flex profile-link rounded align-items-center justify-content-between">
                                            <a class="text-reset" href="pages-faq.html">
                                                <i data-lucide="help-circle" class="me-2 size-4 text-muted"></i> Get help?
                                            </a>
                                            <a href="#!"><i data-lucide="square-arrow-out-up-right" class="size-4 text-body-tertiary"></i></a>
                                        </li>
                                        <li class="d-flex profile-link rounded align-items-center justify-content-between">
                                            <a class="text-reset" href="#">
                                                <i data-lucide="layers" class="me-2 size-4 text-muted"></i> Switch space
                                            </a>
                                            <a href="#!"><span class="bg-body-secondary border text-body-secondary badge badge-square">S</span></a>
                                        </li>
                                        <li>
                                            <a class="profile-link rounded text-reset" href="logout.php">
                                                <i data-lucide="log-out" class="me-2 size-4 text-muted"></i> Log Out
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </header>
            </div>
            <div class="pos-left-side d-flex flex-wrap flex-md-nowrap">
                <div class="p-6 border-end shadow-sm bg-body-secondary category-wrapper" data-simplebar>
                    <div class="nav flex-md-column nav-pills gap-4" id="pos-category-tabs" role="tablist">
                        <button class="nav-link active text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-all-tab" data-bs-toggle="pill" data-bs-target="#cat-all" type="button" role="tab" aria-controls="cat-all" aria-selected="true">
                            <img src="./assets/img-14-Bq_mg9xG.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">All</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-clothing-tab" data-bs-toggle="pill" data-bs-target="#cat-clothing" type="button" role="tab" aria-controls="cat-clothing" aria-selected="true">
                            <img src="./assets/img-01-BBWp8t8E.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Clothing</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-footwear-tab" data-bs-toggle="pill" data-bs-target="#cat-footwear" type="button" role="tab" aria-controls="cat-footwear" aria-selected="false">
                            <img src="./assets/img-03-oTTY_McP.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Footwear</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-watches-tab" data-bs-toggle="pill" data-bs-target="#cat-watches" type="button" role="tab" aria-controls="cat-watches" aria-selected="false">
                            <img src="./assets/img-02-ClVfz9I5.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Watches</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-food-tab" data-bs-toggle="pill" data-bs-target="#cat-food" type="button" role="tab" aria-controls="cat-food" aria-selected="false">
                            <img src="./assets/img-22-D2w1zjf6.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Food</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-accessories-tab" data-bs-toggle="pill" data-bs-target="#cat-accessories" type="button" role="tab" aria-controls="cat-accessories" aria-selected="false">
                            <img src="./assets/img-19-UVF-VI0Q.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Accessories</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-electronics-tab" data-bs-toggle="pill" data-bs-target="#cat-electronics" type="button" role="tab" aria-controls="cat-electronics" aria-selected="false">
                            <img src="./assets/img-24-Q6X8xUcA.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Electronics</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-bags-tab" data-bs-toggle="pill" data-bs-target="#cat-bags" type="button" role="tab" aria-controls="cat-bags" aria-selected="false">
                            <img src="./assets/img-08-BXmGY-HZ.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Bags</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-beauty-tab" data-bs-toggle="pill" data-bs-target="#cat-beauty" type="button" role="tab" aria-controls="cat-beauty" aria-selected="false">
                            <img src="./assets/pr-37-CrN88d3y.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Beauty</span>
                        </button>
                        <button class="nav-link text-reset bg-body-secondary h-22 min-w-24 rounded avatar flex-column p-3" id="cat-fitness-tab" data-bs-toggle="pill" data-bs-target="#cat-fitness" type="button" role="tab" aria-controls="cat-fitness" aria-selected="false">
                            <img src="./assets/pr-40-pfTD79Wn.png" class="img-fluid size-8">
                            <span class="fw-medium fs-13 mt-2">Fitness</span>
                        </button>
                    </div>
                </div>
                <div class="p-5 items-section">
                    <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center mb-4">
                        <div class="d-flex gap-2">
                            <div class="dropdown">
                                <button type="button" class="btn bg-body-secondary text-muted border" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i data-lucide="filter" class="size-4 me-1"></i>
                                    Filter
                                </button>
                                <ul class="dropdown-menu dropdown-menu-start">
                                    <li><a class="dropdown-item" href="#!" data-filter="all">All Products</a></li>
                                    <li><a class="dropdown-item" href="#!" data-filter="instock">In Stock</a></li>
                                    <li><a class="dropdown-item" href="#!" data-filter="outofstock">Out of Stock</a></li>
                                    <li><a class="dropdown-item" href="#!" data-filter="lowstock">Low Stock</a></li>
                                    <li><a class="dropdown-item" href="#!" data-filter="price-low">Price: Low to High</a></li>
                                    <li><a class="dropdown-item" href="#!" data-filter="price-high">Price: High to Low</a></li>
                                    <li><a class="dropdown-item text-danger" href="#!" data-filter="reset">Reset Filters</a></li>
                                </ul>
                            </div>
                            <button type="button" class="btn btn-primary d-none d-xl-block">View All Brands</button>
                        </div>
                        <div class="d-flex gap-2">
                            <div class="position-relative">
                                <input type="text" id="tableSearch" class="form-control ps-10" placeholder="Search product...">
                                <i data-lucide="search" class="size-4 icon-dark position-absolute top-50 start-0 ms-4 translate-middle-y"></i>
                            </div>
                            <button type="button" class="btn btn-outline-light border bg-body-secondary size-10 btn-icon rounded-1">
                                <i class="ri-qr-scan-2-line fs-lg"></i>
                            </button>
                        </div>
                    </div>
                    <div class="px-5 mx-n5 product-cards" data-simplebar>
                        <div class="tab-content" id="pos-category-tabsContent">
                            <div class="tab-pane show active" id="cat-all" role="tabpanel" aria-labelledby="cat-all-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-35-B2Unq0hU.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Elegant Handbag</a>
                                                    <p class="text-muted small mb-0">SKU: BG-HBG-011</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$89.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-21-Dip6sWIz.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Classic Salted Potato Chips</a>
                                                    <p class="text-muted small mb-0">SKU: FD-SNK-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$2.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-3-BzZJ2azf.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Polka Dot Skater Dress</a>
                                                    <p class="text-muted small mb-0">SKU: DR-WMN-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$32.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-36-COs7RIFT.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Designer Shoulder Bag</a>
                                                    <p class="text-muted small mb-0">SKU: BG-HBG-012</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$99.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-2-BB_J2W-Z.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Red Camisole Top</a>
                                                    <p class="text-muted small mb-0">SKU: TP-WMN-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$17.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-16-qIivuYeB.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Running Sports Shoes</a>
                                                    <p class="text-muted small mb-0">SKU: FT-MEN-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$59.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-25-2HQ0Uvnq.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Full Cream Milk (1L)</a>
                                                    <p class="text-muted small mb-0">SKU: FD-DRY-005</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$2.49</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-4-JPhm9DaC.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Formal Pink Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-WMN-004</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$29.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-6-B0ESum4b.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Navy Mini Skirt</a>
                                                    <p class="text-muted small mb-0">SKU: SK-WMN-005</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$22.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-5-CDf-ezTw.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Light Blue Ripped Jeans</a>
                                                    <p class="text-muted small mb-0">SKU: JN-MEN-006</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$44.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-7-DOHIoQVB.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Beige Casual Jacket</a>
                                                    <p class="text-muted small mb-0">SKU: JK-MEN-007</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$64.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-8-D5PGAWyz.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Olive Green Hoodie</a>
                                                    <p class="text-muted small mb-0">SKU: HD-MEN-008</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$42.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-22-AujqxRgd.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Premium Roasted Almonds (200g)</a>
                                                    <p class="text-muted small mb-0">SKU: FD-DRY-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$8.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-9-BDpG0GmU.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Blue Formal Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-MEN-009</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$34.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-20-DLcbFUnr.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Smart Fitness Watch</a>
                                                    <p class="text-muted small mb-0">SKU: WT-SMT-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$99.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-11-eQyjF3vr.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Beige Palazzo Pants</a>
                                                    <p class="text-muted small mb-0">SKU: PT-WMN-011</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$28.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-12-C9E8vuC8.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Girls Floral Party Dress</a>
                                                    <p class="text-muted small mb-0">SKU: DR-KID-012</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$31.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-13-DYerNIVt.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Checked Casual Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-MEN-013</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$33.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-19-DZ7YoUrR.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Stainless Steel Analog Watch</a>
                                                    <p class="text-muted small mb-0">SKU: WT-MEN-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$129.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-14-CCZuqt_h.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Wrap Style Top</a>
                                                    <p class="text-muted small mb-0">SKU: TP-WMN-014</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$24.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-clothing" role="tabpanel" aria-labelledby="cat-clothing-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-1-DpkbRlV7.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Black Hoodie</a>
                                                    <p class="text-muted small mb-0">SKU: HD-MEN-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$39.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-3-BzZJ2azf.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Polka Dot Skater Dress</a>
                                                    <p class="text-muted small mb-0">SKU: DR-WMN-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$32.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-2-BB_J2W-Z.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Red Camisole Top</a>
                                                    <p class="text-muted small mb-0">SKU: TP-WMN-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$17.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-4-JPhm9DaC.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Formal Pink Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-WMN-004</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$29.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-6-B0ESum4b.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Navy Mini Skirt</a>
                                                    <p class="text-muted small mb-0">SKU: SK-WMN-005</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$22.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-5-CDf-ezTw.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Light Blue Ripped Jeans</a>
                                                    <p class="text-muted small mb-0">SKU: JN-MEN-006</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$44.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-7-DOHIoQVB.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Beige Casual Jacket</a>
                                                    <p class="text-muted small mb-0">SKU: JK-MEN-007</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$64.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-8-D5PGAWyz.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Olive Green Hoodie</a>
                                                    <p class="text-muted small mb-0">SKU: HD-MEN-008</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$42.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-9-BDpG0GmU.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Blue Formal Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-MEN-009</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$34.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-10-DD1LaX4c.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Yellow Party Dress</a>
                                                    <p class="text-muted small mb-0">SKU: DR-WMN-010</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$39.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-11-eQyjF3vr.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Beige Palazzo Pants</a>
                                                    <p class="text-muted small mb-0">SKU: PT-WMN-011</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$28.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-12-C9E8vuC8.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Girls Floral Party Dress</a>
                                                    <p class="text-muted small mb-0">SKU: DR-KID-012</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$31.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-13-DYerNIVt.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Checked Casual Shirt</a>
                                                    <p class="text-muted small mb-0">SKU: SH-MEN-013</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$33.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-14-CCZuqt_h.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Wrap Style Top</a>
                                                    <p class="text-muted small mb-0">SKU: TP-WMN-014</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$24.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-footwear" role="tabpanel" aria-labelledby="cat-footwear-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-15-6fjzOYEd.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Leather Ankle Boots</a>
                                                    <p class="text-muted small mb-0">SKU: FT-MEN-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$79.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-16-qIivuYeB.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Running Sports Shoes</a>
                                                    <p class="text-muted small mb-0">SKU: FT-MEN-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$59.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-17-DRBVlZ_i.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Glitter High Heels</a>
                                                    <p class="text-muted small mb-0">SKU: FT-WMN-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$49.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-18-7yzb-3ya.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Casual Sneakers</a>
                                                    <p class="text-muted small mb-0">SKU: FT-MEN-004</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$54.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-watches" role="tabpanel" aria-labelledby="cat-watches-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-19-DZ7YoUrR.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Stainless Steel Analog Watch</a>
                                                    <p class="text-muted small mb-0">SKU: WT-MEN-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$129.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-20-DLcbFUnr.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Smart Fitness Watch</a>
                                                    <p class="text-muted small mb-0">SKU: WT-SMT-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$99.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-food" role="tabpanel" aria-labelledby="cat-food-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-21-Dip6sWIz.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Classic Salted Potato Chips</a>
                                                    <p class="text-muted small mb-0">SKU: FD-SNK-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$2.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-22-AujqxRgd.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Premium Roasted Almonds (200g)</a>
                                                    <p class="text-muted small mb-0">SKU: FD-DRY-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$8.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-23-DKNsLIkt.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Instant Espresso Coffee</a>
                                                    <p class="text-muted small mb-0">SKU: FD-BEV-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$6.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-24-CaCL_grq.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Fresh Orange Juice (500ml)</a>
                                                    <p class="text-muted small mb-0">SKU: FD-BEV-004</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$3.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-25-2HQ0Uvnq.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Full Cream Milk (1L)</a>
                                                    <p class="text-muted small mb-0">SKU: FD-DRY-005</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$2.49</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-26-CbwXBE4S.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Instant Masala Noodles</a>
                                                    <p class="text-muted small mb-0">SKU: FD-SNK-006</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$1.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-accessories" role="tabpanel" aria-labelledby="cat-accessories-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-27-2sMQbU1b.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">USB Fast Charger with Cable</a>
                                                    <p class="text-muted small mb-0">SKU: EL-CHR-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$14.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-28-DCx9aLKb.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Genuine Leather Wallet</a>
                                                    <p class="text-muted small mb-0">SKU: AC-WLT-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$29.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-29-BJ3rc4Q5.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Men’s Classic Leather Belt</a>
                                                    <p class="text-muted small mb-0">SKU: AC-BLT-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$19.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-30-DInzn2Kb.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">UV Protection Sunglasses</a>
                                                    <p class="text-muted small mb-0">SKU: AC-SUN-004</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$24.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-31-CCNnv_hO.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Classic Fedora Hat</a>
                                                    <p class="text-muted small mb-0">SKU: AC-HAT-005</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$21.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-electronics" role="tabpanel" aria-labelledby="cat-electronics-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-32-BkjzPeWb.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Smartphone Pro Max</a>
                                                    <p class="text-muted small mb-0">SKU: EL-MOB-001</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$699.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-33-BAnfmEAf.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Wireless Over-Ear Headphones</a>
                                                    <p class="text-muted small mb-0">SKU: EL-AUD-002</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$129.99,</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-34-IiL_ZDz-.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Smart LED Television</a>
                                                    <p class="text-muted small mb-0">SKU: EL-TV-003</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$399.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-bags" role="tabpanel" aria-labelledby="cat-bags-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-35-B2Unq0hU.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Elegant Handbag</a>
                                                    <p class="text-muted small mb-0">SKU: BG-HBG-011</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$89.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-36-COs7RIFT.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Women’s Designer Shoulder Bag</a>
                                                    <p class="text-muted small mb-0">SKU: BG-HBG-012</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$99.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-beauty" role="tabpanel" aria-labelledby="cat-beauty-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-37-CrN88d3y.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Liquid Foundation Makeup</a>
                                                    <p class="text-muted small mb-0">SKU: BT-MKP-021</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$24.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-38-C9wZYe_k.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Herbal Skincare Essentials Set</a>
                                                    <p class="text-muted small mb-0">SKU: BT-SKN-022</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$49.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-39-Bgva8Ct-.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Matte Lipstick Collection</a>
                                                    <p class="text-muted small mb-0">SKU: BT-MKP-023</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$19.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="cat-fitness" role="tabpanel" aria-labelledby="cat-fitness-tab">
                                <div class="row g-4">
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-40-pfTD79Wn.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Adjustable Dumbbell Set</a>
                                                    <p class="text-muted small mb-0">SKU: FT-DBL-031</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$59.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-41-BqS_jmfx.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Indoor Exercise Spin Bike"</a>
                                                    <p class="text-muted small mb-0">SKU: FT-CRD-032</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$349.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 col-xl-4 col-xxl-3">
                                        <div class="card mb-0 pos-product-card">
                                            <div class="card-body p-4">
                                                <div class="bg-body-tertiary bg-opacity-75 rounded">
                                                    <img src="./assets/pr-42-J9Sh46nX.png" class="img-fluid p-4 d-block object-fit-cover">
                                                </div>
                                                <div class="mt-3">
                                                    <a href="#!" class="mb-1 d-block link link-custom fw-medium fs-16 text-truncate">Multi-Function Bench Press Set</a>
                                                    <p class="text-muted small mb-0">SKU: FT-BCH-033</p>
                                                    <div class="d-flex justify-content-between align-items-end">
                                                        <h6 class="fs-lg mb-0">$499.99</h6>
                                                        <button type="button" class="btn btn-primary btn-icon size-9 rounded-circle "><i data-lucide="plus" class="size-4"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-5 col-xl-4 col-xxl-3 border-top border-top-lg-0 border-start-lg position-relative">
            <div class="bg-body-secondary p-6 h-100">
                <div class="d-flex flex-wrap gap-3 justify-content-between align-items-center mb-5">
                    <h6 class="mb-0 fs-17">Order ID: #GOT1698</h6>
                    <div class="d-flex gap-2">
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-light bg-body-secondary shadow-sm border size-8 btn-icon rounded-1" data-bs-toggle="dropdown" aria-expanded="false"><i data-lucide="ellipsis-vertical" class="size-4"></i></button>
                            <ul class="dropdown-menu dropdown-menu-start">
                                <li><a class="dropdown-item" href="#!"><i data-lucide="printer" class="size-4 me-2"></i>Print Receipt</a></li>
                                <li><a class="dropdown-item" href="#!"><i data-lucide="download" class="size-4 me-2"></i>Download Invoice</a></li>
                                <li><a class="dropdown-item" href="#!"><i data-lucide="send" class="size-4 me-2"></i>Send Invoice</a></li>
                                <li><a class="dropdown-item" href="#!"><i data-lucide="refresh-ccw" class="size-4 me-2"></i>Retry Payment</a></li>
                                <li><a class="dropdown-item" href="#!"><i data-lucide="rotate-ccw" class="size-4 me-2"></i>Refund / Return</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-outline-light bg-body-secondary shadow-sm border size-8 btn-icon rounded-1"><i data-lucide="file-pen-line" class="size-4"></i></button>
                        <button type="button" class="btn btn-sub-danger size-8 btn-icon rounded-1" data-bs-toggle="modal" data-bs-target="#deleteModal"><i data-lucide="trash" class="size-4"></i></button>
                    </div>
                </div>
                <div class="btn-light d-flex flex-wrap align-items-center gap-3 p-3 bg-light bg-opacity-50 border rounded mb-6">
                    <img src="./assets/user-47-C1F3Gd9o.png" alt="Avatar" class="img-fluid size-11 rounded-circle">
                    <div class="flex-grow-1">
                        <a href="#!" class="fw-medium text-reset d-block">Customer</a>
                        <p class="text-muted text-truncate overflow-hidden">Platinum Member • 2,450 Points</p>
                    </div>
                    <button class="btn btn-active-dark btn-icon flex-shrink-0" data-bs-toggle="modal" data-bs-target="#editCustomerModal">
                        <i data-lucide="pencil" class="size-4"></i>
                    </button>
                </div>
                <div class="px-6 mx-n6 order-details" data-simplebar>
                    <div class="pb-5">
                        <div class="d-flex mb-3 justify-content-between align-items-center">
                            <h6 class="mb-0">Order Detail <span id="itemCount" class="text-muted fw-normal ms-1">(Items: 6)</span></h6>
                            <a href="#!" class="link link-custom-primary text-decoration-underline fs-sm">Clear All</a>
                        </div>
                        <div class="table-responsive pos-system-table">
                            <table class="table table-borderless text-nowrap align-middle mb-0 orderTable">
                                <thead>
                                    <tr>
                                        <th class="fw-normal text-muted">Product</th>
                                        <th class="fw-normal text-muted">QTY</th>
                                        <th class="fw-normal text-muted text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="bg-light bg-opacity-50 px-3 py-4 border rounded orderTable">
                        <div class="bg-body-secondary border rounded-pill px-3 py-2 mb-4">
                            <div class="d-flex gap-3 align-items-center">
                                <i data-lucide="badge-percent" class="icon-primary size-6"></i>
                                <span class="flex-grow-1">Discount 10%</span>
                                <a href="#!" class="bg-primary-subtle py-1 px-4 fs-13 border border-primary-subtle text-primary rounded-pill">Apply</a>
                            </div>
                        </div>
                        <div class="px-3 d-flex flex-column gap-4">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Sub Total</span>
                                <span class="text-muted" id="subtotalAmount">$330</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Discount</span>
                                <span class="text-danger" id="discountAmount">− $33</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">GST (18%)</span>
                                <span class="text-muted" id="gstAmount">$54</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Service Charge</span>
                                <span class="text-muted" id="serviceChargeAmount">$10</span>
                            </div>
                            <div class="d-flex justify-content-between fw-semibold border-top pt-4">
                                <span>Total Payable</span>
                                <span id="totalPayableAmount">$361</span>
                            </div>
                        </div>
                    </div>
                    <div class="my-6 orderTable">
                        <h6 class="mb-4">Select Method</h6>
                        <div class="d-flex flex-wrap gap-3">
                            <button class="btn btn-light pay-method" data-pay-btn data-bs-toggle="modal" data-bs-target="#cashModal"><i data-lucide="coins" class="size-4 me-1 align-middle"></i>Cash</button>
                            <button class="btn btn-light pay-method" data-pay-btn data-bs-toggle="modal" data-bs-target="#cardModal"><i data-lucide="credit-card" class="size-4 me-1 align-middle"></i>Card</button>
                            <button class="btn btn-light pay-method" data-pay-btn data-bs-toggle="modal" data-bs-target="#upiModal"><i data-lucide="scan-line" class="size-4 me-1 align-middle"></i>UPI / Scan</button>
                            <button class="btn btn-light pay-method" data-pay-btn data-bs-toggle="modal" data-bs-target="#bankModal"><i data-lucide="landmark" class="size-4 me-1 align-middle"></i>Bank Transfer</button>
                            <button class="btn btn-light pay-method" data-pay-btn data-bs-toggle="modal" data-bs-target="#splitModal"><i data-lucide="layers" class="size-4 me-1 align-middle"></i>Split Payment</button>
                        </div>
                    </div>
                    <div class="text-center avatar rounded-0" id="emptyOrderMessage">
                        <img src="./assets/no-order-CCjZwO4J.svg" alt="Image" class="size-52 my-auto">
                    </div>
                    <button type="button" class="btn btn-primary w-100 mt-auto">Process to Payment</button>
                </div>
            </div>
            <div class="d-flex flex-wrap flex-lg-nowrap gap-3 gap-lg-0 gap-xl-3 text-center justify-content-md-between border-top py-4 px-4 bg-body-secondary advance-features">
                <div class="w-20">
                    <a href="#!" class="text-reset" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <span class="avatar size-11 mx-auto bg-purple-subtle rounded-circle text-purple mb-2">
                            <i data-lucide="euro" class="size-5"></i>
                        </span>
                        <span>Payment</span>
                    </a>
                </div>
                <div class="w-20">
                    <a href="#!" class="text-reset" data-bs-toggle="modal" data-bs-target="#holdModal">
                        <span class="avatar size-11 mx-auto bg-success-subtle rounded-circle text-success mb-2">
                            <i data-lucide="pause" class="size-5"></i>
                        </span>
                        <span>Hold</span>
                    </a>
                </div>
                <div class="w-20">
                    <a href="#!" class="text-reset" data-bs-toggle="modal" data-bs-target="#invoicePreviewModal">
                        <span class="avatar size-11 mx-auto bg-pink-subtle rounded-circle text-pink mb-2">
                            <i data-lucide="file-text" class="size-5"></i>
                        </span>
                        <span>Invoice</span>
                    </a>
                </div>
                <div class="w-20">
                    <a href="#!" class="text-reset" data-bs-toggle="modal" data-bs-target="#payLaterModal">
                        <span class="avatar size-11 mx-auto bg-secondary-subtle rounded-circle text-secondary mb-2">
                            <i data-lucide="clock" class="size-5"></i>
                        </span>
                        <span>Pay Later</span>
                    </a>
                </div>
                <div class="w-20">
                    <a href="#!" class="text-reset" data-bs-toggle="modal" data-bs-target="#historyModal">
                        <span class="avatar size-11 mx-auto bg-danger-subtle rounded-circle text-danger mb-2">
                            <i data-lucide="folder-clock" class="size-5"></i>
                        </span>
                        <span>History</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- load your vendor scripts for all pages  -->
<!-- <div class="modal-backdrop fade show"></div> -->

<!-- Hold Payment Modal -->
<div class="modal fade" id="holdModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Hold Bill</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-4">
                    <div class="rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Bill Amount</span>
                            <h6 class="fs-16 mb-0">$361</h6>
                        </div>
                    </div>
                    <div>
                        <label for="holdReference" class="form-label">Hold Reference</label>
                        <input id="holdReference" type="text" class="form-control" placeholder="Table 5 / John Order">
                        <p class="text-muted mb-0 mt-1 fs-sm">This reference helps you identify the bill later.</p>
                    </div>
                    <div>
                        <label for="Notes" class="form-label">Notes</label>
                        <textarea id="Notes" class="form-control" rows="3" placeholder="Optional"></textarea>
                        <p class="text-muted mb-0 mt-1 fs-sm">Add special instructions or customer notes if needed.</p>
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50">Hold Bill</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Invoice Preview Modal -->
<div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header border-bottom-0 pb-0">
                <div class="d-flex flex-wrap align-items-center gap-4">
                    <h5 class="modal-title">Invoice Preview</h5>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-light btn-sm px-4 h-9"><i class="ri-file-pdf-2-line me-1"></i>PDF</button>
                        <button type="button" class="btn btn-active-dark btn-sm px-4 h-9"><i class="ri-mail-line me-1"></i>Email</button>
                        <button type="button" class="btn btn-active-dark btn-sm px-4 h-9"><i class="ri-bank-card-line me-1"></i>Online Payment</button>
                    </div>
                </div>
                <button type="button" class="btn btn-primary flex-shrink-0 ms-auto btn-sm px-4 h-9 rounded">Save Invoice</button>
            </div>
            <div class="modal-body">
                <div class="border p-4 rounded">
                    <div class="d-flex justify-content-between mb-6">
                        <h6 class="fs-16">#INV-7853</h6>
                        <a href="#!">
                            <img src="./assets/main-logo-CWEU2RA-.png" loading="lazy" aria-label="logo" alt="Logo" height="20" class="mx-auto logo-dark">
                            <img src="./assets/logo-white-B_ImY8Qx.png" loading="lazy" aria-label="logo" alt="Logo" height="20" class="mx-auto logo-light d-none">
                        </a>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p class="text-muted mb-1">Invoice Purpose:</p>
                            <h6>E-Commerce POS Integration</h6>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-10 align-items-center justify-content-end">
                                <div>
                                    <p class="text-muted mb-1">Issued On:</p>
                                    <h6>Apr 05, 2025</h6>
                                </div>
                                <div>
                                    <p class="text-muted mb-1">Due By:</p>
                                    <h6>Apr 15, 2025</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <p class="text-muted mb-2">Seller:</p>
                            <h6>NovaTech Solutions Ltd.</h6>
                            <p class="text-muted mb-1">Sector 62, Noida, India</p>
                            <a href="#!" class="link link-custom-primary d-block mb-1">finance@novatech.io</a>
                            <p class="text-muted">+91 98765 43210</p>
                        </div>
                        <div class="col-md-6 text-md-end">
                            <p class="text-muted mb-2">Client:</p>
                            <h6>BrightMart Retail Pvt. Ltd.</h6>
                            <p class="text-muted mb-1">MG Road, Bengaluru, India</p>
                            <a href="#!" class="link link-custom-primary d-block mb-1">payments@brightmart.in</a>
                            <p class="text-muted">+91 80456 77890</p>
                        </div>
                    </div>
                    <div class="table-responsive mb-2">
                        <table class="table table-borderless text-nowrap">
                            <thead>
                                <tr class="border-bottom">
                                    <th class="fw-medium text-muted">Service Details</th>
                                    <th class="fw-medium text-muted">Qty</th>
                                    <th class="fw-medium text-muted">Rate</th>
                                    <th class="fw-medium text-muted">GST</th>
                                    <th class="fw-medium text-muted text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>POS Backend API Integration</td>
                                    <td>1</td>
                                    <td>₹95,000</td>
                                    <td>₹0.00</td>
                                    <td class="text-end">₹95,000.00</td>
                                </tr>
                                <tr>
                                    <td>Inventory Sync & Reporting</td>
                                    <td>1</td>
                                    <td>₹35,000</td>
                                    <td>₹0.00</td>
                                    <td class="text-end">₹35,000.00</td>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-end">Total Payable</th>
                                    <th class="text-end">₹1,30,000.00</th>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p class="bg-light py-2 px-3 rounded border mb-6">
                        <span class="fw-medium">Payment Terms:</span> Full payment required before final deployment. Delays may affect delivery timelines.
                    </p>
                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <h6>Payment Information:</h6>
                            <p class="text-muted">UPI / Bank Transfer</p>
                            <p class="text-muted">Beneficiary: NovaTech Solutions Ltd.</p>
                            <p class="text-muted">IFSC: HDFC0001298 · A/C No: 502001889334</p>
                            <p class="text-muted">GST Responsibility: Buyer</p>
                        </div>
                        <div class="col-md-6 text-end">
                            <img src="./assets/signature-BiyA9Vug.png" alt="Signature" class="h-12 mb-2">
                            <h6 class="mb-0">Amit Verma</h6>
                            <p class="text-muted mb-0">Finance & Operations Head</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Pay Later Modal -->
<div class="modal fade" id="payLaterModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Pay Later</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-4">
                    <div class="rounded p-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span>Total Bill Amount</span>
                            <h6 class="fs-16 mb-0">$361</h6>
                        </div>
                    </div>
                    <div>
                        <label for="Customer" class="form-label">Customer</label>
                        <input id="Customer" type="text" class="form-control" placeholder="Search customer">
                    </div>
                    <div>
                        <label for="dueDate" class="form-label">Due Date</label>
                        <input type="text" class="form-control" id="dueDate" data-datepicker data-date-format="dd-MM-yyyy" placeholder="Payment date">
                    </div>
                    <div class="d-flex gap-3 mt-3">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary w-50">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Billing History Modal -->
<div class="modal fade" id="historyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Billing History</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-4">
                    <div class="d-flex align-items-center gap-2">
                        <div class="position-relative flex-grow-1">
                            <input type="text" id="lostItemSearch" class="form-control ps-9" placeholder="Search for Invoices...">
                            <i data-lucide="search" class="size-4 icon-dark position-absolute top-50 start-0 ms-3 translate-middle-y"></i>
                        </div>
                        <div class="dropdown">
                            <button type="button" class="btn btn-outline-light border btn-icon" data-bs-toggle="dropdown"><i class="ri-filter-3-line fs-xl"></i></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a href="#!" class="dropdown-item">Cash</a></li>
                                <li><a href="#!" class="dropdown-item">Card</a></li>
                                <li><a href="#!" class="dropdown-item">UPI</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="table-card table-responsive">
                        <table class="table table-borderless align-middle mb-0">
                            <thead>
                                <tr class="bg-light">
                                    <th>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox" id="checkAllInvoices">
                                        </div>
                                    </th>
                                    <th class="fw-medium text-muted">Invoice</th>
                                    <th class="fw-medium text-muted">Customer</th>
                                    <th class="fw-medium text-muted">Payment</th>
                                    <th class="fw-medium text-muted">Date & Time</th>
                                    <th class="fw-medium text-muted">Amount</th>
                                    <th class="fw-medium text-muted text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary"><input class="form-check-input" type="checkbox"></div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1023</a></td>
                                    <td>Walk-in</td>
                                    <td>Cash</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">10:45 AM</span></td>
                                    <td>₹1,250</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1024</a></td>
                                    <td>Rahul Patel</td>
                                    <td>UPI</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">11:10 AM</span></td>
                                    <td>₹3,420</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1025</a></td>
                                    <td>Neha Sharma</td>
                                    <td>Card</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">12:05 PM</span></td>
                                    <td>₹2,180</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1026</a></td>
                                    <td>Walk-in</td>
                                    <td>Cash</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">01:20 PM</span></td>
                                    <td>₹980</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown"><i class="ri-more-2-fill"></i></a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1027</a></td>
                                    <td>Amit Verma</td>
                                    <td>UPI</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">02:05 PM</span></td>
                                    <td>₹4,760</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown">
                                                <i class="ri-more-2-fill"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1028</a></td>
                                    <td>Pooja Shah</td>
                                    <td>Card</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">02:40 PM</span></td>
                                    <td>₹1,890</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown">
                                                <i class="ri-more-2-fill"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1029</a></td>
                                    <td>Walk-in</td>
                                    <td>Cash</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">03:15 PM</span></td>
                                    <td>₹640</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown">
                                                <i class="ri-more-2-fill"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1030</a></td>
                                    <td>Suresh Kumar</td>
                                    <td>UPI</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">04:00 PM</span></td>
                                    <td>₹2,750</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown">
                                                <i class="ri-more-2-fill"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="form-check check-primary">
                                            <input class="form-check-input" type="checkbox">
                                        </div>
                                    </td>
                                    <td><a href="#!" class="link link-custom-primary">#INV-1031</a></td>
                                    <td>Kiran Mehta</td>
                                    <td>Card</td>
                                    <td>16 Jan 2026 <span class="text-muted ms-2">04:35 PM</span></td>
                                    <td>₹3,980</td>
                                    <td class="d-flex justify-content-end gap-2">
                                        <div class="dropdown">
                                            <a href="#!" class="link link-custom-primary" data-bs-toggle="dropdown">
                                                <i class="ri-more-2-fill"></i>
                                            </a>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-eye-line"></i> View</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center"><i class="ri-pencil-line"></i> Edit</a></li>
                                                <li><a href="#!" class="dropdown-item d-flex gap-3 align-items-center text-danger"><i class="ri-delete-bin-5-line"></i> Delete</a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="row align-items-center g-3">
                        <div class="col-md-6">
                            <p class="text-muted text-center text-md-start mb-0">Showing <b class="me-1">1-9</b> of <b class="ms-1">27</b> Results</p>
                        </div>
                        <div class="col-md-6">
                            <nav aria-label="Page navigation example">
                                <ul class="pagination justify-content-center justify-content-md-end mb-0 products-pagination">
                                    <li class="page-item disabled"><a class="page-link" href="#"><i data-lucide="chevron-left" class="size-4"></i>Previous</a></li>
                                    <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                    <li class="page-item"><a class="page-link" href="#">2</a></li>
                                    <li class="page-item"><a class="page-link" href="#">Next<i data-lucide="chevron-right" class="size-4"></i></a></li>
                                </ul>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered w-96">
        <div class="modal-content">
            <button type="button" class="btn-close position-absolute top-0 end-0 p-4" data-bs-dismiss="modal"></button>
            <div class="modal-body pt-3 text-center">
                <img src="./assets/payment-D8Yv-YSk.png" alt="File" class="h-24 mt-4 mb-5">
                <h5>Payment Successful!</h5>
                <p class="text-muted mb-4">Payment successful! Your transaction has been processed smoothly.</p>
                <div class="mb-4">
                    <small class="text-muted">Amount</small>
                    <h4 class="my-1 fw-bold">$391</h4>
                    <small class="text-muted">06-01-2025 · Bill ID: <span class="text-body">#GOT1698</span></small>
                </div>
                <div class="d-flex justify-content-center gap-2 mb-3">
                    <a href="#!" class="btn btn-outline-light border btn-sm">
                        <img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAEAAAABACAMAAACdt4HsAAAAAXNSR0IB2cksfwAAAAlwSFlzAAALEwAACxMBAJqcGAAAAtBQTFRFAAAA4uLi4uLi4uLi4uLi39/f0NDQu7u7t7e3t7e3t7e3t7e34+Pj4uLi4uLi4uLi4uLi39/f0tLSvLy8t7e3t7e3t7e3t7e3t7e34uLi4uLi4uLi1dXVubm5t7e3t7e3t7e3tbW14+Pj4uLi4ODgxcXFt7e3t7e34+Pj4uLizMzM4+Pjzs7Ot7e3t7e3t7e3t7e30tLSt7e3t7e33Nzcvr6+t7e319fXuLi4t7e339/f2NjYxsbGurq6t7e3vkAwvkAwvUAwxFtO4d7d4eHhvLy8vkAwvkAwvkAwvkAwvkAwvkEx2r+81dXVvkAwvkAwvkAwvkEx2ry44eHhvkAwvkAw4uLivkAwwEExzEY151I+x0Qz8VZC51I+ykU0v0Ew8VZC7lVB5lE+30483U073U486H1w7IyA642A6pSJ57Os49fW8VhE7nNj5cfD7Xpr49rZ8FxI57St6aCW8l1K82hX82lX8mBN8VdD8mZU8mRR8VdE8mdV8VpH6pyS+beu/vDu/vDv/eXi95iM8VpG9Hts/evp/evo+bWs8l5L8VlF+sa//u7s96KX/NXQ//79/ejm/eTg/eTh/vb1/vf29YV29pGE/////vHw/vHv+bOq8lxJ//38/efk/N/b9pKF//f29Hlp96SZ//39+Kyj+K6k9Hdn/enm8mJP829e8ltI9HRj//v6+Kuh8l9M/ebj821c96Sa//z8/eHe/NPO9YR1//7+/vTy/vLx/vPx9pWJ9Hxs82ta//j39YN18mhW/ezq8mRS/N7a+r22+r63/NzY+8jB8VhF+sjB/ero829d9YZ4//r6+9bR82tZ/NfT8VZC82dV825d9Hpq9YBx9HZm825e8VZC8VZC8VZC8VZC8FZC8VZC8VZC7nRl8lVB8VZC8VZC8VZC8VVC8VdE7IR357Ot49jW4uLi4uLi4uLi4uLi4uLi4uLi4uLi4uLiUYXpzAAAAPB0Uk5TAAEWJSYmJSYmIQ8BBnXe8fLx8vLy7diLH2T1//////5GAQG0///kTQTG/wX/40fOHf/8e///uv//y//////MCBQWG8v/zAtoyeDi4fbMmvr//fzMXPXMobWzs/+z///////////+/v///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////67///////+O/jfiAVjg/wEgaJWdnun//8j7hOOLDhkDfGCOdgAAAp1JREFUeJzt109o01AcB/Dfdx2urptduxlbdI4Npq0DxSFDKLKbRw8i/kPQo+Bp3r14EI/Dg6AgA0/qGAgqDtSDHuacdBYF2+J0G06pA2Ura+c2XH0vS5ukJHmvycEd/EGT177k0+Y1v9/LA3kMbD4ALGwO5T3sVXAEmuwBVeCRcwKitqdrBGG5EdO2QBd+O5xPW7HCtn6s6kIVEEPeCaDgElUJVUDPujMQ2ug2CO4Ag+AS0AW3AAXx3htAIaRcAMrPSrMNSRdAFD8q7ciEC4B2rfu+a82dr90A1IFZvtseAF6ZgH41CYCMAChHPBN/ZgCOakkIvJMEiHof6UB/UEtC4I00cHhEB05gjWoGEg904NQKeQNOl6uAW+DMskfgbLlSugXOsT9x0RNQX7kPBDUVD20AyW9lVX/EE8ALybA3IIT73gBqvWcPtGkDyeauCP6wZj2+8Y/r1N4t+Mp3u+/YAx0cKNUBU9TNs6PUAKRpHwrqUQF8EAH7tfkXb6kPbBZDQzHwJZcoBsAnpGa8FAJhrFKWMc8ZsK1I2d58EKOJor+F16ngZE4MBJ4Q9Sm/xhigsHpxDJ8X5hLFhcK8Pog1AfE9KuDfkaTmj1IAv4QjefUSDEBn6iBhfE4C0Aaxc9gMdLOPM9GnEkDMN06lcGxiygzs9YFelHISwAGMajeSCahxENXYAM5jLDHkFujhw7HYgls1ABeBm1rvpdlGtlWAG9R+fCY8JABSg+puALeXtN72k2pePU5z5sJdw6OhZTamcZ0kw7oeZHFNFrCuSJ+ANanf0HR50LIm0nSlKl91OB24gkOVdyagHOIHDD3+A5sWEC04hIBgyWOKZMQCECy6jDEJxQpwWvaZgqVPqxVAXTNywDxCZAnUGv8e+AvrcRZQoeKKBQAAAABJRU5ErkJggg==" alt="File" class="size-4"> Download
                    </a>
                    <a href="#!" class="btn btn-outline-light border btn-sm">
                        <i class="ri-printer-line"></i> Print Receipt
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cash Payment Modal -->
<div class="modal fade" id="cashModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Cash Payment</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-5">
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Total Payable</span>
                        <span class="fw-semibold fs-17">$361</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Method</span>
                        <span class="badge bg-success-subtle text-success border border-success-subtle">Cash</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-medium">Cash Received</label>
                    <div class="input-group input-group-lg">
                        <span class="input-group-text">₹</span>
                        <input type="number" class="form-control" placeholder="0.00" autofocus>
                    </div>
                </div>
                <div class="d-flex flex-wrap gap-2 mb-5">
                    <button type="button" class="btn btn-outline-light border flex-fill">₹500</button>
                    <button type="button" class="btn btn-outline-light border flex-fill">₹1000</button>
                    <button type="button" class="btn btn-outline-light border flex-fill">₹2000</button>
                    <button type="button" class="btn btn-outline-light border flex-fill">Exact</button>
                </div>
                <div class="alert alert-success d-flex justify-content-between align-items-center mb-4">
                    <span class="fw-medium">Change to Return</span>
                    <span class="fw-semibold">₹0.00</span>
                </div>
                <div class="d-flex gap-3 mt-7">
                    <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-50">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Card Modal -->
<div class="modal fade" id="cardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Card Payment</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addPaymentForm">
                    <div class="row g-5">
                        <div class="col-12">
                            <ul class="nav nav-pills nav-light gap-3 mb-7" id="paymentMethodTabs" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link border text-start w-32 active" data-bs-toggle="pill" data-bs-target="#visa" type="button">
                                        <img src="./assets/visa-Xmp1xRly.png" alt="Visa" class="h-4 mb-1">
                                        <p>Visa</p>
                                    </button>
                                </li>
                                <li class="nav-item">
                                    <button class="nav-link border text-start w-32" data-bs-toggle="pill" data-bs-target="#mastercard" type="button">
                                        <img src="./assets/mastercard-CH_EB252.png" alt="mastercard" class="h-4 mb-1">
                                        <p>Mastercard</p>
                                    </button>
                                </li>
                            </ul>
                            <div class="tab-content">
                                <div class="tab-pane fade show active" id="visa">
                                    <div class="row g-5">
                                        <div class="col-md-6">
                                            <label class="form-label" for="visaCardNumber">Card Number</label>
                                            <input type="text" class="form-control" id="visaCardNumber" placeholder="XXXX XXXX XXXX 1234">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="visaExpiry">Expiry</label>
                                            <input type="text" class="form-control" id="visaExpiry" placeholder="MM/YY">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="visaCvv">Cvv</label>
                                            <input type="password" class="form-control" id="visaCvv" placeholder="***">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="visaAmount">Amount</label>
                                            <input type="number" class="form-control" id="visaAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="visaPaidAmount">Paid Amount</label>
                                            <input type="number" class="form-control" id="visaPaidAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="visaDueAmount">Due Amount</label>
                                            <input type="number" class="form-control" id="visaDueAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="visaTransactionId">Transaction Id</label>
                                            <input type="text" class="form-control" id="visaTransactionId" placeholder="TXN-000123">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="visaPaymentDate">Payment Date</label>
                                            <input type="text" class="form-control" id="visaPaymentDate" data-datepicker data-date-format="dd-MM-yyyy" placeholder="Payment date">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="visaPaymentNote">Payment Note</label>
                                            <textarea class="form-control" id="visaPaymentNote" rows="3" placeholder="Optional note..."></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="tab-pane fade" id="mastercard">
                                    <div class="row g-5">
                                        <div class="col-md-6">
                                            <label class="form-label" for="masterCardNumber">Card Number</label>
                                            <input type="text" class="form-control" id="masterCardNumber" placeholder="XXXX XXXX XXXX 5678">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="masterExpiry">Expiry</label>
                                            <input type="text" class="form-control" id="masterExpiry" placeholder="MM/YY">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="masterCvv">Cvv</label>
                                            <input type="password" class="form-control" id="masterCvv" placeholder="***">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="masterAmount">Amount</label>
                                            <input type="number" class="form-control" id="masterAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="masterPaidAmount">Paid Amount</label>
                                            <input type="number" class="form-control" id="masterPaidAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label" for="masterDueAmount">Due Amount</label>
                                            <input type="number" class="form-control" id="masterDueAmount" placeholder="0.00">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="masterTransactionId">Transaction Id</label>
                                            <input type="text" class="form-control" id="masterTransactionId" placeholder="TXN-000123">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label" for="masterPaymentDate">Payment Date</label>
                                            <input type="text" class="form-control" id="masterPaymentDate" data-datepicker data-date-format="dd-MM-yyyy" placeholder="Payment date">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="masterPaymentNote">Payment Note</label>
                                            <textarea class="form-control" id="masterPaymentNote" rows="3" placeholder="Optional note..."></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-5">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Submit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Add Scann Modal -->
<div class="modal fade" id="upiModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content">
            <div class="modal-body text-center">
                <p class="sub-title text-muted mb-2">Total Price</p>
                <h4 class="fw-bold sub-title mb-3">$361.00</h4>
                <img src="./assets/qr-CvtWFzmv.png" alt="QR Code" class="img-fluid w-56 mb-1">
                <p class="text-muted mb-3">Ask customer to scan & complete payment</p>
                <div class="d-flex gap-3 mt-7">
                    <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-50" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bank Transfer Modal -->
<div class="modal fade" id="bankModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Bank Transfer</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-3">
                <div class="mb-5">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Payable</span>
                        <span class="fw-semibold fs-17">$361</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment Method</span>
                        <span class="badge bg-info-subtle text-info border border-info-subtle">Bank Transfer</span>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="bankName" class="form-label">Bank Name</label>
                    <input id="bankName" type="text" class="form-control form-control-lg" placeholder="e.g. HDFC Bank, SBI, ICICI">
                </div>
                <div class="mb-4">
                    <label for="referenceID" class="form-label">Transaction Reference ID</label>
                    <input id="referenceID" type="text" class="form-control form-control-lg" placeholder="Enter UTR / Reference number">
                </div>
                <div class="mb-5">
                    <label for="transferDate" class="form-label">Transfer Date</label>
                    <input id="transferDate" type="text" class="form-control" id="visaPaymentDate" data-datepicker data-date-format="dd-MM-yyyy" placeholder="Transfer Date">
                </div>
                <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
                    <i class="ri-bank-line fs-5"></i>
                    <div class="small">
                        Ensure the transfer is completed and reflected in your bank
                        before confirming the payment.
                    </div>
                </div>
                <div class="d-flex gap-3 mt-7">
                    <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-50">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Split Payments Modal -->
<div class="modal fade" id="splitModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Split Payment</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex flex-column gap-5" id="paymentRows">
                    <div class="border rounded-3 p-3 payment-row">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <label for="paymentMethod" class="form-label small text-muted">Payment Method</label>
                                <div id="paymentMethod" class="payment-method"></div>
                            </div>
                            <div class="col-md-5">
                                <label for="Amount" class="form-label small text-muted">Amount</label>
                                <input id="Amount" type="number" class="form-control" placeholder="₹0.00">
                            </div>
                            <div class="col-md-3 text-end">
                                <label class="form-label small text-muted d-block">&nbsp;</label>
                                <button type="button" class="btn btn-secondary w-100">Apply</button>
                            </div>
                        </div>
                    </div>
                    <div class="border rounded-3 p-3 border payment-row">
                        <div class="row g-2 align-items-center">
                            <div class="col-md-4">
                                <label for="paymentMethod2" class="form-label small text-muted">Payment Method</label>
                                <div id="paymentMethod2" class="payment-method"></div>
                            </div>
                            <div class="col-md-5">
                                <label for="Amount2" class="form-label small text-muted">Amount</label>
                                <input id="Amount2" type="number" class="form-control" placeholder="₹0.00">
                            </div>
                            <div class="col-md-3 text-end">
                                <label class="form-label small text-muted d-block">&nbsp;</label>
                                <button type="button" class="btn btn-secondary w-100">Apply</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="d-flex justify-content-end mt-4">
                    <button type="button" class="btn btn-outline-light border" id="addPayment"><i data-lucide="plus" class="size-4"></i> Add Another</button>
                </div>
                <div class="d-flex gap-3 mt-7">
                    <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary w-50">Submit</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Customer Modal -->
<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Edit Customer Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCustomerForm">
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label">Customer Name</label>
                            <div class="d-flex align-items-center gap-2">
                                <input type="file" class="filepond size-10 mb-0 flex-shrink-0" id="squareUpload" name="filepond" accept="image/png, image/jpeg, image/gif" />
                                <input type="text" id="roleName" class="form-control" placeholder="e.g. Customer" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="membershipLevel" class="form-label">Membership Level <span class="text-danger">*</span></label>
                            <div id="membershipLevel"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="customerStatus" class="form-label">Status <span class="text-danger">*</span></label>
                            <div id="customerStatus"></div>
                        </div>
                        <div class="col-12">
                            <label for="Points" class="form-label">Reward Points <span class="text-danger">*</span></label>
                            <input id="Points" type="number" class="form-control" placeholder="e.g. 2450" min="0" required>
                            <small class="text-muted">Loyalty points earned by the customer</small>
                        </div>
                    </div>
                    <div class="d-flex gap-3 mt-7">
                        <button type="button" class="btn btn-light w-50" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary w-50">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xs">
        <div class="modal-content p-7 text-center">
            <div class="d-flex justify-content-center mb-4">
                <div class="size-14 bg-danger-subtle rounded-circle d-flex align-items-center justify-content-center size-16">
                    <i class="ri-delete-bin-line text-danger fs-2xl"></i>
                </div>
            </div>
            <h5 class="mb-4 lh-base">Are you sure you want to delete this details?</h5>
            <div class="d-flex justify-content-center align-items-center gap-2">
                <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Delete</button>
                <button type="button" class="btn btn-link text-reset" data-bs-dismiss="modal">Cancel</button>
            </div>
        </div>
    </div>
</div>

<!-- Virtual Select -->
<script src="assets/libs/virtual-select-plugin/virtual-select.min.js"></script>



</body>

</html><script>
let cart = [];

function addToCart(product) {
    const existingItem = cart.find(item => item.id === product.id);
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: parseFloat(product.price),
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
    cart[index].quantity += delta;
    if (cart[index].quantity <= 0) {
        removeFromCart(index);
    } else {
        updateCartUI();
    }
}

function updateCartUI() {
    const cartTbody = document.querySelector('table.orderTable tbody');
    if (!cartTbody) return;

    cartTbody.innerHTML = '';
    let subtotal = 0;

    cart.forEach((item, index) => {
        subtotal += item.price * item.quantity;
        const rowHtml = `
            <tr>
                <td>
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar size-10 bg-light rounded">
                            <img src="${item.image_path || './assets/img-01-BBWp8t8E.png'}" class="img-fluid">
                        </div>
                        <h6 class="mb-0 fs-14 text-truncate" style="max-width: 100px;">${item.name}</h6>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-sm btn-light border p-1" onclick="updateQuantity(${index}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="btn btn-sm btn-light border p-1" onclick="updateQuantity(${index}, 1)">+</button>
                    </div>
                </td>
                <td class="text-end">$${(item.price * item.quantity).toFixed(2)}</td>
            </tr>
        `;
        cartTbody.insertAdjacentHTML('beforeend', rowHtml);
    });

    document.getElementById('subtotalAmount').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('totalPayableAmount').innerText = '$' + subtotal.toFixed(2);
    document.getElementById('itemCount').innerText = '(Items: ' + cart.length + ')';

    const emptyMsg = document.getElementById('emptyOrderMessage');
    if (cart.length > 0) {
        emptyMsg.style.display = 'none';
    } else {
        emptyMsg.style.display = 'block';
    }
}

function filterCategory(catId) {
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        if (catId === 0 || item.dataset.category == catId) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
}

document.getElementById('tableSearch').addEventListener('input', function(e) {
    const term = e.target.value.toLowerCase();
    const items = document.querySelectorAll('.product-item');
    items.forEach(item => {
        if (item.dataset.name.includes(term)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});

async function processCheckout() {
    if (cart.length === 0) {
        alert("Cart is empty!");
        return;
    }

    if(!confirm("Are you sure you want to process this payment?")) return;

    try {
        const response = await fetch('api/save_sale.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                items: cart,
                total: cart.reduce((acc, item) => acc + (item.price * item.quantity), 0)
            })
        });

        const result = await response.json();
        if (result.success) {
            alert("Sale saved successfully! ID: " + result.sale_id);
            window.open('receipt.php?id=' + result.sale_id, '_blank');
            cart = [];
            updateCartUI();
        } else {
            alert("Error saving sale: " + result.error);
        }
    } catch (e) {
        alert("System error occurred while processing checkout.");
    }
}

// Initial setup
document.addEventListener('DOMContentLoaded', () => {
    const checkoutBtn = document.querySelector('button.btn-primary.w-100.mt-auto');
    if (checkoutBtn) {
        checkoutBtn.onclick = processCheckout;
    }
});
</script>
