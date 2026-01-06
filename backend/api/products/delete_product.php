<?php
/**
 * QuickMart IOMS - Delete Product API
 * POST: { id }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate product ID
if (empty($data['id'])) {
    apiResponse(false, 'Product ID is required', [], 400);
}

$id = (int) $data['id'];

// Check if product exists
$stmt = $conn->prepare("SELECT Product_ID FROM Product WHERE Product_ID = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    apiResponse(false, 'Product not found', [], 404);
}

// Check if product is used in orders
$stmt = $conn->prepare("SELECT Detail_ID FROM OrderDetail WHERE Product_ID = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    apiResponse(false, 'Cannot delete product - it has associated orders', [], 409);
}

// Delete product
$stmt = $conn->prepare("DELETE FROM Product WHERE Product_ID = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    apiResponse(true, 'Product deleted successfully');
} else {
    apiResponse(false, 'Failed to delete product: ' . $conn->error, [], 500);
}
?>