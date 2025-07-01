<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = $_POST['project_id'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    // Validate input
    if (!$projectId || !is_numeric($projectId) || empty($comment)) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid request. Missing or invalid fields.']));
    }

    try {
        // Insert the comment into the database
        $stmt = $db->prepare("INSERT INTO comments (project_id, user_id, comment) VALUES (:project_id, :user_id, :comment)");
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':comment', $comment, PDO::PARAM_STR);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Comment added successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add comment.']);
        }
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}
?>