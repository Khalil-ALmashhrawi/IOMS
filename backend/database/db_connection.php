<?php
/**
 * QuickMart IOMS - Database Connection
 * ملف الاتصال بقاعدة البيانات MySQL
 */

// Include config if not already included
if (!defined('QUICKMART_APP')) {
    require_once __DIR__ . '/../config.php';
}

/**
 * Get database connection
 * @return mysqli
 */
function getDbConnection()
{
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            apiResponse(false, 'Database connection failed: ' . $conn->connect_error, [], 500);
        }

        $conn->set_charset("utf8mb4");
    }

    return $conn;
}

// For backward compatibility - create connection directly
$conn = getDbConnection();
?>