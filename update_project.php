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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = sanitizeInput($_POST['project_id']);
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $currentImagePath = $_POST['current_image'] ?? '';

    // Handle image upload
    $imagePath = $currentImagePath;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName = basename($_FILES['image']['name']);
        $imagePath = $uploadDir . uniqid() . '_' . $fileName;

        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }

    try {
        $stmt = $db->prepare("
            UPDATE projects
            SET title = ?, description = ?, image_path = ?
            WHERE project_id = ?
        ");
        $stmt->execute([$title, $description, $imagePath, $projectId]);

        echo json_encode(['success' => 'Project updated successfully.']);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['error' => 'An error occurred while updating the project.']);
    }
}
?>