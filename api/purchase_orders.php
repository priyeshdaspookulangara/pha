<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        handle_get_po($conn);
        break;
    case 'POST':
        handle_post_po($conn);
        break;
    case 'PUT':
        handle_put_po($conn);
        break;
    default:
        http_response_code(405);
        echo json_encode(array("message" => "Method Not Allowed"));
        break;
}

function handle_put_po($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['id']) && !empty($data->status)) {
        $id = intval($_GET['id']);
        $status = htmlspecialchars(strip_tags($data->status));

        $query = "UPDATE purchase_orders SET status = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $status, $id);

        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                http_response_code(200);
                echo json_encode(array("message" => "Purchase order status updated."));
            } else {
                http_response_code(404);
                echo json_encode(array("message" => "Purchase order not found or status unchanged."));
            }
        } else {
            http_response_code(503);
            echo json_encode(array("message" => "Unable to update purchase order status."));
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "ID or status is missing."));
    }
}

function handle_get_po($conn) {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $query = "
            SELECT po.*, d.name as distributor_name
            FROM purchase_orders po
            JOIN distributors d ON po.distributor_id = d.id
            WHERE po.id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $po = $result->fetch_assoc();

            $items_query = "
                SELECT poi.*, p.name as product_name
                FROM purchase_order_items poi
                JOIN products p ON poi.product_id = p.id
                WHERE poi.purchase_order_id = ?";
            $items_stmt = $conn->prepare($items_query);
            $items_stmt->bind_param("i", $id);
            $items_stmt->execute();
            $items_result = $items_stmt->get_result();
            $items = [];
            while($row = $items_result->fetch_assoc()) {
                $items[] = $row;
            }
            $po['items'] = $items;

            http_response_code(200);
            echo json_encode($po);
        } else {
            http_response_code(404);
            echo json_encode(array("message" => "Purchase Order not found."));
        }
    } else {
        $query = "
            SELECT po.id, po.order_date, po.status, po.total_amount, d.name as distributor_name
            FROM purchase_orders po
            JOIN distributors d ON po.distributor_id = d.id
            ORDER BY po.order_date DESC";
        $result = $conn->query($query);
        $pos = array();
        while ($row = $result->fetch_assoc()) {
            $pos[] = $row;
        }
        http_response_code(200);
        echo json_encode($pos);
    }
}

function handle_post_po($conn) {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->distributor_id) &&
        !empty($data->order_date) &&
        !empty($data->products) && is_array($data->products)
    ) {
        $distributor_id = intval($data->distributor_id);
        $order_date = htmlspecialchars(strip_tags($data->order_date));
        $expected_delivery_date = !empty($data->expected_delivery_date) ? htmlspecialchars(strip_tags($data->expected_delivery_date)) : null;

        $conn->begin_transaction();

        try {
            $total_order_amount = 0;
            foreach ($data->products as $product) {
                $total_order_amount += floatval($product->quantity) * floatval($product->price_per_unit);
            }

            // 1. Create purchase_orders record
            $query = "INSERT INTO purchase_orders (distributor_id, order_date, expected_delivery_date, total_amount, status) VALUES (?, ?, ?, ?, 'draft')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isds", $distributor_id, $order_date, $expected_delivery_date, $total_order_amount);
            if (!$stmt->execute()) throw new Exception("Failed to create purchase order.");
            $purchase_order_id = $stmt->insert_id;

            // 2. Process each product item
            foreach ($data->products as $product) {
                $product_id = intval($product->product_id);
                $quantity = intval($product->quantity);
                $price_per_unit = floatval($product->price_per_unit);
                $total_price = $quantity * $price_per_unit;

                $item_query = "INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, price_per_unit, total_price) VALUES (?, ?, ?, ?, ?)";
                $item_stmt = $conn->prepare($item_query);
                $item_stmt->bind_param("iiidd", $purchase_order_id, $product_id, $quantity, $price_per_unit, $total_price);
                if (!$item_stmt->execute()) throw new Exception("Failed to add item to purchase order.");
            }

            $conn->commit();
            http_response_code(201);
            echo json_encode(array("message" => "Purchase order created successfully.", "purchase_order_id" => $purchase_order_id));

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(503);
            echo json_encode(array("message" => "Failed to create purchase order. " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to create purchase order. Data is incomplete."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed for this endpoint."));
}

close_connection($conn);
?>
