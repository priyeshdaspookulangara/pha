<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

// Validate the data
if (
    !empty($data->username) &&
    !empty($data->password) &&
    !empty($data->role)
) {
    // Sanitize the data
    $username = htmlspecialchars(strip_tags($data->username));
    $password = htmlspecialchars(strip_tags($data->password));
    $role = htmlspecialchars(strip_tags($data->role));

    // Hash the password
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Create the query
    $query = "INSERT INTO users (username, password, role) VALUES (?, ?, ?)";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $username, $password_hash, $role);

    // Execute the query
    if ($stmt->execute()) {
        http_response_code(201);
        echo json_encode(array("message" => "User was successfully registered."));
    } else {
        http_response_code(503);
        echo json_encode(array("message" => "Unable to register the user."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to register user. Data is incomplete."));
}

// Close the connection
close_connection($conn);
?>
