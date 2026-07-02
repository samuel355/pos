<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "includes/table_packages.php";

ensureTablePackageSchema($pdo);

$message = "";
$error = "";
$isAdminUser = isAdmin();

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "GHS " . number_format((float)$amount, 2);
}

$allowedPaymentMethods = ["Cash", "Card", "Mobile Money", "Bank Transfer"];

// Handle Update Sale
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_sale"]) && !$isAdminUser) {
    $error = "Only admin users can edit sales.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["update_sale"]) && $isAdminUser) {
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
if (isset($_GET["delete"]) && !$isAdminUser) {
    $error = "Only admin users can delete sales.";
}

if (isset($_GET["delete"]) && $isAdminUser) {
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
        MIN(s.id) AS id,
        COALESCE(tb.sale_id, MIN(s.id)) AS representative_sale_id,
        s.table_booking_id,
        GROUP_CONCAT(s.id ORDER BY s.created_at ASC) AS receipt_ids,
        COUNT(*) AS receipt_count,
        SUM(s.total_amount) AS total_amount,
        SUM(s.discount) AS discount,
        SUM(s.tax) AS tax,
        SUM(s.final_amount) AS final_amount,
        COALESCE(
            GROUP_CONCAT(DISTINCT NULLIF(s.payment_method, '') ORDER BY s.payment_method SEPARATOR ', '),
            'Cash'
        ) AS payment_method,
        MAX(s.created_at) AS created_at,
        COALESCE(MAX(u.username), 'N/A') AS username,
        COALESCE(
            MAX(NULLIF(s.customer_name, '')),
            MAX(NULLIF(tb.customer_name, '')),
            'Walk-in Customer'
        ) AS resolved_customer_name,
        MAX(rt.name) AS table_name
    FROM sales s
    LEFT JOIN users u ON s.user_id = u.id
    LEFT JOIN table_bookings tb ON tb.id = s.table_booking_id
    LEFT JOIN restaurant_tables rt ON rt.id = COALESCE(s.table_id, tb.table_id)
    $where
    GROUP BY
        CASE
            WHEN s.table_booking_id IS NULL THEN CONCAT('sale-', s.id)
            ELSE CONCAT('booking-', s.table_booking_id)
        END,
        s.table_booking_id,
        tb.sale_id
    ORDER BY MAX(s.created_at) DESC, MIN(s.id) DESC
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

<style>
    .sale-modal .modal-content {
        overflow: hidden;
        border: 0;
        border-radius: 22px;
        box-shadow: 0 30px 80px rgba(15, 23, 42, .22);
    }

    .sale-modal-header {
        color: #ffffff;
        background: linear-gradient(135deg, #ff762d, #f59e0b);
        padding: 20px 22px;
    }

    .sale-summary-card {
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 18px;
        padding: 14px;
        background: #f9fafb;
    }

    .sale-item-row {
        display: grid;
        grid-template-columns: minmax(0, 1fr) 70px 110px 120px;
        gap: 12px;
        align-items: center;
        padding: 12px 14px;
        border: 1px solid rgba(15, 23, 42, .08);
        border-radius: 14px;
        background: #ffffff;
    }

    .sale-item-head {
        color: #6b7280;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        background: #f9fafb;
    }

    @media (max-width: 767px) {
        .sale-item-row {
            grid-template-columns: 1fr 58px;
        }

        .sale-item-row .unit,
        .sale-item-row .subtotal {
            display: none;
        }
    }

    .receipt-modal-backdrop {
        position: fixed;
        inset: 0;
        z-index: 9998;
        background: rgba(15, 23, 42, .55);
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    .receipt-modal-backdrop.show {
        display: flex;
    }

    .receipt-modal {
        width: min(960px, 100%);
        height: min(92vh, 900px);
        background: #ffffff;
        border-radius: 16px;
        overflow: hidden;
        display: flex;
        flex-direction: column;
        box-shadow: 0 24px 80px rgba(15, 23, 42, .35);
    }

    .receipt-modal-header {
        padding: 14px 18px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }

    .receipt-modal-header h6 {
        margin: 0;
        font-size: 16px;
    }

    .receipt-modal-actions {
        display: flex;
        gap: 8px;
    }

    .receipt-modal-body {
        flex: 1;
        background: #f3f4f6;
    }

    .receipt-modal-body iframe {
        width: 100%;
        height: 100%;
        border: 0;
        background: #ffffff;
    }
</style>

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

<?php if ($isAdminUser): ?>
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
<?php else: ?>
    <div class="alert alert-light border">
        Staff access can view and print sales records. Editing, deleting, and aggregate sales totals require an admin account.
    </div>
<?php endif; ?>

<div class="card">
    <div class="card-header d-flex flex-wrap gap-3 align-items-center justify-content-between">
        <h6 class="card-title mb-0">All Sales</h6>

        <div class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text"
                   id="salesSearchInput"
                   class="form-control"
                   style="min-width: 260px;"
                   placeholder="Search receipt no, cashier, customer, payment...">

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
                            <th>Customer</th>
                            <th>Payment</th>
                            <th>Subtotal</th>
                            <th>Discount</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th style="width: 230px;">Action</th>
                        </tr>
                    </thead>

                    <tbody id="salesTableBody">
                        <?php foreach ($sales as $sale): ?>
                            <?php
                            $representativeSaleId = (int)($sale["representative_sale_id"] ?: $sale["id"]);
                            $receiptCount = (int)$sale["receipt_count"];
                            $isTableBill = !empty($sale["table_booking_id"]);
                            $saleDateOnly = date('Y-m-d', strtotime($sale["created_at"]));
                            $saleSearchText = strtolower(
                                    'receipt no ' . $representativeSaleId . ' receipt ' . $representativeSaleId . ' ' .
                                    ($sale["receipt_ids"] ?: '') . ' ' .
                                    ($isTableBill ? 'table bill ' . ($sale["table_name"] ?: '') : '') . ' ' .
                                    ($sale["username"] ?: "N/A") . ' ' .
                                    ($sale["resolved_customer_name"] ?: "walk-in customer") . ' ' .
                                    ($sale["payment_method"] ?: "Cash") . ' ' .
                                    $sale["created_at"] . ' ' .
                                    $sale["final_amount"]
                            );
                            ?>
                            <tr class="sale-row"
                                data-search="<?php echo e($saleSearchText); ?>"
                                data-sale-date="<?php echo e($saleDateOnly); ?>">
	                                <td>
                                        <?php if ($isTableBill): ?>
                                            <strong>Table Bill</strong>
                                            <div class="text-muted fs-sm">
                                                <?php echo e($sale["table_name"] ?: "Table"); ?> ·
                                                <?php echo $receiptCount; ?> receipt<?php echo $receiptCount === 1 ? "" : "s"; ?>
                                            </div>
                                        <?php else: ?>
	                                        <strong><?php echo $representativeSaleId; ?></strong>
                                        <?php endif; ?>
	                                </td>

                                <td><?php echo e($sale["username"] ?: "N/A"); ?></td>

                                <td><?php echo e($sale["resolved_customer_name"] ?: "Walk-in Customer"); ?></td>

                                <td><?php echo e($sale["payment_method"] ?: "Cash"); ?></td>

                                <td><?php echo money($sale["total_amount"]); ?></td>

                                <td><?php echo money($sale["discount"]); ?></td>

                                <td class="fw-bold text-primary">
                                    <?php echo money($sale["final_amount"]); ?>
                                </td>

                                <td><?php echo e($sale["created_at"]); ?></td>

                                <td>
                                    <div class="d-flex flex-wrap gap-2">
	                                        <button type="button"
	                                                class="btn btn-sm btn-light border view-sale-btn"
	                                                data-sale-id="<?php echo $representativeSaleId; ?>">
	                                            View
	                                        </button>

                                            <?php if ($isAdminUser && !$isTableBill): ?>
    	                                        <button type="button"
    	                                                class="btn btn-sm btn-primary edit-sale-btn"
    	                                                data-sale='<?php echo e(json_encode([
    	                                                    "id" => $representativeSaleId,
    	                                                    "payment_method" => $sale["payment_method"] ?: "Cash",
    	                                                    "discount" => (float)$sale["discount"],
    	                                                    "tax" => (float)$sale["tax"],
    	                                                    "total_amount" => (float)$sale["total_amount"],
    	                                                    "final_amount" => (float)$sale["final_amount"],
    	                                                ])); ?>'>
    	                                            Edit
    	                                        </button>
                                            <?php endif; ?>
	
	                                        <button type="button"
	                                                class="btn btn-sm btn-secondary print-sale-btn"
	                                                data-sale-id="<?php echo $representativeSaleId; ?>">
	                                            Print
	                                        </button>

                                            <?php if ($isAdminUser && !$isTableBill): ?>
    	                                        <a href="sales.php?delete=<?php echo $representativeSaleId; ?>"
    	                                           class="btn btn-sm btn-danger"
    	                                           onclick="return confirm('Delete this sale? Stock will be restored.')">
    	                                            Delete
    	                                        </a>
                                            <?php endif; ?>
	                                    </div>
	                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="noFilteredSalesRow" class="d-none">
                            <td colspan="9" class="text-center py-5">
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

