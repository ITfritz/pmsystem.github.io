<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify user is logged in
if (!isLoggedIn()) {
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
    <?php include 'user_sidebar.php'; ?>

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
            <h1>Portfolio</h1>
            <p>JT Kitchen Equipment Installation Services | User Dashboard</p>
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
    background: #28a745;
    color: #fff;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    font-size: 12px;
    transition: background 0.3s ease;
}
.status-tag.done:hover {
    background: #fff;
    color: #28a745;
    border: 1px solid #28a745;
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

<div class="main-content1">
    <h1><?= $isAdmin ? 'Manage Profile' : 'Overview' ?></h1>


    <div class="container">
        <div class="card-container">
            <!-- CARD 1 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 1</h3>
                    <p class="card-description">This is a description of card 1.</p>
                    <button class="card-button" onclick="openModal(1)">Read More</button>
                </div>
            </div>

            <!-- CARD 2 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 2</h3>
                    <p class="card-description">This is a description of card 2.</p>
                    <button class="card-button" onclick="openModal(2)">Read More</button>
                </div>
            </div>

            <!-- CARD 3 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 3</h3>
                    <p class="card-description">This is a description of card 3.</p>
                    <button class="card-button" onclick="openModal(3)">Read More</button>
                </div>
            </div>

            <!-- CARD 4 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 4</h3>
                    <p class="card-description">This is a description of card 4.</p>
                    <button class="card-button" onclick="openModal(4)">Read More</button>
                </div>
            </div>

            <!-- CARD 5 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 5</h3>
                    <p class="card-description">This is a description of card 5.</p>
                    <button class="card-button" onclick="openModal(5)">Read More</button>
                </div>
            </div>

            <!-- CARD 6 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 6</h3>
                    <p class="card-description">This is a description of card 6.</p>
                    <button class="card-button" onclick="openModal(6)">Read More</button>
                </div>
            </div>

            <!-- CARD 7 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 7</h3>
                    <p class="card-description">This is a description of card 7.</p>
                    <button class="card-button" onclick="openModal(7)">Read More</button>
                </div>
            </div>

            <!-- CARD 8 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 8</h3>
                    <p class="card-description">This is a description of card 8.</p>
                    <button class="card-button" onclick="openModal(8)">Read More</button>
                </div>
            </div>

            <!-- CARD 9 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 9</h3>
                    <p class="card-description">This is a description of card 9.</p>
                    <button class="card-button" onclick="openModal(9)">Read More</button>
                </div>
            </div>

            <!-- CARD 10 -->
            <div class="card">
            <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 10</h3>
                    <p class="card-description">This is a description of card 10.</p>
                    <button class="card-button" onclick="openModal(10)">Read More</button>
                </div>
            </div>

              <!-- CARD 11 -->
              <div class="card">
              <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 11</h3>
                    <p class="card-description">This is a description of card 11.</p>
                    <button class="card-button" onclick="openModal(11)">Read More</button>
                </div>
            </div>

              <!-- CARD 12 -->
              <div class="card">
              <img src="images/bg3.png" alt="Post Image" class="card-image">
                <div class="card-content">
                    <h3 class="card-title">Card Title 12</h3>
                    <p class="card-description">This is a description of card 12.</p>
                    <button class="card-button" onclick="openModal(12)">Read More</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="myModal1" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(1)">&times;</span>
        <h2>Card Title 1</h2>
        <p>Detailed info for card 1.</p>
    </div>
</div>

<div id="myModal2" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(2)">&times;</span>
        <h2>Card Title 2</h2>
        <p>Detailed info for card 2.</p>
    </div>
</div>

<div id="myModal3" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(3)">&times;</span>
        <h2>Card Title 3</h2>
        <p>Detailed info for card 3.</p>
    </div>
</div>

<div id="myModal4" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(4)">&times;</span>
        <h2>Card Title 4</h2>
        <p>Detailed info for card 4.</p>
    </div>
</div>

<div id="myModal5" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(5)">&times;</span>
        <h2>Card Title 5</h2>
        <p>Detailed info for card 5.</p>
    </div>
</div>

<div id="myModal6" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(6)">&times;</span>
        <h2>Card Title 6</h2>
        <p>Detailed info for card 6.</p>
    </div>
</div>

<div id="myModal7" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(7)">&times;</span>
        <h2>Card Title 7</h2>
        <p>Detailed info for card 7.</p>
    </div>
</div>

<div id="myModal8" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(8)">&times;</span>
        <h2>Card Title 8</h2>
        <p>Detailed info for card 8.</p>
    </div>
</div>

<div id="myModal9" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(9)">&times;</span>
        <h2>Card Title 9</h2>
        <p>Detailed info for card 9.</p>
    </div>
</div>

<div id="myModal10" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(10)">&times;</span>
        <h2>Card Title 10</h2>
        <p>Detailed info for card 10.</p>
    </div>
</div>

<div id="myModal11" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(11)">&times;</span>
        <h2>Card Title 11</h2>
        <p>Detailed info for card 11.</p>
    </div>
</div>

<div id="myModal12" class="modal" style="display: none;">
    <div class="modal-content">
        <span class="close" onclick="closeModal(12)">&times;</span>
        <h2>Card Title 12</h2>
        <p>Detailed info for card 12.</p>
    </div>
</div>

<!-- ✅ JavaScript -->
<script>
function openModal(id) {
    document.getElementById('myModal' + id).style.display = 'flex';
}

function closeModal(id) {
    document.getElementById('myModal' + id).style.display = 'none';
}

// Optional: close modal when clicking outside
window.onclick = function(event) {
    for (let i = 1; i <= 8; i++) {
        const modal = document.getElementById('myModal' + i);
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    }
}
</script>

<style>
/* MODAL BACKDROP */
.modal {
    position: fixed;
    inset: 0;
    background-color: rgba(0,0,0,0.5);
    z-index: 1000;

    /* Center content */
    display: flex;
    justify-content: center;
    align-items: center;
}

/* MODAL CONTENT BOX */
.modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 500px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.3);
    position: relative;
}

