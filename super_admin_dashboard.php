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

// Handle block/unblock user
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'block_user') {
    $userId = $_POST['user_id'];
    $blockStatus = $_POST['block_status'] == 'block' ? 1 : 0;

    try {
        $stmt = $db->prepare("UPDATE users SET is_blocked = :block_status WHERE user_id = :user_id");
        $stmt->execute(['block_status' => $blockStatus, 'user_id' => $userId]);
        $_SESSION['success'] = "User status updated successfully.";
    } catch (PDOException $e) {
        error_log("Failed to update user status: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update user status.";
    }
    header("Location: super_admin_dashboard.php");
    exit();
}

// Handle change user password
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'change_password') {
    $userId = $_POST['user_id'];
    $newPassword = password_hash($_POST['new_password'], PASSWORD_DEFAULT);

    try {
        $stmt = $db->prepare("UPDATE users SET password = :new_password WHERE user_id = :user_id");
        $stmt->execute(['new_password' => $newPassword, 'user_id' => $userId]);
        $_SESSION['success'] = "User password updated successfully.";
    } catch (PDOException $e) {
        error_log("Failed to update user password: " . $e->getMessage());
        $_SESSION['error'] = "Failed to update user password.";
    }
    header("Location: super_admin_dashboard.php");
    exit();
}

// Handle search for users
$userSearch = $_GET['user_search'] ?? '';
$userWhereClause = $userSearch ? "WHERE email LIKE :user_search" : "";
$userParams = $userSearch ? ['user_search' => "%$userSearch%"] : [];

