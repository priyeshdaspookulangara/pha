<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // This query calculates the total quantity for each product and compares it to the reorder level.
    $query = "
        SELECT
            p.id,
            p.name,
            p.reorder_level,
            SUM(i.quantity) AS total_quantity
        FROM
            products p
        LEFT JOIN
            inventory i ON p.id = i.product_id
        GROUP BY
            p.id, p.name, p.reorder_level
        HAVING
            total_quantity <= p.reorder_level OR total_quantity IS NULL
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    $alerts = array();
    while ($row = $result->fetch_assoc()) {
        $alerts[] = $row;
    }

    http_response_code(200);
    echo json_encode($alerts);

} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

close_connection($conn);
?>
