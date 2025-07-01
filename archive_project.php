<?php
ob_start(); // Prevents accidental whitespace output
header('Content-Type: application/json');

session_start();
require_once 'connect.php';
require_once 'functions.php';

if (!isAdmin()) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied.']));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $projectId = $_POST['project_id'] ?? null;

    if (!$projectId) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid request. Missing project ID.']));
    }

    try {
        $stmt = $db->prepare("SELECT is_archived FROM projects WHERE project_id = :project_id");
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $project = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$project) {
            http_response_code(404);
            die(json_encode(['success' => false, 'message' => 'Project not found.']));
        }

        $newStatus = $project['is_archived'] ? 0 : 1;
        $updateStmt = $db->prepare("UPDATE projects SET is_archived = :is_archived WHERE project_id = :project_id");
        $updateStmt->bindParam(':is_archived', $newStatus, PDO::PARAM_INT);
        $updateStmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $updateStmt->execute();

        echo json_encode([
            'success' => true,
            'message' => $newStatus ? 'Project archived successfully.' : 'Project posted again successfully.',
            'is_archived' => $newStatus
        ]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}
