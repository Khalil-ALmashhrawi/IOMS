<?php
/**
 * QuickMart IOMS - Global Configuration
 * هذا الملف يحتوي على الإعدادات العامة للمشروع
 */

// Prevent direct access
if (!defined('QUICKMART_APP')) {
    define('QUICKMART_APP', true);
}

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'QuickMartIOMS');

// API Response Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Standard API Response Function
 * @param bool $success
 * @param string $message
 * @param array $data
 * @param int $statusCode
 */
function apiResponse($success, $message, $data = [], $statusCode = 200)
{
    http_response_code($statusCode);
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Get POST data (JSON or Form)
 * @return array
 */
function getPostData()
{
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if ($data === null) {
        // Fallback to regular POST
        return $_POST;
    }

    return $data;
}

/**
 * Sanitize input data
 * @param string $data
 * @return string
 */
function sanitize($data)
{
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn()
{
    return isset($_SESSION['staff_id']) && !empty($_SESSION['staff_id']);
}

/**
 * Require authentication
 */
function requireAuth()
{
    if (!isLoggedIn()) {
        apiResponse(false, 'Unauthorized - Please login first', [], 401);
    }
}
?>