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
            COALESCE(s.customer_name, tb.customer_name) AS customer_name,
            COALESCE(s.customer_contact, tb.customer_contact) AS customer_contact,
            tb.id AS booking_id,
            tb.sale_id AS booking_sale_id,
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
            ON tb.id = s.table_booking_id
            OR tb.sale_id = s.id
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

    $linkedSaleIds = [$saleId];
    $billItems = $items;
    $billSummary = [
        "total_amount" => (float)$sale["total_amount"],
        "package_amount" => !empty($sale["package_id"]) ? (float)$sale["total_amount"] : 0,
        "additional_amount" => !empty($sale["package_id"]) ? 0 : (float)$sale["total_amount"],
        "discount" => (float)$sale["discount"],
        "tax" => (float)$sale["tax"],
        "final_amount" => (float)$sale["final_amount"],
        "sale_count" => 1
    ];

    if (!empty($sale["booking_id"])) {
        $linkedSalesStmt = $pdo->prepare("
            SELECT id
            FROM sales
            WHERE table_booking_id = ?
               OR id = ?
               OR id = ?
               OR (
                    table_id = ?
                    AND created_at >= ?
                    AND (? IS NULL OR created_at <= ?)
               )
            ORDER BY created_at ASC, id ASC
        ");
        $linkedSalesStmt->execute([
            (int)$sale["booking_id"],
            (int)$sale["id"],
            (int)$sale["booking_sale_id"],
            (int)($sale["table_id"] ?? 0),
            $sale["booked_at"],
            $sale["closed_at"],
            $sale["closed_at"]
        ]);
        $linkedSaleIds = array_map("intval", array_column($linkedSalesStmt->fetchAll(), "id"));

        if (empty($linkedSaleIds)) {
            $linkedSaleIds = [$saleId];
        }

        $placeholders = implode(",", array_fill(0, count($linkedSaleIds), "?"));

        $billItemsStmt = $pdo->prepare("
            SELECT
                si.*,
                p.name,
                s.created_at AS sale_created_at
            FROM sale_items si
            INNER JOIN sales s ON s.id = si.sale_id
            LEFT JOIN products p ON si.product_id = p.id
            WHERE si.sale_id IN ($placeholders)
            ORDER BY s.created_at ASC, si.id ASC
        ");
        $billItemsStmt->execute($linkedSaleIds);
        $billItems = $billItemsStmt->fetchAll();

        $summaryStmt = $pdo->prepare("
            SELECT
                COALESCE(SUM(total_amount), 0) AS total_amount,
                COALESCE(SUM(CASE WHEN id = ? THEN total_amount ELSE 0 END), 0) AS package_amount,
                COALESCE(SUM(CASE WHEN id <> ? THEN total_amount ELSE 0 END), 0) AS additional_amount,
                COALESCE(SUM(discount), 0) AS discount,
                COALESCE(SUM(tax), 0) AS tax,
                COALESCE(SUM(final_amount), 0) AS final_amount,
                COUNT(*) AS sale_count
            FROM sales
            WHERE id IN ($placeholders)
        ");
        $summaryStmt->execute(array_merge([
            (int)$sale["booking_sale_id"],
            (int)$sale["booking_sale_id"]
        ], $linkedSaleIds));
        $billSummary = $summaryStmt->fetch();
    }

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
        "bill_items" => $billItems,
        "bill_summary" => $billSummary,
        "linked_sale_ids" => $linkedSaleIds,
        "package_items" => $packageItems
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