// Fetch users from the database
try {
    $stmt = $db->prepare("
        SELECT user_id, email, role, created_at, is_blocked 
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
    die("An error occurred while fetching users: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link rel="stylesheet" href="superadmin.css">
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
                <h1>Welcome, Super Admin!</h1>
                <p>JT Kitchen Equipment Installation Services | Super Admin Dashboard</p>
            </div>
            <div class="header-right">
                <span id="currentDateTime"></span>
                <div class="profile-menu">
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <i class="fas fa-user-circle" onclick="toggleMenu()" style="font-size: 44px;"></i>
                    </div>
                </div>
            </div>
        </div>
    </header>

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
                        <th>Status</th>
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
                                <td><?= htmlspecialchars($user['role']) ?></td>
                                <td><?= $user['is_blocked'] ? 'Blocked' : 'Active' ?></td>
                                <td><?= date('M d, Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <input type="hidden" name="action" value="block_user">
                                        <input type="hidden" name="block_status" value="<?= $user['is_blocked'] ? 'unblock' : 'block' ?>">
                                        <button type="submit" class="status-tag <?= $user['is_blocked'] ? 'done' : 'admin' ?>"><?= $user['is_blocked'] ? 'Unblock' : 'Block' ?></button>
                                    </form>
                                    <button class="status-tag admin" onclick="showChangePasswordModal(<?= $user['user_id'] ?>, '<?= addslashes($user['email']) ?>')">Change Password</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center;">No users found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeChangePasswordModal()">&times;</span>
            <h2>Change Password</h2>
            <form method="POST" id="changePasswordForm">
                <input type="hidden" name="user_id" id="changePasswordUserId">
                <input type="hidden" name="action" value="change_password">
                <label for="new_password">New Password:</label>
                <input type="password" id="new_password" name="new_password" required>
                <button type="submit" class="status-tag add">Change Password</button>
            </form>
        </div>
    </div>

    <script>
        function showChangePasswordModal(userId, userEmail) {
            document.getElementById('changePasswordUserId').value = userId;
            document.getElementById('new_password').value = ''; // Clear previous password
            document.getElementById('changePasswordModal').style.display = 'block';
        }

        function closeChangePasswordModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }

        document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
            event.preventDefault();
            this.submit();
        });
    </script>
</body>
</html>

<style>

    /* Main Content Styles */
.main-content {
    padding: 20px;
    font-family: Arial, sans-serif;
    background-color: #f4f7fc;
}

.section {
    margin-bottom: 30px;
}

/* Title */
h2 {
    font-size: 24px;
    color: #333;
    margin-bottom: 15px;
}

/* Search Form Styles */
.search-form {
    display: flex;
    justify-content: flex-start;
    margin-bottom: 20px;
}

.search-form input {
    padding: 8px 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    margin-right: 10px;
    width: 250px;
}

.search-form button {
    padding: 8px 15px;
    background-color:#8a0000;
    color: white;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.search-form button:hover {
    background-color: #0056b3;
}

/* Table Styles */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: white;
    border-radius: 5px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.admin-table th,
.admin-table td {
    padding: 12px 15px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.admin-table th {
    background-color:#8a0000;
    color: white;
    font-size: 16px;
}

.admin-table td {
    font-size: 14px;
}

/* Row Hover Effect */
.admin-table tr:hover {
    background-color: #f1f1f1;
}

/* Role Dropdown Styles */
.editable-role {
    padding: 5px 10px;
    font-size: 14px;
    border-radius: 5px;
    border: 1px solid #ccc;
    background-color: #fff;
    cursor: pointer;
}

/* Action Buttons */
.status-tag {
    display: inline-block;
    padding: 6px 12px;
    text-align: center;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    text-decoration: none;
    transition: background-color 0.3s ease;
}

.status-tag.admin {
    background-color:green;
    color: white;
}

.status-tag.admin:hover {
    background-color: #218838;
}

.status-tag.done {
    background-color: #dc3545;
    color: white;
}

.status-tag.done:hover {
    background-color: #c82333;
}

/* No users found message */
.admin-table td[colspan="5"] {
    font-size: 16px;
    color: #999;
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .admin-table th, .admin-table td {
        padding: 10px;
    }

    .search-form {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-form input {
        width: 100%;
        margin-bottom: 10px;
    }

    .search-form button {
        width: 100%;
    }

    .status-tag {
        font-size: 12px;
        padding: 5px 10px;
    }

    .admin-table {
        font-size: 12px;
    }
}

    
/* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: sans-serif;
}

/* Body Styling */
body {
    background: white;
    min-height: 100vh;
    display: flex;
    flex-direction: column; /* Better for mobile stacking */
    gap: 25px;
    color: #333; /* Consistent text color */
}

.main-header h1 {
    margin: 0;
    font-size: 2.5rem;
}

.main-header p {
    margin-top: 10px;
    font-size: 1.1rem;
}

/* Header styling */
.header {
    background: linear-gradient(135deg, #8a0000, #660000);
    padding: 25px;
    text-align: center;
    color: #8a0000;
    border-radius: 12px;
    box-shadow: 0 6px 14px rgba(0, 0, 0, 0.3);
    font-size: 30px;
    font-weight: 700;
    letter-spacing: 1px;
}

/* Main Content */
.main-content {
    flex: 1;
    margin-left: 80px; /* Adjust for the sidebar width */
    border-radius: 18px;
    padding: 30px;
    color: black;
    max-width: 1430px;
    min-width: 320px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* added shadow */
}

.main-content1 h1 {
    color: #8a0000;
    font-weight: bold;
}


/* Main Content */
.main-content1 {
    flex: 1;
    margin-left: 80px; /* Adjust for the sidebar width */
    border-radius: 18px;
    padding: 30px;
    color: black;
    max-width: 1430px;
    min-width: 320px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* added shadow */
    background-color: whitesmoke;
}

/* Main Content */
.main-content3 {
    flex: 1;
    margin-left: 80px; /* Adjust for the sidebar width */
    border-radius: 18px;
    padding: 30px;
    color: black;
    max-width: 1430px;
    min-width: 320px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* added shadow */
    background-color: whitesmoke;
 
}

.main-content3 h1 {
    color: #8a0000;
    font-weight: bold;
}

/* Main Content */
.main-content4 {
    flex: 1;
    margin-left: 80px; /* Adjust for the sidebar width */
    border-radius: 18px;
    padding: 30px;
    color: black;
    max-width: 1430px;
    min-width: 320px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* added shadow */
    background-color: whitesmoke;
}

.main-content4 h1 {
    color: #8a0000;
    font-weight: bold;
}

/* Messages */
.success-message,
.error-message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
    font-weight: 500;
}
.success-message {
    background: #4CAF50;
    color: #fff;
}
.error-message {
    background: #F44336;
    color: #fff;
}

/* Search Form */
.search-form {
    display: flex;
    gap: 10px;
    margin: 20px 0;
}
.search-form input {
    flex: 1;
    padding: 12px;
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 8px;
    color: #333;
}
.search-form button {
    background: #8a0000;
    color: #fff;
    padding: 12px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
}
.search-form button:hover {
    background: #D4AF37;
}



/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(0,0,0,0.7);
    z-index: 1000;
}
.modal-content {
    background: #fff;
    margin: 5% auto;
    padding: 25px;
    width: 800px;
    max-width: 95%;
    border-radius: 12px;
    position: relative;
    max-height: 80vh;
    overflow-y: auto;
    overflow-x: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
}
.modal-content .close {
    cursor: pointer;
    transition: transform 0.5s ease, color 0.3s ease;  /* Smooth transition for transform and color */
}

.modal-content .close:hover {
    color: #D32F2F;  /* Change color */
    transform: rotate(180deg);  /* Rotate by 180 degrees */
}



.modal-content h2 {
    margin-bottom: 20px;
    color: #8a0000;
}
.modal-content form label {
    display: block;
    margin: 10px 0 5px;
    font-weight: 600;
}
.modal-content form textarea {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    resize: vertical; /* or 'none' if you want no resizing */
}

.modal-content form input,
.modal-content form select {
    width: 100%;
    padding: 12px;
    margin-bottom: 12px;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.modal-content form button {
    background: #8a0000;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.modal-content form button:hover {
    background: #660000;
}

/* Submit Button */
.status-tag.add {
    background: #28a745;
    color: #fff;
    padding: 12px 24px;
    border: none;
    border-radius: 25px;
    cursor: pointer;
    transition: background 0.3s ease, transform 0.2s ease;
    margin-top: 10px;
}
.status-tag.add:hover {
    background: #218838;
    transform: translateY(-2px);
}

/* Status Done */
.status-tag.done {
    background: #8a0000;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 14px;
    transition: background 0.3s ease;
}
.status-tag.done:hover {
    background: #660000;
}

/* Responsive */
@media (max-width: 1024px) {
    body {
        flex-direction: column;
        align-items: center;
        padding: 15px;
    }
    .sidebar {
        width: 100%;
        max-width: 300px;
        margin-bottom: 20px;
    }
}
@media (max-width: 768px) {
    .admin-table th,
    .admin-table td {
        padding: 12px;
        font-size: 13px;
    }
}
@media (max-width: 480px) {
    .admin-table th,
    .admin-table td {
        padding: 10px;
        font-size: 12px;
    }
    .modal-content {
        width: 95%;
    }
}
</style>