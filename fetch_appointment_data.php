<?php
session_start();
require 'connect.php'; // Make sure this connects to your database

header('Content-Type: application/json');

try {
    $period = $_GET['period'] ?? 'week'; // Default to 'week' if not specified

    // Adjust the query based on the period
    if ($period === 'week') {
        $query = "SELECT DATE(appointment_date) as date, bookings FROM appointments ORDER BY appointment_date DESC LIMIT 7";
    } elseif ($period === 'month') {
        $query = "SELECT DATE_FORMAT(appointment_date, '%M') as month, SUM(bookings) as total_bookings FROM appointments GROUP BY DATE_FORMAT(appointment_date, '%M') ORDER BY DATE_FORMAT(appointment_date, '%M') DESC LIMIT 12";
    } elseif ($period === 'year') {
        $query = "SELECT DATE_FORMAT(appointment_date, '%Y') as year, SUM(bookings) as total_bookings FROM appointments GROUP BY DATE_FORMAT(appointment_date, '%Y') ORDER BY DATE_FORMAT(appointment_date, '%Y') DESC LIMIT 10";
    } else {
        $query = "SELECT DATE(appointment_date) as date, bookings FROM appointments ORDER BY appointment_date DESC";
    }

    $stmt = $db->prepare($query);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $labels = [];
    $values = [];
    foreach ($data as $row) {
        // Correctly format the date based on the period
        if ($period === 'week' || $period === 'month') {
            $labels[] = date('M j, Y', strtotime($row['date']));
        } elseif ($period === 'year') {
            $labels[] = $row['year'];
        } else {
            $labels[] = $row['date'];
        }
        $values[] = (int)($row['total_bookings'] ?? $row['bookings']);
    }

    echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching appointment data: ' . $e->getMessage()]);
    error_log("Error fetching appointment data: " . $e->getMessage());
}
?>