<?php
/**
 * QuickMart IOMS - Check Session API
 * GET: Returns current user session info
 */

require_once __DIR__ . '/../../config.php';

if (isLoggedIn()) {
    apiResponse(true, 'Session active', [
        'logged_in' => true,
        'user' => [
            'staff_id' => $_SESSION['staff_id'],
            'full_name' => $_SESSION['full_name'],
            'email' => $_SESSION['email']
        ]
    ]);
} else {
    apiResponse(false, 'Not logged in', [
        'logged_in' => false
    ], 401);
}
?>