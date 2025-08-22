<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $query = "SELECT id, name, specialization FROM doctors";
    $result = $conn->query($query);

    $doctors = array();
    while ($row = $result->fetch_assoc()) {
        $doctors[] = $row;
    }

    http_response_code(200);
    echo json_encode($doctors);
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

close_connection($conn);
?>
