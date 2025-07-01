<?php
header('Content-Type: application/json'); // Add this at the top

require 'connect.php';
require 'functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get raw POST data and decode it
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Debug: Log received data
    error_log("Received data: " . print_r($data, true));
    
    if (!isset($data['tool_name']) || !isset($data['usage_count'])) {
        error_log("Missing required fields");
        echo json_encode([
            'success' => false,
            'message' => 'Missing required fields'
        ]);
        exit();
    }

    $toolName = sanitizeInput($data['tool_name']);
    $usageCount = (int)$data['usage_count'];

    if (empty($toolName) || $usageCount < 0) {
        error_log("Validation failed");
        echo json_encode([
            'success' => false,
            'message' => 'Invalid input data. Please check the form and try again.'
        ]);
        exit();
    }

    try {
        error_log("Attempting to insert: $toolName, $usageCount");
        $stmt = $db->prepare("INSERT INTO tools (tool_name, usage_count) VALUES (:tool_name, :usage_count)");
        $stmt->bindParam(':tool_name', $toolName, PDO::PARAM_STR);
        $stmt->bindParam(':usage_count', $usageCount, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            error_log("Insert successful");
            echo json_encode([
                'success' => true,
                'message' => 'Equipment data added successfully!'
            ]);
        } else {
            error_log("Execute failed: " . print_r($stmt->errorInfo(), true));
            echo json_encode([
                'success' => false,
                'message' => 'Execute failed: ' . print_r($stmt->errorInfo(), true)
            ]);
        }
    } catch (PDOException $e) {
        error_log("PDO Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Database error: ' . $e->getMessage()
        ]);
    }
} else {
    error_log("Invalid method: " . $_SERVER['REQUEST_METHOD']);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
?>