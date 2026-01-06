<?php
require_once '../../config.php';
require_once '../../database/db_connection.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Invalid request method', [], 405);
}

// Get and sanitize input
$data = getPostData();
$order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;

if ($order_id <= 0) {
    apiResponse(false, 'Invalid Order ID', [], 400);
}

try {
    $conn->begin_transaction();

    // 1. Check current order status
    $checkSql = "SELECT Status, Order_Type FROM OrderHeader WHERE Order_ID = ?";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bind_param("i", $order_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception("Order not found");
    }

    $order = $result->fetch_assoc();

    // Only allow cancelling Pending orders
    if ($order['Status'] !== 'Pending') {
        throw new Exception("Only pending orders can be cancelled");
    }

    // 2. Update status to Cancelled
    $updateSql = "UPDATE OrderHeader SET Status = 'Cancelled' WHERE Order_ID = ?";
    $updateStmt = $conn->prepare($updateSql);
    $updateStmt->bind_param("i", $order_id);

    if (!$updateStmt->execute()) {
        throw new Exception("Failed to update order status");
    }

    // 3. Restore stock if it was a Sell order
    $itemsSql = "SELECT Product_ID, Ordered_Qty FROM OrderDetail WHERE Order_ID = ?";
    $itemsStmt = $conn->prepare($itemsSql);
    $itemsStmt->bind_param("i", $order_id);
    $itemsStmt->execute();
    $itemsResult = $itemsStmt->get_result();

    while ($item = $itemsResult->fetch_assoc()) {
        $productId = $item['Product_ID'];
        $qty = $item['Ordered_Qty'];

        if ($order['Order_Type'] === 'Sell') {
            // Restore stock (Increase)
            $stockSql = "UPDATE Product SET Quantity = Quantity + ? WHERE Product_ID = ?";
        } else {
            // Remove added stock (Decrease)
            $stockSql = "UPDATE Product SET Quantity = Quantity - ? WHERE Product_ID = ?";
        }

        $stockStmt = $conn->prepare($stockSql);
        $stockStmt->bind_param("ii", $qty, $productId);
        if (!$stockStmt->execute()) {
            throw new Exception("Failed to update stock for product ID: $productId");
        }
    }

    $conn->commit();
    apiResponse(true, 'Order cancelled successfully');

} catch (Exception $e) {
    $conn->rollback();
    apiResponse(false, $e->getMessage(), [], 500);
}
