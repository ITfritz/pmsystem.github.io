<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Determine if the user is an admin
$isAdmin = isAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $taskId = (int)$_GET['id'];

    try {
        // Update the task status to "Done" instead of deleting it
        $stmt = $db->prepare("UPDATE tasks SET status = 'done' WHERE task_id = ?");
        $stmt->execute([$taskId]);

        if ($stmt->rowCount() > 0) {
            $_SESSION['success'] = "Task marked as Done successfully!";
        } else {
            $_SESSION['error'] = "Failed to mark task as Done.";
        }
    } catch (PDOException $e) {
        error_log("Mark as Done failed: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while marking the task as Done.";
    }

    // Redirect back to the to-do page
    header("Location: admin_dashboard.php");
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: admin_dashboard.php");
    exit();
}
?>