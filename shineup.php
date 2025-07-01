<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    $db = new PDO('mysql:host=localhost;dbname=your_database', 'username', 'password');

    // Sanitize inputs
    $first_name = htmlspecialchars($_POST['first_name']);
    $last_name = htmlspecialchars($_POST['last_name']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $phone = htmlspecialchars($_POST['phone']);
    $token = bin2hex(random_bytes(16)); // Generate a secure token

    // Check if email already exists
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetchColumn() > 0) {
        die("Email already exists.");
    }

    // Insert user into the database
    $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, phone, email_token) VALUES (?, ?, ?, ?, ?, ?)");
    if ($stmt->execute([$first_name, $last_name, $email, $password, $phone, $token])) {
        // Send confirmation email
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'your_email@gmail.com';
            $mail->Password   = 'your_password'; // Use an App Password if 2FA is enabled
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;

            $mail->setFrom('your_email@gmail.com', 'Your Name');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Email Confirmation';
            $mail->Body    = "Please click the link to verify your email: <a href='http://localhost/verify.php?token=$token'>Verify Email</a>";

            $mail->send();
            echo "Confirmation email sent. Please check your inbox.";
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    } else {
        echo "Signup failed. Please try again.";
    }
}
?>
