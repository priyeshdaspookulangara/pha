<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_inventory($conn);
        break;
    case 'POST':
        handle_post_inventory($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get_inventory($conn) {
    $query = "SELECT i.*, p.name as product_name FROM inventory i JOIN products p ON i.product_id = p.id";

    if (isset($_GET['product_id'])) {
        $product_id = intval($_GET['product_id']);
        $query .= " WHERE i.product_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $product_id);
    } else {
        $stmt = $conn->prepare($query);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $inventory = array();
    while ($row = $result->fetch_assoc()) {
        $inventory[] = $row;
    }

    http_response_code(200);
    echo json_encode($inventory);
}

function handle_post_inventory($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->product_id) &&
        !empty($data->batch_number) &&
        isset($data->quantity) &&
        !empty($data->manufacturing_date) &&
        !empty($data->expiry_date) &&
        isset($data->price)
    ) {
        $product_id = intval($data->product_id);
        $batch_number = htmlspecialchars(strip_tags($data->batch_number));
        $quantity = intval($data->quantity);
        $manufacturing_date = htmlspecialchars(strip_tags($data->manufacturing_date));
        $expiry_date = htmlspecialchars(strip_tags($data->expiry_date));
        $price = floatval($data->price);

        $query = "INSERT INTO inventory (product_id, batch_number, quantity, manufacturing_date, expiry_date, price) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isissd", $product_id, $batch_number, $quantity, $manufacturing_date, $expiry_date, $price);

        if ($stmt->execute()) {
            http_response_code(201);
            echo json_encode(array("message" => "Inventory item was added."));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to add inventory item."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to add inventory item. Data is incomplete."));
    }
}

close_connection($conn);
?>
