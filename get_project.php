<?php
require_once 'connect.php';

$projectId = $_GET['id'] ?? null;

if ($projectId) {
    $stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($project) {
        echo json_encode($project);
        exit();
    }
}

http_response_code(404);
echo json_encode(['error' => 'Project not found']);
?>