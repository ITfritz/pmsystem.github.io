<?php
session_start();
require 'connect.php';
require 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $profit_month = sanitizeInput($_POST['profit_month'] ?? '');
    $profit_year = $_POST['profit_year'] ?? '';
    $profit_amount = $_POST['profit_amount'] ?? '';

    // Basic validation
    if (empty($profit_month) || !is_numeric($profit_year) || $profit_year < 0 || !is_numeric($profit_amount) || $profit_amount < 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid month, year, or amount.']);
        exit();
    }

    $profit_amount = (float)$profit_amount;
    $profit_year = (int)$profit_year;

    try {
        // Use ON DUPLICATE KEY UPDATE to prevent duplicate entries for the same month and year
        $stmt = $db->prepare("
            INSERT INTO profit (profit_month, profit_year, profit_amount)
            VALUES (:profit_month, :profit_year, :profit_amount)
            ON DUPLICATE KEY UPDATE profit_amount = :profit_amount
        ");
        $stmt->bindParam(':profit_month', $profit_month);
        $stmt->bindParam(':profit_year', $profit_year, PDO::PARAM_INT);
        $stmt->bindParam(':profit_amount', $profit_amount);

        if ($stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Profit data added/updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to save data.']);
            error_log("PDO Error: " . implode(", ", $stmt->errorInfo()));
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        error_log("Database error in add_profit_data.php: " . $e->getMessage());
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>