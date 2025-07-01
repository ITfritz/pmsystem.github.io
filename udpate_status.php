<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify user is logged in
if (!isLoggedIn()) {
    http_response_code(403); // Forbidden
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit();
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit();
}

// Parse incoming JSON data
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['task_id'], $data['status'])) {
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit();
}

try {
    // Validate task ID and status
    $taskId = (int)$data['task_id'];
    $newStatus = strtolower(trim($data['status']));

    // Define allowed statuses
    $allowedStatuses = ['to-do', 'in-progress', 'done'];

    if (!in_array($newStatus, $allowedStatuses)) {
        http_response_code(400); // Bad Request
        echo json_encode(['success' => false, 'error' => 'Invalid status value']);
        exit();
    }

    // Update the task status in the database
    $stmt = $db->prepare("UPDATE tasks SET status = :status WHERE task_id = :id");
    $success = $stmt->execute([':status' => $newStatus, ':id' => $taskId]);

    if ($success && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['success' => false, 'error' => 'Task not found or status unchanged']);
    }
} catch (PDOException $e) {
    http_response_code(500); // Internal Server Error
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>