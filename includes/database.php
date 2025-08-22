<?php
// Include the config file
require_once __DIR__ . '/../config/config.php';

// Create a new MySQLi object
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check for connection errors
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to close the connection (optional, as PHP closes it automatically)
function close_connection($conn) {
    $conn->close();
}
?>
