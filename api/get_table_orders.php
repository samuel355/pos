<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Please login again.']);
    exit();
}

$tableId = isset($_GET['table_id']) ? (int)$_GET['table_id'] : 0;

if ($tableId <= 0) {
    echo json_encode(['success' => false, 'error' => 'Table ID is required.']);
    exit();
}

try {
    $tableStmt = $pdo->prepare('SELECT id, name, capacity, status FROM restaurant_tables WHERE id = ?');
    $tableStmt->execute([$tableId]);
    $table = $tableStmt->fetch();

    if (!$table) {
        echo json_encode(['success' => false, 'error' => 'Table not found.']);
        exit();
    }

    $ordersStmt = $pdo->prepare("
        SELECT
            s.id,
            s.final_amount,
            s.payment_method,
            s.created_at,
            COUNT(si.id) AS item_count
        FROM sales s
        LEFT JOIN sale_items si ON si.sale_id = s.id
        WHERE s.table_id = ?
          AND DATE(s.created_at) = CURDATE()
        GROUP BY s.id
        ORDER BY s.created_at ASC
    ");
    $ordersStmt->execute([$tableId]);
    $orders = $ordersStmt->fetchAll();

    $itemsStmt = $pdo->prepare("
        SELECT
            si.sale_id,
            p.name AS product_name,
            si.quantity,
            si.unit_price,
            si.subtotal
        FROM sale_items si
        INNER JOIN sales s ON s.id = si.sale_id
        INNER JOIN products p ON p.id = si.product_id
        WHERE s.table_id = ?
          AND DATE(s.created_at) = CURDATE()
        ORDER BY s.created_at ASC, si.id ASC
    ");
    $itemsStmt->execute([$tableId]);
    $allItems = $itemsStmt->fetchAll();

    $itemsBySale = [];
    foreach ($allItems as $item) {
        $saleId = (int)$item['sale_id'];
        if (!isset($itemsBySale[$saleId])) {
            $itemsBySale[$saleId] = [];
        }
        $itemsBySale[$saleId][] = [
            'product_name' => $item['product_name'],
            'quantity' => (int)$item['quantity'],
            'unit_price' => (float)$item['unit_price'],
            'subtotal' => (float)$item['subtotal'],
        ];
    }

    $formattedOrders = [];
    $grandTotal = 0;

    foreach ($orders as $order) {
        $orderId = (int)$order['id'];
        $amount = (float)$order['final_amount'];
        $grandTotal += $amount;

        $formattedOrders[] = [
            'id' => $orderId,
            'final_amount' => $amount,
            'payment_method' => $order['payment_method'],
            'created_at' => $order['created_at'],
            'item_count' => (int)$order['item_count'],
            'items' => $itemsBySale[$orderId] ?? [],
        ];
    }

    echo json_encode([
        'success' => true,
        'table' => $table,
        'orders' => $formattedOrders,
        'order_count' => count($formattedOrders),
        'grand_total' => $grandTotal,
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
