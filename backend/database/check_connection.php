<?php
/**
 * Database Connection Check Script
 * Run this file to verify that MySQL connection is working
 */

echo "<h1 style='font-family: Arial; text-align: center;'>🔍 QuickMart IOMS - Database Connection Check</h1>";
echo "<div style='font-family: Arial; max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>";

// Database configuration
$host = 'localhost';
$username = 'root';
$password = ''; // Default XAMPP has no password
$database = 'QuickMartIOMS';

echo "<h3>📋 Connection Details:</h3>";
echo "<ul>";
echo "<li><strong>Host:</strong> $host</li>";
echo "<li><strong>Username:</strong> $username</li>";
echo "<li><strong>Database:</strong> $database</li>";
echo "</ul>";

echo "<hr>";

// Step 1: Check MySQL Server Connection
echo "<h3>Step 1: Checking MySQL Server Connection...</h3>";
$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    echo "<p style='color: red;'>❌ <strong>MySQL Server Connection Failed:</strong> " . $conn->connect_error . "</p>";
    echo "<p>💡 <strong>Tip:</strong> Make sure Apache and MySQL are running in XAMPP Control Panel.</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ <strong>MySQL Server Connection Successful!</strong></p>";
}

// Step 2: Check if Database Exists
echo "<h3>Step 2: Checking if Database '$database' exists...</h3>";
$result = $conn->query("SHOW DATABASES LIKE '$database'");

if ($result->num_rows > 0) {
    echo "<p style='color: green;'>✅ <strong>Database '$database' exists!</strong></p>";
} else {
    echo "<p style='color: orange;'>⚠️ <strong>Database '$database' does not exist.</strong></p>";
    echo "<p>📌 Creating database...</p>";

    if ($conn->query("CREATE DATABASE $database")) {
        echo "<p style='color: green;'>✅ <strong>Database '$database' created successfully!</strong></p>";
    } else {
        echo "<p style='color: red;'>❌ <strong>Failed to create database:</strong> " . $conn->error . "</p>";
        exit();
    }
}

// Step 3: Connect to the specific database
echo "<h3>Step 3: Connecting to '$database' database...</h3>";
$conn->select_db($database);

if ($conn->error) {
    echo "<p style='color: red;'>❌ <strong>Failed to select database:</strong> " . $conn->error . "</p>";
    exit();
} else {
    echo "<p style='color: green;'>✅ <strong>Successfully connected to '$database'!</strong></p>";
}

// Step 4: Check Tables
echo "<h3>Step 4: Checking Tables...</h3>";
$tables_result = $conn->query("SHOW TABLES");

if ($tables_result->num_rows > 0) {
    echo "<p style='color: green;'>✅ <strong>Found " . $tables_result->num_rows . " table(s):</strong></p>";
    echo "<ul>";
    while ($row = $tables_result->fetch_array()) {
        echo "<li>📁 " . $row[0] . "</li>";
    }
    echo "</ul>";
} else {
    echo "<p style='color: orange;'>⚠️ <strong>No tables found in database.</strong></p>";
    echo "<p>💡 <strong>Tip:</strong> Import the <code>database.sql</code> file via phpMyAdmin to create the tables.</p>";
}

// Step 5: MySQL Version Info
echo "<h3>Step 5: MySQL Server Info</h3>";
echo "<p><strong>MySQL Version:</strong> " . $conn->server_info . "</p>";

echo "<hr>";
echo "<h2 style='color: green; text-align: center;'>🎉 All Checks Passed! Database is Ready.</h2>";

// Close connection
$conn->close();

echo "</div>";
?>