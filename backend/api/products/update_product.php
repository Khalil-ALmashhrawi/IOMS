<?php
/**
 * QuickMart IOMS - Update Product API
 * POST: { id, name, category, quantity, price, status }
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

// Build update query dynamically
$updates = [];
$params = [];
$types = "";

if (isset($data['name']) && $data['name'] !== '') {
    $updates[] = "Name = ?";
    $params[] = sanitize($data['name']);
    $types .= "s";
}

if (isset($data['category']) && $data['category'] !== '') {
    $updates[] = "Category = ?";
    $params[] = sanitize($data['category']);
    $types .= "s";
}

if (isset($data['quantity'])) {
    $updates[] = "Quantity = ?";
    $params[] = (int) $data['quantity'];
    $types .= "i";
}

if (isset($data['price'])) {
    $updates[] = "Price = ?";
    $params[] = (float) $data['price'];
    $types .= "d";
}

if (isset($data['status']) && $data['status'] !== '') {
    $updates[] = "Status = ?";
    $params[] = sanitize($data['status']);
    $types .= "s";
}

if (empty($updates)) {
    apiResponse(false, 'No fields to update', [], 400);
}

// Add product ID to params
$params[] = $id;
$types .= "i";

$sql = "UPDATE Product SET " . implode(", ", $updates) . " WHERE Product_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);

if ($stmt->execute()) {
    apiResponse(true, 'Product updated successfully');
} else {
    apiResponse(false, 'Failed to update product: ' . $conn->error, [], 500);
}
?>