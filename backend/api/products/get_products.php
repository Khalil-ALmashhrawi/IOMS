<?php
/**
 * QuickMart IOMS - Get Products API
 * GET: Returns all products with optional search and filter
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$category = isset($_GET['category']) ? sanitize($_GET['category']) : 'all';

// Build query
$sql = "SELECT Product_ID, Name, Category, Quantity, Price, Status FROM Product WHERE 1=1";
$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND Name LIKE ?";
    $searchParam = "%$search%";
    $params[] = &$searchParam;
    $types .= "s";
}

if ($category !== 'all' && !empty($category)) {
    $sql .= " AND Category = ?";
    $params[] = &$category;
    $types .= "s";
}

$sql .= " ORDER BY Product_ID DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = [
        'id' => $row['Product_ID'],
        'name' => $row['Name'],
        'category' => $row['Category'],
        'quantity' => (int) $row['Quantity'],
        'price' => (float) $row['Price'],
        'status' => $row['Status']
    ];
}

apiResponse(true, 'Products retrieved', [
    'products' => $products,
    'count' => count($products)
]);
?>