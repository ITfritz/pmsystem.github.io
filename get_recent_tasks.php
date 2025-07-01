<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Ensure user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['role'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'User not logged in or role not set.']);
    exit();
}

$clientEmail = $_SESSION['email'];
$role = $_SESSION['role']; // expected values: 'user', 'admin', 'superadmin'

try {
    if ($role === 'user') {
        // Regular user: fetch only their own tasks
        $stmt = $db->prepare("SELECT task_id, title, created_at
                              FROM tasks 
                              WHERE client_email = ?
                              ORDER BY created_at DESC 
                              LIMIT 4");
        $stmt->execute([$clientEmail]);

    } elseif ($role === 'admin' || $role === 'superadmin') {
        // Admin and Superadmin: fetch all tasks
        $stmt = $db->prepare("SELECT task_id, title, created_at
                              FROM tasks 
                              ORDER BY created_at DESC 
                              LIMIT 4");
        $stmt->execute();

    } else {
        // Unknown role
        http_response_code(403); // Forbidden
        echo json_encode(['error' => 'Invalid user role.']);
        exit();
    }

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['tasks' => $tasks]);

} catch (PDOException $e) {
    error_log("DB Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch tasks.']);
}
?>
