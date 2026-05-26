<?php
session_start();
require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);
$items = $data['items'] ?? [];
$total = $data['total'] ?? 0;

if (empty($items)) {
    echo json_encode(['success' => false, 'error' => 'Cart is empty']);
    exit();
}

try {
    $pdo->beginTransaction();

    // Insert Sale
    $stmt = $pdo->prepare("INSERT INTO sales (user_id, total_amount, final_amount) VALUES (?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $total, $total]);
    $sale_id = $pdo->lastInsertId();

    // Insert Sale Items
    $stmt = $pdo->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, subtotal) VALUES (?, ?, ?, ?, ?)");
    foreach ($items as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $stmt->execute([$sale_id, $item['id'], $item['quantity'], $item['price'], $subtotal]);
        
        // Update stock
        $updateStock = $pdo->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
        $updateStock->execute([$item['quantity'], $item['id']]);
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'sale_id' => $sale_id]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
