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

function money($amount) {
    return 'GHS ' . number_format((float)$amount, 2);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Receipt No.: <?php echo (int)$sale_id; ?></title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            background: #f3f4f6;
            font-family: Arial, sans-serif;
        }

        .preview-page {
            min-height: 100vh;
            padding: 24px;
        }

        .preview-header {
            max-width: 460px;
            margin: 0 auto 18px;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 14px;
            padding: 16px;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
        }

        .preview-header h2 {
            margin: 0 0 4px;
            font-size: 20px;
        }

        .preview-header p {
            margin: 0;
            color: #6b7280;
            font-size: 14px;
        }

        .actions {
            max-width: 460px;
            margin: 0 auto 18px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn {
            border: 0;
            border-radius: 10px;
            padding: 10px 14px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-primary {
            color: #ffffff;
            background: #ff762d;
        }

        .btn-light {
            color: #111827;
            background: #ffffff;
            border: 1px solid #d1d5db;
        }

        .receipt-preview {
            max-width: 380px;
            margin: 0 auto;
        }

        .receipt-copy {
            width: 320px;
            margin: 0 auto 20px;
            padding: 18px;
            color: #000;
            background: #ffffff;
            border: 1px solid #e5e7eb;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .08);
            font-family: monospace;
            font-size: 13px;
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

        .small {
            font-size: 12px;
        }

        .muted {
            color: #6b7280;
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

        .copy-label {
            margin-top: 8px;
            font-weight: 700;
        }
        .receipt-number {
            margin: 8px 0 4px;
            font-size: 15px;
        }

        .receipt-number strong {
            font-size: 20px;
            font-weight: 800;
        }
        .print-only {
            display: none;
        }

        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }

            html,
            body {
                width: 80mm;
                margin: 0;
                padding: 0;
                background: #ffffff;
            }

            .no-print {
                display: none !important;
            }

            .preview-page {
                padding: 0;
                margin: 0;
                background: #ffffff;
            }

            .receipt-preview {
                max-width: none;
                width: 100%;
                margin: 0;
                padding: 0;
            }

            .receipt-copy {
                width: 80mm;
                margin: 0;
                padding: 4mm;
                border: 0;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
                font-size: 12px;
            }

            .receipt-copy:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .print-only {
                display: block;
            }
        }
    </style>
</head>
<body>
<div class="preview-page">
    <div class="preview-header no-print">
        <h2>Receipt Preview</h2>
        <p class="receipt-number">
            Receipt No.: <strong><?php echo (int)$sale_id; ?></strong>
            <span>— review before printing.</span>
        </p>
    </div>

    <div class="actions no-print">
        <button type="button" class="btn btn-primary" onclick="printReceipt()">
            Print Receipt
        </button>

        <button type="button" class="btn btn-light" onclick="window.close()">
            Close
        </button>
    </div>

    <div class="receipt-preview" id="receiptPreview">
        <?php
        $copies = [
                'Customer Copy',
                'Merchant Copy'
        ];
        ?>

        <?php foreach ($copies as $copyLabel): ?>
            <div class="receipt-copy">
                <div class="text-center">
                    <h3>GotPOS System</h3>
                    <p>Main Branch</p>
                    <p class="receipt-number">Receipt No.: <strong><?php echo (int)$sale_id; ?></strong></p>
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
                                <div class="small muted">@ <?php echo money($item['unit_price']); ?></div>
                            </td>
                            <td align="center"><?php echo (int)$item['quantity']; ?></td>
                            <td class="text-end"><?php echo money($item['subtotal']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <hr>

                <div class="small">
                    <div class="d-flex">
                        <span>Subtotal:</span>
                        <span><?php echo money($sale['total_amount']); ?></span>
                    </div>
                    <div class="d-flex">
                        <span>Tax:</span>
                        <span><?php echo money($sale['tax']); ?></span>
                    </div>
                    <div class="d-flex">
                        <span>Discount:</span>
                        <span><?php echo money($sale['discount']); ?></span>
                    </div>
                </div>

                <hr>

                <div class="d-flex">
                    <strong>Total:</strong>
                    <strong><?php echo money($sale['final_amount']); ?></strong>
                </div>

                <hr>

                <div class="text-center">
                    <p>Thank you for your business!</p>
                    <p class="copy-label">--- <?php echo e($copyLabel); ?> ---</p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function printReceipt() {
        window.print();
    }
</script>
</body>
</html>