// file_management.php

<?php
session_start();
require 'connect.php';
require 'functions.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

try {
    $userId = $_SESSION['user_id'];
    $stmt = $db->prepare("
        SELECT upload_id, doc_type, file_path, uploaded_at
        FROM uploads
        WHERE user_id = ?
        ORDER BY uploaded_at DESC
    ");
    $stmt->execute([$userId]);
    $uploads = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching uploads.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Management</title>
</head>
<body>
    <h1>Uploaded Files</h1>
    <table border="1">
        <thead>
            <tr>
                <th>Document Type</th>
                <th>File Name</th>
                <th>Uploaded At</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($uploads as $upload): ?>
                <tr>
                    <td><?= htmlspecialchars($upload['doc_type']) ?></td>
                    <td><?= htmlspecialchars(basename($upload['file_path'])) ?></td>
                    <td><?= htmlspecialchars($upload['uploaded_at']) ?></td>
                    <td>
                        <a href="<?= htmlspecialchars($upload['file_path']) ?>" download>Download</a>
                        <!-- Add delete functionality if needed -->
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>