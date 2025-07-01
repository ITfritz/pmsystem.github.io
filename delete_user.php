<?php
session_start();
require 'connect.php';
require 'functions.php';

if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$userId = $_GET['id'] ?? null;

if (!$userId) {
    header("Location: admin_user.php");
    exit();
}

// Delete user
$stmt = $db->prepare("DELETE FROM users WHERE user_id = ?");
$stmt->execute([$userId]);

header("Location: admin_users.php");
exit();
?>