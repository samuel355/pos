<?php

function tableColumnExists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

function tableExists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

function ensureTablePackageSchema(PDO $pdo): void
{
    if (tableExists($pdo, "categories") && !tableColumnExists($pdo, "categories", "status")) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active' AFTER name");
    }

    if (tableExists($pdo, "categories") && !tableColumnExists($pdo, "categories", "image_path")) {
        $pdo->exec("ALTER TABLE categories ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER status");
    }

    if (tableExists($pdo, "restaurant_tables") && !tableColumnExists($pdo, "restaurant_tables", "capacity")) {
        $pdo->exec("ALTER TABLE restaurant_tables ADD COLUMN capacity INT NOT NULL DEFAULT 5 AFTER name");
    }

    if (tableExists($pdo, "restaurant_tables") && !tableColumnExists($pdo, "restaurant_tables", "customer_contact")) {
        $pdo->exec("ALTER TABLE restaurant_tables ADD COLUMN customer_contact VARCHAR(50) DEFAULT NULL AFTER reserved_by");
    }

    if (tableExists($pdo, "sales") && !tableColumnExists($pdo, "sales", "customer_name")) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN customer_name VARCHAR(120) DEFAULT NULL AFTER table_id");
    }

    if (tableExists($pdo, "sales") && !tableColumnExists($pdo, "sales", "customer_contact")) {
        $pdo->exec("ALTER TABLE sales ADD COLUMN customer_contact VARCHAR(50) DEFAULT NULL AFTER customer_name");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS table_packages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            price DECIMAL(10, 2) NOT NULL,
            capacity INT NOT NULL,
            tier ENUM('VIP', 'VVIP') NOT NULL DEFAULT 'VIP',
            product_id INT DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (tableExists($pdo, "table_packages")) {
        $pdo->exec("UPDATE table_packages SET tier = 'VIP' WHERE tier = 'Regular'");
        $pdo->exec("ALTER TABLE table_packages MODIFY COLUMN tier ENUM('VIP','VVIP') NOT NULL DEFAULT 'VIP'");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS table_package_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            package_id INT NOT NULL,
            product_id INT DEFAULT NULL,
            item_name VARCHAR(150) NOT NULL,
            item_type ENUM('premium', 'regular', 'other') NOT NULL DEFAULT 'regular',
            quantity INT NOT NULL DEFAULT 1,
            unit_cost DECIMAL(10, 2) NOT NULL DEFAULT 0,
            display_order INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (package_id) REFERENCES table_packages(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    if (tableExists($pdo, "table_package_items") && !tableColumnExists($pdo, "table_package_items", "product_id")) {
        $pdo->exec("ALTER TABLE table_package_items ADD COLUMN product_id INT DEFAULT NULL AFTER package_id");
    }

    if (tableExists($pdo, "table_package_items") && !tableColumnExists($pdo, "table_package_items", "unit_cost")) {
        $pdo->exec("ALTER TABLE table_package_items ADD COLUMN unit_cost DECIMAL(10, 2) NOT NULL DEFAULT 0 AFTER quantity");
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS table_bookings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            table_id INT NOT NULL,
            package_id INT NOT NULL,
            sale_id INT DEFAULT NULL,
            customer_name VARCHAR(120) NOT NULL,
            customer_contact VARCHAR(50) NOT NULL,
            status ENUM('open', 'closed', 'cancelled') NOT NULL DEFAULT 'open',
            booked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            closed_at TIMESTAMP NULL DEFAULT NULL,
            created_by INT DEFAULT NULL,
            FOREIGN KEY (table_id) REFERENCES restaurant_tables(id) ON DELETE CASCADE,
            FOREIGN KEY (package_id) REFERENCES table_packages(id) ON DELETE RESTRICT,
            FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

}

function createPackageSale(PDO $pdo, int $tableId, int $packageId, int $userId): int
{
    $stmt = $pdo->prepare("
        SELECT tp.id, tp.name, tp.price, tp.product_id
        FROM table_packages tp
        WHERE tp.id = ? AND tp.is_active = 1
        LIMIT 1
    ");
    $stmt->execute([$packageId]);
    $package = $stmt->fetch();

    if (!$package) {
        throw new Exception("Selected package does not exist.");
    }

    if (empty($package["product_id"])) {
        throw new Exception("Selected package is missing its POS product link.");
    }

    $saleStmt = $pdo->prepare("
        INSERT INTO sales (user_id, table_id, total_amount, discount, tax, final_amount, payment_method)
        VALUES (?, ?, ?, 0, 0, ?, 'Cash')
    ");
    $saleStmt->execute([$userId, $tableId, $package["price"], $package["price"]]);
    $saleId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal)
        VALUES (?, ?, 1, ?, ?)
    ");
    $itemStmt->execute([$saleId, (int)$package["product_id"], $package["price"], $package["price"]]);

    return $saleId;
}

function ensureCategory(PDO $pdo, string $name): int
{
    $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    if ($id) {
        return (int)$id;
    }

    $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
    $stmt->execute([$name]);
    return (int)$pdo->lastInsertId();
}

function ensureProduct(PDO $pdo, string $name, int $categoryId, float $price): int
{
    $stmt = $pdo->prepare("SELECT id FROM products WHERE name = ? LIMIT 1");
    $stmt->execute([$name]);
    $id = $stmt->fetchColumn();

    if ($id) {
        $update = $pdo->prepare("UPDATE products SET category_id = ?, price = ? WHERE id = ?");
        $update->execute([$categoryId, $price, (int)$id]);
        return (int)$id;
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (name, category_id, price, stock_quantity, image_path)
        VALUES (?, ?, ?, 9999, NULL)
    ");
    $stmt->execute([$name, $categoryId, $price]);
    return (int)$pdo->lastInsertId();
}

function seedClubTables(PDO $pdo): void
{
    for ($i = 1; $i <= 20; $i++) {
        $name = "Table " . str_pad((string)$i, 2, "0", STR_PAD_LEFT);
        $stmt = $pdo->prepare("SELECT id FROM restaurant_tables WHERE name = ? LIMIT 1");
        $stmt->execute([$name]);

        if (!$stmt->fetch()) {
            $insert = $pdo->prepare("INSERT INTO restaurant_tables (name, capacity, status) VALUES (?, 5, 'Active')");
            $insert->execute([$name]);
        }
    }
}

function seedTablePackages(PDO $pdo): void
{
    $existingCount = (int)$pdo->query("SELECT COUNT(*) FROM table_packages")->fetchColumn();

    if ($existingCount > 0) {
        return;
    }

    $categoryId = ensureCategory($pdo, "Table Packages");

    $packages = [
        [
            "name" => "10K Package",
            "price" => 10000,
            "capacity" => 5,
            "tier" => "VIP",
            "items" => [
                ["1 BOTTLE HENNESSY VS", "premium", 1],
                ["2 BOTTLES MOËT NECTAR ROSE", "premium", 2],
                ["1 BOTTLE TEQUILA", "premium", 1],
                ["5 Energy Drink", "regular", 5],
                ["5 Entry Passes", "other", 5],
            ],
        ],
        [
            "name" => "20K Package",
            "price" => 20000,
            "capacity" => 5,
            "tier" => "VIP",
            "items" => [
                ["2 HENNESSY VSOP", "premium", 2],
                ["2 MOËT NECTAR ROSE", "premium", 2],
                ["2 VEUVE CLIQUOT RICH", "premium", 2],
                ["2 BOTTLES OF TEQUILA", "premium", 2],
                ["5 ENERGY DRINK", "regular", 5],
                ["5 ENTRY PASSES", "other", 5],
            ],
        ],
        [
            "name" => "30K Package",
            "price" => 30000,
            "capacity" => 7,
            "tier" => "VIP",
            "items" => [
                ["2 HENNESSY VSOP", "premium", 2],
                ["4 CHAMPAGNE NECTAR ROSE", "premium", 4],
                ["3 VEUVE CLIQUOT RICH", "premium", 3],
                ["3 BOTTLES OF TEQUILA", "premium", 3],
                ["5 ENERGY DRINK", "regular", 5],
                ["7 ENTRY PASSES", "other", 7],
            ],
        ],
        [
            "name" => "40K Package",
            "price" => 40000,
            "capacity" => 10,
            "tier" => "VVIP",
            "items" => [
                ["3 HENNESSY VSOP", "premium", 3],
                ["5 CHAMPAGNE NECTAR ROSE", "premium", 5],
                ["4 VEUVE CLIQUOT RICH", "premium", 4],
                ["3 BOTTLES OF TEQUILA", "premium", 3],
                ["10 ENERGY DRINK", "regular", 10],
                ["10 BEL AQUA WATER", "regular", 10],
                ["10 ENTRY PASSES", "other", 10],
            ],
        ],
        [
            "name" => "50K Package",
            "price" => 50000,
            "capacity" => 10,
            "tier" => "VVIP",
            "items" => [
                ["4 HENNESSY VSOP", "premium", 4],
                ["6 MOËT NECTAR ROSÈ", "premium", 6],
                ["5 VEUVE CLIQUOT RICH", "premium", 5],
                ["4 BOTTLES OF TEQUILA", "premium", 4],
                ["10 Energy Drink", "regular", 10],
                ["10 WATER", "regular", 10],
                ["10 ENTRY PASSES", "other", 10],
            ],
        ],
    ];

    foreach ($packages as $package) {
        $productId = ensureProduct($pdo, $package["name"], $categoryId, (float)$package["price"]);

        $stmt = $pdo->prepare("SELECT id FROM table_packages WHERE name = ? LIMIT 1");
        $stmt->execute([$package["name"]]);
        $packageId = $stmt->fetchColumn();

        if ($packageId) {
            $update = $pdo->prepare("
                UPDATE table_packages
                SET price = ?, capacity = ?, tier = ?, product_id = ?, is_active = 1
                WHERE id = ?
            ");
            $update->execute([$package["price"], $package["capacity"], $package["tier"], $productId, (int)$packageId]);
            $packageId = (int)$packageId;
        } else {
            $insert = $pdo->prepare("
                INSERT INTO table_packages (name, price, capacity, tier, product_id)
                VALUES (?, ?, ?, ?, ?)
            ");
            $insert->execute([$package["name"], $package["price"], $package["capacity"], $package["tier"], $productId]);
            $packageId = (int)$pdo->lastInsertId();
        }

        $deleteItems = $pdo->prepare("DELETE FROM table_package_items WHERE package_id = ?");
        $deleteItems->execute([$packageId]);

        $itemInsert = $pdo->prepare("
            INSERT INTO table_package_items (package_id, item_name, item_type, quantity, display_order)
            VALUES (?, ?, ?, ?, ?)
        ");

        foreach ($package["items"] as $index => $item) {
            $itemInsert->execute([$packageId, $item[0], $item[1], $item[2], $index + 1]);
        }
    }
}
