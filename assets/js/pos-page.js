const POS_IS_ADMIN = Boolean(window.POS_CONFIG && window.POS_CONFIG.isAdmin);
const POS_USER_ID = Number((window.POS_CONFIG && window.POS_CONFIG.userId) || 0);
const POS_USERNAME = String((window.POS_CONFIG && window.POS_CONFIG.username) || "Staff");
let cart = [];
let activeCategory = 'all';
let activeFilter = 'all';
let searchTerm = '';
let selectedTableId = null;
let selectedTableName = null;
let allTables = [];
let modalTableSearchTerm = '';
let activeTableFilter = 'all';
let currentOrdersForPrint = [];
const API_TABLES = 'api/tables.php';
const API_TABLE_ORDERS = 'api/get_table_orders.php';
const API_SAVE_SALE = 'api/save_sale.php';

function money(amount) {
    return 'GHS ' + Number(amount || 0).toFixed(2);
}

function escapeHtml(value) {
    return String(value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function buildQuery(params) {
    return new URLSearchParams(params).toString();
}

async function fetchJson(url, options) {
    const response = await fetch(url, options);
    return response.json();
}

async function postJson(url, payload) {
    return fetchJson(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
}

function addToCart(product) {
    const selectedTable = getSelectedTableRecord();

    if (selectedTable && !selectedTable.booking_id) {
        showToast('Book ' + selectedTable.name + ' before adding items to the cart.', 'error');
        return;
    }

    const existingItem = cart.find(item => Number(item.id) === Number(product.id));

    if (existingItem) {
        if (existingItem.quantity >= existingItem.stock) {
            showToast('Not enough stock available.', 'error');
            return;
        }

        existingItem.quantity += 1;
        showToast(existingItem.name + ' quantity updated in cart.', 'success');
    } else {
        if (product.stock <= 0) {
            showToast('This product is out of stock.', 'error');
            return;
        }

        cart.push({
            id: Number(product.id),
            name: product.name,
            price: Number(product.price),
            stock: Number(product.stock),
            quantity: 1,
            image_path: product.image_path
        });
        showToast(product.name + ' added to cart.', 'success');
    }

    updateCartUI();
}

function removeFromCart(index) {
    cart.splice(index, 1);
    updateCartUI();
}

function updateQuantity(index, delta) {
    if (!cart[index]) {
        return;
    }

    const nextQuantity = cart[index].quantity + delta;

    if (nextQuantity <= 0) {
        removeFromCart(index);
        return;
    }

    if (nextQuantity > cart[index].stock) {
        showToast('Not enough stock available.', 'error');
        return;
    }

    cart[index].quantity = nextQuantity;
    updateCartUI();
}

function clearCart() {
    if (cart.length === 0) {
        return;
    }

    if (!confirm('Clear current order?')) {
        return;
    }

    cart = [];
    updateCartUI();
}

function updateCartUI() {
    const cartTbody = document.getElementById('cartItems');
    const emptyMsg = document.getElementById('emptyOrderMessage');

    cartTbody.innerHTML = '';

    let subtotal = 0;
    let totalQty = 0;

    cart.forEach((item, index) => {
        const lineTotal = item.price * item.quantity;
        subtotal += lineTotal;
        totalQty += item.quantity;

        const rowHtml = `
            <tr>
                <td class="ps-0">
                    <div class="d-flex align-items-center gap-3">
                        <div class="avatar size-10 bg-light rounded p-1">
                            <img src="${escapeHtml(item.image_path || './assets/uploads/placeholder.png')}" class="img-fluid" alt="${escapeHtml(item.name)}">
                        </div>
                        <div>
                            <h6 class="mb-1 fs-14 text-truncate cart-product-name">${escapeHtml(item.name)}</h6>
                            <p class="text-muted mb-0 fs-sm">${money(item.price)}</p>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-light border qty-btn" onclick="updateQuantity(${index}, -1)">-</button>
                        <span class="fw-medium">${item.quantity}</span>
                        <button type="button" class="btn btn-light border qty-btn" onclick="updateQuantity(${index}, 1)">+</button>
                    </div>
                </td>
                <td class="text-end pe-0">
                    <div class="fw-medium">${money(lineTotal)}</div>
                    <button type="button" class="btn btn-link text-danger p-0 fs-sm" onclick="removeFromCart(${index})">Remove</button>
                </td>
            </tr>
        `;

        cartTbody.insertAdjacentHTML('beforeend', rowHtml);
    });

    document.getElementById('subtotalAmount').innerText = money(subtotal);
    document.getElementById('taxAmount').innerText = money(0);
    document.getElementById('discountAmount').innerText = money(0);
    document.getElementById('totalPayableAmount').innerText = money(subtotal);
    document.getElementById('itemCount').innerText = 'Items: ' + totalQty;

    emptyMsg.style.display = cart.length > 0 ? 'none' : 'block';
}

function applyProductFilters() {
    const items = Array.from(document.querySelectorAll('.product-item'));
    const noProductsFound = document.getElementById('noProductsFound');
    let visibleCount = 0;

    items.forEach(item => {
        const productName = item.dataset.name || '';
        const productCategory = item.dataset.category || '';
        const productStock = Number(item.dataset.stock || 0);

        let visible = true;

        if (activeCategory !== 'all' && productCategory !== activeCategory) {
            visible = false;
        }

        if (searchTerm !== '' && productName.indexOf(searchTerm) === -1) {
            visible = false;
        }

        if (activeFilter === 'instock' && productStock <= 0) {
            visible = false;
        }

        if (activeFilter === 'outofstock' && productStock > 0) {
            visible = false;
        }

        if (activeFilter === 'lowstock' && !(productStock > 0 && productStock <= 5)) {
            visible = false;
        }

        item.style.display = visible ? 'block' : 'none';

        if (visible) {
            visibleCount++;
        }
    });

    if (activeFilter === 'price-low' || activeFilter === 'price-high') {
        const grid = document.getElementById('productsGrid');

        items.sort((a, b) => {
            const aPrice = Number(a.dataset.price || 0);
            const bPrice = Number(b.dataset.price || 0);

            return activeFilter === 'price-low' ? aPrice - bPrice : bPrice - aPrice;
        });

        items.forEach(item => grid.appendChild(item));
    }

    if (noProductsFound) {
        noProductsFound.classList.toggle('d-none', visibleCount > 0);
    }
}

function formatTime(dateString) {
    if (!dateString) {
        return '';
    }

    const date = new Date(dateString.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) {
        return dateString;
    }

    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
}

function syncCartTableSelect() {
    const select = document.getElementById('cartTableSelect');
    if (!select) {
        return;
    }

    select.value = selectedTableId ? String(selectedTableId) : '';
}

function getTableVisualState(table) {
    if (table.booking_id) {
        return 'booked';
    }

    if (table.serve_status === 'ready') {
        return 'ready';
    }

    if (table.serve_status === 'serving') {
        return 'serving';
    }

    if (table.reserved_by) {
        return 'reserved';
    }

    if (Number(table.order_count || 0) > 0) {
        return 'occupied';
    }

    return 'free';
}

function getTableStatusLabel(table) {
    const state = getTableVisualState(table);

    if (state === 'booked') {
        return 'Booked';
    }

    if (state === 'ready') {
        return 'Ready to Serve';
    }

    if (state === 'serving') {
        return 'Serving';
    }

    if (state === 'reserved') {
        return 'Reserved';
    }

    if (state === 'occupied') {
        return 'Has Orders';
    }

    return 'Free';
}

function canFinishService(table) {
    const isServingState = table.serve_status === 'serving' || table.serve_status === 'ready';
    return isServingState && Number(table.serving_user_id) === POS_USER_ID;
}

function isCurrentUserServing(table) {
    if (!table) {
        return false;
    }

    if (Number(table.serving_user_id) === POS_USER_ID) {
        return true;
    }

    const serverName = String(table.serving_username || '').trim().toLowerCase();
    const currentName = String(POS_USERNAME || '').trim().toLowerCase();
    return serverName !== '' && currentName !== '' && serverName === currentName;
}

function getSelectedTableRecord() {
    if (!selectedTableId) {
        return null;
    }

    return allTables.find(item => Number(item.id) === Number(selectedTableId)) || null;
}

function updateServingIndicator() {
    const indicator = document.getElementById('servingIndicator');
    const text = document.getElementById('servingIndicatorText');
    const table = getSelectedTableRecord();

    if (!table || table.serve_status === 'none') {
        indicator.classList.remove('show', 'ready');
        text.innerText = 'Serving —';
        return;
    }

    indicator.classList.add('show');
    indicator.classList.toggle('ready', table.serve_status === 'ready');

    if (table.serve_status === 'ready') {
        text.innerText = 'Ready to serve ' + table.name;
    } else if (Number(table.serving_user_id) === POS_USER_ID) {
        text.innerText = 'You are serving ' + table.name;
    } else {
        text.innerText = table.name + ' · served by ' + (table.serving_username || 'staff');
    }
}

function updateSelectedTableBanner() {
    const table = getSelectedTableRecord();
    const badge = document.getElementById('selectedTableStatusBadge');
    const meta = document.getElementById('selectedTableMeta');
    const serveBtn = document.getElementById('serveSelectedTableBtn');
    const bookBtn = document.getElementById('bookSelectedTableBtn');

    if (!table) {
        return;
    }

    const state = getTableVisualState(table);
    badge.innerText = getTableStatusLabel(table);
    badge.className = 'badge mb-1 table-status-pill ' + state;

    const metaParts = ['New items will link to this table'];

    if (table.booking_id) {
        metaParts.unshift(
            'Booked: ' + (table.booking_customer_name || 'Guest') +
            (table.booking_package_name ? ' · ' + table.booking_package_name : '')
        );
    } else {
        metaParts.unshift('Not booked yet — book this table to serve it');
    }

    if (table.serve_status === 'serving') {
        metaParts.unshift('Serving: ' + (table.serving_username || 'Staff'));
    }

    if (table.serve_status === 'ready') {
        metaParts.unshift('Ready to serve');
    }

    meta.innerText = metaParts.join(' · ');

    if (table.serve_status === 'none') {
        serveBtn.innerText = 'Serve';
        serveBtn.className = 'btn btn-primary btn-sm';
    } else {
        serveBtn.innerText = 'Serve Again';
        serveBtn.className = 'btn btn-warning btn-sm';
    }

    // A table can only be served once it has a real booking — same rule as
    // the Tables modal cards. A free/unbooked table can still be linked to a
    // plain walk-in sale, but there is nothing to "serve" until it's booked.
    serveBtn.classList.toggle('d-none', !table.booking_id);
    bookBtn.classList.toggle('d-none', Boolean(table.booking_id));

    updateServingIndicator();
}

function getWalkInCustomerName() {
    const input = document.getElementById('walkInCustomerName');
    return input ? input.value.trim() : '';
}

function getWalkInCustomerContact() {
    const input = document.getElementById('walkInCustomerContact');
    return input ? input.value.trim() : '';
}

function updateCartCustomerCard() {
    const avatar = document.getElementById('cartCustomerAvatar');
    const icon = document.getElementById('cartCustomerIcon');
    const name = document.getElementById('cartCustomerName');
    const meta = document.getElementById('cartCustomerMeta');
    const walkInFields = document.getElementById('walkInCustomerFields');

    if (!avatar || !icon || !name || !meta) {
        return;
    }

    const table = getSelectedTableRecord();

    // A typed-in walk-in name/phone only makes sense when there is no table —
    // once a table is linked, the customer's identity comes from its booking.
    if (walkInFields) {
        walkInFields.classList.toggle('d-none', Boolean(table));
    }

    if (!table) {
        avatar.className = 'avatar size-11 bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center';
        icon.className = 'ri-user-line fs-lg';
        name.innerText = getWalkInCustomerName() || 'Walk-in Customer';
        meta.innerText = getWalkInCustomerContact() || 'Default POS customer';
        return;
    }

    if (table.booking_id) {
        avatar.className = 'avatar size-11 bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center';
        icon.className = 'ri-user-star-line fs-lg';
        name.innerText = table.booking_customer_name || 'Guest';
        meta.innerText = table.name + (table.booking_package_name ? ' · ' + table.booking_package_name : ' · Booked table');
        return;
    }

    avatar.className = 'avatar size-11 bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center';
    icon.className = 'ri-table-line fs-lg';
    name.innerText = 'Walk-in Customer';
    meta.innerText = 'Linked to ' + table.name;
}

function updateCheckoutAvailability() {
    const checkoutBtn = document.getElementById('checkoutBtn');
    if (!checkoutBtn) {
        return;
    }

    const table = getSelectedTableRecord();
    const blocked = Boolean(table) && !table.booking_id;

    checkoutBtn.disabled = blocked;
    checkoutBtn.title = blocked ? ('Book ' + table.name + ' before processing this order.') : '';
}

function setSelectedTable(tableId, tableName) {
    selectedTableId = tableId ? Number(tableId) : null;
    selectedTableName = tableName || null;

    const banner = document.getElementById('selectedTableBanner');
    const ordersSection = document.getElementById('tableOrdersSection');
    const label = document.getElementById('selectedTableLabel');

    if (selectedTableId) {
        banner.classList.remove('d-none');
        ordersSection.classList.remove('d-none');
        label.innerText = selectedTableName || ('Table #' + selectedTableId);
    } else {
        banner.classList.add('d-none');
        ordersSection.classList.add('d-none');
        label.innerText = '—';
    }

    syncCartTableSelect();
    updateSelectedTableBanner();
    updateCartCustomerCard();
    updateCheckoutAvailability();

    if (selectedTableId) {
        loadTableOrders(selectedTableId);
    } else {
        updateServingIndicator();
    }
}

function clearSelectedTable() {
    setSelectedTable(null, null);
}

async function loadTables(showLoader) {
    const grid = document.getElementById('tablesGrid');
    const loadingMsg = document.getElementById('tablesLoadingMsg');

    if (showLoader !== false) {
        grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">Loading tables...</div>';
    }

    try {
        const result = await fetchJson(API_TABLES);

        if (!result.success) {
            grid.innerHTML = '<div class="col-12"><div class="alert alert-warning mb-0">' + escapeHtml(result.error || 'Unable to load tables.') + '</div></div>';
            return;
        }

        allTables = result.tables || [];
        renderTablesGrid();
        refreshCartTableOptions();
        updateSelectedTableBanner();
        updateCartCustomerCard();
        updateCheckoutAvailability();
    } catch (error) {
        grid.innerHTML = '<div class="col-12"><div class="alert alert-danger mb-0">Failed to load tables. Run database migrations if this is a fresh setup.</div></div>';
    } finally {
        if (loadingMsg) {
            loadingMsg.remove();
        }
    }
}

function refreshCartTableOptions() {
    const select = document.getElementById('cartTableSelect');
    if (!select) {
        return;
    }

    const currentValue = select.value;
    select.innerHTML = '<option value="">No table — walk-in order</option>';

    allTables
        .filter(table => table.status === 'Active')
        .forEach(table => {
            const option = document.createElement('option');
            option.value = String(table.id);
            option.textContent = table.name;
            select.appendChild(option);
        });

    select.value = currentValue;
    syncCartTableSelect();
}

function renderTablesGrid() {
    const grid = document.getElementById('tablesGrid');
    const query = modalTableSearchTerm.toLowerCase();
    const filtered = allTables.filter(table => {
        if (table.status !== 'Active' && !POS_IS_ADMIN) {
            return false;
        }

        if (query !== '' && String(table.name).toLowerCase().indexOf(query) === -1) {
            return false;
        }

        const state = getTableVisualState(table);

        if (activeTableFilter === 'free') {
            return state === 'free';
        }

        if (activeTableFilter === 'booked') {
            return state === 'booked';
        }

        if (activeTableFilter === 'reserved') {
            return state === 'reserved';
        }

        if (activeTableFilter === 'serving') {
            return state === 'serving';
        }

        if (activeTableFilter === 'ready') {
            return state === 'ready';
        }

        return true;
    });

    if (filtered.length === 0) {
        grid.innerHTML = '<div class="col-12 text-center py-5 text-muted">No tables match your search or filter.</div>';
        return;
    }

    grid.innerHTML = filtered.map(table => {
        const orderCount = Number(table.order_count || 0);
        const totalAmount = Number(table.total_amount || 0);
        const isSelected = selectedTableId === Number(table.id);
        const state = getTableVisualState(table);
        const cardClasses = ['card', 'table-card', 'h-100', 'mb-0', 'state-' + state];

        if (isSelected) {
            cardClasses.push('selected');
        }

        const bookedLine = table.booking_id
            ? `<div class="table-meta-line"><i class="ri-user-star-line me-1"></i>Booked: ${escapeHtml(table.booking_customer_name || 'Guest')}</div>` +
              (table.booking_package_name ? `<div class="table-meta-line"><i class="ri-gift-line me-1"></i>${escapeHtml(table.booking_package_name)} (${escapeHtml(table.booking_package_tier || '')}) — ${money(table.booking_package_price)}</div>` : '')
            : '';

        const servingLine = table.booking_id && table.serve_status !== 'none'
            ? `<div class="table-meta-line"><i class="ri-restaurant-line me-1"></i>${table.serve_status === 'ready' ? 'Ready to serve' : 'Serving'} · ${escapeHtml(table.serving_username || 'Staff')}</div>`
            : '';

        // A table can only be Served/Add-Drinks once it has a real booking
        // (package + customer) — same rule as tables.php. Until then the
        // only action on the card is Reserve, which creates that booking.
        const showReserveButton = !table.booking_id;
        const reserveButton = !showReserveButton
            ? ''
            : `<button type="button" class="btn btn-outline-purple btn-sm reserve-table-btn table-action-full" data-table-id="${table.id}" data-table-name="${escapeHtml(table.name)}">Reserve</button>`;

        const showServeButton = Boolean(table.booking_id);
        const serveButtonLabel = table.serve_status !== 'none' ? 'Add Drinks Again' : 'Add Drinks';
        const serveButtonClass = table.serve_status !== 'none' ? 'btn-warning' : 'btn-outline-primary';
        const serveButton = !showServeButton
            ? ''
            : `<button type="button" class="btn ${serveButtonClass} btn-sm serve-table-btn table-action-full" data-table-id="${table.id}">${serveButtonLabel}</button>`;

        const showPrintButton = Boolean(table.booking_id) || orderCount > 0;
        const printButton = !showPrintButton
            ? ''
            : `<button type="button" class="btn btn-outline-secondary btn-sm print-table-orders-btn table-action-full" data-table-id="${table.id}" data-table-name="${escapeHtml(table.name)}">Print Receipts</button>`;

        const closeButton = !table.booking_id
            ? ''
            : `<button type="button" class="btn btn-success btn-sm close-table-btn table-action-full" data-table-id="${table.id}" data-table-name="${escapeHtml(table.name)}">Close</button>`;

        return `
            <div class="col-md-6 col-xl-4 col-xxl-3">
                <div class="${cardClasses.join(' ')}" data-table-id="${table.id}" data-table-name="${escapeHtml(table.name)}">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="table-card-content">
                        <div class="table-card-header">
                            <div class="table-card-icon">
                                <i class="ri-restaurant-2-line"></i>
                            </div>
                            <span class="table-status-pill ${state}">${getTableStatusLabel(table)}</span>
                        </div>

                        <h6 class="mb-1 fw-bold">${escapeHtml(table.name)}</h6>
                        ${bookedLine}
                        ${servingLine}
                        <div class="table-meta-line">${orderCount} order${orderCount === 1 ? '' : 's'} today</div>
                        <div class="fw-bold text-primary fs-15">${money(totalAmount)}</div>
                        </div>

                        <div class="table-card-actions" onclick="event.stopPropagation()">
                            ${reserveButton}
                            ${serveButton}
                            ${printButton}
                            ${closeButton}
                        </div>
                    </div>
                </div>
            </div>
        `;
    }).join('');

    grid.querySelectorAll('.table-card').forEach(card => {
        card.addEventListener('click', function() {
            selectTableFromModal(Number(this.dataset.tableId), this.dataset.tableName);
        });
    });

    grid.querySelectorAll('.reserve-table-btn').forEach(button => {
        button.addEventListener('click', function() {
            openReserveModal(Number(this.dataset.tableId), this.dataset.tableName);
        });
    });

    grid.querySelectorAll('.serve-table-btn').forEach(button => {
        button.addEventListener('click', function() {
            handleServeAction(Number(this.dataset.tableId));
        });
    });

    grid.querySelectorAll('.print-table-orders-btn').forEach(button => {
        button.addEventListener('click', function() {
            const tableId = Number(this.dataset.tableId);
            const tableName = this.dataset.tableName || null;
            setSelectedTable(tableId, tableName);
            openOrdersModal(tableId, tableName);
        });
    });

    grid.querySelectorAll('.close-table-btn').forEach(button => {
        button.addEventListener('click', function() {
            closeTableBooking(Number(this.dataset.tableId), this.dataset.tableName);
        });
    });

    if (POS_IS_ADMIN) {
        grid.querySelectorAll('.edit-table-btn').forEach(button => {
            button.addEventListener('click', function() {
                openEditTableModal(Number(this.dataset.tableId));
            });
        });
    }
}

function selectTableFromModal(tableId, tableName) {
    setSelectedTable(tableId, tableName);
    showToast('Selected ' + tableName, 'success');
}

async function tableAction(action, tableId, extraData) {
    try {
        const payload = Object.assign({ action: action, id: tableId }, extraData || {});
        const result = await postJson(API_TABLES, payload);

        if (!result.success) {
            showToast(result.error || 'Action failed.', 'error');
            return false;
        }

        if (result.table) {
            const index = allTables.findIndex(item => Number(item.id) === Number(result.table.id));
            if (index >= 0) {
                allTables[index] = result.table;
            }
        }

        await loadTables(false);
        renderTablesGrid();
        refreshCartTableOptions();

        if (selectedTableId === tableId && result.table) {
            selectedTableName = result.table.name;
            updateSelectedTableBanner();
        }

        if (result.message) {
            showToast(result.message, 'success');
        }

        return true;
    } catch (error) {
        showToast('Unable to complete table action.', 'error');
        return false;
    }
}

async function handleServeAction(tableId) {
    const table = allTables.find(item => Number(item.id) === Number(tableId));
    if (!table) {
        return;
    }

    if (!table.booking_id) {
        showToast('This table is not booked yet. Book it first to serve it.', 'error');
        return;
    }

    const ok = await tableAction('serve', tableId);
    if (ok) {
        setSelectedTable(tableId, table.name);
        closeTablesModal();
    }
}

async function closeTableBooking(tableId, tableName) {
    const label = tableName || ('Table #' + tableId);

    if (!confirm('Close ' + label + '? This computes the final bill (package + all drinks added) and frees the table.')) {
        return;
    }

    try {
        const result = await postJson(API_TABLES, { action: 'close', id: tableId });

        if (!result.success) {
            showToast(result.error || 'Unable to close table.', 'error');
            return;
        }

        if (result.table) {
            const index = allTables.findIndex(item => Number(item.id) === Number(result.table.id));
            if (index >= 0) {
                allTables[index] = result.table;
            }
        }

        await loadTables(false);
        renderTablesGrid();
        refreshCartTableOptions();

        if (selectedTableId === tableId) {
            clearSelectedTable();
        }

        showToast(result.message || 'Table closed.', 'success');

        if (result.sale_ids && result.sale_ids.length > 0) {
            const url = 'receipt.php?' + buildQuery({ ids: result.sale_ids.join(',') });
            openReceiptModalByUrl('Final Table Bill - ' + label, url);
        }
    } catch (error) {
        showToast('Unable to close table.', 'error');
    }
}

function openReserveModal(tableId, tableName) {
    document.getElementById('reserveTableId').value = tableId;
    document.getElementById('reserveModalTableName').innerText = tableName;
    document.getElementById('reserveGuestName').value = '';
    document.getElementById('reserveGuestContact').value = '';
    document.getElementById('reservePackageSelect').value = '';
    document.getElementById('reserveModalBackdrop').classList.add('show');
    setTimeout(function() {
        document.getElementById('reserveGuestName').focus();
    }, 100);
}

function closeReserveModal() {
    document.getElementById('reserveModalBackdrop').classList.remove('show');
}

async function confirmReserveTable() {
    const tableId = Number(document.getElementById('reserveTableId').value);
    const customerName = document.getElementById('reserveGuestName').value.trim();
    const customerContact = document.getElementById('reserveGuestContact').value.trim();
    const packageId = Number(document.getElementById('reservePackageSelect').value || 0);

    if (!customerName) {
        showToast('Customer name is required.', 'error');
        return;
    }

    if (!packageId) {
        showToast('Please select a package.', 'error');
        return;
    }

    const confirmBtn = document.getElementById('confirmReserveBtn');
    confirmBtn.disabled = true;
    confirmBtn.innerText = 'Booking...';

    try {
        const result = await postJson(API_TABLES, {
            action: 'book',
            id: tableId,
            package_id: packageId,
            customer_name: customerName,
            customer_contact: customerContact
        });

        if (!result.success) {
            showToast(result.error || 'Unable to book table.', 'error');
            return;
        }

        if (result.table) {
            const index = allTables.findIndex(item => Number(item.id) === Number(result.table.id));
            if (index >= 0) {
                allTables[index] = result.table;
            } else {
                allTables.push(result.table);
            }
        }

        await loadTables(false);
        renderTablesGrid();
        refreshCartTableOptions();

        const table = allTables.find(item => Number(item.id) === tableId);
        const tableName = table ? table.name : document.getElementById('reserveModalTableName').innerText;

        setSelectedTable(tableId, tableName);
        closeReserveModal();
        closeTablesModal();
        showToast(result.message || 'Table booked.', 'success');

        if (result.sale_id) {
            openReceiptModal(result.sale_id);
        }
    } catch (error) {
        showToast('Unable to book table.', 'error');
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerText = 'Book Table & Create Sale';
    }
}

async function loadTableOrders(tableId) {
    const list = document.getElementById('tableOrdersList');
    const countBadge = document.getElementById('tableOrdersCount');
    const grandTotalEl = document.getElementById('tableGrandTotal');

    list.innerHTML = '<p class="text-muted text-center py-4 mb-0">Loading orders...</p>';

    try {
        const result = await fetchJson(API_TABLE_ORDERS + '?' + buildQuery({ table_id: tableId }));

        if (!result.success) {
            list.innerHTML = '<p class="text-danger text-center py-4 mb-0">' + escapeHtml(result.error || 'Unable to load orders.') + '</p>';
            return;
        }

        const orders = result.orders || [];
        countBadge.innerText = orders.length + ' order' + (orders.length === 1 ? '' : 's');
        grandTotalEl.innerText = money(result.grand_total || 0);

        if (orders.length === 0) {
            list.innerHTML = '<p class="text-muted text-center py-4 mb-0">No orders yet for this table.</p>';
            return;
        }

        list.innerHTML = orders.map(order => {
            const itemsHtml = (order.items || []).map(item => `
                <li class="fs-sm text-muted">${escapeHtml(item.product_name)} × ${item.quantity} — ${money(item.subtotal)}</li>
            `).join('');

            return `
                <div class="border-bottom px-3 py-3">
                    <div class="d-flex justify-content-between align-items-start gap-2">
                        <div>
                            <div class="fw-medium">Order #${order.id}</div>
                            <div class="text-muted fs-sm">${formatTime(order.created_at)} · ${escapeHtml(order.payment_method || 'Cash')}</div>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">${money(order.final_amount)}</div>
                            <div class="text-muted fs-sm">${order.item_count} item${order.item_count === 1 ? '' : 's'}</div>
                        </div>
                    </div>
                    ${itemsHtml ? '<ul class="mb-0 mt-2 ps-3">' + itemsHtml + '</ul>' : ''}
                </div>
            `;
        }).join('');
    } catch (error) {
        list.innerHTML = '<p class="text-danger text-center py-4 mb-0">Failed to load table orders.</p>';
    }
}

function openTablesModal() {
    document.getElementById('tablesModalBackdrop').classList.add('show');
    loadTables();
}

function closeTablesModal() {
    document.getElementById('tablesModalBackdrop').classList.remove('show');
}

function openEditTableModal(tableId) {
    const table = allTables.find(item => Number(item.id) === Number(tableId));
    if (!table) {
        return;
    }

    document.getElementById('editTableId').value = table.id;
    document.getElementById('editTableName').value = table.name;
    document.getElementById('editTableStatus').value = table.status || 'Active';
    document.getElementById('editTableModalBackdrop').classList.add('show');
}

function closeEditTableModal() {
    document.getElementById('editTableModalBackdrop').classList.remove('show');
}

async function createTable() {
    const name = document.getElementById('newTableName').value.trim();
    const status = document.getElementById('newTableStatus').value;

    if (!name) {
        showToast('Table name is required.', 'error');
        return;
    }

    try {
        const result = await postJson(API_TABLES, { action: 'create', name, status });

        if (result.success) {
            showToast(result.message || 'Table created.', 'success');
            document.getElementById('newTableName').value = '';
            document.getElementById('addTableForm').classList.add('d-none');
            await loadTables();
        } else {
            showToast(result.error || 'Unable to create table.', 'error');
        }
    } catch (error) {
        showToast('Failed to create table.', 'error');
    }
}

async function updateTable() {
    const id = Number(document.getElementById('editTableId').value);
    const name = document.getElementById('editTableName').value.trim();
    const status = document.getElementById('editTableStatus').value;

    if (!name) {
        showToast('Table name is required.', 'error');
        return;
    }

    try {
        const result = await postJson(API_TABLES, { action: 'update', id, name, status });

        if (result.success) {
            showToast(result.message || 'Table updated.', 'success');
            closeEditTableModal();
            await loadTables();

            if (selectedTableId === id) {
                setSelectedTable(id, name);
            }
        } else {
            showToast(result.error || 'Unable to update table.', 'error');
        }
    } catch (error) {
        showToast('Failed to update table.', 'error');
    }
}

async function deleteTable(tableId, tableName) {
    const label = tableName || ('Table #' + tableId);

    if (!confirm('Delete ' + label + '? Existing sales will keep their history but lose the table link.')) {
        return;
    }

    try {
        const result = await postJson(API_TABLES, { action: 'delete', id: tableId });

        if (result.success) {
            showToast(result.message || 'Table deleted.', 'success');
            closeEditTableModal();

            if (selectedTableId === tableId) {
                clearSelectedTable();
            }

            await loadTables();
        } else {
            showToast(result.error || 'Unable to delete table.', 'error');
        }
    } catch (error) {
        showToast('Failed to delete table.', 'error');
    }
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('posToast');

    toast.className = 'pos-toast show ' + type;
    toast.innerText = message;

    clearTimeout(window.posToastTimeout);
    window.posToastTimeout = setTimeout(function() {
        toast.className = 'pos-toast';
        toast.innerText = '';
    }, 3000);
}

function openReceiptModal(saleId) {
    const titleText = 'Receipt Preview - Receipt No.: ' + saleId;
    const receiptUrl = 'receipt.php?' + buildQuery({
        id: saleId,
        embedded: '1'
    });
    openReceiptModalByUrl(titleText, receiptUrl);
}

function openReceiptModalByUrl(titleText, receiptUrl) {
    const modal = document.getElementById('receiptModalBackdrop');
    const frame = document.getElementById('receiptPreviewFrame');
    const title = document.getElementById('receiptModalTitle');

    title.innerText = titleText;
    frame.src = receiptUrl;
    modal.classList.add('show');
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

    const closeAfterPrint = function() {
        setTimeout(function() {
            closeReceiptModal();
        }, 300);
    };

    frame.contentWindow.onafterprint = closeAfterPrint;

    frame.contentWindow.focus();
    frame.contentWindow.print();

    setTimeout(function() {
        if (document.hasFocus()) {
            closeReceiptModal();
        }
    }, 1200);
}

async function openOrdersModal(tableId, tableName) {
    const modal = document.getElementById('ordersModalBackdrop');
    const content = document.getElementById('ordersModalContent');
    const titleName = document.getElementById('ordersModalTableName');

    titleName.innerText = tableName || ('Table #' + tableId);
    content.innerHTML = '<p class="text-center text-muted">Loading orders...</p>';
    modal.classList.add('show');

    try {
        const result = await fetchJson(API_TABLE_ORDERS + '?' + buildQuery({ table_id: tableId }));

        if (!result.success) {
            content.innerHTML = '<p class="text-danger text-center">' + escapeHtml(result.error || 'Unable to load orders.') + '</p>';
            return;
        }

        const orders = result.orders || [];
        currentOrdersForPrint = orders;

        if (orders.length === 0) {
            currentOrdersForPrint = [];
            content.innerHTML = '<p class="text-center text-muted">No orders for this table.</p>';
            return;
        }

        // Everything added to a booked table (the package plus every drink round)
        // prints and pays as one bill — these cards are just a breakdown of what
        // makes up that one receipt, not separate receipts.
        let html = `
            <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                <span>${orders.length} order${orders.length === 1 ? '' : 's'} will combine into <strong>one receipt</strong> when printed</span>
                <strong class="text-primary fs-16">${money(result.grand_total || 0)}</strong>
            </div>
        `;

        html += '<div class="row g-4">';

        orders.forEach(order => {
            const itemsHtml = (order.items || []).map(item => `
                <tr>
                    <td>${escapeHtml(item.product_name)}</td>
                    <td class="text-center">${item.quantity}</td>
                    <td class="text-end">${money(item.subtotal)}</td>
                </tr>
            `).join('');

            html += `
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100">
                        <div class="card-header bg-light">
                            <div class="d-flex justify-content-between align-items-center">
                                <h6 class="mb-0 fw-bold">Order #${order.id}</h6>
                                <span class="badge bg-primary">${order.item_count} items</span>
                            </div>
                            <p class="text-muted small mb-0 mt-2">${formatTime(order.created_at)}</p>
                        </div>
                        <div class="card-body">
                            <table class="table table-sm table-borderless mb-3">
                                <tbody>
                                    ${itemsHtml}
                                </tbody>
                            </table>
                            <div class="border-top pt-3">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Payment:</span>
                                    <span class="fw-medium">${escapeHtml(order.payment_method || 'Cash')}</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong class="text-primary">${money(order.final_amount)}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });

        html += '</div>';
        html += `
            <div class="border-top mt-4 pt-3 d-flex flex-wrap justify-content-end gap-2">
                <button type="button" class="btn btn-primary btn-sm" id="printAllReceiptsBottomBtn">
                    <i class="ri-printer-line me-1"></i> Print Receipt
                </button>
            </div>
        `;
        content.innerHTML = html;

        const printAllBottomBtn = document.getElementById('printAllReceiptsBottomBtn');
        if (printAllBottomBtn) {
            printAllBottomBtn.addEventListener('click', printOrdersFromModal);
        }
    } catch (error) {
        currentOrdersForPrint = [];
        content.innerHTML = '<p class="text-danger text-center">Failed to load orders.</p>';
    }
}

function closeOrdersModal() {
    document.getElementById('ordersModalBackdrop').classList.remove('show');
    currentOrdersForPrint = [];
}

function buildTableReceiptUrl(ids, autoPrint) {
    const params = { ids: ids.join(','), embedded: '1' };

    if (autoPrint) {
        params.auto_print = '1';
    }

    return 'receipt.php?' + buildQuery(params);
}

function buildTableReceiptTitle(tableLabel, ids) {
    return ids.length > 1
        ? 'Table Bill - ' + tableLabel + ' (' + ids.length + ' orders combined)'
        : 'Receipt Preview - ' + tableLabel;
}

// Every sale tied to a booked table (the package plus every drink round) is
// one running bill. Whenever we show a receipt for a booked table, pull in
// every linked order instead of just the single sale that was just created.
async function openCombinedTableReceipt(tableId, tableName) {
    try {
        const result = await fetchJson(API_TABLE_ORDERS + '?' + buildQuery({ table_id: tableId }));
        const orders = (result && result.success) ? (result.orders || []) : [];
        const ids = orders.map(order => Number(order.id)).filter(id => id > 0);

        if (ids.length === 0) {
            return;
        }

        const tableLabel = tableName || ('Table #' + tableId);
        openReceiptModalByUrl(buildTableReceiptTitle(tableLabel, ids), buildTableReceiptUrl(ids, false));
    } catch (error) {
        showToast('Unable to load the combined receipt for this table.', 'error');
    }
}

function printOrdersFromModal() {
    if (!selectedTableId || currentOrdersForPrint.length === 0) {
        return;
    }

    const ids = currentOrdersForPrint
        .map(order => Number(order.id))
        .filter(id => id > 0);

    if (ids.length === 0) {
        return;
    }

    const tableLabel = selectedTableName || ('Table #' + selectedTableId);
    closeOrdersModal();
    openReceiptModalByUrl(buildTableReceiptTitle(tableLabel, ids), buildTableReceiptUrl(ids, true));
}

async function processCheckout() {
    const selectedTable = getSelectedTableRecord();

    if (selectedTable && !selectedTable.booking_id) {
        showToast('Book ' + selectedTable.name + ' before processing this order.', 'error');
        return;
    }

    if (cart.length === 0) {
        showToast('Cart is empty.', 'error');
        return;
    }

    const checkoutBtn = document.getElementById('checkoutBtn');
    checkoutBtn.disabled = true;
    checkoutBtn.innerText = 'Processing...';

    showToast('Processing payment...', 'success');

    try {
        const total = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);
        const tableSelect = document.getElementById('cartTableSelect');
        const tableId = tableSelect && tableSelect.value !== '' ? Number(tableSelect.value) : null;

        const result = await postJson(API_SAVE_SALE, {
            items: cart,
            total: total,
            payment_method: document.getElementById('paymentMethod').value,
            table_id: tableId,
            customer_name: tableId ? '' : getWalkInCustomerName(),
            customer_contact: tableId ? '' : getWalkInCustomerContact()
        });

        if (result.success) {
            showToast('Payment completed. Receipt No.: ' + result.sale_id, 'success');

            cart = [];
            updateCartUI();

            if (tableId) {
                setSelectedTable(tableId, selectedTableName);
                await loadTables();

                const orderedTable = allTables.find(item => Number(item.id) === Number(tableId));

                if (orderedTable && orderedTable.booking_id) {
                    await openCombinedTableReceipt(tableId, orderedTable.name);
                } else {
                    openReceiptModal(result.sale_id);
                }
            } else {
                document.getElementById('walkInCustomerName').value = '';
                document.getElementById('walkInCustomerContact').value = '';
                updateCartCustomerCard();
                openReceiptModal(result.sale_id);
            }
        } else {
            showToast(result.error || 'Unable to save sale.', 'error');
        }
    } catch (error) {
        showToast('System error occurred while processing checkout.', 'error');
    } finally {
        checkoutBtn.innerText = 'Process Order';
        updateCheckoutAvailability();
    }
}

function updateClock() {
    const now = new Date();

    const dateEl = document.getElementById('pos-date');
    const timeEl = document.getElementById('pos-time');

    if (dateEl) {
        dateEl.innerText = now.toLocaleDateString();
    }

    if (timeEl) {
        timeEl.innerText = now.toLocaleTimeString();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.add-to-cart-btn').forEach(button => {
        button.addEventListener('click', function() {
            addToCart(JSON.parse(this.dataset.product));
        });
    });

    document.querySelectorAll('.category-filter').forEach(button => {
        button.addEventListener('click', function() {
            document.querySelectorAll('.category-filter').forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            activeCategory = this.dataset.category;
            applyProductFilters();
        });
    });

    document.querySelectorAll('.product-filter').forEach(link => {
        link.addEventListener('click', function(event) {
            event.preventDefault();

            activeFilter = this.dataset.filter;

            if (activeFilter === 'reset') {
                activeFilter = 'all';
                activeCategory = 'all';
                searchTerm = '';
                document.getElementById('productSearch').value = '';

                document.querySelectorAll('.category-filter').forEach(btn => btn.classList.remove('active'));
                document.querySelector('.category-filter[data-category="all"]').classList.add('active');
            }

            applyProductFilters();
        });
    });

    document.getElementById('productSearch').addEventListener('input', function() {
        searchTerm = this.value.toLowerCase().trim();
        applyProductFilters();
    });

    if (document.getElementById('posBtn')) {
        document.getElementById('posBtn').addEventListener('click', function(e) {
            e.preventDefault();
        });
    }

    document.getElementById('clearCartBtn').addEventListener('click', clearCart);
    document.getElementById('checkoutBtn').addEventListener('click', processCheckout);

    const walkInNameInput = document.getElementById('walkInCustomerName');
    const walkInContactInput = document.getElementById('walkInCustomerContact');

    if (walkInNameInput) {
        walkInNameInput.addEventListener('input', updateCartCustomerCard);
    }

    if (walkInContactInput) {
        walkInContactInput.addEventListener('input', updateCartCustomerCard);
    }

    document.getElementById('openTablesBtn').addEventListener('click', openTablesModal);
    document.getElementById('closeTablesModalBtn').addEventListener('click', closeTablesModal);
    document.getElementById('changeTableBtn').addEventListener('click', openTablesModal);
    document.getElementById('clearTableBtn').addEventListener('click', clearSelectedTable);
    document.getElementById('serveSelectedTableBtn').addEventListener('click', function() {
        if (!selectedTableId) {
            return;
        }

        handleServeAction(selectedTableId);
    });

    document.getElementById('bookSelectedTableBtn').addEventListener('click', function() {
        if (!selectedTableId) {
            return;
        }

        const table = getSelectedTableRecord();
        openReserveModal(selectedTableId, table ? table.name : selectedTableName);
    });

    document.getElementById('confirmReserveBtn').addEventListener('click', confirmReserveTable);
    document.getElementById('cancelReserveModalBtn').addEventListener('click', closeReserveModal);
    document.getElementById('reserveModalBackdrop').addEventListener('click', function(event) {
        if (event.target === this) {
            closeReserveModal();
        }
    });

    document.getElementById('reserveGuestName').addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            confirmReserveTable();
        }
    });

    document.querySelectorAll('.tables-filter-chip').forEach(chip => {
        chip.addEventListener('click', function() {
            document.querySelectorAll('.tables-filter-chip').forEach(item => item.classList.remove('active'));
            this.classList.add('active');
            activeTableFilter = this.dataset.tableFilter || 'all';
            renderTablesGrid();
        });
    });

    document.getElementById('cartTableSelect').addEventListener('change', function() {
        if (this.value === '') {
            clearSelectedTable();
            return;
        }

        const tableId = Number(this.value);
        const table = allTables.find(item => Number(item.id) === tableId);
        setSelectedTable(tableId, table ? table.name : null);
    });

    document.getElementById('tablesModalBackdrop').addEventListener('click', function(event) {
        if (event.target === this) {
            closeTablesModal();
        }
    });

    if (POS_IS_ADMIN) {
        document.getElementById('showAddTableFormBtn').addEventListener('click', function() {
            document.getElementById('addTableForm').classList.toggle('d-none');
        });

        document.getElementById('cancelAddTableBtn').addEventListener('click', function() {
            document.getElementById('addTableForm').classList.add('d-none');
        });

        document.getElementById('saveNewTableBtn').addEventListener('click', createTable);

        document.getElementById('closeEditTableModalBtn').addEventListener('click', closeEditTableModal);
        document.getElementById('saveEditTableBtn').addEventListener('click', updateTable);
        document.getElementById('deleteTableBtn').addEventListener('click', function() {
            deleteTable(Number(document.getElementById('editTableId').value), document.getElementById('editTableName').value);
        });

        document.getElementById('editTableModalBackdrop').addEventListener('click', function(event) {
            if (event.target === this) {
                closeEditTableModal();
            }
        });
    }

    document.getElementById('modalTableSearch').addEventListener('input', function() {
        modalTableSearchTerm = this.value.toLowerCase().trim();
        renderTablesGrid();
    });

    document.getElementById('closeReceiptModalBtn').addEventListener('click', closeReceiptModal);
    document.getElementById('printReceiptBtn').addEventListener('click', printReceiptFromModal);

    document.getElementById('receiptModalBackdrop').addEventListener('click', function(event) {
        if (event.target === this) {
            closeReceiptModal();
        }
    });

    document.getElementById('viewOrdersBtn').addEventListener('click', function() {
        if (selectedTableId) {
            openOrdersModal(selectedTableId, selectedTableName);
        }
    });

    document.getElementById('closeOrdersModalBtn').addEventListener('click', closeOrdersModal);
    document.getElementById('printOrdersBtn').addEventListener('click', printOrdersFromModal);

    document.getElementById('ordersModalBackdrop').addEventListener('click', function(event) {
        if (event.target === this) {
            closeOrdersModal();
        }
    });

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeReceiptModal();
        }
    });

    updateCartUI();
    updateClock();
    setInterval(updateClock, 1000);

    loadTables().then(function() {
        const params = new URLSearchParams(window.location.search);
        const tableParam = params.get('table');

        if (tableParam) {
            const tableId = Number(tableParam);
            const table = allTables.find(item => Number(item.id) === tableId);

            if (table) {
                // "Add Drinks" (tables.php) and "Serve" (POS card) are the same action:
                // both open this table's running tab for adding more items.
                handleServeAction(tableId);
            }
        }
    });
});
