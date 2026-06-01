<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login again.'
    ]);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid request data.'
    ]);
    exit();
}

$items = isset($data['items']) && is_array($data['items']) ? $data['items'] : [];
$paymentMethod = isset($data['payment_method']) ? trim((string)$data['payment_method']) : 'Cash';

if (empty($items)) {
    echo json_encode([
        'success' => false,
        'error' => 'Cart is empty.'
    ]);
    exit();
}

$allowedPaymentMethods = ['Cash', 'Card', 'Mobile Money', 'Bank Transfer'];

if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
    $paymentMethod = 'Cash';
}

try {
    $pdo->beginTransaction();

    $calculatedTotal = 0;
    $validatedItems = [];

    $productStmt = $pdo->prepare("
        SELECT id, name, price, stock_quantity
        FROM products
        WHERE id = ?
        FOR UPDATE
    ");

    foreach ($items as $item) {
        $productId = isset($item['id']) ? (int)$item['id'] : 0;
        $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;

        if ($productId <= 0 || $quantity <= 0) {
            throw new Exception('Invalid cart item.');
        }

        $productStmt->execute([$productId]);
        $product = $productStmt->fetch();

        if (!$product) {
            throw new Exception('One of the selected products no longer exists.');
        }

        if ((int)$product['stock_quantity'] < $quantity) {
            throw new Exception('Insufficient stock for "' . $product['name'] . '". Available: ' . (int)$product['stock_quantity']);
        }

        $unitPrice = (float)$product['price'];
        $subtotal = $unitPrice * $quantity;

        $calculatedTotal += $subtotal;

        $validatedItems[] = [
            'product_id' => (int)$product['id'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'subtotal' => $subtotal,
        ];
    }

    if ($calculatedTotal <= 0) {
        throw new Exception('Sale total must be greater than zero.');
    }

    $discount = 0;
    $tax = 0;
    $finalAmount = $calculatedTotal + $tax - $discount;

    $saleStmt = $pdo->prepare("
        INSERT INTO sales 
            (user_id, total_amount, discount, tax, final_amount, payment_method)
        VALUES 
            (?, ?, ?, ?, ?, ?)
    ");

    $saleStmt->execute([
        (int)$_SESSION['user_id'],
        $calculatedTotal,
        $discount,
        $tax,
        $finalAmount,
        $paymentMethod
    ]);

    $saleId = (int)$pdo->lastInsertId();

    $itemStmt = $pdo->prepare("
        INSERT INTO sale_items 
            (sale_id, product_id, quantity, unit_price, subtotal)
        VALUES 
            (?, ?, ?, ?, ?)
    ");

    $stockStmt = $pdo->prepare("
        UPDATE products
        SET stock_quantity = stock_quantity - ?
        WHERE id = ?
    ");

    foreach ($validatedItems as $item) {
        $itemStmt->execute([
            $saleId,
            $item['product_id'],
            $item['quantity'],
            $item['unit_price'],
            $item['subtotal']
        ]);

        $stockStmt->execute([
            $item['quantity'],
            $item['product_id']
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'sale_id' => $saleId,
        'total' => number_format($finalAmount, 2, '.', '')
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
