<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

try {
    // Fetch data for the last 6 months for example, ordered chronologically
    // You can adjust the LIMIT and WHERE clauses based on your desired range (1D, 1W, 1M, etc.)
    $stmt = $db->query("SELECT profit_month, profit_amount, profit_year FROM profit ORDER BY profit_year ASC, FIELD(profit_month, 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec') ASC LIMIT 12");
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    $totalProfit = 0;

    foreach ($data as $row) {
        $labels[] = $row['profit_month']; // e.g., "Jan", "Feb"
        $values[] = (float)$row['profit_amount'];
        $totalProfit += (float)$row['profit_amount'];
    }

    echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values, 'totalProfit' => $totalProfit]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching profit data: ' . $e->getMessage()]);
    error_log("Error fetching profit data: " . $e->getMessage());
}
?>