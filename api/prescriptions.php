<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->doctor_id) &&
        !empty($data->patient_id) &&
        !empty($data->prescription_date) &&
        !empty($data->products) && is_array($data->products)
    ) {
        $doctor_id = intval($data->doctor_id);
        $patient_id = intval($data->patient_id);
        $prescription_date = htmlspecialchars(strip_tags($data->prescription_date));

        // Start transaction
        $conn->begin_transaction();

        try {
            // 1. Create prescription record
            $query = "INSERT INTO prescriptions (doctor_id, patient_id, prescription_date) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iis", $doctor_id, $patient_id, $prescription_date);
            if (!$stmt->execute()) throw new Exception("Failed to create prescription.");
            $prescription_id = $stmt->insert_id;

            // 2. Create order record
            $query = "INSERT INTO orders (order_type, reference_id, total_amount, status) VALUES ('prescription', ?, 0, 'pending')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $prescription_id);
            if (!$stmt->execute()) throw new Exception("Failed to create order.");
            $order_id = $stmt->insert_id;

            $total_order_amount = 0;

            // 3. Process each product
            foreach ($data->products as $product) {
                $product_id = intval($product->product_id);
                $quantity_needed = intval($product->quantity);

                // Find inventory batch (FEFO - First-Expiry, First-Out)
                $query = "SELECT id, quantity, price FROM inventory WHERE product_id = ? AND quantity >= ? AND expiry_date > CURDATE() ORDER BY expiry_date ASC LIMIT 1";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $product_id, $quantity_needed);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows == 0) {
                    // Not enough stock in any single batch
                    throw new Exception("Not enough stock for product ID: " . $product_id);
                }

                $inventory_item = $result->fetch_assoc();
                $inventory_id = $inventory_item['id'];
                $price_per_unit = $inventory_item['price'];
                $total_price = $quantity_needed * $price_per_unit;
                $total_order_amount += $total_price;

                // Add to order_items
                $query = "INSERT INTO order_items (order_id, product_id, inventory_id, quantity, price_per_unit, total_price) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("iiiidd", $order_id, $product_id, $inventory_id, $quantity_needed, $price_per_unit, $total_price);
                if (!$stmt->execute()) throw new Exception("Failed to add order item.");

                // Update inventory
                $query = "UPDATE inventory SET quantity = quantity - ? WHERE id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ii", $quantity_needed, $inventory_id);
                if (!$stmt->execute()) throw new Exception("Failed to update inventory.");
            }

            // 4. Update total amount in order
            $query = "UPDATE orders SET total_amount = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("di", $total_order_amount, $order_id);
            if (!$stmt->execute()) throw new Exception("Failed to update order total.");

            // Commit transaction
            $conn->commit();

            http_response_code(201);
            echo json_encode(array("message" => "Prescription processed successfully.", "order_id" => $order_id));

        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            http_response_code(503);
            echo json_encode(array("message" => "Failed to process prescription. " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to process prescription. Data is incomplete."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

close_connection($conn);
?>
