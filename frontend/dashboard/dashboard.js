document.addEventListener('DOMContentLoaded', () => {
    // API Base URL
    const API_BASE = 'http://localhost/BTEC%20CODE/backend/api';

    // DOM Elements
    const totalSalesEl = document.getElementById('totalSales');
    const newOrdersEl = document.getElementById('newOrdersCount');
    const usersEl = document.getElementById('usersCount');
    const refundsEl = document.getElementById('refundsCount');
    const recentOrdersBody = document.getElementById('recentOrdersBody');
    const topProductsList = document.getElementById('topProductsList');
    const logoutBtn = document.getElementById('logoutBtn');

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

    // Check Authorization
    const user = JSON.parse(localStorage.getItem('user') || '{}');
    if (!user.Staff_ID) {
        window.location.href = '../login/login.html';
        return;
    }

    // Set User Name
    try {
        document.querySelector('.user-name').textContent = user.Full_Name || 'Admin';
        document.querySelector('.avatar').textContent = (user.Full_Name || 'AM').substring(0, 2).toUpperCase();
    } catch (e) { }

    // Default Data
    const MOCK_DATA = {
        users: 1200,
        refunds: 5
    };

    // --- Fetch Dashboard Stats ---
    async function loadDashboardStats() {
        try {
            // Fetch Basics
            const statsRes = await fetch(`${API_BASE}/dashboard/get_stats.php`);
            const statsData = await statsRes.json();

            // Fetch Orders to Calculate Sales and Get Recent Orders
            const ordersRes = await fetch(`${API_BASE}/orders/get_orders.php`);
            const ordersData = await ordersRes.json();

            if (statsData.success && ordersData.success) {
                const orders = ordersData.data.orders;

                // 1. Calculate Total Sales (Sum of all orders)
                // 1. Calculate Total Sales (Sum of all orders)
                const totalSales = orders
                    .filter(o => o.type === 'Sell' && o.status !== 'Cancelled') // Only Sell orders count as Sales
                    .reduce((sum, item) => sum + (parseFloat(item.price) * item.quantity), 0);

                totalSalesEl.textContent = '$' + totalSales.toLocaleString(undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 });

                // 2. New Orders (Pending)
                newOrdersEl.textContent = statsData.data.pendingOrders;

                // 3. Users
                usersEl.textContent = (MOCK_DATA.users / 1000).toFixed(1) + 'k';

                // 4. Refunds
                refundsEl.textContent = statsData.data.refunds || MOCK_DATA.refunds;

                // 5. Product Stats
                if (document.getElementById('totalProductsCount')) {
                    document.getElementById('totalProductsCount').textContent = statsData.data.totalProducts;
                }
                if (document.getElementById('lowStockCount')) {
                    document.getElementById('lowStockCount').textContent = statsData.data.lowStock;
                }

                // 5. Recent Orders Table (Last 5 unique orders)
                renderRecentOrders(orders);

                // 6. Top Products
                renderTopProducts();

                // 7. Render Chart
                renderSalesChart();
            }

        } catch (error) {
            console.error('Error loading dashboard data:', error);
        }
    }

    function renderRecentOrders(allOrders) {
        // Group by ID to get unique orders
        const uniqueOrdersMap = new Map();
        allOrders.forEach(item => {
            if (!uniqueOrdersMap.has(item.id)) {
                uniqueOrdersMap.set(item.id, {
                    id: item.id,
                    customer: item.partyName,
                    status: item.status || 'Pending',
                    total: 0
                });
            }
            // Add to total
            const order = uniqueOrdersMap.get(item.id);
            order.total += (parseFloat(item.price) * item.quantity);
        });

        const sortedOrders = Array.from(uniqueOrdersMap.values())
            .sort((a, b) => b.id - a.id) // Sort by ID desc (newest first)
            .slice(0, 5); // Take top 5

        recentOrdersBody.innerHTML = '';
        sortedOrders.forEach(order => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td>#${order.id}</td>
                <td>${order.customer}</td>
                <td><span class="status ${order.status.toLowerCase()}">${order.status}</span></td>
                <td>$${order.total.toLocaleString()}</td>
            `;
            recentOrdersBody.appendChild(tr);
        });
    }

    function renderTopProducts() {

        topProductsList.innerHTML = `
            <div class="product-progress">
                <div class="prod-info">
                    <span>Product A</span>
                    <span>40%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: 40%"></div>
                </div>
            </div>
            <div class="product-progress">
                <div class="prod-info">
                    <span>Product B</span>
                    <span>30%</span>
                </div>
                <div class="progress-bar-bg">
                    <div class="progress-bar-fill" style="width: 30%"></div>
                </div>
            </div>
        `;
    }

    let salesChart = null;
    function renderSalesChart() {
        const ctx = document.getElementById('salesChart');
        if (!ctx) return;

        // Chart data
        const data = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
            datasets: [{
                label: 'Sales',
                data: [65, 59, 80, 81, 56, 55, 40],
                fill: true,
                borderColor: '#5d5fef',
                backgroundColor: 'rgba(93, 95, 239, 0.1)',
                tension: 0.4
            }]
        };

        if (salesChart) {
            salesChart.destroy();
        }

        salesChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    x: { grid: { display: false } },
                    y: { display: false }
                }
            }
        });
    }

    // Initial Load
    loadDashboardStats();
});
