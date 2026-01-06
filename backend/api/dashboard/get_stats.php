<?php
/**
 * QuickMart IOMS - Dashboard Statistics API
 * GET: Returns dashboard stats (total products, low stock, pending orders)
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

// Get total products count
$result = $conn->query("SELECT COUNT(*) as total FROM Product");
$totalProducts = $result->fetch_assoc()['total'];

// Get low stock products count
$result = $conn->query("SELECT COUNT(*) as low FROM Product WHERE Status = 'Low Stock' OR Quantity < 10");
$lowStock = $result->fetch_assoc()['low'];

// Get pending/recent orders count (last 7 days)
$result = $conn->query("SELECT COUNT(*) as pending FROM OrderHeader WHERE Order_Date >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$pendingOrders = $result->fetch_assoc()['pending'];

// Get recent activity (last 5 orders)
$result = $conn->query("
    SELECT oh.Order_ID, oh.Order_Type, oh.Party_Name, oh.Order_Date, p.Name as Product_Name
    FROM OrderHeader oh
    LEFT JOIN OrderDetail od ON oh.Order_ID = od.Order_ID
    LEFT JOIN Product p ON od.Product_ID = p.Product_ID
    ORDER BY oh.Order_Date DESC
    LIMIT 5
");

$recentActivity = [];
while ($row = $result->fetch_assoc()) {
    $recentActivity[] = $row;
}

// Get inventory distribution for chart
$result = $conn->query("
    SELECT 
        SUM(CASE WHEN Status = 'Normal' AND Quantity >= 10 THEN 1 ELSE 0 END) as normal,
        SUM(CASE WHEN Status = 'Low Stock' OR Quantity < 10 THEN 1 ELSE 0 END) as low_stock,
        SUM(CASE WHEN Quantity = 0 THEN 1 ELSE 0 END) as out_of_stock
    FROM Product
");
$inventory = $result->fetch_assoc();

apiResponse(true, 'Dashboard stats retrieved', [
    'totalProducts' => (int) $totalProducts,
    'lowStock' => (int) $lowStock,
    'pendingOrders' => (int) $pendingOrders,
    'recentActivity' => $recentActivity,
    'inventoryDistribution' => [
        'normal' => (int) $inventory['normal'],
        'lowStock' => (int) $inventory['low_stock'],
        'outOfStock' => (int) $inventory['out_of_stock']
    ]
]);
?>