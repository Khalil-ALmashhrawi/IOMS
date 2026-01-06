<?php
require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../database/db_connection.php';

try {
    $sql = "ALTER TABLE OrderHeader ADD COLUMN Status VARCHAR(20) DEFAULT 'Pending'";
    if ($conn->query($sql) === TRUE) {
        echo "Database updated successfully: Added Status column to OrderHeader.";
    } else {
        // Check if column already exists
        if (strpos($conn->error, "Duplicate column name") !== false) {
            echo "Column 'Status' already exists.";
        } else {
            throw new Exception($conn->error);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo "Error updating database: " . $e->getMessage();
}
?>