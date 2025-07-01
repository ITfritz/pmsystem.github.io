<?php
session_start();

require_once 'connect.php';
require_once 'functions.php';

// Enable error reporting (for debugging; disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Only handle POST requests
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !validateCsrfToken($_POST['csrf_token'])) {
        $_SESSION['error'] = "Invalid CSRF token.";
        header("Location: login.php");
        exit();
    }

    // Sanitize and validate email
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password']; // Do not sanitize passwords

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
        header("Location: login.php");
        exit();
    }

    try {
        // Get database connection
        $db = DatabaseConnection::getInstance()-> getConnection();

        // Fetch user by email
        $stmt = $db->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check credentials
        if ($user && password_verify($password, $user['password'])) {
            // Secure session
            session_regenerate_id(true);

            // Store user data in session
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin_dashboard.php");
            } else {
                header("Location: project.php");
            }
            exit();
        } else {
            $_SESSION['error'] = "Invalid username or password.";
            header("Location: login.php");
            exit();
        }
    } catch (PDOException $e) {
        // Log and show generic error
        error_log("Database error: " . $e->getMessage());
        $_SESSION['error'] = "An internal error occurred. Please try again later.";
        header("Location: login.php");
        exit();
    }
}
