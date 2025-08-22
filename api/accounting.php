<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '1970-01-01';
    $end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d H:i:s');

    // Get all ledger entries
    $ledger_query = "SELECT * FROM accounting_ledger WHERE transaction_date BETWEEN ? AND ? ORDER BY transaction_date DESC";
    $stmt = $conn->prepare($ledger_query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    $ledger_result = $stmt->get_result();
    $ledger_entries = [];
    while($row = $ledger_result->fetch_assoc()) {
        $ledger_entries[] = $row;
    }

    // Get summary statistics for the given date range
    $summary_query = "
        SELECT
            type,
            SUM(amount) as total
        FROM
            accounting_ledger
        WHERE
            transaction_date BETWEEN ? AND ?
        GROUP BY
            type";
    $summary_stmt = $conn->prepare($summary_query);
    $summary_stmt->bind_param("ss", $start_date, $end_date);
    $summary_stmt->execute();
    $summary_result = $summary_stmt->get_result();

    $total_revenue = 0;
    $total_expense = 0;
    while($row = $summary_result->fetch_assoc()) {
        if ($row['type'] == 'revenue') {
            $total_revenue = $row['total'];
        } else if ($row['type'] == 'expense') {
            $total_expense = $row['total'];
        }
    }

    $net_profit = $total_revenue - $total_expense;

    // Prepare the final response object
    $response = [
        'summary' => [
            'total_revenue' => floatval($total_revenue),
            'total_expense' => floatval($total_expense),
            'net_profit' => floatval($net_profit)
        ],
        'ledger' => $ledger_entries
    ];

    http_response_code(200);
    echo json_encode($response);

} else {
    http_response_code(405);
    echo json_encode(array("message" => "Method Not Allowed"));
}

close_connection($conn);
?>
