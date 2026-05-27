<?php
session_start();

header("Content-Type: application/json");

require_once "../config/db.php";

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
            u.username
        FROM sales s
        LEFT JOIN users u ON s.user_id = u.id
        WHERE s.id = ?
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

    echo json_encode([
        "success" => true,
        "sale" => $sale,
        "items" => $items
    ]);
} catch (Exception $e) {
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage()
    ]);
}
