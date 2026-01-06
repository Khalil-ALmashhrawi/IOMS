document.addEventListener('DOMContentLoaded', () => {
    // API Base URL
    const API_BASE = 'http://localhost/BTEC%20CODE/backend/api';

    // DOM Elements
    const productModal = document.getElementById('productModal');
    const addProductBtn = document.getElementById('addProductBtn');
    const closeButtons = document.querySelectorAll('.close-modal');
    const productForm = document.getElementById('productForm');
    const productsTableBody = document.getElementById('productsTableBody');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const showingText = document.getElementById('showingText');
    const modalTitle = document.getElementById('modalTitle');
    const logoutBtn = document.getElementById('logoutBtn');

    // Form Inputs
    const idInput = document.getElementById('productId');
    const nameInput = document.getElementById('productName');
    const categoryInput = document.getElementById('category');
    const quantityInput = document.getElementById('quantity');
    const priceInput = document.getElementById('price');
    const statusInput = document.getElementById('status');

    // Current products cache
    let allProducts = [];

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

    // --- Fetch Products from API ---
    async function loadProducts(search = '', category = 'all') {
        try {
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (category !== 'all') params.append('category', category);

            const response = await fetch(`${API_BASE}/products/get_products.php?${params}`);
            const data = await response.json();

            if (data.success) {
                allProducts = data.data.products;
                renderProducts(allProducts);
            }
        } catch (error) {
            console.error('Error loading products:', error);
            productsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 30px; color: red;">Error loading products</td></tr>';
        }
    }

    // --- Rendering ---
    function renderProducts(products) {
        productsTableBody.innerHTML = '';

        if (products.length === 0) {
            productsTableBody.innerHTML = '<tr><td colspan="6" style="text-align:center; padding: 30px;">No products found.</td></tr>';
            showingText.innerText = 'Showing 0 products';
            return;
        }

        products.forEach(product => {
            const row = document.createElement('tr');
            const statusClass = product.status === 'Normal' ? 'normal' : 'low-stock';

            row.innerHTML = `
                <td><strong>${product.name}</strong></td>
                <td>${product.category}</td>
                <td>${product.quantity}</td>
                <td>$${parseFloat(product.price).toFixed(2)}</td>
                <td><span class="badge ${statusClass}">${product.status}</span></td>
                <td>
                    <button class="action-btn edit-btn" onclick="editProduct(${product.id})">Edit</button>
                    <button class="action-btn delete-btn" onclick="deleteProduct(${product.id})">Delete</button>
                </td>
            `;
            productsTableBody.appendChild(row);
        });
        showingText.innerText = `Showing ${products.length} products`;
    }

    // Initial Load
    loadProducts();

    // --- Modal Logic ---
    function openModal(isEdit = false) {
        productModal.classList.add('active');
        if (!isEdit) {
            modalTitle.innerText = 'Add Product';
            productForm.reset();
            idInput.value = '';
        } else {
            modalTitle.innerText = 'Edit Product';
        }
    }

    addProductBtn.addEventListener('click', () => openModal(false));

    closeButtons.forEach(btn => {
        btn.addEventListener('click', () => productModal.classList.remove('active'));
    });

    window.addEventListener('click', (e) => {
        if (e.target === productModal) {
            productModal.classList.remove('active');
        }
    });

    // --- CRUD Actions ---

    // Edit Product
    window.editProduct = function (id) {
        const product = allProducts.find(p => p.id === id);
        if (product) {
            idInput.value = product.id;
            nameInput.value = product.name;
            categoryInput.value = product.category;
            quantityInput.value = product.quantity;
            priceInput.value = product.price;
            statusInput.value = product.status;
            openModal(true);
        }
    }

    // Delete Product
    window.deleteProduct = async function (id) {
        if (!confirm('Are you sure you want to delete this product?')) return;

        try {
            const response = await fetch(`${API_BASE}/products/delete_product.php`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id })
            });

            const data = await response.json();

            if (data.success) {
                loadProducts(searchInput.value, categoryFilter.value);
            } else {
                alert(data.message || 'Failed to delete product');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Error deleting product');
        }
    }

    // Form Submit (Create & Update)
    productForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const productData = {
            name: nameInput.value,
            category: categoryInput.value,
            quantity: parseInt(quantityInput.value),
            price: parseFloat(priceInput.value),
            status: statusInput.value
        };

        const isEdit = idInput.value !== '';
        const url = isEdit
            ? `${API_BASE}/products/update_product.php`
            : `${API_BASE}/products/add_product.php`;

        if (isEdit) {
            productData.id = parseInt(idInput.value);
        }

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(productData)
            });

            const data = await response.json();

            if (data.success) {
                loadProducts(searchInput.value, categoryFilter.value);
                productModal.classList.remove('active');
            } else {
                alert(data.message || 'Failed to save product');
            }
        } catch (error) {
            console.error('Save error:', error);
            alert('Error saving product');
        }
    });

    // --- Search & Filter ---
    let searchTimeout;
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadProducts(searchInput.value, categoryFilter.value);
        }, 300);
    });

    categoryFilter.addEventListener('change', () => {
        loadProducts(searchInput.value, categoryFilter.value);
    });

    // Auto-update status based on quantity
    quantityInput.addEventListener('input', () => {
        if (quantityInput.value <= 10) {
            statusInput.value = 'Low Stock';
        } else {
            statusInput.value = 'Normal';
        }
    });
});
