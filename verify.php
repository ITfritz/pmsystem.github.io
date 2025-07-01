<?php
require_once 'connect.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Email Verification</title>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; text-align: center; background-color: #f4f4f4; }
        .message { background: white; padding: 30px; border-radius: 10px; display: inline-block; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #333; }
    </style>
</head>
<body>
<div class='message'>";

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    $db = DatabaseConnection::getInstance()->getConnection();

    // Search for user with this token
    $stmt = $db->prepare("SELECT * FROM users WHERE email_token = ?");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Optional: check if already verified
        if ($user['is_verified']) {
            echo "<h2>Your email is already verified.</h2>";
        } else {
            // Update to verified and remove token
           $stmt = $db->prepare("UPDATE users SET is_verified = 1, email_token = '' WHERE id = ?");
            $stmt->execute([$user['id']]);

            echo "<h2>Your email has been verified successfully!</h2>";
        }
    } else {
        echo "<h2>Invalid or expired token.</h2>";
    }
} else {
    echo "<h2>No token provided.</h2>";
}

echo "</div></body></html>";
?>
