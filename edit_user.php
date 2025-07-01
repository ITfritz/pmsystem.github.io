<?php
session_start();
require 'connect.php'; // Database connection
require 'functions.php'; // Helper functions

// Verify superadmin access
if (!isSuperAdmin()) {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: login.php");
    exit();
}

// Check if the user ID was passed and is numeric
$userId = $_GET['id'] ?? '';
if (!ctype_digit($userId)) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: manage_users.php");
    exit();
}

// Fetch user details from the database
try {
    $stmt = $db->prepare("SELECT * FROM users WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: manage_users.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching user details.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : $user['password'];
    $age = isset($_POST['age']) ? (int) $_POST['age'] : $user['age'];
    $sex = htmlspecialchars(trim($_POST['sex'] ?? ''));

    // Update user details in the database
    try {
        $stmt = $db->prepare("UPDATE users SET first_name = :first_name, last_name = :last_name, email = :email, password = :password, age = :age, sex = :sex WHERE user_id = :user_id");
        $stmt->execute([
            'first_name' => $first_name,
            'last_name' => $last_name,
            'email' => $email,
            'password' => $password,
            'age' => $age,
            'sex' => $sex,
            'user_id' => $userId
        ]);
        $_SESSION['success'] = "User details updated successfully.";
        header("Location: manage_users.php");
        exit();
    } catch (PDOException $e) {
        error_log("Database query failed: " . $e->getMessage());
        $_SESSION['error'] = "An error occurred while updating user details.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <?php include 'super_admin_sidebar.php'; ?>

    <div class="main-content">
        <h2>Edit User</h2>
        <?php if (isset($_SESSION['success'])): ?>
            <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <form method="POST" action="">
            <label for="first_name">First Name:</label>
            <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($user['first_name']) ?>" required>

            <label for="last_name">Last Name:</label>
            <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($user['last_name']) ?>" required>

            <label for="email">Email:</label>
            <input type="email" id="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" placeholder="Leave blank to keep current">

            <label for="age">Age:</label>
            <input type="number" id="age" name="age" value="<?= htmlspecialchars($user['age']) ?>" required>

            <label for="sex">Sex:</label>
            <select id="sex" name="sex">
                <option value="" disabled selected>Choose Sex</option>
                <option value="male" <?= $user['sex'] === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $user['sex'] === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $user['sex'] === 'other' ? 'selected' : '' ?>>Other</option>
            </select>

            <button type="submit">Update User</button>
        </form>
    </div>
</body>
</html>