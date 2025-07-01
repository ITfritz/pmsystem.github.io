<?php
require 'db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    $userId = $_SESSION['user_id'];

    // Optional: ensure the notification belongs to the user
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $userId]);
}

header("Location: dashboard.php"); // redirect back to your dashboard
exit;
