<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';

$sale_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT s.*, u.username FROM sales s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$sale_id]);
$sale = $stmt->fetch();

if (!$sale) {
    die("Sale not found");
}

$stmt = $pdo->prepare("SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id = p.id WHERE si.sale_id = ?");
$stmt->execute([$sale_id]);
$items = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Receipt #<?php echo $sale_id; ?></title>
    <style>
        body { font-family: monospace; width: 300px; margin: 0 auto; padding: 20px; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        hr { border-top: 1px dashed #000; }
        table { width: 100%; }
    </style>
</head>
<body onload="window.print()">
    <div class="text-center">
        <h4>GotPOS System</h4>
        <p>Main Branch</p>
        <p>Receipt #<?php echo $sale_id; ?></p>
        <p><?php echo $sale['created_at']; ?></p>
    </div>
    <hr>
    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th class="text-end">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr>
    <p class="text-end">Total: $<?php echo number_format($sale['final_amount'], 2); ?></p>
    <hr>
    <div class="text-center">
        <p>Thank you for your business!</p>
        <p>--- Customer Copy ---</p>
    </div>
    
    <div style="page-break-after: always;"></div>
    
    <div class="text-center">
        <h4>GotPOS System</h4>
        <p>Receipt #<?php echo $sale_id; ?> (Merchant Copy)</p>
        <p>Staff: <?php echo $sale['username']; ?></p>
    </div>
    <hr>
    <table>
        <?php foreach ($items as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['name']); ?> x <?php echo $item['quantity']; ?></td>
                <td class="text-end">$<?php echo number_format($item['subtotal'], 2); ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <p class="text-end">Total: $<?php echo number_format($sale['final_amount'], 2); ?></p>
</body>
</html>
