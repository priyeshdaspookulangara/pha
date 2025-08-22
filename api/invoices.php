<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_invoices($conn);
        break;
    case 'POST':
        handle_post_invoices($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get_invoices($conn) {
    $query = "SELECT * FROM invoices";
    $params = [];
    $types = "";

    if (isset($_GET['id'])) {
        $query .= " WHERE id = ?";
        $params[] = intval($_GET['id']);
        $types .= "i";
    } elseif (isset($_GET['order_id'])) {
        $query .= " WHERE order_id = ?";
        $params[] = intval($_GET['order_id']);
        $types .= "i";
    }

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    $invoices = array();
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }

    http_response_code(200);
    echo json_encode($invoices);
}

function handle_post_invoices($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->order_id)) {
        $order_id = intval($data->order_id);

        // Check if invoice already exists for this order
        $query = "SELECT id FROM invoices WHERE order_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            http_response_code(409); // Conflict
            echo json_encode(array("message" => "Invoice for this order already exists."));
            return;
        }

        // Get total amount from the order
        $query = "SELECT total_amount FROM orders WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 0) {
            http_response_code(404);
            echo json_encode(array("message" => "Order not found."));
            return;
        }
        $total_amount = $result->fetch_assoc()['total_amount'];

        // Create the invoice
        $query = "INSERT INTO invoices (order_id, total_amount, payment_status) VALUES (?, ?, 'unpaid')";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("id", $order_id, $total_amount);

        if ($stmt->execute()) {
            $invoice_id = $stmt->insert_id;
            http_response_code(201);
            echo json_encode(array(
                "message" => "Invoice created successfully.",
                "invoice_id" => $invoice_id,
                "order_id" => $order_id,
                "total_amount" => $total_amount,
                "payment_status" => "unpaid"
            ));
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to create invoice."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create invoice. Order ID is missing."));
    }
}

close_connection($conn);
?>
