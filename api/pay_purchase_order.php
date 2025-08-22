<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (!empty($data->purchase_order_id)) {
        $purchase_order_id = intval($data->purchase_order_id);

        $conn->begin_transaction();

        try {
            // Get PO details
            $po_query = "SELECT total_amount, payment_status FROM purchase_orders WHERE id = ?";
            $po_stmt = $conn->prepare($po_query);
            $po_stmt->bind_param("i", $purchase_order_id);
            $po_stmt->execute();
            $po_result = $po_stmt->get_result();
            if ($po_result->num_rows == 0) {
                throw new Exception("Purchase Order not found.");
            }
            $po = $po_result->fetch_assoc();
            if ($po['payment_status'] == 'paid') {
                throw new Exception("This Purchase Order has already been paid.");
            }
            $amount = $po['total_amount'];

            // 1. Update PO payment status
            $update_query = "UPDATE purchase_orders SET payment_status = 'paid' WHERE id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("i", $purchase_order_id);
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update PO payment status.");
            }

            // 2. Add entry to accounting ledger
            $description = "Expense for Purchase Order #" . $purchase_order_id;
            $ledger_query = "INSERT INTO accounting_ledger (type, description, amount, reference_id, reference_type) VALUES ('expense', ?, ?, ?, 'purchase_order')";
            $ledger_stmt = $conn->prepare($ledger_query);
            $ledger_stmt->bind_param("sdi", $description, $amount, $purchase_order_id);
            if (!$ledger_stmt->execute()) {
                throw new Exception("Failed to log expense in ledger.");
            }

            $conn->commit();
            http_response_code(200);
            echo json_encode(array("message" => "Purchase order marked as paid and expense logged."));

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(503);
            echo json_encode(array("message" => "Failed to process payment. " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Purchase Order ID is missing."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed."));
}

close_connection($conn);
?>
