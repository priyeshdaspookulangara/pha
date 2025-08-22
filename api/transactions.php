<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (
        !empty($data->invoice_id) &&
        !empty($data->payment_method) &&
        isset($data->amount)
    ) {
        $invoice_id = intval($data->invoice_id);
        $payment_method = htmlspecialchars(strip_tags($data->payment_method));
        $amount = floatval($data->amount);

        // Start transaction
        $conn->begin_transaction();

        try {
            // Check invoice status
            $query = "SELECT payment_status, total_amount FROM invoices WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $invoice_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows == 0) {
                throw new Exception("Invoice not found.");
            }
            $invoice = $result->fetch_assoc();
            if ($invoice['payment_status'] == 'paid') {
                throw new Exception("Invoice is already paid.");
            }
            // Optional: Check if amount matches total_amount
            // if ($amount < $invoice['total_amount']) {
            //     throw new Exception("Partial payments not supported by this endpoint.");
            // }

            // 1. Create transaction record
            $query = "INSERT INTO transactions (invoice_id, payment_method, amount) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("isd", $invoice_id, $payment_method, $amount);
            if (!$stmt->execute()) {
                throw new Exception("Failed to create transaction record.");
            }
            $transaction_id = $stmt->insert_id;

            // 2. Update invoice status to 'paid'
            $query = "UPDATE invoices SET payment_status = 'paid' WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $invoice_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to update invoice status.");
            }

            // 3. Add entry to accounting ledger
            $order_id_query = "SELECT order_id FROM invoices WHERE id = ?";
            $order_stmt = $conn->prepare($order_id_query);
            $order_stmt->bind_param("i", $invoice_id);
            $order_stmt->execute();
            $order_id = $order_stmt->get_result()->fetch_assoc()['order_id'];
            $description = "Revenue from customer order #" . $order_id;

            $ledger_query = "INSERT INTO accounting_ledger (type, description, amount, reference_id, reference_type) VALUES ('revenue', ?, ?, ?, 'customer_order')";
            $ledger_stmt = $conn->prepare($ledger_query);
            $ledger_stmt->bind_param("sdi", $description, $amount, $order_id);
            if (!$ledger_stmt->execute()) {
                throw new Exception("Failed to log transaction in ledger.");
            }

            // Commit transaction
            $conn->commit();

            http_response_code(201);
            echo json_encode(array(
                "message" => "Transaction successful.",
                "transaction_id" => $transaction_id
            ));

        } catch (Exception $e) {
            $conn->rollback();
            http_response_code(503);
            echo json_encode(array("message" => "Transaction failed: " . $e->getMessage()));
        }

    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Unable to process transaction. Data is incomplete."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

close_connection($conn);
?>
