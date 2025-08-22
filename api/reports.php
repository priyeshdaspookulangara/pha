<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    if (isset($_GET['type'])) {
        $type = $_GET['type'];
        switch ($type) {
            case 'sales':
                generate_sales_report($conn);
                break;
            case 'stock':
                generate_stock_report($conn);
                break;
            case 'expiring':
                generate_expiring_report($conn);
                break;
            case 'popular_items':
                generate_popular_items_report($conn);
                break;
            case 'expired_items':
                generate_expired_items_report($conn);
                break;
            default:
                http_response_code(400);
                echo json_encode(array("message" => "Invalid report type."));
                break;
        }
    } else {
        http_response_code(400);
        echo json_encode(array("message" => "Report type is required."));
    }
} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

function generate_sales_report($conn) {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '1970-01-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

    $query = "
        SELECT
            DATE(transaction_date) as sale_date,
            SUM(amount) as total_sales
        FROM
            transactions
        WHERE
            DATE(transaction_date) BETWEEN ? AND ?
        GROUP BY
            DATE(transaction_date)
        ORDER BY
            sale_date ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();

    $report_data = array();
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    http_response_code(200);
    echo json_encode($report_data);
}

function generate_stock_report($conn) {
    $query = "
        SELECT
            p.id as product_id,
            p.name as product_name,
            SUM(i.quantity) as total_quantity
        FROM
            products p
        LEFT JOIN
            inventory i ON p.id = i.product_id
        GROUP BY
            p.id, p.name
        ORDER BY
            p.name ASC
    ";

    $result = $conn->query($query);
    $report_data = array();
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    http_response_code(200);
    echo json_encode($report_data);
}

function generate_expiring_report($conn) {
    $days = isset($_GET['days']) ? intval($_GET['days']) : 30;

    $query = "
        SELECT
            p.name as product_name,
            i.batch_number,
            i.quantity,
            i.expiry_date
        FROM
            inventory i
        JOIN
            products p ON i.product_id = p.id
        WHERE
            i.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL ? DAY)
        ORDER BY
            i.expiry_date ASC
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $days);
    $stmt->execute();
    $result = $stmt->get_result();

    $report_data = array();
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    http_response_code(200);
    echo json_encode($report_data);
}

function generate_popular_items_report($conn) {
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;

    $query = "
        SELECT
            p.name as product_name,
            SUM(oi.quantity) as total_sold
        FROM
            order_items oi
        JOIN
            products p ON oi.product_id = p.id
        GROUP BY
            p.id, p.name
        ORDER BY
            total_sold DESC
        LIMIT ?
    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    $report_data = array();
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    http_response_code(200);
    echo json_encode($report_data);
}

function generate_expired_items_report($conn) {
    $query = "
        SELECT
            p.name as product_name,
            i.batch_number,
            i.quantity,
            i.expiry_date
        FROM
            inventory i
        JOIN
            products p ON i.product_id = p.id
        WHERE
            i.expiry_date < CURDATE()
        ORDER BY
            i.expiry_date DESC
    ";

    $result = $conn->query($query);
    $report_data = array();
    while ($row = $result->fetch_assoc()) {
        $report_data[] = $row;
    }

    http_response_code(200);
    echo json_encode($report_data);
}

close_connection($conn);
?>
