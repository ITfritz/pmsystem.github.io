<?php
require 'connect.php'; // Ensure this connects to your database
require 'functions.php'; // Ensure this contains sanitizeInput()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    error_log(print_r($data, true)); // Debugging: Log the received data

    $appointment_date = sanitizeInput($data['appointment_date']);
    $bookings = sanitizeInput($data['bookings']); 

    try {
        $stmt = $db->prepare("
            INSERT INTO appointments (appointment_date, bookings)
            VALUES (:appointment_date, :bookings)
        ");
        $stmt->execute([
            ':appointment_date' => $appointment_date,
            ':bookings' => $bookings
        ]);

        echo json_encode(['success' => true, 'message' => 'Appointment data added successfully!']);
    } catch (PDOException $e) {
        error_log("Failed to add appointment data: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to add appointment data: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>