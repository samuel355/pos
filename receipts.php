<?php
require_once "includes/auth_check.php";
require_once "config/db.php";
require_once "includes/table_packages.php";

ensureTablePackageSchema($pdo);

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

function money($amount) {
    return "GHS " . number_format((float)$amount, 2);
}

$receipts = $pdo->query("
    SELECT
        s.*,
        u.username,
        COALESCE(s.customer_name, tb.customer_name, 'Walk-in Customer') AS customer_name,
        COALESCE(s.customer_contact, tb.customer_contact) AS customer_contact,
        rt.name AS table_name,
        tp.name AS package_name
    FROM sales s
    LEFT JOIN users u ON u.id = s.user_id
    LEFT JOIN table_bookings tb
        ON tb.id = s.table_booking_id
        OR tb.sale_id = s.id
        OR (
            s.table_id IS NOT NULL
            AND tb.table_id = s.table_id
            AND s.created_at >= tb.booked_at
            AND (tb.closed_at IS NULL OR s.created_at <= tb.closed_at)
        )
    LEFT JOIN restaurant_tables rt ON rt.id = COALESCE(s.table_id, tb.table_id)
    LEFT JOIN table_packages tp ON tp.id = tb.package_id
    GROUP BY s.id
    ORDER BY s.created_at DESC, s.id DESC
")->fetchAll();

$summary = $pdo->query("
    SELECT
        COUNT(*) AS receipt_count,
        COALESCE(SUM(final_amount), 0) AS total_amount
    FROM sales
")->fetch();
?>

<?php include "includes/header.php"; ?>
<?php include "includes/sidebar.php"; ?>

<style>
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
        <h5 class="mb-1">Receipts</h5>
        <p class="text-muted mb-0">Every individual receipt, including package and extra-order receipts linked to table bills.</p>
    </div>

    <a href="sales.php" class="btn btn-primary">
        <i class="ri-line-chart-line me-1"></i>
        Sales Summary
    </a>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Total Receipts</p>
                <h5 class="mb-0"><?php echo number_format((int)$summary["receipt_count"]); ?></h5>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body">
                <p class="text-muted mb-1">Receipt Total</p>
                <h5 class="mb-0"><?php echo money($summary["total_amount"]); ?></h5>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-3">
        <h6 class="card-title mb-0">All Receipts</h6>
        <input
            type="text"
            id="receiptSearchInput"
            class="form-control"
            style="min-width: 260px; max-width: 360px;"
            placeholder="Search receipt, customer, table, payment...">
    </div>

    <div class="card-body">
        <?php if (empty($receipts)): ?>
            <div class="text-center py-5">
                <img src="./assets/no-order-CCjZwO4J.svg" alt="No receipts" style="max-width: 120px;" class="mb-3">
                <h6 class="mb-1">No receipts found</h6>
                <p class="text-muted mb-0">Receipts will appear here after checkout.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt</th>
                            <th>Cashier</th>
                            <th>Customer</th>
                            <th>Table</th>
                            <th>Payment</th>
                            <th>Subtotal</th>
                            <th>Total</th>
                            <th>Date</th>
                            <th style="width: 160px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receipts as $receipt): ?>
                            <?php
                            $searchText = strtolower(
                                "receipt " . $receipt["id"] . " " .
                                ($receipt["username"] ?: "N/A") . " " .
                                ($receipt["customer_name"] ?: "Walk-in Customer") . " " .
                                ($receipt["table_name"] ?: "walk-in") . " " .
                                ($receipt["package_name"] ?: "") . " " .
                                ($receipt["payment_method"] ?: "Cash") . " " .
                                $receipt["created_at"] . " " .
                                $receipt["final_amount"]
                            );
                            ?>
                            <tr class="receipt-row" data-search="<?php echo e($searchText); ?>">
                                <td><strong>#<?php echo (int)$receipt["id"]; ?></strong></td>
                                <td><?php echo e($receipt["username"] ?: "N/A"); ?></td>
                                <td>
                                    <?php echo e($receipt["customer_name"] ?: "Walk-in Customer"); ?>
                                    <?php if (!empty($receipt["customer_contact"])): ?>
                                        <div class="text-muted fs-sm"><?php echo e($receipt["customer_contact"]); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo e($receipt["table_name"] ?: "Walk-in"); ?>
                                    <?php if (!empty($receipt["package_name"])): ?>
                                        <div class="text-muted fs-sm"><?php echo e($receipt["package_name"]); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo e($receipt["payment_method"] ?: "Cash"); ?></td>
                                <td><?php echo money($receipt["total_amount"]); ?></td>
                                <td class="fw-bold text-primary"><?php echo money($receipt["final_amount"]); ?></td>
                                <td><?php echo e($receipt["created_at"]); ?></td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-sm btn-light border view-receipt-btn" data-receipt-id="<?php echo (int)$receipt["id"]; ?>">
                                            View
                                        </button>
                                        <button type="button" class="btn btn-sm btn-secondary print-receipt-btn" data-receipt-id="<?php echo (int)$receipt["id"]; ?>">
                                            Print
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <tr id="noFilteredReceiptsRow" class="d-none">
                            <td colspan="9" class="text-center py-5">
                                <h6 class="mb-1">No receipts found</h6>
                                <p class="text-muted mb-0">Try another receipt number, customer, table, or payment method.</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="receiptModalBackdrop" class="receipt-modal-backdrop">
    <div class="receipt-modal">
        <div class="receipt-modal-header">
            <h6 id="receiptModalTitle" class="mb-0">Receipt Preview</h6>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="printReceiptBtn">Print</button>
                <button type="button" class="btn btn-light border btn-sm" id="closeReceiptModalBtn">Close</button>
            </div>
        </div>
        <div class="receipt-modal-body">
            <iframe id="receiptPreviewFrame" src="about:blank"></iframe>
        </div>
    </div>
</div>

<script>
    function openReceiptModal(receiptId) {
        const modal = document.getElementById('receiptModalBackdrop');
        const frame = document.getElementById('receiptPreviewFrame');
        const title = document.getElementById('receiptModalTitle');

        title.innerText = 'Receipt No.: ' + receiptId;
        frame.src = 'receipt.php?' + new URLSearchParams({ id: receiptId, embedded: '1' }).toString();
        modal.classList.add('show');
    }

    function closeReceiptModal() {
        document.getElementById('receiptModalBackdrop').classList.remove('show');
        document.getElementById('receiptPreviewFrame').src = 'about:blank';
    }

    function printReceiptFromModal() {
        const frame = document.getElementById('receiptPreviewFrame');

        if (!frame || !frame.contentWindow) {
            return;
        }

        frame.contentWindow.focus();
        frame.contentWindow.print();
    }

    function applyReceiptFilter() {
        const input = document.getElementById('receiptSearchInput');
        const rows = Array.from(document.querySelectorAll('.receipt-row'));
        const emptyRow = document.getElementById('noFilteredReceiptsRow');
        const term = (input.value || '').toLowerCase().trim();
        let visibleCount = 0;

        rows.forEach(function(row) {
            const visible = term === '' || (row.dataset.search || '').indexOf(term) !== -1;
            row.style.display = visible ? '' : 'none';

            if (visible) {
                visibleCount++;
            }
        });

        if (emptyRow) {
            emptyRow.classList.toggle('d-none', visibleCount > 0);
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const search = document.getElementById('receiptSearchInput');

        if (search) {
            search.addEventListener('input', applyReceiptFilter);
        }

        document.querySelectorAll('.view-receipt-btn, .print-receipt-btn').forEach(function(button) {
            button.addEventListener('click', function() {
                openReceiptModal(this.dataset.receiptId);
            });
        });

        document.getElementById('printReceiptBtn').addEventListener('click', printReceiptFromModal);
        document.getElementById('closeReceiptModalBtn').addEventListener('click', closeReceiptModal);
        document.getElementById('receiptModalBackdrop').addEventListener('click', function(event) {
            if (event.target === this) {
                closeReceiptModal();
            }
        });
    });
</script>

<?php include "includes/footer.php"; ?>
