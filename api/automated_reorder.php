<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

// This script generates draft purchase orders for low-stock items.

// 1. Get low-stock items
$low_stock_query = "
    SELECT p.id as product_id, p.name, p.reorder_level, SUM(i.quantity) AS total_quantity
    FROM products p
    LEFT JOIN inventory i ON p.id = i.product_id
    GROUP BY p.id, p.name, p.reorder_level
    HAVING total_quantity <= p.reorder_level OR total_quantity IS NULL
";
$low_stock_result = $conn->query($low_stock_query);
$low_stock_items = [];
while($row = $low_stock_result->fetch_assoc()) {
    $low_stock_items[] = $row;
}

if (empty($low_stock_items)) {
    http_response_code(200);
    echo json_encode(array("message" => "No items are currently below their reorder level."));
    exit();
}

// 2. Find distributors and group items
$orders_by_distributor = [];
$default_reorder_quantity = 50; // A fixed quantity to reorder

foreach ($low_stock_items as $item) {
    // Find a distributor for this product (simplistic: picks the first one)
    $dist_query = "SELECT distributor_id, price FROM product_distributor WHERE product_id = ? LIMIT 1";
    $dist_stmt = $conn->prepare($dist_query);
    $dist_stmt->bind_param("i", $item['product_id']);
    $dist_stmt->execute();
    $dist_result = $dist_stmt->get_result();

    if ($dist_result->num_rows > 0) {
        $dist_data = $dist_result->fetch_assoc();
        $distributor_id = $dist_data['distributor_id'];
        $price = $dist_data['price'];

        if (!isset($orders_by_distributor[$distributor_id])) {
            $orders_by_distributor[$distributor_id] = [];
        }
        $orders_by_distributor[$distributor_id][] = [
            'product_id' => $item['product_id'],
            'quantity' => $default_reorder_quantity,
            'price_per_unit' => $price
        ];
    }
}

if (empty($orders_by_distributor)) {
    http_response_code(200);
    echo json_encode(array("message" => "Found low-stock items but no assigned distributors for them."));
    exit();
}

// 3. Create draft purchase orders
$conn->begin_transaction();
try {
    $created_pos = 0;
    foreach ($orders_by_distributor as $distributor_id => $products) {
        $total_amount = 0;
        foreach ($products as $p) {
            $total_amount += $p['quantity'] * $p['price_per_unit'];
        }

        $po_query = "INSERT INTO purchase_orders (distributor_id, order_date, status, total_amount) VALUES (?, CURDATE(), 'draft', ?)";
        $po_stmt = $conn->prepare($po_query);
        $po_stmt->bind_param("id", $distributor_id, $total_amount);
        if (!$po_stmt->execute()) throw new Exception("Failed to create a draft PO.");
        $purchase_order_id = $po_stmt->insert_id;

        foreach ($products as $p) {
            $item_query = "INSERT INTO purchase_order_items (purchase_order_id, product_id, quantity, price_per_unit, total_price) VALUES (?, ?, ?, ?, ?)";
            $item_stmt = $conn->prepare($item_query);
            $total_price = $p['quantity'] * $p['price_per_unit'];
            $item_stmt->bind_param("iiidd", $purchase_order_id, $p['product_id'], $p['quantity'], $p['price_per_unit'], $total_price);
            if (!$item_stmt->execute()) throw new Exception("Failed to add items to a draft PO.");
        }
        $created_pos++;
    }

    $conn->commit();
    http_response_code(201);
    echo json_encode(array("message" => "Successfully created " . $created_pos . " new draft purchase order(s)."));

} catch (Exception $e) {
    $conn->rollback();
    http_response_code(503);
    echo json_encode(array("message" => "Failed to create draft purchase orders. " . $e->getMessage()));
}

close_connection($conn);
?>
