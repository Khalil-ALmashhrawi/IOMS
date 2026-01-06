<?php
/**
 * QuickMart IOMS - Get Orders API
 * GET: Returns all orders with details
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$type = isset($_GET['type']) ? sanitize($_GET['type']) : 'all';

// Build query with JOIN
$sql = "
    SELECT 
        oh.Order_ID,
        oh.Order_Date,
        oh.Order_Type,
        oh.Party_Name,
        oh.Staff_ID,
        IFNULL(oh.Status, 'Pending') as Status,
        s.Full_Name as Staff_Name,
        od.Ordered_Qty,
        od.Sold_Price,
        p.Product_ID,
        p.Name as Product_Name
    FROM OrderHeader oh
    LEFT JOIN Staff s ON oh.Staff_ID = s.Staff_ID
    LEFT JOIN OrderDetail od ON oh.Order_ID = od.Order_ID
    LEFT JOIN Product p ON od.Product_ID = p.Product_ID
    WHERE 1=1
";

$params = [];
$types = "";

if (!empty($search)) {
    $sql .= " AND (oh.Party_Name LIKE ? OR p.Name LIKE ? OR oh.Order_ID LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= "sss";
}

if ($type !== 'all' && !empty($type)) {
    $sql .= " AND oh.Order_Type = ?";
    $params[] = $type;
    $types .= "s";
}

$sql .= " ORDER BY oh.Order_Date DESC";

$stmt = $conn->prepare($sql);

if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = [
        'id' => $row['Order_ID'],
        'date' => $row['Order_Date'],
        'type' => $row['Order_Type'],
        'partyName' => $row['Party_Name'],
        'status' => $row['Status'],
        'staffId' => $row['Staff_ID'],
        'staffName' => $row['Staff_Name'],
        'productId' => $row['Product_ID'],
        'productName' => $row['Product_Name'],
        'quantity' => (int) $row['Ordered_Qty'],
        'price' => (float) $row['Sold_Price']
    ];
}

apiResponse(true, 'Orders retrieved', [
    'orders' => $orders,
    'count' => count($orders)
]);
?>