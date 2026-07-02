<?php
session_start();

header('Content-Type: application/json');

require_once '../config/db.php';
require_once '../includes/table_packages.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Unauthorized. Please login again.'
    ]);
    exit();
}

ensureTablePackageSchema($pdo);

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
$tableId = isset($data['table_id']) && $data['table_id'] !== '' && $data['table_id'] !== null
    ? (int)$data['table_id']
    : null;
$customerName = isset($data['customer_name']) ? trim((string)$data['customer_name']) : '';
$customerContact = isset($data['customer_contact']) ? trim((string)$data['customer_contact']) : '';
$tableBookingId = null;

if (mb_strlen($customerName) > 120) {
    $customerName = mb_substr($customerName, 0, 120);
}

if (mb_strlen($customerContact) > 50) {
    $customerContact = mb_substr($customerContact, 0, 50);
}

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

    if ($tableId !== null) {
        if ($tableId <= 0) {
            $tableId = null;
        } else {
            $tableCheck = $pdo->prepare("
                SELECT
                    rt.id,
                    tb.id AS booking_id
                FROM restaurant_tables rt
                LEFT JOIN table_bookings tb
                    ON tb.table_id = rt.id
                   AND tb.status = 'open'
                WHERE rt.id = ?
                  AND rt.status = 'Active'
                LIMIT 1
            ");
            $tableCheck->execute([$tableId]);
            $table = $tableCheck->fetch();

            if (!$table) {
                throw new Exception('Selected table is not available.');
            }

            if (empty($table['booking_id'])) {
                throw new Exception('Book the selected table with a package before adding extra orders to it.');
            }

            $tableBookingId = (int)$table['booking_id'];
        }
    }

    $discount = 0;
    $tax = 0;
    $finalAmount = $calculatedTotal + $tax - $discount;

    $saleStmt = $pdo->prepare("
        INSERT INTO sales
            (user_id, table_id, table_booking_id, customer_name, customer_contact, total_amount, discount, tax, final_amount, payment_method)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $saleStmt->execute([
        (int)$_SESSION['user_id'],
        $tableId,
        $tableBookingId,
        $customerName !== '' ? $customerName : null,
        $customerContact !== '' ? $customerContact : null,
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
