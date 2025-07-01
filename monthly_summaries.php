<?php
session_start();
require 'connect.php';

header('Content-Type: application/json');

try {
    // Fetch data for the most recent month for simplicity
    // Or you can fetch specific month/year if parameters are passed.
    $stmt = $db->query("SELECT summary_month, summary_year, weekly_data FROM monthly_summaries ORDER BY summary_year DESC, FIELD(summary_month, 'Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec') DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    $labels = ['Week 1', 'Week 2', 'Week 3', 'Week 4'];
    $values = [0, 0, 0, 0]; // Default values

    if ($data) {
        $weekly_data_array = json_decode($data['weekly_data'], true);
        if (is_array($weekly_data_array)) {
            $values = array_pad($weekly_data_array, 4, 0); // Pad to ensure 4 weeks
        }
        // Labels might need to be dynamic if you show more than 4 weeks or different periods.
        // For now, keep fixed labels as per the image.
    }

    echo json_encode(['success' => true, 'labels' => $labels, 'values' => $values]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Error fetching monthly summary data: ' . $e->getMessage()]);
    error_log("Error fetching monthly summary data: " . $e->getMessage());
}
?>