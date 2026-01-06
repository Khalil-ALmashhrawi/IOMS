<?php
/**
 * QuickMart IOMS - Update Order API
 * POST: { order_id, order_type, party_name, order_date, items: [{product_id, quantity, price}] }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate order ID
if (empty($data['order_id'])) {
    apiResponse(false, 'Order ID is required', [], 400);
}

$orderId = (int) $data['order_id'];

// Check if order exists
$stmt = $conn->prepare("SELECT Order_ID, Order_Type FROM OrderHeader WHERE Order_ID = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    apiResponse(false, 'Order not found', [], 404);
}

$existingOrder = $result->fetch_assoc();
$oldOrderType = $existingOrder['Order_Type'];

$orderType = isset($data['order_type']) ? sanitize($data['order_type']) : $oldOrderType;
$partyName = isset($data['party_name']) ? sanitize($data['party_name']) : null;
$orderDate = isset($data['order_date']) ? sanitize($data['order_date']) : null;

// Validate order type
if (!in_array($orderType, ['Sell', 'Purchase'])) {
    apiResponse(false, 'Invalid order type. Must be Sell or Purchase', [], 400);
}

// Start transaction
$conn->begin_transaction();

try {
    // Update OrderHeader
    $updates = [];
    $params = [];
    $types = "";

    if ($partyName !== null) {
        $updates[] = "Party_Name = ?";
        $params[] = $partyName;
        $types .= "s";
    }

    if ($orderDate !== null) {
        $updates[] = "Order_Date = ?";
        $params[] = $orderDate;
        $types .= "s";
    }

    if ($orderType !== $oldOrderType) {
        $updates[] = "Order_Type = ?";
        $params[] = $orderType;
        $types .= "s";
    }

    if (!empty($updates)) {
        $params[] = $orderId;
        $types .= "i";
        $sql = "UPDATE OrderHeader SET " . implode(", ", $updates) . " WHERE Order_ID = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
    }

    // If items are provided, update them
    if (!empty($data['items']) && is_array($data['items'])) {
        // First, restore quantities from old order details
        $stmt = $conn->prepare("SELECT Product_ID, Ordered_Qty FROM OrderDetail WHERE Order_ID = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();
        $oldItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        foreach ($oldItems as $oldItem) {
            $stmt = $conn->prepare("SELECT Quantity FROM Product WHERE Product_ID = ?");
            $stmt->bind_param("i", $oldItem['Product_ID']);
            $stmt->execute();
            $currentQty = $stmt->get_result()->fetch_assoc()['Quantity'];

            // Reverse the old order effect
            if ($oldOrderType === 'Sell') {
                $restoredQty = $currentQty + $oldItem['Ordered_Qty'];
            } else {
                $restoredQty = $currentQty - $oldItem['Ordered_Qty'];
            }

            $status = $restoredQty < 10 ? 'Low Stock' : 'Normal';
            $stmt = $conn->prepare("UPDATE Product SET Quantity = ?, Status = ? WHERE Product_ID = ?");
            $stmt->bind_param("isi", $restoredQty, $status, $oldItem['Product_ID']);
            $stmt->execute();
        }

        // Delete old order details
        $stmt = $conn->prepare("DELETE FROM OrderDetail WHERE Order_ID = ?");
        $stmt->bind_param("i", $orderId);
        $stmt->execute();

        // Add new order details
        foreach ($data['items'] as $item) {
            if (empty($item['product_id']))
                continue;

            $productId = (int) $item['product_id'];
            $quantity = (int) $item['quantity'];
            $price = (float) $item['price'];

            // Get current product quantity
            $stmt = $conn->prepare("SELECT Quantity FROM Product WHERE Product_ID = ?");
            $stmt->bind_param("i", $productId);
            $stmt->execute();
            $product = $stmt->get_result()->fetch_assoc();

            if (!$product) {
                throw new Exception("Product not found: $productId");
            }

            $currentQuantity = (int) $product['Quantity'];

            // For Sell orders, check if enough stock
            if ($orderType === 'Sell' && $currentQuantity < $quantity) {
                throw new Exception("Insufficient stock for product ID $productId. Available: $currentQuantity");
            }

            // Insert new order detail
            $stmt = $conn->prepare("INSERT INTO OrderDetail (Order_ID, Product_ID, Ordered_Qty, Sold_Price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $orderId, $productId, $quantity, $price);
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
    }

    $conn->commit();

    apiResponse(true, 'Order updated successfully');

} catch (Exception $e) {
    $conn->rollback();
    apiResponse(false, 'Failed to update order: ' . $e->getMessage(), [], 500);
}
?>