<div class="modal fade sale-modal" id="viewSaleModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="sale-modal-header d-flex justify-content-between align-items-start gap-3">
                <div>
                    <h5 class="mb-1" id="viewSaleTitle">Sale Details</h5>
                    <p class="mb-0 opacity-75 fs-sm" id="viewSaleSubtitle">Review sale items, payment, and totals.</p>
                </div>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>

            <div class="modal-body" id="viewSaleBody">
                <div class="text-center py-4 text-muted">Loading...</div>
            </div>

            <div class="modal-footer">
                <button type="button" id="viewSaleReceiptBtn" class="btn btn-primary">
                    <i class="ri-printer-line me-1"></i>
                    Print Receipt
                </button>
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

<div id="receiptModalBackdrop" class="receipt-modal-backdrop">
    <div class="receipt-modal">
        <div class="receipt-modal-header">
            <h6 id="receiptModalTitle">Receipt Preview</h6>
            <div class="receipt-modal-actions">
                <button type="button" class="btn btn-primary btn-sm" id="printReceiptBtn">
                    Print
                </button>
                <button type="button" class="btn btn-light border btn-sm" id="closeReceiptModalBtn">
                    Close
                </button>
            </div>
        </div>

        <div class="receipt-modal-body">
            <iframe id="receiptPreviewFrame" src="about:blank"></iframe>
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
	        return 'GHS ' + Number(amount || 0).toLocaleString(undefined, {
	            minimumFractionDigits: 2,
	            maximumFractionDigits: 2
	        });
	    }

    function openReceiptModalByUrl(titleText, receiptUrl) {
        const modal = document.getElementById('receiptModalBackdrop');
        const frame = document.getElementById('receiptPreviewFrame');
        const title = document.getElementById('receiptModalTitle');

        title.innerText = titleText;
        frame.src = receiptUrl;
        modal.classList.add('show');
    }

	    function openReceiptModal(saleId) {
	        openReceiptModalByUrl('Receipt No.: ' + saleId, 'receipt.php?' + new URLSearchParams({ id: saleId, embedded: '1' }).toString());
	    }

	    function openLinkedReceipt(saleId, linkedSaleIds) {
	        const ids = Array.isArray(linkedSaleIds)
	            ? linkedSaleIds.map(function (id) { return Number(id); }).filter(function (id) { return id > 0; })
	            : [];

	        if (ids.length > 1) {
	            openReceiptModalByUrl(
	                'Table Bill - ' + ids.length + ' orders combined',
	                'receipt.php?' + new URLSearchParams({ ids: ids.join(','), embedded: '1' }).toString()
	            );
	            return;
	        }

	        openReceiptModal(saleId);
	    }

	    async function fetchSaleDetails(saleId) {
	        const response = await fetch('api/get_sale.php?id=' + encodeURIComponent(saleId));
	        return response.json();
	    }

    function closeReceiptModal() {
        const modal = document.getElementById('receiptModalBackdrop');
        const frame = document.getElementById('receiptPreviewFrame');

        modal.classList.remove('show');
        frame.src = 'about:blank';
    }

    function printReceiptFromModal() {
        const frame = document.getElementById('receiptPreviewFrame');

        if (!frame || !frame.contentWindow) {
            closeReceiptModal();
            return;
        }

        const closeAfterPrint = function () {
            setTimeout(function () {
                closeReceiptModal();
            }, 300);
        };

        frame.contentWindow.onafterprint = closeAfterPrint;

        frame.contentWindow.focus();
        frame.contentWindow.print();

        setTimeout(function () {
            if (document.hasFocus()) {
                closeReceiptModal();
            }
        }, 1200);
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
                document.getElementById('editSaleReceipt').value = sale.id;
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
                const subtitle = document.getElementById('viewSaleSubtitle');
                const receiptBtn = document.getElementById('viewSaleReceiptBtn');

                title.innerText = saleId;
                subtitle.innerText = 'Loading sale details...';
	                receiptBtn.dataset.saleId = saleId;
	                receiptBtn.dataset.linkedSaleIds = '';
                body.innerHTML = '<div class="text-center py-5 text-muted"><div class="spinner-border text-primary mb-3" role="status" aria-hidden="true"></div><div>Loading sale details...</div></div>';

                const modal = new bootstrap.Modal(document.getElementById('viewSaleModal'));
                modal.show();

                try {
	                    const result = await fetchSaleDetails(saleId);

                    if (!result.success) {
                        body.innerHTML = '<div class="alert alert-danger mb-0">' + (result.error || 'Unable to load sale.') + '</div>';
                        return;
                    }

	                    const sale = result.sale;
	                    const items = result.bill_items || result.items;
	                    const billSummary = result.bill_summary || sale;
	                    const linkedSaleIds = result.linked_sale_ids || [sale.id];
	                    const packageItems = result.package_items || [];
	                    receiptBtn.dataset.linkedSaleIds = linkedSaleIds.join(',');
	                    subtitle.innerText = linkedSaleIds.length > 1
	                        ? (sale.payment_method || 'Cash') + ' · Table bill with ' + linkedSaleIds.length + ' orders'
	                        : (sale.payment_method || 'Cash') + ' · ' + sale.created_at;

                    let rows = `
                        <div class="sale-item-row sale-item-head mb-2">
                            <span>Product</span>
                            <span class="text-center">Qty</span>
                            <span class="unit">Unit</span>
                            <span class="subtotal text-end">Subtotal</span>
                        </div>
                    `;

                    items.forEach(function (item) {
                        rows += `
                            <div class="sale-item-row mb-2">
                                <div class="fw-semibold text-truncate">${item.name}</div>
                                <div class="text-center">
                                    <span class="badge bg-light text-body border">${item.quantity}</span>
                                </div>
                                <div class="unit text-muted">${money(item.unit_price)}</div>
                                <div class="subtotal text-end fw-semibold">${money(item.subtotal)}</div>
                            </div>
                        `;
                    });

                    let packageRows = '';

                    if (packageItems.length === 0) {
                        packageRows = '<div class="text-muted border rounded p-3 bg-light">No package items for this sale.</div>';
                    } else {
                        packageRows = `
                            <div class="sale-item-row sale-item-head mb-2">
                                <span>Product Name</span>
                                <span class="text-center">Qty</span>
                                <span class="unit">Type</span>
                                <span class="subtotal text-end">Cost</span>
                            </div>
                        `;

                        packageItems.forEach(function (item) {
                            packageRows += `
                                <div class="sale-item-row mb-2">
                                    <div class="fw-semibold text-truncate">${item.item_name}</div>
                                    <div class="text-center">
                                        <span class="badge bg-light text-body border">${item.quantity}</span>
                                    </div>
                                    <div class="unit text-muted">${item.item_type || 'regular'}</div>
                                    <div class="subtotal text-end fw-semibold">${money(item.unit_cost)}</div>
                                </div>
                            `;
                        });
                    }

                    body.innerHTML = `
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Customer</span>
                                <h6 class="mb-0">${sale.customer_name || 'Walk-in Customer'}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Number</span>
                                <h6 class="mb-0">${sale.customer_contact || 'N/A'}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Cashier</span>
                                <h6 class="mb-0">${sale.username || 'N/A'}</h6>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Payment</span>
                                <h6 class="mb-0">${sale.payment_method || 'Cash'}</h6>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Package</span>
                                <h6 class="mb-0">${sale.package_name || 'No package'}</h6>
                                <div class="text-muted fs-sm">${sale.package_price ? money(sale.package_price) : '—'}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Table</span>
                                <h6 class="mb-0">${sale.table_name || 'No table'}</h6>
                                <div class="text-muted fs-sm">${sale.package_tier || '—'}</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="sale-summary-card">
                                <span class="text-muted fs-sm">Date</span>
                                <h6 class="mb-0 fs-sm">${sale.created_at}</h6>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="mb-0">Package Items</h6>
                            <span class="badge bg-light text-muted border">${packageItems.length} item row(s)</span>
                        </div>
                        ${packageRows}
                    </div>

                    <div class="mb-4">
	                        <div class="d-flex justify-content-between align-items-center mb-3">
	                            <h6 class="mb-0">${linkedSaleIds.length > 1 ? 'Table Bill Items' : 'Sale Items'}</h6>
	                            <span class="badge bg-light text-muted border">${items.length} item row(s)</span>
	                        </div>
                        ${rows}
                    </div>

                    <div class="row g-3 justify-content-end">
                        <div class="col-md-5">
                            <div class="sale-summary-card bg-white">
	                                <div class="d-flex justify-content-between mb-2">
	                                    <span class="text-muted">Package Amount</span>
	                                    <strong>${money(billSummary.package_amount || 0)}</strong>
	                                </div>
	                                <div class="d-flex justify-content-between mb-2">
	                                    <span class="text-muted">Additional Order Amount</span>
	                                    <strong>${money(billSummary.additional_amount || 0)}</strong>
	                                </div>
	                                <div class="d-flex justify-content-between mb-2">
	                                    <span class="text-muted">Subtotal</span>
	                                    <strong>${money(billSummary.total_amount)}</strong>
	                                </div>
	                                <div class="d-flex justify-content-between mb-2">
	                                    <span class="text-muted">Discount</span>
	                                    <strong>${money(billSummary.discount)}</strong>
	                                </div>
	                                <div class="d-flex justify-content-between mb-2">
	                                    <span class="text-muted">Tax</span>
	                                    <strong>${money(billSummary.tax)}</strong>
	                                </div>
	                                <hr>
	                                <div class="d-flex justify-content-between fs-16 text-primary">
	                                    <span class="fw-semibold">Total</span>
	                                    <strong>${money(billSummary.final_amount)}</strong>
	                                </div>
	                                ${linkedSaleIds.length > 1 ? '<div class="text-muted fs-sm mt-2">Includes receipts: ' + linkedSaleIds.join(', ') + '</div>' : ''}
	                            </div>
	                        </div>
                    </div>
                `;
                } catch (error) {
                    body.innerHTML = '<div class="alert alert-danger mb-0">System error while loading sale.</div>';
                }
            });
        });

	        document.querySelectorAll('.print-sale-btn').forEach(function (button) {
	            button.addEventListener('click', async function () {
	                const saleId = this.dataset.saleId;
	                try {
	                    const result = await fetchSaleDetails(saleId);
	                    if (result.success) {
	                        openLinkedReceipt(saleId, result.linked_sale_ids || [saleId]);
	                        return;
	                    }
	                } catch (error) {
	                    // Fall back to the single receipt below.
	                }

	                openReceiptModal(saleId);
	            });
	        });

	        document.getElementById('viewSaleReceiptBtn').addEventListener('click', function () {
	            if (this.dataset.saleId) {
	                const linkedIds = (this.dataset.linkedSaleIds || '')
	                    .split(',')
	                    .map(function (id) { return Number(id); })
	                    .filter(function (id) { return id > 0; });
	                openLinkedReceipt(this.dataset.saleId, linkedIds);
	            }
	        });

        document.getElementById('closeReceiptModalBtn').addEventListener('click', closeReceiptModal);
        document.getElementById('printReceiptBtn').addEventListener('click', printReceiptFromModal);

        document.getElementById('receiptModalBackdrop').addEventListener('click', function (event) {
            if (event.target === this) {
                closeReceiptModal();
            }
        });

        applySalesFilters();
    });
</script>

<?php include "includes/footer.php"; ?>
