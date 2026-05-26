<!DOCTYPE html>
<html
  lang="en"
  class="scroll-smooth group"
  data-layout="two-column"
  data-content-width="fluid"
  data-bs-theme="light"
  data-sidebar-colors="light"
  data-sidebar="large"
  data-nav-type="default"
  dir="ltr"
  data-colors="default"
  data-profile-sidebar
>
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no" />
    <title>GotPOS - Clean POS System</title>
    <link rel="shortcut icon" href="./assets/favicon-B-3ALmIB.ico" />

    <!-- App CSS -->
    <link rel="stylesheet" crossorigin href="./assets/admin-Bly6avC4.css" />
    <link rel="stylesheet" crossorigin href="./assets/swiper-bundle-PpNm7Js3.css" />

    <!-- Admin Bundle JS -->
    <script type="module" crossorigin src="./assets/src/index-DUABbF3Z.js"></script>
    <link rel="modulepreload" crossorigin href="./assets/admin.bundle-CBXoUBAg.js" />
    <link rel="modulepreload" crossorigin href="./assets/main-B7Jkv9i9.js" />
  </head>
  <body>
    <header class="main-topbar" id="main-topbar">
      <div class="navbar-brand gap-2">
        <div class="logos">
          <a href="dashboard.php" aria-label="Topbar Logo">
            <img src="./assets/main-logo-CWEU2RA-.png" height="22" alt="Logo" class="logo-dark" />
          </a>
        </div>
        <button type="button" id="toggleSidebar" class="sidebar-toggle btn p-0" aria-label="sidebar-toggle">
          <i class="ri-layout-left-line fs-17"></i>
        </button>
      </div>
      <div class="d-flex align-items-center gap-3 ms-auto pe-4">
        <a href="pos.php" class="btn h-9 px-3 btn-primary py-6px d-flex align-items-center">
          <i class="ri-shopping-bag-3-line fs-16 pe-1"></i> POS
        </a>
        <div class="dropdown profile-dropdown">
          <button class="btn p-0" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="./assets/user-71-RNjOCE17.png" alt="User" class="rounded size-8" />
          </button>
          <div class="dropdown-menu w-48 profile-dropdown-menu p-0 dropdown-menu-end">
            <div class="px-4 py-3 border-bottom">
                <h6 class="mb-0 text-truncate"><?php echo $_SESSION["username"] ?? "Admin"; ?></h6>
            </div>
            <div class="p-2">
              <a class="dropdown-item rounded" href="logout.php">
                <i class="ri-logout-circle-line me-2"></i> Log Out
              </a>
            </div>
          </div>
        </div>
      </div>
    </header>
