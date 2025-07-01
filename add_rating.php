<?php
session_start();
require_once 'connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'You must be logged in to rate projects']);
    exit;
}

try {
    // Validate inputs
    $project_id = filter_input(INPUT_POST, 'project_id', FILTER_VALIDATE_INT);
    $rating = filter_input(INPUT_POST, 'rating', FILTER_VALIDATE_INT, [
        'options' => ['min_range' => 1, 'max_range' => 5]
    ]);
    
    if (!$project_id || !$rating) {
        throw new Exception('Invalid rating data provided');
    }

    // Check if project exists
    $stmt = $db->prepare("SELECT 1 FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    if (!$stmt->fetch()) {
        throw new Exception('Project does not exist');
    }

    // Insert or update rating
    $stmt = $db->prepare("INSERT INTO ratings (project_id, user_id, rating) 
                         VALUES (:project_id, :user_id, :rating)
                         ON DUPLICATE KEY UPDATE rating = :rating, updated_at = CURRENT_TIMESTAMP");
    
    $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':rating', $rating, PDO::PARAM_INT);
    $stmt->execute();

    // Get updated rating stats
    $stmt = $db->prepare("SELECT 
                          AVG(rating) AS average_rating, 
                          COUNT(*) AS total_ratings 
                          FROM ratings WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $ratingData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'average_rating' => round($ratingData['average_rating'], 1),
        'total_ratings' => $ratingData['total_ratings']
    ]);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>