document.addEventListener('DOMContentLoaded', () => {
    // API Base URL
    const API_BASE = 'http://localhost/BTEC%20CODE/backend/api';

    // DOM Elements
    const orderModal = document.getElementById('orderModal');
    const createOrderBtn = document.getElementById('createOrderBtn');
    const closeButtons = document.querySelectorAll('.close-modal');
    const orderForm = document.getElementById('orderForm');
    const ordersTableBody = document.getElementById('ordersTableBody');
    const searchInput = document.getElementById('searchInput');
    const typeFilter = document.getElementById('typeFilter');
    const showingText = document.getElementById('showingText');
    const logoutBtn = document.getElementById('logoutBtn');
    const modalTitle = document.getElementById('modalTitle');
    const submitOrderBtn = document.getElementById('submitOrderBtn');
    const orderIdInput = document.getElementById('orderId');
    const addItemBtn = document.getElementById('addItemBtn');
    const orderItemsList = document.getElementById('orderItemsList');
    const orderTotalEl = document.getElementById('orderTotal');
    const itemTemplate = document.getElementById('orderItemTemplate');

    // Products cache
    let productsCache = [];
    let allOrders = [];
    let itemIndex = 0;

    // --- Logout Logic ---
    if (logoutBtn) {
        logoutBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            try {
                await fetch(`${API_BASE}/auth/logout.php`);
                localStorage.removeItem('user');
                window.location.href = '../login/login.html';
            } catch (error) {
                window.location.href = '../login/login.html';
            }
        });
    }

    // --- Date Pre-fill ---
    const dateInput = document.getElementById('orderDate');
    if (dateInput) {
        dateInput.valueAsDate = new Date();
    }

    // --- Fetch Products for dropdown ---
    async function loadProducts() {
        try {
            const response = await fetch(`${API_BASE}/products/get_products.php`);
            const data = await response.json();
            if (data.success) {
                productsCache = data.data.products;
            }
        } catch (error) {
            console.error('Error loading products:', error);
        }
    }

    // --- Fetch Orders from API ---
    async function loadOrders(search = '', type = 'all') {
        try {
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (type !== 'all') params.append('type', type);

            const response = await fetch(`${API_BASE}/orders/get_orders.php?${params}`);
            const data = await response.json();

            if (data.success) {
                // Group orders by Order_ID
                const groupedOrders = {};
                data.data.orders.forEach(order => {
                    if (!groupedOrders[order.id]) {
                        groupedOrders[order.id] = {
                            id: order.id,
                            date: order.date,
                            type: order.type,
                            partyName: order.partyName,
                            staffName: order.staffName,
                            status: order.status || 'Pending',
                            items: []
                        };
                    }
                    groupedOrders[order.id].items.push({
                        productId: order.productId,
                        productName: order.productName,
                        quantity: order.quantity,
                        price: order.price
                    });
                });
                allOrders = Object.values(groupedOrders);
                renderOrders(allOrders);
            }
        } catch (error) {
            console.error('Error loading orders:', error);
            ordersTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 30px; color: red;">Error loading orders</td></tr>';
        }
    }

    // --- Rendering ---
    function renderOrders(orders) {
        ordersTableBody.innerHTML = '';

        if (orders.length === 0) {
            ordersTableBody.innerHTML = '<tr><td colspan="8" style="text-align:center; padding: 30px;">No orders found.</td></tr>';
            showingText.innerText = 'Showing 0 orders';
            return;
        }

        orders.forEach(order => {
            const row = document.createElement('tr');
            const formattedDate = order.date ? new Date(order.date).toLocaleDateString() : 'N/A';
            const productNames = order.items.map(i => i.productName).join(', ');
            const totalItems = order.items.reduce((sum, i) => sum + i.quantity, 0);
            const statusClass = order.status === 'Confirmed' ? 'confirmed' : 'pending';
            const isConfirmed = order.status === 'Confirmed';

            row.innerHTML = `
                <td>#${order.id}</td>
                <td><strong>${productNames || 'N/A'}</strong></td>
                <td><span class="badge ${order.type.toLowerCase()}">${order.type}</span></td>
                <td>${totalItems}</td>
                <td>${order.partyName || 'N/A'}</td>
                <td>${formattedDate}</td>
                <td><span class="status-badge ${statusClass}">${order.status || 'Pending'}</span></td>
                <td>
                    ${!isConfirmed && order.status !== 'Cancelled' ? `
                        <button class="action-btn confirm-btn" onclick="confirmOrder(${order.id})">Confirm</button>
                        <button class="action-btn cancel-btn" onclick="cancelOrder(${order.id})">Cancel</button>
                    ` : ''}
                    <button class="action-btn edit-btn" onclick="editOrder(${order.id})" ${isConfirmed || order.status === 'Cancelled' ? 'disabled' : ''}>Edit</button>
                </td>
            `;
            ordersTableBody.appendChild(row);
        });
        showingText.innerText = `Showing ${orders.length} orders`;
    }

    // Initial Load
    loadProducts().then(() => loadOrders());

    // --- Add Item Row ---
    function addItemRow(productId = '', quantity = 1, price = '') {
        const template = itemTemplate.content.cloneNode(true);
        const row = template.querySelector('.order-item-row');
        row.dataset.index = itemIndex++;

        // Populate product dropdown
        const productSelect = row.querySelector('.product-select');
        productsCache.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = `${product.name} (Stock: ${product.quantity})`;
            option.dataset.price = product.price;
            if (product.id == productId) option.selected = true;
            productSelect.appendChild(option);
        });

        // Set values if editing
        if (quantity) row.querySelector('.item-quantity').value = quantity;
        if (price) row.querySelector('.item-price-input').value = price;

        // Auto-fill price when product changes
        productSelect.addEventListener('change', function () {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.dataset.price) {
                row.querySelector('.item-price-input').value = selectedOption.dataset.price;
                updateSubtotal(row);
            }
        });

        // Update subtotal on quantity/price change
        row.querySelector('.item-quantity').addEventListener('input', () => updateSubtotal(row));
        row.querySelector('.item-price-input').addEventListener('input', () => updateSubtotal(row));

        // Remove item button
        row.querySelector('.btn-remove-item').addEventListener('click', () => {
            row.remove();
            updateOrderTotal();
        });

        orderItemsList.appendChild(row);
        updateSubtotal(row);
    }

    function updateSubtotal(row) {
        const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
        const price = parseFloat(row.querySelector('.item-price-input').value) || 0;
        const subtotal = qty * price;
        row.querySelector('.subtotal-value').textContent = `$${subtotal.toFixed(2)}`;
        updateOrderTotal();
    }

    function updateOrderTotal() {
        let total = 0;
        document.querySelectorAll('.order-item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-quantity').value) || 0;
            const price = parseFloat(row.querySelector('.item-price-input').value) || 0;
            total += qty * price;
        });
        orderTotalEl.textContent = `$${total.toFixed(2)}`;
    }

    // Add Item Button
    addItemBtn.addEventListener('click', () => addItemRow());

    // --- Modal Handling ---
    createOrderBtn.addEventListener('click', () => {
        openModal(false);
    });

    // Generic Close Modal Logic
    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const modal = btn.closest('.modal-overlay');
            if (modal) {
                modal.classList.remove('active');
            }
        });
    });

    // Close when clicking outside of any modal
    window.addEventListener('click', (e) => {
        if (e.target.classList.contains('modal-overlay')) {
            e.target.classList.remove('active');
        }
    });

    function openModal(isEdit = false, orderData = null) {
        orderModal.classList.add('active');
        orderForm.reset();
        orderItemsList.innerHTML = '';
        itemIndex = 0;
        document.getElementById('orderDate').valueAsDate = new Date();

        if (isEdit && orderData) {
            modalTitle.textContent = 'Edit Order';
            submitOrderBtn.textContent = 'Update Order';
            orderIdInput.value = orderData.id;
            document.getElementById('orderType').value = orderData.type;
            document.getElementById('entityName').value = orderData.partyName;
            if (orderData.date) {
                document.getElementById('orderDate').value = orderData.date.split('T')[0].split(' ')[0];
            }
            // Add existing items
            orderData.items.forEach(item => {
                addItemRow(item.productId, item.quantity, item.price);
            });
        } else {
            modalTitle.textContent = 'Create New Order';
            submitOrderBtn.textContent = 'Create Order';
            orderIdInput.value = '';
            addItemRow(); // Start with one empty item row
        }
    }

    // Cancel Order
    window.cancelOrder = async function (id) {
        if (!confirm('Are you sure you want to CANCEL this order? This will restore values to stock and cannot be undone.')) {
            return;
        }

        try {
            const response = await fetch(`${API_BASE}/orders/cancel_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: id })
            });

            const data = await response.json();
            if (data.success) {
                loadOrders(searchInput.value, typeFilter.value);
            } else {
                alert(data.message || 'Failed to cancel order');
            }
        } catch (error) {
            console.error('Error cancelling order:', error);
            alert('Error cancelling order');
        }
    };

    // Confirm Order Logic
    const confirmModal = document.getElementById('confirmModal');
    const finalConfirmBtn = document.getElementById('finalConfirmBtn');
    let orderToConfirmId = null;

    // Show Confirmation Modal
    window.confirmOrder = function (id) {
        const order = allOrders.find(o => o.id === id);
        if (!order) return;

        orderToConfirmId = id;

        // Populate Modal
        document.getElementById('confOrderId').innerText = '#' + order.id;
        document.getElementById('confOrderDate').innerText = order.date ? new Date(order.date).toLocaleDateString() : 'N/A';
        document.getElementById('confOrderType').innerText = order.type;
        document.getElementById('confOrderType').className = `badge ${order.type.toLowerCase()}`;
        document.getElementById('confPartyName').innerText = order.partyName;

        const itemsList = document.getElementById('confItemsList');
        itemsList.innerHTML = '';
        let grandTotal = 0;

        order.items.forEach(item => {
            const total = item.quantity * item.price;
            grandTotal += total;
            const row = `
                <tr>
                    <td>${item.productName}</td>
                    <td>${item.quantity}</td>
                    <td>$${parseFloat(item.price).toFixed(2)}</td>
                    <td>$${total.toFixed(2)}</td>
                </tr>
            `;
            itemsList.innerHTML += row;
        });

        document.getElementById('confGrandTotal').innerText = '$' + grandTotal.toFixed(2);
        confirmModal.classList.add('active');
    };

    // Handle Final Confirm Click
    finalConfirmBtn.addEventListener('click', async () => {
        if (!orderToConfirmId) return;

        finalConfirmBtn.innerText = 'Confirming...';
        finalConfirmBtn.disabled = true;

        try {
            const response = await fetch(`${API_BASE}/orders/confirm_order.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ order_id: orderToConfirmId })
            });

            const data = await response.json();
            if (data.success) {
                confirmModal.classList.remove('active');
                loadOrders(searchInput.value, typeFilter.value);
            } else {
                alert(data.message || 'Failed to confirm order');
            }
        } catch (error) {
            console.error('Error confirming order:', error);
            alert('Error confirming order');
        } finally {
            finalConfirmBtn.innerText = 'Confirm Order';
            finalConfirmBtn.disabled = false;
        }
    });



    // Edit Order
    window.editOrder = function (id) {
        const order = allOrders.find(o => o.id === id);
        if (order) {
            openModal(true, order);
        }
    };

    // --- Form Submission ---
    orderForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        // Collect items from form
        const items = [];
        document.querySelectorAll('.order-item-row').forEach(row => {
            const productId = row.querySelector('.product-select').value;
            const productName = row.querySelector('.product-select').options[row.querySelector('.product-select').selectedIndex].text.split(' (')[0];
            const quantity = parseInt(row.querySelector('.item-quantity').value);
            const price = parseFloat(row.querySelector('.item-price-input').value);

            if (productId && quantity > 0) {
                items.push({ product_id: productId, product_name: productName, quantity, price });
            }
        });

        if (items.length === 0) {
            alert('Please add at least one item to the order');
            return;
        }

        const orderData = {
            order_type: document.getElementById('orderType').value,
            party_name: document.getElementById('entityName').value,
            order_date: document.getElementById('orderDate').value,
            items: items
        };

        const isEdit = orderIdInput.value !== '';
        if (isEdit) {
            orderData.order_id = parseInt(orderIdInput.value);
        }

        submitOrderBtn.innerText = isEdit ? 'Updating...' : 'Creating...';
        submitOrderBtn.disabled = true;

        try {
            const url = isEdit
                ? `${API_BASE}/orders/update_order.php`
                : `${API_BASE}/orders/create_order.php`;

            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(orderData)
            });

            const data = await response.json();

            if (data.success) {
                loadOrders(searchInput.value, typeFilter.value);
                orderModal.classList.remove('active');
            } else {
                alert(data.message || 'Failed to save order');
            }
        } catch (error) {
            console.error('Order error:', error);
            alert('Error saving order');
        } finally {
            submitOrderBtn.innerText = isEdit ? 'Update Order' : 'Create Order';
            submitOrderBtn.disabled = false;
        }
    });

    // --- Search & Filter ---
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadOrders(searchInput.value, typeFilter.value);
        }, 300);
    });

    typeFilter.addEventListener('change', () => {
        loadOrders(searchInput.value, typeFilter.value);
    });
});
