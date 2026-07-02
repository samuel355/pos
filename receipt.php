<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "includes/table_packages.php";

ensureTablePackageSchema($pdo);

$singleSaleId = isset($_GET["id"]) ? (int) $_GET["id"] : 0;
$idsParam = isset($_GET["ids"]) ? trim((string) $_GET["ids"]) : "";
$autoPrint = isset($_GET["auto_print"]) && $_GET["auto_print"] === "1";
$embedded = isset($_GET["embedded"]) && $_GET["embedded"] === "1";

$saleIds = [];
if ($idsParam !== "") {
  foreach (explode(",", $idsParam) as $rawId) {
    $id = (int) trim($rawId);
    if ($id > 0) {
      $saleIds[] = $id;
    }
  }
  $saleIds = array_values(array_unique($saleIds));
} elseif ($singleSaleId > 0) {
  $saleIds[] = $singleSaleId;
}

if (empty($saleIds)) {
  die("Sale not found");
}

$salePlaceholders = implode(",", array_fill(0, count($saleIds), "?"));

$stmt = $pdo->prepare("
    SELECT
        s.*,
        u.username,
        rt.name AS table_name,
        COALESCE(s.customer_name, (
            SELECT tb.customer_name
            FROM table_bookings tb
            WHERE tb.sale_id = s.id
               OR (
                    s.table_id IS NOT NULL
                    AND tb.table_id = s.table_id
                    AND s.created_at >= tb.booked_at
                    AND (tb.closed_at IS NULL OR s.created_at <= tb.closed_at)
               )
            ORDER BY tb.id DESC
            LIMIT 1
        )) AS display_customer_name,
        COALESCE(s.customer_contact, (
            SELECT tb.customer_contact
            FROM table_bookings tb
            WHERE tb.sale_id = s.id
               OR (
                    s.table_id IS NOT NULL
                    AND tb.table_id = s.table_id
                    AND s.created_at >= tb.booked_at
                    AND (tb.closed_at IS NULL OR s.created_at <= tb.closed_at)
               )
            ORDER BY tb.id DESC
            LIMIT 1
        )) AS display_customer_contact
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN restaurant_tables rt ON rt.id = COALESCE(
        s.table_id,
        (
            SELECT tb.table_id
            FROM table_bookings tb
            WHERE tb.sale_id = s.id
               OR (
                    s.table_id IS NOT NULL
                    AND tb.table_id = s.table_id
                    AND s.created_at >= tb.booked_at
                    AND (tb.closed_at IS NULL OR s.created_at <= tb.closed_at)
               )
            ORDER BY tb.id DESC
            LIMIT 1
        )
    )
    WHERE s.id IN ($salePlaceholders)
");
$stmt->execute($saleIds);
$salesRows = $stmt->fetchAll();

if (empty($salesRows)) {
  die("Sale not found");
}

$salesById = [];
foreach ($salesRows as $row) {
  $salesById[(int) $row["id"]] = $row;
}

$stmt = $pdo->prepare("
    SELECT si.*, p.name
    FROM sale_items si
    LEFT JOIN products p ON si.product_id = p.id
    WHERE si.sale_id IN ($salePlaceholders)
");
$stmt->execute($saleIds);
$itemsRows = $stmt->fetchAll();

$packageProductIds = [];
foreach ($itemsRows as $itemRow) {
  if (!empty($itemRow["product_id"])) {
    $packageProductIds[] = (int) $itemRow["product_id"];
  }
}
$packageProductIds = array_values(array_unique($packageProductIds));

$packageItemsByProductId = [];
if (!empty($packageProductIds)) {
  $packagePlaceholders = implode(",", array_fill(0, count($packageProductIds), "?"));
  $packageStmt = $pdo->prepare("
      SELECT
          tp.product_id,
          tpi.item_name,
          tpi.item_type,
          tpi.quantity
      FROM table_packages tp
      INNER JOIN table_package_items tpi ON tpi.package_id = tp.id
      WHERE tp.product_id IN ($packagePlaceholders)
      ORDER BY tp.id ASC, tpi.display_order ASC
  ");
  $packageStmt->execute($packageProductIds);

  foreach ($packageStmt->fetchAll() as $packageItem) {
    $productId = (int) $packageItem["product_id"];
    if (!isset($packageItemsByProductId[$productId])) {
      $packageItemsByProductId[$productId] = [];
    }
    $packageItemsByProductId[$productId][] = $packageItem;
  }
}

$itemsBySaleId = [];
foreach ($itemsRows as $itemRow) {
  $saleKey = (int) $itemRow["sale_id"];
  if (!isset($itemsBySaleId[$saleKey])) {
    $itemsBySaleId[$saleKey] = [];
  }
  $itemsBySaleId[$saleKey][] = $itemRow;
}

$receipts = [];
foreach ($saleIds as $id) {
  if (!isset($salesById[$id])) {
    continue;
  }

  $receipts[] = [
    "sale" => $salesById[$id],
    "items" => $itemsBySaleId[$id] ?? [],
  ];
}

if (empty($receipts)) {
  die("Sale not found");
}

function e($value)
{
  return htmlspecialchars((string) $value, ENT_QUOTES, "UTF-8");
}

function money($amount)
{
  return "GHS " . number_format((float) $amount, 2);
}

$firstReceiptId = (int) $receipts[0]["sale"]["id"];
$isBatch = count($receipts) > 1;
$includedSaleIds = array_map(function ($receipt) {
  return (int) $receipt["sale"]["id"];
}, $receipts);

// A batch of linked sales (a table's package + its extra drink rounds) prints
// as one combined bill with one total, not one full receipt per round.
if ($isBatch) {
  $combinedItems = [];
  $combinedSubtotal = 0;
  $combinedDiscount = 0;
  $combinedTax = 0;
  $combinedFinal = 0;
  $paymentMethods = [];
  $cashiers = [];
  $tableName = null;
  $customerName = null;
  $customerContact = null;
  $latestCreatedAt = null;

  foreach ($receipts as $receipt) {
    $sale = $receipt["sale"];

    $combinedSubtotal += (float) $sale["total_amount"];
    $combinedDiscount += (float) $sale["discount"];
    $combinedTax += (float) $sale["tax"];
    $combinedFinal += (float) $sale["final_amount"];

    $paymentMethods[$sale["payment_method"] ?: "Cash"] = true;
    $cashiers[$sale["username"] ?: "N/A"] = true;

    if (!empty($sale["table_name"])) {
      $tableName = $sale["table_name"];
    }

    if (!empty($sale["display_customer_name"])) {
      $customerName = $sale["display_customer_name"];
    }

    if (!empty($sale["display_customer_contact"])) {
      $customerContact = $sale["display_customer_contact"];
    }

    if ($latestCreatedAt === null || $sale["created_at"] > $latestCreatedAt) {
      $latestCreatedAt = $sale["created_at"];
    }

    foreach ($receipt["items"] as $item) {
      $combinedItems[] = $item;
    }
  }

  $receipts = [[
    "sale" => [
      "id" => $firstReceiptId,
      "created_at" => $latestCreatedAt,
      "username" => implode(", ", array_keys($cashiers)),
      "payment_method" => implode(", ", array_keys($paymentMethods)),
      "table_name" => $tableName,
      "display_customer_name" => $customerName,
      "display_customer_contact" => $customerContact,
      "total_amount" => $combinedSubtotal,
      "discount" => $combinedDiscount,
      "tax" => $combinedTax,
      "final_amount" => $combinedFinal,
    ],
    "items" => $combinedItems,
  ]];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $isBatch
      ? "Receipts (" . count($receipts) . ")"
      : "Receipt No.: " . $firstReceiptId; ?></title>
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

        .kt-mark {
            padding: 1px 6px;
            border-radius: 4px;
            color: #ffffff;
            background: linear-gradient(135deg, #ff762d, #f59e0b);
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
    <?php if (!$embedded): ?>
        <div class="preview-header no-print">
            <h2>Receipt Preview</h2>
            <p class="receipt-number">
                <?php if ($isBatch): ?>
                    Table Bill: <strong><?php echo (int) count($includedSaleIds); ?> orders combined</strong>
                    <span>— review before printing.</span>
                <?php else: ?>
                    Receipt No.: <strong><?php echo $firstReceiptId; ?></strong>
                    <span>— review before printing.</span>
                <?php endif; ?>
            </p>
        </div>

        <div class="actions no-print">
            <button type="button" class="btn btn-primary" onclick="printReceipt()">
                Print Receipt
            </button>

            <button type="button" class="btn btn-light" onclick="closeReceipt()">
                Close
            </button>
        </div>
    <?php endif; ?>

    <div class="receipt-preview" id="receiptPreview">
        <?php $copies = ["Customer Copy", "Merchant Copy"]; ?>
        <?php foreach ($receipts as $receipt): ?>
            <?php $sale = $receipt["sale"]; ?>
            <?php $items = $receipt["items"]; ?>
            <?php foreach ($copies as $copyLabel): ?>
                <div class="receipt-copy">
                    <div class="text-center">
                        <h3><span class="kt-mark">KT</span> POS System</h3>
                        <p>Main Branch</p>
                        <?php if ($isBatch): ?>
                            <p class="receipt-number">Table Bill</p>
                            <p class="small muted">Includes Receipts: <?php echo e(implode(", ", $includedSaleIds)); ?></p>
                        <?php else: ?>
                            <p class="receipt-number">Receipt No.: <strong><?php echo (int) $sale[
                              "id"
                            ]; ?></strong></p>
                        <?php endif; ?>
                        <p><?php echo e($sale["created_at"]); ?></p>
                    </div>

                    <hr>

                    <div class="small">
                        <?php if (!empty($sale["display_customer_name"])): ?>
                        <div class="d-flex">
                            <span>Customer:</span>
                            <span><?php echo e($sale["display_customer_name"]); ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($sale["display_customer_contact"])): ?>
                        <div class="d-flex">
                            <span>Phone:</span>
                            <span><?php echo e($sale["display_customer_contact"]); ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="d-flex">
                            <span>Cashier:</span>
                            <span><?php echo e($sale["username"] ?: "N/A"); ?></span>
                        </div>
                        <div class="d-flex">
                            <span>Payment:</span>
                            <span><?php echo e($sale["payment_method"] ?: "Cash"); ?></span>
                        </div>
                        <?php if (!empty($sale["table_name"])): ?>
                        <div class="d-flex">
                            <span>Table:</span>
                            <span><?php echo e($sale["table_name"]); ?></span>
                        </div>
                        <?php endif; ?>
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
                                    <?php echo e($item["name"]); ?>
                                    <div class="small muted">@ <?php echo money(
                                      $item["unit_price"],
                                    ); ?></div>
                                    <?php $includedItems = $packageItemsByProductId[(int) $item["product_id"]] ?? []; ?>
                                    <?php if (!empty($includedItems)): ?>
                                        <div class="small muted" style="margin-top: 6px;">
                                            <strong>Content</strong>
                                            <table style="margin-top: 3px;">
                                                <thead>
                                                <tr>
                                                    <th align="left" style="width: 34px;">qty</th>
                                                    <th align="left">product name</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <?php foreach ($includedItems as $included): ?>
                                                    <tr>
                                                        <td style="width: 34px;"><?php echo (int) $included["quantity"]; ?></td>
                                                        <td><?php echo e($included["item_name"]); ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td align="center"><?php echo (int) $item[
                                  "quantity"
                                ]; ?></td>
                                <td class="text-end"><?php echo money(
                                  $item["subtotal"],
                                ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <hr>

                    <div class="small">
                        <div class="d-flex">
                            <span>Subtotal:</span>
                            <span><?php echo money($sale["total_amount"]); ?></span>
                        </div>
                        <div class="d-flex">
                            <span>Tax:</span>
                            <span><?php echo money($sale["tax"]); ?></span>
                        </div>
                        <div class="d-flex">
                            <span>Discount:</span>
                            <span><?php echo money($sale["discount"]); ?></span>
                        </div>
                    </div>

                    <hr>

                    <div class="d-flex">
                        <strong>Total:</strong>
                        <strong><?php echo money($sale["final_amount"]); ?></strong>
                    </div>

                    <hr>

                    <div class="text-center">
                        <p>Thank you for your business!</p>
                        <p class="copy-label">--- <?php echo e($copyLabel); ?> ---</p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function printReceipt() {
        window.print();
    }
    function closeReceipt(){
        if (window.parent && typeof window.parent.closeReceiptModal === 'function') {
            window.parent.closeReceiptModal();
            return;
        }
        window.location.href = 'pos.php';
    }
    <?php if ($autoPrint): ?>
    window.addEventListener('load', function() {
        setTimeout(function() {
            window.print();
        }, 150);
    });
    <?php endif; ?>
</script>
</body>
</html>
