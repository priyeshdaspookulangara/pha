<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

// Get the posted data
$data = json_decode(file_get_contents("php://input"));

// Validate the data
if (
    !empty($data->username) &&
    !empty($data->password)
) {
    $username = htmlspecialchars(strip_tags($data->username));
    $password = htmlspecialchars(strip_tags($data->password));

    // Query to get user details
    $query = "SELECT id, username, password, role FROM users WHERE username = ? LIMIT 0,1";

    // Prepare the statement
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $id = $row['id'];
        $username_db = $row['username'];
        $password_db = $row['password'];
        $role = $row['role'];

        // Verify the password
        if (password_verify($password, $password_db)) {
            // For now, we just return a success message.
            // In a real app, you would generate a JWT here.
            http_response_code(200);
            echo json_encode(array(
                "message" => "Successful login.",
                "user" => array(
                    "id" => $id,
                    "username" => $username_db,
                    "role" => $role
                )
            ));
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "Login failed. Invalid password."));
        }
    } else {
        http_response_code(404);
        echo json_encode(array("message" => "Login failed. User not found."));
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "Unable to login. Data is incomplete."));
}

// Close the connection
close_connection($conn);
?>
