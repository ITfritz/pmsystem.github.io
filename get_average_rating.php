<?php
session_start();
require_once 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $projectId = $_GET['project_id'] ?? null;

    if (!$projectId) {
        http_response_code(400);
        die(json_encode(['success' => false, 'message' => 'Invalid request. Missing project ID.']));
    }

    try {
        $stmt = $db->prepare("SELECT AVG(rating) AS average_rating FROM ratings WHERE project_id = :project_id");
        $stmt->bindParam(':project_id', $projectId, PDO::PARAM_INT);
        $stmt->execute();
        $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
        $averageRating = $ratingData['average_rating'] ? round($ratingData['average_rating'], 1) : 'N/A';

        echo json_encode(['success' => true, 'average_rating' => $averageRating]);
    } catch (PDOException $e) {
        error_log("Database error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    die(json_encode(['success' => false, 'message' => 'Method not allowed.']));
}
?>