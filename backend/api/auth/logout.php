<?php
/**
 * QuickMart IOMS - Logout API
 * POST/GET: Destroys session and logs out user
 */

require_once __DIR__ . '/../../config.php';

// Destroy session
$_SESSION = [];
session_destroy();

apiResponse(true, 'Logged out successfully', [
    'redirect' => '../login/login.html'
]);
?>