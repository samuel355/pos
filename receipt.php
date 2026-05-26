<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$sale_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT s.*, u.username 
    FROM sales s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die('Sale not found');
}

$stmt = $pdo->prepare("
    SELECT si.*, p.name 
    FROM sale_items si 
    LEFT JOIN products p ON si.product_id = p.id 
    WHERE si.sale_id = ?
");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo (int)$sale_id; ?></title>
    <style>
        body {
            font-family: monospace;
            width: 320px;
            margin: 0 auto;
            padding: 20px;
            color: #000;
        }

        .text-center {
            text-align: center;
        }

        .text-end {
            text-align: right;
        }

        .d-flex {
            display: flex;
            justify-content: space-between;
            gap: 10px;
        }

        hr {
            border: 0;
            border-top: 1px dashed #000;
            margin: 12px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            padding: 4px 0;
            vertical-align: top;
        }

        .small {
            font-size: 12px;
        }

        @media print {
            body {
                padding: 0;
            }

            .no-print {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
<div class="text-center">
    <h3>GotPOS System</h3>
    <p>Main Branch</p>
    <p>Receipt #<?php echo (int)$sale_id; ?></p>
    <p><?php echo e($sale['created_at']); ?></p>
</div>

<hr>

<div class="small">
    <div class="d-flex">
        <span>Cashier:</span>
        <span><?php echo e($sale['username'] ?: 'N/A'); ?></span>
    </div>
    <div class="d-flex">
        <span>Payment:</span>
        <span><?php echo e($sale['payment_method'] ?: 'Cash'); ?></span>
    </div>
</div>

<hr>

<table>
    <thead>
    <tr>
        <th align="left">Item</th>
        <th align="center">Qty</th>
        <th class="text-end">Total</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($items as $item): ?>
        <tr>
            <td>
                <?php echo e($item['name']); ?>
                <div class="small">@ $<?php echo number_format((float)$item['unit_price'], 2); ?></div>
            </td>
            <td align="center"><?php echo (int)$item['quantity']; ?></td>
            <td class="text-end">$<?php echo number_format((float)$item['subtotal'], 2); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<hr>

<div class="small">
    <div class="d-flex">
        <span>Subtotal:</span>
        <span>$<?php echo number_format((float)$sale['total_amount'], 2); ?></span>
    </div>
    <div class="d-flex">
        <span>Tax:</span>
        <span>$<?php echo number_format((float)$sale['tax'], 2); ?></span>
    </div>
    <div class="d-flex">
        <span>Discount:</span>
        <span>$<?php echo number_format((float)$sale['discount'], 2); ?></span>
    </div>
</div>

<hr>

<div class="d-flex">
    <strong>Total:</strong>
    <strong>$<?php echo number_format((float)$sale['final_amount'], 2); ?></strong>
</div>

<hr>

<div class="text-center">
    <p>Thank you for your business!</p>
    <p>--- Customer Copy ---</p>
</div>

<div class="no-print text-center">
    <button onclick="window.print()">Print Again</button>
    <button onclick="window.close()">Close</button>
</div>
</body>
</html>