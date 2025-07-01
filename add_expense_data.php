<?php
session_start();
require 'connect.php';
require 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $expense_category = sanitizeInput($_POST['expense_category'] ?? '');
    $expense_amount = $_POST['expense_amount'] ?? '';
    $expense_date = $_POST['expense_date'] ?? '';

    // Basic validation
    if (empty($expense_category) || !is_numeric($expense_amount) || $expense_amount < 0 || empty($expense_date)) {
        echo json_encode(['success' => false, 'message' => 'Invalid category, amount, or date.']);
        exit();
    }

    $expense_amount = (float)$expense_amount;

    try {
        $stmt = $db->prepare("INSERT INTO expenses (expense_category, amount, expense_date) VALUES (:expense_category, :amount, :expense_date)");
        $stmt->bindParam(':expense_category', $expense_category);
        $stmt->bindParam(':amount', $expense_amount);
        $stmt->bindParam(':expense_date', $expense_date);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Expense data added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save data.']);
            error_log("PDO Error: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        error_log("Database error in add_expense_data.php: " . $e->getMessage());
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>