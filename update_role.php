<?php
session_start();
require 'connect.php'; // Database connection
require 'functions.php'; // Helper functions

// Verify superadmin access
if (!isSuperAdmin()) {
    echo json_encode(['success' => false, 'error' => "You do not have permission to access this page."]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'] ?? null;
    $newRole = $_POST['role'] ?? null;

    if ($userId && $newRole) {
        try {
            $stmt = $db->prepare("UPDATE users SET role = :role WHERE user_id = :user_id");
            $stmt->execute(['role' => $newRole, 'user_id' => $userId]);
            echo json_encode(['success' => true]);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'error' => "An error occurred while updating the role: " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => "Invalid user ID or role."]);
    }
}
?>