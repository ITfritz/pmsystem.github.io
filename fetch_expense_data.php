<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

try {
    // This fetches data for the current month by default.
    // You can add logic to filter by '1D', '1W', '1M', '3M', '1Y', 'All' based on a GET parameter.
    $currentMonth = date('Y-m');
    $stmt = $db->prepare("SELECT expense_category, SUM(amount) AS total_amount FROM expenses WHERE DATE_FORMAT(expense_date, '%Y-%m') = :current_month GROUP BY expense_category");
    $stmt->bindParam(':current_month', $currentMonth);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    $totalExpenses = 0;

    foreach ($data as $row) {
        $labels[] = $row['expense_category'];
        $values[] = (float)$row['total_amount'];
        $totalExpenses += (float)$row['total_amount'];
    }

    // Default categories if no data for the month
    if (empty($labels)) {
        $labels = ['Rent', 'Utilities', 'Staff', 'Supplies', 'Misc'];
        $values = [0, 0, 0, 0, 0];
    }

    echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values, 'totalExpenses' => $totalExpenses]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching expenses data: ' . $e->getMessage()]);
    error_log("Error fetching expenses data: " . $e->getMessage());
}
?>