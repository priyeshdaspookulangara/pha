<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/../includes/database.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    // Get all ledger entries
    $ledger_query = "SELECT * FROM accounting_ledger ORDER BY transaction_date DESC";
    $ledger_result = $conn->query($ledger_query);
    $ledger_entries = [];
    while($row = $ledger_result->fetch_assoc()) {
        $ledger_entries[] = $row;
    }

    // Get summary statistics
    $revenue_query = "SELECT SUM(amount) as total_revenue FROM accounting_ledger WHERE type = 'revenue'";
    $revenue_result = $conn->query($revenue_query);
    $total_revenue = $revenue_result->fetch_assoc()['total_revenue'] ?? 0;

    $expense_query = "SELECT SUM(amount) as total_expense FROM accounting_ledger WHERE type = 'expense'";
    $expense_result = $conn->query($expense_query);
    $total_expense = $expense_result->fetch_assoc()['total_expense'] ?? 0;

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
