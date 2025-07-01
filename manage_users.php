<?php
session_start(); // Start the session
require 'connect.php'; // Database connection
require 'functions.php'; // Helper functions

// Verify superadmin access
if (!isSuperAdmin()) {
    $_SESSION['error'] = "You do not have permission to access this page.";
    header("Location: login.php");
    exit();
}

// Handle search for users
$userSearch = $_GET['user_search'] ?? '';
$userWhereClause = $userSearch ? "WHERE email LIKE :user_search" : "";
$userParams = $userSearch ? ['user_search' => "%$userSearch%"] : [];

// Fetch users from the database with additional fields
try {
    $stmt = $db->prepare("
        SELECT user_id, email, role, created_at, first_name, last_name, age, sex 
        FROM users 
        $userWhereClause 
        ORDER BY created_at DESC
    ");
    $stmt->execute($userParams);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($users)) {
        error_log("No users found.");
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching users.");
}


// Handle search for tasks
$taskSearch = $_GET['task_search'] ?? '';
$taskWhereClause = $taskSearch ? "WHERE title LIKE :task_search OR description LIKE :task_search" : "";
$taskParams = $taskSearch ? ['task_search' => "%$taskSearch%"] : [];

// Fetch tasks from the database
try {
    $query = "
        SELECT task_id, title, description, status, created_at 
        FROM tasks 
        $taskWhereClause 
        ORDER BY created_at DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute($taskParams);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tasks)) {
        error_log("No tasks found.");
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching tasks.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="manage_user.css"> <!-- Corrected the CSS file link -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Include the Sidebar -->
    <?php include 'super_admin_sidebar.php'; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= htmlspecialchars($_SESSION['success']) ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= htmlspecialchars($_SESSION['error']) ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

<!-- Main Header -->
<header class="main-header">
    <div class="header-content">
        <div>
            <h1>Manage Users</h1> <!-- Corrected the HTML tag -->
            <p>JT Kitchen Equipment Installation Services | Super Admin Dashboard</p> <!-- Corrected the HTML tag -->
        </div>
        <div class="header-right">
            <span id="currentDateTime"></span> <!-- Corrected the ID -->
            <div class="profile-menu">
                <div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <i class="fas fa-user-circle" onclick="toggleMenu()" style="font-size: 44px;"></i> <!-- Corrected the font size -->
                    </div>
                </div>
                <div class="dropdown" id="dropdownMenu"> <!-- Corrected the ID -->
                    <div class="sidebar-bottom">
                        <ul class="profile-dropdown">
                            <li onclick="window.location.href='super_admin_settings.php'" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
                                <i class="fas fa-user-edit"></i>
                                <span style="font-size: 12px; margin-top: 6px;">Edit</span> <!-- Corrected the HTML tag -->
                            </li>
                            <li onclick="window.location.href='logout.php'" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
                                <i class="fas fa-sign-out-alt"></i>
                                <span style="font-size: 12px; margin-top: 6px;">Log Out</span> <!-- Corrected the HTML tag -->
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
const username = <?php echo json_encode($_SESSION['first_name'] ?? 'Guest'); ?>;
document.addEventListener('DOMContentLoaded', function() {
    const welcomeMessage = `Welcome, ${username}!`;
    document.getElementById('welcome').textContent = welcomeMessage;
});

// Get the current date and time
function updateDateTime() {
    const now = new Date();
    
    const options = {
        weekday: 'long',  // Full weekday name (e.g., "Monday")
        year: 'numeric',
        month: 'long',    // Full month name (e.g., "January")
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };

    // Format the current date and time
    const formattedDateTime = now.toLocaleDateString('en-US', options);

    // Display the formatted date and time in the element with ID 'currentDateTime'
    document.getElementById('currentDateTime').textContent = formattedDateTime;
}

// Call the function to update the date and time
updateDateTime();

// Optionally, set an interval to update the time every minute
setInterval(updateDateTime, 1000);

// Toggle dropdown menu
function toggleMenu() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

// Hide dropdown if clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdownMenu');
    const icon = document.querySelector('.profile-icon');
    if (!dropdown.contains(event.target) && !icon.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});
</script>

<!-- Main Content -->
<div class="main-content">
    <!-- User Management Section -->
    <div class="section">
        <h2>User Management</h2>

        <!-- Search Form for Users -->
        <form method="GET" class="search-form">
            <input type="text" name="user_search" placeholder="Search by email..." value="<?= htmlspecialchars($userSearch) ?>">
            <button type="submit">Search</button>
        </form>

        <!-- User Table -->
        <table class="admin-table">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($users)): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?= htmlspecialchars($user['user_id']) ?></td>
                            <td><?= htmlspecialchars($user['email']) ?></td>
                            <td>
                                <select class="editable-role" data-user-id="<?= $user['user_id'] ?>" data-current-role="<?= htmlspecialchars($user['role']) ?>">
                                    <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                    <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                    <option value="superadmin" <?= $user['role'] === 'superadmin' ? 'selected' : '' ?>>Super Admin</option>
                                </select>
                            </td>
                            <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                            <td>
                                <a href="edit_user.php?id=<?= $user['user_id'] ?>" class="status-tag admin">Edit</a>
                                <a href="delete_user.php?id=<?= $user['user_id'] ?>" class="status-tag done" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const roleDropdowns = document.querySelectorAll('.editable-role');
    roleDropdowns.forEach(dropdown => {
        dropdown.addEventListener('change', function () {
            const userId = this.dataset.userId;
            const newRole = this.value;

            // Send AJAX request to update the role
            fetch('update_role.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' }, // Corrected the typo
                body: JSON.stringify({ user_id: userId, role: newRole })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Role updated successfully!');
                } else {
                    throw new Error(data.error || 'Failed to update role.');
                }
            })
            .catch(error => {
                console.error('Error updating role:', error);
                alert('An error occurred while updating the role.');
                this.value = this.dataset.currentRole;
            });
        });
    });
});
</script>
</body>
</html>