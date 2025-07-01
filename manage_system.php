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

// Fetch users from the database
try {
    $stmt = $db->prepare("
        SELECT user_id, email, role, created_at 
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
            <h1> Manage System </h1>
            <p>JT Kitchen Equipment Installation Services | Super Admin Dashboard</p>
        </div>
        <div class="header-right">
            <span id="currentDateTime"></span>
            <div class="profile-menu">

            <div>
            <div style="display: flex; flex-direction: column; align-items: center;">
    <i class="fas fa-user-circle" onclick="toggleMenu()" style="font-size: 44px;"></i>
</div>




</style>


<style>
    /* Profile Icon Styling */
.profile-menu i, .profile-menu img {
    font-size: 40px; /* For Font Awesome icon */
    width: 50px; /* For image */
    height: 30px; /* For image */
    color:whitesmoke;
    cursor: pointer; /* Make the icon or image clickable */
    margin-top: 10px;
}

.profile-menu i:hover, .profile-menu img:hover {
    transform: scale(1.1); /* Slightly enlarge the icon/image on hover */
   color:gray;
}

</style>
                <div class="dropdown" id="dropdownMenu">
                <div class="sidebar-bottom">
                <ul class="profile-dropdown">
                <li onclick="window.location.href='usersettings.php'" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
    <i class="fas fa-user-edit"></i>
    <span style="font-size: 12px; margin-top: 6px;">Edit</span>
</li>
<li onclick="window.location.href='logout.php'" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
    <i class="fas fa-sign-out-alt"></i>
    <span style="font-size: 12px; margin-top: 6px;">Log Out</span>
</li>



</ul>

    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<style>
.main-header {
    background-color: #8a0000;
    color:  whitesmoke;
    padding: 20px;
    text-align: left;
    margin-left: 70px;
    box-shadow: 0 4px 4px -2px rgba(0, 0, 0, 0.2);
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.header-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    position: relative;
    font-weight: bold; /* Make text bold */
}


.profile-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    margin-top: 5px;
    color: white;
}

.profile-menu li i {
    font-size: 20px;
    display: block;
    margin: 0 auto;
    color: white;
}


.profile-menu li small {
    font-size: 12px;
    color: white;
}


.dropdown {
    display: none;
    position: absolute;
    top: 75px;
    right: -5px;
    background-color:#8a0000;
    color: black;
    min-width: 40px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    border-radius: 5px;
    z-index: 1;
}
</style>

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


<?php include 'super_admin_sidebar.php'; ?>
    <div class="main-content">
        <h2>Manage System</h2>
        <a href="edit_index.php" class="status-tag admin">Edit Index Page</a>
    </div>

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
    background-color: #D4AF37;
}

/* Task Table Styles */
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
    background-color: #8a0000;
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

/* Status Dropdown Styles */
.editable-status {
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
    background-color: green;
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

/* No tasks found message */
.admin-table td[colspan="6"] {
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
    background:#660000;
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