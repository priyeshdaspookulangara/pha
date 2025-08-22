<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->purchase_order_id) &&
        !empty($data->items) && is_array($data->items)
    ) {
        $purchase_order_id = intval($data->purchase_order_id);

        $conn->begin_transaction();

        try {
            // Check if PO is in a state that can be received
            $po_query = "SELECT status FROM purchase_orders WHERE id = ?";
            $po_stmt = $conn->prepare($po_query);
            $po_stmt->bind_param("i", $purchase_order_id);
            $po_stmt->execute();
            $po_result = $po_stmt->get_result();
            if ($po_result->num_rows == 0) {
                throw new Exception("Purchase Order not found.");
            }
            $po_status = $po_result->fetch_assoc()['status'];
            if ($po_status == 'completed' || $po_status == 'cancelled') {
                throw new Exception("Shipment for this PO cannot be received as it is already " . $po_status);
            }

            // Process each received item
            foreach ($data->items as $item) {
                if (empty($item->batch_number) || empty($item->manufacturing_date) || empty($item->expiry_date)) {
                    throw new Exception("Batch number, manufacturing date, and expiry date are required for all items.");
                }

                // Get price from original PO item
                $price_query = "SELECT price_per_unit FROM purchase_order_items WHERE purchase_order_id = ? AND product_id = ?";
                $price_stmt = $conn->prepare($price_query);
                $price_stmt->bind_param("ii", $purchase_order_id, $item->product_id);
                $price_stmt->execute();
                $price_result = $price_stmt->get_result();
                if ($price_result->num_rows == 0) {
                    throw new Exception("Product ID " . $item->product_id . " not found in original PO.");
                }
                $price = $price_result->fetch_assoc()['price_per_unit'];

                // Add to inventory
                $inv_query = "INSERT INTO inventory (product_id, batch_number, quantity, manufacturing_date, expiry_date, price) VALUES (?, ?, ?, ?, ?, ?)";
                $inv_stmt = $conn->prepare($inv_query);
                $inv_stmt->bind_param("isisss", $item->product_id, $item->batch_number, $item->quantity, $item->manufacturing_date, $item->expiry_date, $price);
                if (!$inv_stmt->execute()) {
                    throw new Exception("Failed to add item to inventory.");
                }
            }

            // Update PO status to 'completed'
            $update_po_query = "UPDATE purchase_orders SET status = 'completed' WHERE id = ?";
            $update_po_stmt = $conn->prepare($update_po_query);
            $update_po_stmt->bind_param("i", $purchase_order_id);
            if (!$update_po_stmt->execute()) {
                throw new Exception("Failed to update Purchase Order status.");
            }

            $conn->commit();
            http_response_code(200);
            echo json_encode(array("message" => "Shipment received and inventory updated successfully."));

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(503);
            echo json_encode(array("message" => "Failed to receive shipment. " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to receive shipment. Data is incomplete."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed."));
}

close_connection($conn);
?>
