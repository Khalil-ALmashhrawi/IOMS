<?php
/**
 * QuickMart IOMS - Create Order API (Multi-Item Support)
 * POST: { order_type, party_name, order_date, items: [{product_id, product_name, quantity, price}] }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate required fields
if (empty($data['order_type']) || empty($data['party_name'])) {
    apiResponse(false, 'Order type and party name are required', [], 400);
}

// Check for items (support both old single-item and new multi-item format)
$items = [];
if (!empty($data['items']) && is_array($data['items'])) {
    $items = $data['items'];
} elseif (!empty($data['product_name'])) {
    // Backward compatibility for single item
    $items[] = [
        'product_name' => $data['product_name'],
        'quantity' => $data['quantity'] ?? 1,
        'price' => $data['price'] ?? 0
    ];
}

if (empty($items)) {
    apiResponse(false, 'At least one item is required', [], 400);
}

$orderType = sanitize($data['order_type']);
$partyName = sanitize($data['party_name']);
$orderDate = isset($data['order_date']) ? sanitize($data['order_date']) : date('Y-m-d H:i:s');
$staffId = isset($_SESSION['staff_id']) ? $_SESSION['staff_id'] : null;

// Validate order type
if (!in_array($orderType, ['Sell', 'Purchase'])) {
    apiResponse(false, 'Invalid order type. Must be Sell or Purchase', [], 400);
}

// Start transaction
$conn->begin_transaction();

try {
    // Create OrderHeader
    $stmt = $conn->prepare("INSERT INTO OrderHeader (Order_Date, Order_Type, Party_Name, Staff_ID) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $orderDate, $orderType, $partyName, $staffId);
    $stmt->execute();
    $orderId = $conn->insert_id;

    // Process each item
    foreach ($items as $item) {
        $productId = null;
        $currentQuantity = 0;
        $productPrice = 0;

        // Get product by ID or name
        if (!empty($item['product_id'])) {
            $stmt = $conn->prepare("SELECT Product_ID, Quantity, Price FROM Product WHERE Product_ID = ?");
            $stmt->bind_param("i", $item['product_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $productId = $product['Product_ID'];
                $currentQuantity = (int) $product['Quantity'];
                $productPrice = (float) $product['Price'];
            }
        } elseif (!empty($item['product_name'])) {
            $productName = sanitize($item['product_name']);
            $stmt = $conn->prepare("SELECT Product_ID, Quantity, Price FROM Product WHERE Name = ?");
            $stmt->bind_param("s", $productName);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $product = $result->fetch_assoc();
                $productId = $product['Product_ID'];
                $currentQuantity = (int) $product['Quantity'];
                $productPrice = (float) $product['Price'];
            } elseif ($orderType === 'Purchase') {
                // Create new product for purchase orders
                $price = isset($item['price']) ? (float) $item['price'] : 0;
                $stmt = $conn->prepare("INSERT INTO Product (Name, Category, Quantity, Price, Status) VALUES (?, 'Uncategorized', 0, ?, 'Normal')");
                $stmt->bind_param("sd", $productName, $price);
                $stmt->execute();
                $productId = $conn->insert_id;
                $currentQuantity = 0;
                $productPrice = $price;
            }
        }

        if (!$productId) {
            throw new Exception("Product not found: " . ($item['product_name'] ?? $item['product_id']));
        }

        $quantity = (int) $item['quantity'];
        $soldPrice = isset($item['price']) ? (float) $item['price'] : $productPrice;

        // For Sell orders, check if enough stock
        if ($orderType === 'Sell' && $currentQuantity < $quantity) {
            throw new Exception("Insufficient stock for product ID $productId. Available: $currentQuantity");
        }

        // Create OrderDetail
        $stmt = $conn->prepare("INSERT INTO OrderDetail (Order_ID, Product_ID, Ordered_Qty, Sold_Price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $orderId, $productId, $quantity, $soldPrice);
        $stmt->execute();

        // Update product quantity
        if ($orderType === 'Sell') {
            $newQuantity = $currentQuantity - $quantity;
        } else {
            $newQuantity = $currentQuantity + $quantity;
        }

        $status = $newQuantity < 10 ? 'Low Stock' : 'Normal';
        $stmt = $conn->prepare("UPDATE Product SET Quantity = ?, Status = ? WHERE Product_ID = ?");
        $stmt->bind_param("isi", $newQuantity, $status, $productId);
        $stmt->execute();
    }

    $conn->commit();

    apiResponse(true, 'Order created successfully', [
        'order_id' => $orderId
    ], 201);

} catch (Exception $e) {
    $conn->rollback();
    apiResponse(false, 'Failed to create order: ' . $e->getMessage(), [], 500);
}
?>