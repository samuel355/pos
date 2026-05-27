<?php
require_once "includes/auth_check.php";
require_once "config/db.php";

$message = "";
$error = "";

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "GHS " . number_format((float)$amount, 2);
}

$allowedPaymentMethods = ["Cash", "Card", "Mobile Money", "Bank Transfer"];

// Handle Update Sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_sale"])) {
    try {
        $saleId = isset($_POST["sale_id"]) ? (int)$_POST["sale_id"] : 0;
        $paymentMethod = trim($_POST["payment_method"] ?? "Cash");
        $discount = isset($_POST["discount"]) ? (float)$_POST["discount"] : 0;
        $tax = isset($_POST["tax"]) ? (float)$_POST["tax"] : 0;

        if ($saleId <= 0) {
            throw new Exception("Invalid sale selected.");
        }

        if (!in_array($paymentMethod, $allowedPaymentMethods, true)) {
            $paymentMethod = "Cash";
        }

        if ($discount < 0) {
            throw new Exception("Discount cannot be negative.");
        }

        if ($tax < 0) {
            throw new Exception("Tax cannot be negative.");
        }

        $stmt = $pdo->prepare("SELECT total_amount FROM sales WHERE id = ?");
        $stmt->execute([$saleId]);
        $sale = $stmt->fetch();

        if (!$sale) {
            throw new Exception("Sale not found.");
        }

        $finalAmount = ((float)$sale["total_amount"] + $tax) - $discount;

        if ($finalAmount < 0) {
            throw new Exception("Final amount cannot be negative.");
        }

        $stmt = $pdo->prepare("
            UPDATE sales
            SET payment_method = ?, discount = ?, tax = ?, final_amount = ?
            WHERE id = ?
        ");
        $stmt->execute([
            $paymentMethod,
            $discount,
            $tax,
            $finalAmount,
            $saleId
        ]);

        header("Location: sales.php?updated=1");
        exit;
    } catch (Exception $ex) {
        $error = $ex->getMessage();
    }
}

