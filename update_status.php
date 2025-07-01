<?php
session_start();
require 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $taskId = $data['task_id'];
    $newStatus = $data['status'];

    try {
        $stmt = $db->prepare("UPDATE tasks SET status = ? WHERE task_id = ?");
        $stmt->execute([$newStatus, $taskId]);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
?>