.close {
    color: #aaa;
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}

.close:hover, .close:focus {
    color: black;
}
</style>


<style>
.card-container {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); /* Responsive grid */
    gap: 20px;
    padding: 30px;
    justify-content: center;
}

.card {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    margin: 20px;
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

.card-image {
    width: 100%;
    height: 200px;
    object-fit: cover;
    object-position: top;
    border-bottom: 2px solid #ddd;
}

.card-content {
    padding: 20px;
}

.card-title {
    font-size: 20px;
    font-weight: bold;
    color: #333;
    margin-bottom: 10px;
}

.card-description {
    font-size: 16px;
    color: #555;
    margin-bottom: 15px;
    line-height: 1.5;
}

.card-button {
    padding: 12px 20px;
    background-color: #8a0000;
    color: white;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

.card-button:hover {
    background-color:#D4AF37;
}

@media (max-width: 768px) {
    .card-container {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* Smaller grid items on mobile */
    }
}

</style>

    <script>
function addComment() {
    const commentInput = document.getElementById('commentInput');
    const commentsList = document.getElementById('commentsList');

    const commentText = commentInput.value.trim();
    if (commentText !== "") {
        const newComment = document.createElement('li');
        newComment.textContent = commentText;
        commentsList.appendChild(newComment);
        commentInput.value = "";
    }
}
</script>



</div>


    <div class="main-content3">
    <h1><?= $isAdmin ? 'Manage Profile' : 'Sample Works' ?></h1>
        <!-- Sample Work 1 -->
        <div class="card">
            <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
                <h3 class="card-title">Draft</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(1)">View Details</button>
            </div>
        </div>

                <!-- Sample Work 1 -->
                <div class="card">
                <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
                <h3 class="card-title">Draft</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(1)">View Details</button>
            </div>
        </div>


           <!-- Sample Work 1 -->
           <div class="card">
           <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
                <h3 class="card-title">Draft</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(1)">View Details</button>
            </div>
        </div>


           <!-- Sample Work 1 -->
           <div class="card">
           <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
                <h3 class="card-title">Draft</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(1)">View Details</button>
            </div>
        </div>
        
        <!-- Add more cards as needed -->
    </div>
</div>


</div>


    <div class="main-content4">
    <h1><?= $isAdmin ? 'Manage Profile' : 'Completed' ?></h1>
        <!-- Completed Work 1 -->
        <div class="card">
        <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
                <h3 class="card-title">Completed</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(3)">View Project</button>
            </div>
        </div>



          <!-- Completed Work 1 -->
          <div class="card">
          <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
            <h3 class="card-title">Completed</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(3)">View Project</button>
            </div>
        </div>

          <!-- Completed Work 1 -->
          <div class="card">
          <img src="images/bg3.png" alt="Sample Work 1" class="card-image">
            <div class="card-content">
            <h3 class="card-title">Completed</h3>
                <p class="card-description">details</p>
                <button class="card-button" onclick="openModal(3)">View Project</button>
            </div>
        </div>
        
        
        <!-- Add more cards as needed -->
    </div>
</div>



</div>