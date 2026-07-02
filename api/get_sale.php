<?php
session_start();

header("Content-Type: application/json");

require_once "../config/db.php";
require_once "../includes/table_packages.php";

ensureTablePackageSchema($pdo);

if (!isset($_SESSION["user_id"])) {
    echo json_encode([
        "success" => false,
        "error" => "Unauthorized."
    ]);
    exit;
}

$saleId = isset($_GET["id"]) ? (int)$_GET["id"] : 0;

if ($saleId <= 0) {
    echo json_encode([
        "success" => false,
        "error" => "Invalid sale ID."
    ]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT
            s.*,
            u.username,
            rt.name AS table_name,
            tb.customer_name,
            tb.customer_contact,
            tb.booked_at,
            tb.closed_at,
            tp.id AS package_id,
            tp.name AS package_name,
            tp.price AS package_price,
            tp.capacity AS package_capacity,
            tp.tier AS package_tier
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        LEFT JOIN table_bookings tb
            ON tb.sale_id = s.id
            OR (
                s.table_id IS NOT NULL
                AND tb.table_id = s.table_id
                AND s.created_at >= tb.booked_at
                AND (tb.closed_at IS NULL OR s.created_at <= tb.closed_at)
            )
        LEFT JOIN restaurant_tables rt ON rt.id = COALESCE(s.table_id, tb.table_id)
        LEFT JOIN table_packages tp ON tp.id = tb.package_id
        WHERE s.id = ?
        ORDER BY tb.id DESC
        LIMIT 1
    ");
    $stmt->execute([$saleId]);
    $sale = $stmt->fetch();

    if (!$sale) {
        echo json_encode([
            "success" => false,
            "error" => "Sale not found."
        ]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            si.*,
            p.name
        FROM sale_items si
        LEFT JOIN products p ON si.product_id = p.id
        WHERE si.sale_id = ?
        ORDER BY si.id ASC
    ");
    $stmt->execute([$saleId]);
    $items = $stmt->fetchAll();

    $packageItems = [];
    if (!empty($sale["package_id"])) {
        $packageItemsStmt = $pdo->prepare("
            SELECT item_name, item_type, quantity, unit_cost
            FROM table_package_items
            WHERE package_id = ?
            ORDER BY display_order ASC, id ASC
        ");
        $packageItemsStmt->execute([(int)$sale["package_id"]]);
        $packageItems = $packageItemsStmt->fetchAll();
    }

    echo json_encode([
        "success" => true,
        "sale" => $sale,
        "items" => $items,
        "package_items" => $packageItems
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
