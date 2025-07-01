<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify admin access
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

// Set first_name if not already in session
if (!isset($_SESSION['first_name'])) {
    // Function to fetch the first_name based on user ID
    function getfirst_nameFromDatabase($userId) {
        global $db;  // Assuming this is your PDO connection
    
        $query = "SELECT first_name FROM users WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                error_log("first_name found: " . $user['first_name']);
                return $user['first_name'];
            } else {
                error_log("No user found with ID: $userId");
                return null;
            }
        } else {
            error_log("Error executing query: " . implode(", ", $stmt->errorInfo()));
            return null;
        }
    }

    // 🟢 Call the function and set the session variable
    if (isset($_SESSION['user_id'])) {
        $_SESSION['first_name'] = getfirst_nameFromDatabase($_SESSION['user_id']);
    } else {
        error_log("User ID not found in session.");
        $_SESSION['first_name'] = 'Guest';  // fallback
    }
}




$isAdmin = isAdmin();  // Check if user is admin

// Handle form submission (Add Task)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    $status = $isAdmin ? $_POST['status'] : 'pending';

    $userId = $_SESSION['user_id']; // Logged-in user's ID

    $today = date('Y-m-d');
    if ($startDate < $today || $endDate < $today || $endDate < $startDate) {
        $_SESSION['error'] = "Invalid date inputs.";
        header("Location: project.php");
        exit();
    }

    try {
        $stmt = $db->prepare("
            INSERT INTO tasks (title, description, user_id, start_date, end_date, status)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $description, $userId, $startDate, $endDate, $status]);

        $_SESSION['success'] = "Task added successfully!";
    } catch (PDOException $e) {
        error_log("Task addition failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add task.";
    }

    header("Location: project.php");
    exit();
}

// Handle search
$search = $_GET['search'] ?? '';
$whereClause = $search ? "AND (title LIKE :search OR description LIKE :search)" : "";
$params = $search ? ['search' => "%$search%"] : [];

// Fetch tasks from the database
try {
    $userId = $_SESSION['user_id']; // Assuming user ID is stored in the session
    $query = "
        SELECT task_id, title, description, created_at, status 
        FROM tasks 
        WHERE 1=1
    ";
    if (!$isAdmin) {
        $query .= " AND client = :user_id"; // Restrict tasks to those assigned to the user
        $params['user_id'] = $userId;
    }
    $query .= " $whereClause ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tasks)) {
        error_log("No tasks found for the user.");
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
    <title><?= $isAdmin ? 'Manage Project' : 'My Tasks' ?></title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>


</head>
<body>
    <!-- Include the Sidebar -->
    <?php include 'admin_sidebar.php'; ?>

    <!-- Success/Error Messages -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

<!-- Main Header -->
<header class="main-header">
    <div class="header-content">
        <div>
        <h1>Help & Support</h1>

            <p>JT Kitchen Equipment Installation Services | Admin Dashboard</p>
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
                <li onclick="window.location.href='settings.php'" style="text-align: center; display: flex; flex-direction: column; align-items: center;">
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

<style>
    
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

</style>

<div class="main-content">
        <h1><?= $isAdmin ? 'Manage Profile' : 'Help & Report' ?></h1>
<div class="container">


  