// Handle Delete Sale
if (isset($_GET["delete"])) {
    try {
        $saleId = (int)$_GET["delete"];

        if ($saleId <= 0) {
            throw new Exception("Invalid sale selected.");
        }

        $pdo->beginTransaction();

        $itemStmt = $pdo->prepare("
            SELECT product_id, quantity
            FROM sale_items
            WHERE sale_id = ?
        ");
        $itemStmt->execute([$saleId]);
        $items = $itemStmt->fetchAll();

        $restoreStock = $pdo->prepare("
            UPDATE products
            SET stock_quantity = stock_quantity + ?
            WHERE id = ?
        ");

        foreach ($items as $item) {
            $restoreStock->execute([
                (int)$item["quantity"],
                (int)$item["product_id"]
            ]);
        }

        $deleteStmt = $pdo->prepare("DELETE FROM sales WHERE id = ?");
        $deleteStmt->execute([$saleId]);

        $pdo->commit();

        header("Location: sales.php?deleted=1");
        exit;
    } catch (Exception $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $error = $ex->getMessage();
    }
}

if (isset($_GET["updated"]) && $_GET["updated"] === "1") {
    $message = "Sale updated successfully.";
}

if (isset($_GET["deleted"]) && $_GET["deleted"] === "1") {
    $message = "Sale deleted successfully and stock restored.";
}

$search = "";
$params = [];
$where = "";

if ($search !== "") {
    $where = "
        WHERE 
            s.id LIKE ?
            OR u.username LIKE ?
            OR s.payment_method LIKE ?
    ";

    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
}

$stmt = $pdo->prepare("
    SELECT 
        s.*,
        u.username,
        COUNT(si.id) AS item_count,
        COALESCE(SUM(si.quantity), 0) AS total_quantity
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN sale_items si ON si.sale_id = s.id
    $where
    GROUP BY s.id, u.username
    ORDER BY s.created_at DESC
");
$stmt->execute($params);
$sales = $stmt->fetchAll();

$summaryStmt = $pdo->query("
    SELECT
        COUNT(*) AS total_sales,
        COALESCE(SUM(final_amount), 0) AS total_revenue,
        COALESCE(SUM(discount), 0) AS total_discount,
        COALESCE(SUM(tax), 0) AS total_tax
    FROM sales
");
$summary = $summaryStmt->fetch();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<div class="page-heading mb-4 d-flex gap-2 flex-column flex-md-row align-items-md-center justify-content-between">
    <div>
        <h5 class="mb-1">Sales</h5>
        <p class="text-muted mb-0">View, edit, print, and manage all sales records.</p>
    </div>

    <a href="pos.php" class="btn btn-primary">
        <i class="ri-shopping-cart-2-line me-1"></i>
        New Sale
    </a>
</div>

<?php if ($message): ?>
    <div class="alert alert-success"><?php echo e($message); ?></div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo e($error); ?></div>
<?php endif; ?>

<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Sales</p>
                <h5 class="mb-0"><?php echo number_format((int)$summary["total_sales"]); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Revenue</p>
                <h5 class="mb-0"><?php echo money($summary["total_revenue"]); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Discount</p>
                <h5 class="mb-0"><?php echo money($summary["total_discount"]); ?></h5>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Tax</p>
                <h5 class="mb-0"><?php echo money($summary["total_tax"]); ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <h6 class="card-title mb-0">All Sales</h6>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text"
                   id="salesSearchInput"
                   class="form-control"
                   style="min-width: 260px;"
                   placeholder="Search receipt no, cashier, payment...">

            <select id="salesDatePreset" class="form-control" style="min-width: 160px;">
                <option value="all">All Dates</option>
                <option value="today">Today</option>
                <option value="yesterday">Yesterday</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="custom">Custom Range</option>
            </select>

            <input type="date" id="salesStartDate" class="form-control sales-date-range d-none">
            <input type="date" id="salesEndDate" class="form-control sales-date-range d-none">

            <button type="button" id="resetSalesFilters" class="btn btn-light border">
                Reset
            </button>
        </div>
    </div>

    <div class="card-body">
        <?php if (empty($sales)): ?>
            <div class="text-center py-5">
                <img src="./assets/no-order-CCjZwO4J.svg" alt="No sales" style="max-width: 120px;" class="mb-3">
                <h6 class="mb-1">No sales found</h6>
                <p class="text-muted mb-0">Sales will appear here after checkout.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt</th>
                            <th>Cashier</th>
                            <th>Items</th>
                            <th>Payment</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Tax</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th style="width: 230px;">Action</th>
                        </tr>
                    </thead>

                    <tbody id="salesTableBody">
                        <?php foreach ($sales as $sale): ?>
                            <?php
                            $saleDateOnly = date('Y-m-d', strtotime($sale["created_at"]));
                            $saleSearchText = strtolower(
                                    'receipt no ' . $sale["id"] . ' receipt ' . $sale["id"] . ' ' .
                                    ($sale["username"] ?: "N/A") . ' ' .
                                    ($sale["payment_method"] ?: "Cash") . ' ' .
                                    $sale["created_at"] . ' ' .
                                    $sale["final_amount"]
                            );
                            ?>
                            <tr class="sale-row"
                                data-search="<?php echo e($saleSearchText); ?>"
                                data-sale-date="<?php echo e($saleDateOnly); ?>">
                                <td>
                                    <strong>Receipt No.: <?php echo (int)$sale["id"]; ?></strong>
                                </td>

                                <td><?php echo e($sale["username"] ?: "N/A"); ?></td>

                                <td>
                                    <span class="badge bg-light text-body border">
                                        <?php echo (int)$sale["total_quantity"]; ?> Qty
                                    </span>
                                </td>

                                <td><?php echo e($sale["payment_method"] ?: "Cash"); ?></td>

                                <td><?php echo money($sale["total_amount"]); ?></td>

                                <td><?php echo money($sale["discount"]); ?></td>

                                <td><?php echo money($sale["tax"]); ?></td>

                                <td class="fw-bold text-primary">
                                    <?php echo money($sale["final_amount"]); ?>
                                </td>

                                <td><?php echo e($sale["created_at"]); ?></td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button"
                                                class="btn btn-sm btn-light border view-sale-btn"
                                                data-sale-id="<?php echo (int)$sale["id"]; ?>">
                                            View
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-primary edit-sale-btn"
                                                data-sale='<?php echo e(json_encode([
                                                    "id" => (int)$sale["id"],
                                                    "payment_method" => $sale["payment_method"] ?: "Cash",
                                                    "discount" => (float)$sale["discount"],
                                                    "tax" => (float)$sale["tax"],
                                                    "total_amount" => (float)$sale["total_amount"],
                                                    "final_amount" => (float)$sale["final_amount"],
                                                ])); ?>'>
                                            Edit
                                        </button>

                                        <a href="receipt.php?id=<?php echo (int)$sale["id"]; ?>"
                                           target="_blank"
                                           class="btn btn-sm btn-secondary">
                                            Print
                                        </a>

                                        <a href="sales.php?delete=<?php echo (int)$sale["id"]; ?>"
                                           class="btn btn-sm btn-danger"
                                           onclick="return confirm('Delete this sale? Stock will be restored.')">
                                            Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="noFilteredSalesRow" class="d-none">
                            <td colspan="10" class="text-center py-5">
                                <img src="./assets/no-order-CCjZwO4J.svg" alt="No sales" style="max-width: 110px;" class="mb-3">
                                <h6 class="mb-1">No sales found</h6>
                                <p class="text-muted mb-0">Try another receipt number, cashier, payment method, or date range.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal fade" id="viewSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="viewSaleTitle">Sale Details</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body" id="viewSaleBody">
                <div class="text-center py-4 text-muted">Loading...</div>
            </div>

            <div class="modal-footer">
                <a href="#" target="_blank" id="viewSaleReceiptBtn" class="btn btn-primary">
                    Print Receipt
                </a>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="sales.php">
                <input type="hidden" name="sale_id" id="editSaleId">

                <div class="modal-header">
                    <h6 class="modal-title">Edit Sale</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="vstack gap-3">
                        <div>
                            <label class="form-label">Receipt</label>
                            <input type="text" id="editSaleReceipt" class="form-control" readonly>
                        </div>

                        <div>
                            <label class="form-label">Payment Method</label>
                            <select name="payment_method" id="editSalePaymentMethod" class="form-control">
                                <?php foreach ($allowedPaymentMethods as $method): ?>
                                    <option value="<?php echo e($method); ?>"><?php echo e($method); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="form-label">Discount - GHS</label>
                            <input type="number" step="0.01" min="0" name="discount" id="editSaleDiscount" class="form-control">
                        </div>

                        <div>
                            <label class="form-label">Tax - GHS</label>
                            <input type="number" step="0.01" min="0" name="tax" id="editSaleTax" class="form-control">
                        </div>

                        <div class="alert alert-light border mb-0">
                            <div class="d-flex justify-content-between">
                                <span>Subtotal:</span>
                                <strong id="editSaleSubtotal"></strong>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span>Final Total:</span>
                                <strong id="editSaleFinal"></strong>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_sale" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function money(amount) {
        return 'GHS ' + Number(amount || 0).toFixed(2);
    }

    function formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function getDateRangeFromPreset(preset) {
        const today = new Date();
        let start = null;
        let end = null;

        if (preset === 'today') {
            start = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        }

        if (preset === 'yesterday') {
            start = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
            end = new Date(today.getFullYear(), today.getMonth(), today.getDate() - 1);
        }

        if (preset === 'week') {
            const day = today.getDay();
            const diffToMonday = day === 0 ? -6 : 1 - day;

            start = new Date(today.getFullYear(), today.getMonth(), today.getDate() + diffToMonday);
            end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        }

        if (preset === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
            end = new Date(today.getFullYear(), today.getMonth(), today.getDate());
        }

        return {
            start: start ? formatDate(start) : '',
            end: end ? formatDate(end) : ''
        };
    }

    function applySalesFilters() {
        const searchInput = document.getElementById('salesSearchInput');
        const presetInput = document.getElementById('salesDatePreset');
        const startInput = document.getElementById('salesStartDate');
        const endInput = document.getElementById('salesEndDate');
        const noFilteredSalesRow = document.getElementById('noFilteredSalesRow');
        const rows = Array.from(document.querySelectorAll('.sale-row'));

        const searchTerm = (searchInput.value || '').toLowerCase().trim();
        const preset = presetInput.value;

        let startDate = '';
        let endDate = '';

        if (preset === 'custom') {
            startDate = startInput.value;
            endDate = endInput.value;
        } else {
            const range = getDateRangeFromPreset(preset);
            startDate = range.start;
            endDate = range.end;
        }

        let visibleCount = 0;

        rows.forEach(function (row) {
            const rowSearch = row.dataset.search || '';
            const rowDate = row.dataset.saleDate || '';

            let visible = true;

            if (searchTerm !== '' && rowSearch.indexOf(searchTerm) === -1) {
                visible = false;
            }

            if (startDate !== '' && rowDate < startDate) {
                visible = false;
            }

            if (endDate !== '' && rowDate > endDate) {
                visible = false;
            }

            row.style.display = visible ? '' : 'none';

            if (visible) {
                visibleCount++;
            }
        });

        if (noFilteredSalesRow) {
            noFilteredSalesRow.classList.toggle('d-none', visibleCount > 0);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const salesSearchInput = document.getElementById('salesSearchInput');
        const salesDatePreset = document.getElementById('salesDatePreset');
        const salesStartDate = document.getElementById('salesStartDate');
        const salesEndDate = document.getElementById('salesEndDate');
        const resetSalesFilters = document.getElementById('resetSalesFilters');
        const dateRangeInputs = document.querySelectorAll('.sales-date-range');

        if (salesSearchInput) {
            salesSearchInput.addEventListener('input', applySalesFilters);
        }

        if (salesDatePreset) {
            salesDatePreset.addEventListener('change', function () {
                const isCustom = this.value === 'custom';

                dateRangeInputs.forEach(function (input) {
                    input.classList.toggle('d-none', !isCustom);
                });

                if (!isCustom) {
                    salesStartDate.value = '';
                    salesEndDate.value = '';
                }

                applySalesFilters();
            });
        }

        if (salesStartDate) {
            salesStartDate.addEventListener('change', applySalesFilters);
        }

        if (salesEndDate) {
            salesEndDate.addEventListener('change', applySalesFilters);
        }

        if (resetSalesFilters) {
            resetSalesFilters.addEventListener('click', function () {
                salesSearchInput.value = '';
                salesDatePreset.value = 'all';
                salesStartDate.value = '';
                salesEndDate.value = '';

                dateRangeInputs.forEach(function (input) {
                    input.classList.add('d-none');
                });

                applySalesFilters();
            });
        }

        document.querySelectorAll('.edit-sale-btn').forEach(function (button) {
            button.addEventListener('click', function () {
                const sale = JSON.parse(this.dataset.sale);

                document.getElementById('editSaleId').value = sale.id;
                document.getElementById('editSaleReceipt').value = 'Receipt No.: ' + sale.id;
                document.getElementById('editSalePaymentMethod').value = sale.payment_method || 'Cash';
                document.getElementById('editSaleDiscount').value = Number(sale.discount || 0).toFixed(2);
                document.getElementById('editSaleTax').value = Number(sale.tax || 0).toFixed(2);
                document.getElementById('editSaleSubtotal').innerText = money(sale.total_amount);
                document.getElementById('editSaleFinal').innerText = money(sale.final_amount);

                new bootstrap.Modal(document.getElementById('editSaleModal')).show();
            });
        });

        document.querySelectorAll('.view-sale-btn').forEach(function (button) {
            button.addEventListener('click', async function () {
                const saleId = this.dataset.saleId;
                const body = document.getElementById('viewSaleBody');
                const title = document.getElementById('viewSaleTitle');
                const receiptBtn = document.getElementById('viewSaleReceiptBtn');

                title.innerText = 'Sale Details - Receipt No.: ' + saleId;
                receiptBtn.href = 'receipt.php?id=' + encodeURIComponent(saleId);
                body.innerHTML = '<div class="text-center py-4 text-muted">Loading...</div>';

                const modal = new bootstrap.Modal(document.getElementById('viewSaleModal'));
                modal.show();

                try {
                    const response = await fetch('api/get_sale.php?id=' + encodeURIComponent(saleId));
                    const result = await response.json();

                    if (!result.success) {
                        body.innerHTML = '<div class="alert alert-danger mb-0">' + (result.error || 'Unable to load sale.') + '</div>';
                        return;
                    }

                    const sale = result.sale;
                    const items = result.items;

                    let rows = '';

                    items.forEach(function (item) {
                        rows += `
                        <tr>
                            <td>${item.name}</td>
                            <td class="text-center">${item.quantity}</td>
                            <td>${money(item.unit_price)}</td>
                            <td class="text-end">${money(item.subtotal)}</td>
                        </tr>
                    `;
                    });

                    body.innerHTML = `
                    <div class="mb-3">
                        <h6 class="mb-1">Receipt No.: ${sale.id}</h6>
                        <p class="text-muted mb-0">Cashier: ${sale.username || 'N/A'} | Payment: ${sale.payment_method || 'Cash'}</p>
                        <p class="text-muted mb-0">Date: ${sale.created_at}</p>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th class="text-center">Qty</th>
                                    <th>Unit</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>

                    <div class="border rounded p-3 ms-auto" style="max-width: 320px;">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <strong>${money(sale.total_amount)}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Discount:</span>
                            <strong>${money(sale.discount)}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax:</span>
                            <strong>${money(sale.tax)}</strong>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between text-primary">
                            <span>Total:</span>
                            <strong>${money(sale.final_amount)}</strong>
                        </div>
                    </div>
                `;
                } catch (error) {
                    body.innerHTML = '<div class="alert alert-danger mb-0">System error while loading sale.</div>';
                }
            });
        });

        applySalesFilters();
    });
</script>

<?php include "includes/footer.php"; ?>
