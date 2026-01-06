<?php
/**
 * QuickMart IOMS - Add Product API
 * POST: { name, category, quantity, price, status }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate required fields
$required = ['name', 'category', 'quantity', 'price'];
foreach ($required as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        apiResponse(false, ucfirst($field) . ' is required', [], 400);
    }
}

$name = sanitize($data['name']);
$category = sanitize($data['category']);
$quantity = (int) $data['quantity'];
$price = (float) $data['price'];
$status = isset($data['status']) ? sanitize($data['status']) : 'Normal';

// Auto-set status based on quantity
if ($quantity < 10) {
    $status = 'Low Stock';
}

// Validate values
if ($quantity < 0) {
    apiResponse(false, 'Quantity cannot be negative', [], 400);
}

if ($price < 0) {
    apiResponse(false, 'Price cannot be negative', [], 400);
}

// Insert product
$stmt = $conn->prepare("INSERT INTO Product (Name, Category, Quantity, Price, Status) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("ssids", $name, $category, $quantity, $price, $status);

if ($stmt->execute()) {
    $productId = $conn->insert_id;
    apiResponse(true, 'Product added successfully', [
        'product_id' => $productId
    ], 201);
} else {
    apiResponse(false, 'Failed to add product: ' . $conn->error, [], 500);
}
?>