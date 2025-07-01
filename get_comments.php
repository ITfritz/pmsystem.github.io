<?php
session_start();
require_once 'connect.php';

// Ensure the request method is GET
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405); // Method Not Allowed
    die(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}

// Retrieve and validate the project_id parameter
$projectId = $_GET['project_id'] ?? null;

// Log the received project_id for debugging purposes
error_log("Received project_id: " . print_r($projectId, true));

// Validate project ID
if (!$projectId || !is_numeric($projectId)) {
    http_response_code(400); // Bad Request
    die(json_encode(['success' => false, 'message' => 'Invalid request. Missing or invalid project ID.']));
}

try {
    // Prepare and execute the SQL query to fetch comments
    $stmt = $db->prepare("
    SELECT c.comment, u.name AS username, c.created_at 
    FROM comments c 
    JOIN users u ON c.user_id = u.user_id 
    WHERE c.project_id = :project_id 
    ORDER BY c.created_at DESC
    ");
    $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
    $stmt->execute();

    // Fetch all comments as an associative array
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Log the number of comments fetched for debugging purposes
    error_log("Fetched " . count($comments) . " comments for project ID: " . $projectId);

    // Return comments as JSON
    echo json_encode($comments);
} catch (PDOException $e) {
    // Log the database error for debugging
    error_log("Database query failed: " . $e->getMessage());

    // Return a 500 Internal Server Error response with an error message
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
}
try {
    // First check if tables exist
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Existing tables: " . print_r($tables, true));
    
    if (!in_array('users', $tables)) {
        throw new Exception("Users table doesn't exist");
    }
    
    // Check users table columns
    $columns = $db->query("SHOW COLUMNS FROM users")->fetchAll(PDO::FETCH_COLUMN);
    error_log("Users table columns: " . print_r($columns, true));
    
    // Rest of your query...
} catch (Exception $e) {
    error_log("Schema verification failed: " . $e->getMessage());
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Database schema issue: ' . $e->getMessage()]));
}
?>