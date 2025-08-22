<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_orders($conn);
        break;
    case 'PUT':
        handle_put_orders($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_get_orders($conn) {
    if (isset($_GET['id'])) {
        // Get a single order with its items
        $id = intval($_GET['id']);
        $query = "SELECT * FROM orders WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $order = $result->fetch_assoc();

            $query_items = "SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?";
            $stmt_items = $conn->prepare($query_items);
            $stmt_items->bind_param("i", $id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            $items = array();
            while($row = $result_items->fetch_assoc()) {
                $items[] = $row;
            }
            $order['items'] = $items;

            http_response_code(200);
            echo json_encode($order);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Order not found."));
        }
    } else {
        // Get all orders, with optional status filter
        $query = "SELECT * FROM orders";
        if (isset($_GET['status'])) {
            $status = htmlspecialchars(strip_tags($_GET['status']));
            $query .= " WHERE status = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $conn->prepare($query);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $orders = array();
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }

        http_response_code(200);
        echo json_encode($orders);
    }
}

function handle_put_orders($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['id']) && !empty($data->status)) {
        $id = intval($_GET['id']);
        $status = htmlspecialchars(strip_tags($data->status));

        if ($status == 'cancelled') {
            // Handle cancellation with a transaction to return items to stock
            $conn->begin_transaction();
            try {
                // Check current status
                $query = "SELECT status FROM orders WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                if($result->num_rows == 0) throw new Exception("Order not found.");
                $current_status = $result->fetch_assoc()['status'];
                if($current_status == 'cancelled' || $current_status == 'completed') {
                    throw new Exception("Order cannot be cancelled.");
                }

                // Update order status
                $query = "UPDATE orders SET status = 'cancelled' WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                if (!$stmt->execute()) throw new Exception("Failed to update order status.");

                // Get order items
                $query = "SELECT inventory_id, quantity FROM order_items WHERE order_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $items = $stmt->get_result();

                // Return items to inventory
                while($item = $items->fetch_assoc()) {
                    $query = "UPDATE inventory SET quantity = quantity + ? WHERE id = ?";
                    $stmt_inv = $conn->prepare($query);
                    $stmt_inv->bind_param("ii", $item['quantity'], $item['inventory_id']);
                    if (!$stmt_inv->execute()) throw new Exception("Failed to return items to inventory.");
                }

                $conn->commit();
                http_response_code(200);
                echo json_encode(array("message" => "Order cancelled successfully."));

            } catch (Exception $e) {
                $conn->rollback();
                http_response_code(503);
                echo json_encode(array("message" => "Failed to cancel order. " . $e->getMessage()));
            }
        } else {
            // Handle other status updates (e.g., 'completed')
            $query = "UPDATE orders SET status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $status, $id);

            if ($stmt->execute()) {
                http_response_code(200);
                echo json_encode(array("message" => "Order status updated."));
            } else {
                http_response_code(503);
                echo json_encode(array("message" => "Unable to update order status."));
            }
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to update status. ID or status is missing."));
    }
}

close_connection($conn);
?>
