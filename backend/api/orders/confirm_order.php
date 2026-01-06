<?php
/**
 * QuickMart IOMS - Confirm Order API
 * POST: { order_id }
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
$stmt = $conn->prepare("SELECT Order_ID, Status FROM OrderHeader WHERE Order_ID = ?");
$stmt->bind_param("i", $orderId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    apiResponse(false, 'Order not found', [], 404);
}

$order = $result->fetch_assoc();

// Check if already confirmed
if ($order['Status'] === 'Confirmed') {
    apiResponse(false, 'Order is already confirmed', [], 400);
}

// Update order status to Confirmed
$stmt = $conn->prepare("UPDATE OrderHeader SET Status = 'Confirmed' WHERE Order_ID = ?");
$stmt->bind_param("i", $orderId);

if ($stmt->execute()) {
    apiResponse(true, 'Order confirmed successfully', [
        'order_id' => $orderId,
        'status' => 'Confirmed'
    ]);
} else {
    apiResponse(false, 'Failed to confirm order: ' . $conn->error, [], 500);
}
?>