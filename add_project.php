<?php
session_start();
require_once 'connect.php';

if (!isset($_SESSION['user_id'])) {
    die(json_encode(['success' => false, 'message' => 'Not authorized']));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die(json_encode(['success' => false, 'message' => 'Invalid request']));
}

// Validate inputs
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$image = $_FILES['image'] ?? null;

if (empty($title) || empty($description) || !$image || $image['error'] !== UPLOAD_ERR_OK) {
    die(json_encode(['success' => false, 'message' => 'All fields are required']));
}

// Validate image
$allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
if (!in_array($image['type'], $allowedTypes)) {
    die(json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF images are allowed']));
}

// Upload image
$uploadDir = 'uploads/projects/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$fileName = uniqid() . '_' . basename($image['name']);
$targetPath = $uploadDir . $fileName;

if (!move_uploaded_file($image['tmp_name'], $targetPath)) {
    die(json_encode(['success' => false, 'message' => 'Failed to upload image']));
}

// Insert into database - modified to match your actual columns
try {
    $stmt = $db->prepare("INSERT INTO projects (title, description, image_path) VALUES (?, ?, ?)");
    $stmt->execute([$title, $description, $targetPath]);
    
    echo json_encode(['success' => true, 'message' => 'Project added successfully']);
} catch (PDOException $e) {
    // Delete the uploaded file if database insert fails
    if (file_exists($targetPath)) {
        unlink($targetPath);
    }
    die(json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]));
}