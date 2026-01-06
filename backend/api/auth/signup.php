<?php
/**
 * QuickMart IOMS - Signup API
 * POST: { staff_id, fullname, email, password }
 */

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    apiResponse(false, 'Method not allowed', [], 405);
}

$data = getPostData();

// Validate required fields
$required = ['staff_id', 'fullname', 'email', 'password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        apiResponse(false, ucfirst($field) . ' is required', [], 400);
    }
}

$staff_id = (int) sanitize($data['staff_id']);
$fullname = sanitize($data['fullname']);
$email = sanitize($data['email']);
$password = $data['password'];
$phone = isset($data['phone']) ? sanitize($data['phone']) : null;

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    apiResponse(false, 'Invalid email format', [], 400);
}

// Validate password length
if (strlen($password) < 6) {
    apiResponse(false, 'Password must be at least 6 characters', [], 400);
}

// Check if staff_id already exists
$stmt = $conn->prepare("SELECT Staff_ID FROM Staff WHERE Staff_ID = ?");
$stmt->bind_param("i", $staff_id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    apiResponse(false, 'Staff ID already exists', [], 409);
}

// Check if email already exists
$stmt = $conn->prepare("SELECT Staff_ID FROM Staff WHERE Email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    apiResponse(false, 'Email already registered', [], 409);
}

// Hash password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert new staff member
$stmt = $conn->prepare("INSERT INTO Staff (Staff_ID, Full_Name, Email, Password, Phone_Number) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("issss", $staff_id, $fullname, $email, $hashedPassword, $phone);

if ($stmt->execute()) {
    apiResponse(true, 'Account created successfully', [
        'staff_id' => $staff_id,
        'redirect' => '../login/login.html'
    ], 201);
} else {
    apiResponse(false, 'Failed to create account: ' . $conn->error, [], 500);
}
?>