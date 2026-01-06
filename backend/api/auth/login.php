<?php
/**
 * QuickMart IOMS - Login API
 * POST: { staff_id, password }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate required fields
if (empty($data['staff_id']) || empty($data['password'])) {
    apiResponse(false, 'Staff ID and Password are required', [], 400);
}

$staff_id = sanitize($data['staff_id']);
$password = $data['password'];

// Query database for user
$stmt = $conn->prepare("SELECT Staff_ID, Full_Name, Email, Password FROM Staff WHERE Staff_ID = ?");

if (!$stmt) {
    apiResponse(false, 'Database error: ' . $conn->error, [], 500);
}

$stmt->bind_param("i", $staff_id);
if (!$stmt->execute()) {
    apiResponse(false, 'Database error: ' . $stmt->error, [], 500);
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    apiResponse(false, 'Invalid Staff ID or Password', [], 401);
}

$user = $result->fetch_assoc();

// Verify password
if (!password_verify($password, $user['Password'])) {
    apiResponse(false, 'Invalid Staff ID or Password', [], 401);
}

// Set session
$_SESSION['staff_id'] = $user['Staff_ID'];
$_SESSION['full_name'] = $user['Full_Name'];
$_SESSION['email'] = $user['Email'];

// Return success with user info (exclude password)
unset($user['Password']);
apiResponse(true, 'Login successful', [
    'user' => $user,
    'redirect' => '../dashboard/dashboard.html'
]);
?>