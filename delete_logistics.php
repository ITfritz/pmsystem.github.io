<?php
session_start();

require_once 'connect.php';
require_once 'functions.php';

// Verify admin access
if (!isAdmin()) {
    http_response_code(403); // Forbidden
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

// With:
$input = json_decode(file_get_contents('php://input'), true);
$logisticsId = isset($input['logistics_id']) ? intval($input['logistics_id']) : null;

if (!$logisticsId || !is_numeric($logisticsId)) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid logistics ID.']);
    exit();
}

try {
    $stmt = $db->prepare("DELETE FROM logistics WHERE logistics_id = ?");
    $stmt->execute([$logisticsId]);
    $logistics = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Logistics entry deleted successfully.']);
    } else {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Logistics entry not found.']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500); // Internal Server Error
    echo json_encode(['error' => 'An error occurred while deleting the logistics entry.']);
}
?>