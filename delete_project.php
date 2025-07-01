<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

// Verify admin access
if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit();
}

$projectId = $_POST['project_id'] ?? null;

if (!$projectId || !is_numeric($projectId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid project ID.']);
    exit();
}

try {
    $stmt = $db->prepare("SELECT image_path FROM projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found.']);
        exit();
    }

    $imagePath = $project['image_path'];
    if ($imagePath && file_exists($imagePath)) {
        unlink($imagePath); // Delete the image file
    }

    $stmt = $db->prepare("DELETE FROM projects WHERE project_id = ?");
    $stmt->execute([$projectId]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => 'Project deleted successfully.']);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to delete the project.']);
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while deleting the project.']);
}
?>