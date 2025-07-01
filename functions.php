<?php
// functions.php

// Function to check if the user is an admin
function isAdmin() {
    $isAdmin = isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    error_log("isAdmin(): Session role is '" . ($_SESSION['role'] ?? 'Not Set') . "', isAdmin = " . ($isAdmin ? 'true' : 'false'));
    return $isAdmin;
}

// Function to check if the user is a superadmin
function isSuperAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'superadmin';
}

// Function to check if the user is logged in
function isLoggedIn() {
    $isLoggedIn = isset($_SESSION['user_id']);
    error_log("isLoggedIn(): Session user_id is " . ($isLoggedIn ? 'set' : 'not set') . ", isLoggedIn = " . ($isLoggedIn ? 'true' : 'false'));
    return $isLoggedIn;
}

// Function to sanitize user input
function sanitizeInput($data) {
    $sanitizedData = htmlspecialchars(stripslashes(trim($data)));
    error_log("sanitizeInput(): Input data sanitized. Original: '$data', Sanitized: '$sanitizedData'");
    return $sanitizedData;
}

// Function to validate email format
function validateEmail($email) {
    $isValid = filter_var($email, FILTER_VALIDATE_EMAIL);
    error_log("validateEmail(): Email validation result for '$email' = " . ($isValid ? 'true' : 'false'));
    return $isValid;
}

// Function to hash passwords securely
function hashPassword($password) {
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    error_log("hashPassword(): Password hashed successfully.");
    return $hashedPassword;
}

// Function to log security-related events
function logSecurityEvent($event, $message) {
    error_log("[$event] $message");
}

// CSRF Token Functions
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        error_log("generateCsrfToken(): CSRF token generated: " . $_SESSION['csrf_token']);
    } else {
        error_log("generateCsrfToken(): CSRF token already exists: " . $_SESSION['csrf_token']);
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    $sessionToken = $_SESSION['csrf_token'] ?? 'Not Set';
    error_log("validateCsrfToken(): Session Token: '$sessionToken', Submitted Token: '$token'");
    $isValid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    error_log("validateCsrfToken(): Token validation result = " . ($isValid ? 'true' : 'false'));
    return $isValid;
}

// Function to truncate names for display purposes
function truncateName($name, $maxLength = 20) {
    $truncatedName = strlen($name) > $maxLength 
        ? htmlspecialchars(substr($name, 0, $maxLength) . '...') 
        : htmlspecialchars($name);
    error_log("truncateName(): Name truncated. Original: '$name', Truncated: '$truncatedName'");
    return $truncatedName;
}

// Function to redirect users based on their role
function redirectToRolePage() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
    if (isSuperAdmin()) {
        header("Location: super_admin_dashboard.php");
        exit();
    } elseif (isAdmin()) {
        header("Location: admin_dashboard.php");
        exit();
    } else {
        header("Location: userdashboard.php");
        exit();
    }
}

// Function to verify user credentials during login
function verifyUserCredentials($email, $password) {
    global $db; // Assuming $db is your database connection object

    try {
        $stmt = $db->prepare("SELECT user_id, email, password, role FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            return true;
        } else {
            error_log("verifyUserCredentials(): Invalid email or password for email: $email");
            return false;
        }
    } catch (PDOException $e) {
        error_log("verifyUserCredentials(): Database query failed: " . $e->getMessage());
        return false;
    }
}

// Function to fetch user details by ID
function getUserById($userId) {
    global $db;

    try {
        $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user;
    } catch (PDOException $e) {
        error_log("getUserById(): Database query failed: " . $e->getMessage());
        return null;
    }
}

// Function to update user role
function updateUserRole($userId, $newRole) {
    global $db;

    try {
        $stmt = $db->prepare("UPDATE users SET role = ? WHERE user_id = ?");
        $stmt->execute([$newRole, $userId]);
        return $stmt->rowCount() > 0; // Return true if the update was successful
    } catch (PDOException $e) {
        error_log("updateUserRole(): Database update failed: " . $e->getMessage());
        return false;
    }
}

// Function to get approved clients count for the current month
function getApprovedClientsCount($db) {
    $currentMonth = date('m');
    $currentYear = date('Y');
    $firstDayOfMonth = date('Y-m-01', strtotime("$currentYear-$currentMonth-01"));
    $lastDayOfMonth = date('Y-m-t', strtotime("$currentYear-$currentMonth-01"));

    $query = "SELECT COUNT(*) FROM client_approvals 
              WHERE approval_date >= ? AND approval_date <= ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$firstDayOfMonth, $lastDayOfMonth]);
    $count = $stmt->fetchColumn();
    error_log("getApprovedClientsCount(): Count for current month: $count");
    return $count;
}

// Function to approve client
function approveClient($db, $client_id) {
    $currentCount = getApprovedClientsCount($db);
    if ($currentCount >= 3) {
        error_log("approveClient(): Approval limit reached for this month.");
        return "Approval limit reached for this month.";
    }

    $approvalDate = date('Y-m-d');
    $query = "INSERT INTO client_approvals (client_id, approval_date, status) 
              VALUES (?, ?, 'approved')";
    $stmt = $db->prepare($query);
    $stmt->execute([$client_id, $approvalDate]);

    error_log("approveClient(): Client approved successfully. Client ID: $client_id");
    return "Client approved successfully.";
}

?>
