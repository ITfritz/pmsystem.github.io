<?php
session_start();
require 'connect.php';
require 'functions.php';


// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}


$pdo = new PDO("mysql:host=localhost;dbname=projectmanagementsystem", "root", "");

// Get the logged-in user's ID
$userId = $_SESSION['user_id'] ?? null;

// Default counts
$completedCount = 0;
$pendingCount = 0;
$inProgressCount = 0;

// Make sure $userId is set
$userId = $_SESSION['user_id'] ?? null;

if ($userId) {
    // Completed tasks
    $stmtCompleted = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE client = ? AND status = 'done'");
    $stmtCompleted->execute([$userId]);
    $completedCount = $stmtCompleted->fetchColumn();

    // Pending tasks
    $stmtPending = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE client = ? AND status = 'pending'");
    $stmtPending->execute([$userId]);
    $pendingCount = $stmtPending->fetchColumn();

    // In-progress tasks
    $stmtInProgress = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE client = ? AND status = 'in-progress'");
    $stmtInProgress->execute([$userId]);
    $inProgressCount = $stmtInProgress->fetchColumn();
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
    $params = []; // Initialize params array

    $query = "
        SELECT
            task_id,
            title,
            description,
            created_at,
            status,
            client_name,     /* <<< ADD THIS */
            client_address,  /* <<< ADD THIS */
            client_phone,    /* <<< ADD THIS */
            client_email     /* <<< ADD THIS */
        FROM
            tasks
        WHERE 1=1
    ";

    if (!$isAdmin) {
        $query .= " AND client = :user_id"; // Restrict tasks to those assigned to the user
        $params['user_id'] = $userId;
    }

    // Ensure $whereClause is defined, even if empty, to avoid errors
    // If $whereClause is dynamic from a search/filter, make sure it's constructed earlier
    // For this example, let's assume it might be empty or correctly defined.
    // If it's undefined, this line will cause an error.
    // You might want to define it as $whereClause = ''; if it's not always set by filters.
    $query .= " $whereClause ORDER BY created_at DESC";


    $stmt = $db->prepare($query);
    $stmt->execute($params); // Pass params directly to execute for prepared statements
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tasks)) {
        error_log("No tasks found for the user or query returned no results."); // More descriptive log
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching tasks. Please check server logs."); // More user-friendly error
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">



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
            <h1 id="welcome"></h1>
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


<div class="main-content-inner">
<div class="main-content-left">
    <h2>My Account</h2>

<div class="dashboard-card">
    <h3>Completed</h3>
    <p id="completed-tasks"><?= $completedCount ?></p>
</div>

<div class="dashboard-card">
    <h3>In-Progress</h3>
    <p id="in-progress"><?= $inProgressCount ?></p>
</div>

<div class="dashboard-card">
    <h3>Pendings</h3>
    <p id="pending-bookings"><?= $pendingCount ?></p>
</div>


    <div class="dashboard-card">
    
    </div>
</div>


<script>
function addCompletedTask() {
    let el = document.getElementById('completed-tasks');
    el.textContent = parseInt(el.textContent) + 1;
}

function addPendingBooking() {
    let el = document.getElementById('pending-bookings');
    el.textContent = parseInt(el.textContent) + 1;
}

    function updateNotificationCount() {
        const count = document.querySelectorAll('#notifications-list li').length;
        document.getElementById('notification-count').textContent = count;
    }

    function dismissNotification(button) {
        const li = button.parentElement;
        li.remove();
        updateNotificationCount();
    }

    // Initialize count on page load
    updateNotificationCount();
</script>


<div class="main-content-right">
    <h2>Recent Tasks</h2>
    <br>
    <p id="pendingCountMessage"></p>
    <br>
    <ul id="pendingBookingsList" style="list-style-type: none; padding: 0;"></ul>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    fetch('get_recent_tasks.php')
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            const list = document.getElementById('pendingBookingsList');
            const message = document.getElementById('pendingCountMessage');
            list.innerHTML = '';

            if (data.tasks && data.tasks.length > 0) {
                message.textContent = `You have ${data.tasks.length} recent task(s).`;
                data.tasks.forEach(task => {
                    const li = document.createElement('li');
                    const created = new Date(task.created_at).toLocaleString();
                    li.innerHTML = `
                                              <div style="padding: 10px; margin-bottom: 10px; border: 2px solid #8a0000; border-radius: 5px;">
                          <strong style="color: #8a0000;">${task.title}</strong><br>
                            <small style="color: black">Added: ${created}</small>
                        </div>
                    `;
                    list.appendChild(li);
                });
            } else if (data.error) {
                message.textContent = data.error;
            } else {
                message.textContent = 'No recent tasks found.';
            }
        })
        .catch(error => {
            console.error('Error fetching tasks:', error);
            document.getElementById('pendingCountMessage').textContent = 'Error loading tasks.';
        });
});
</script>





</div>

<style>

#pendingBookingsList {
    padding: 0;
    margin: 0;
}

#pendingBookingsList li {
    background-color: #f9f9f9;
    margin-bottom: 10px;
    padding: 12px 16px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    transition: transform 0.2s ease;
    font-family: 'Segoe UI', sans-serif;
}

#pendingBookingsList li:hover {
    transform: translateY(-2px);
    background-color:#D4AF37;
}


.pending-booking-item {
    padding: 24px 28px; /* slightly reduced for balance */
    background: whitesmoke;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
    display: flex;
    justify-content: flex;
    align-items: flex-start;
    color: #8a0000;
    font-size: 15px;
    gap: 24px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.pending-booking-item:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    background-color: #f7f7f7; /* subtle hover effect */
}

.booking-details {
    display: flex;
    flex-direction:column-reverse;
    gap: 4px;
    flex: 1;
    color: #333;
    font-size: 18px;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    padding: 20px;
}


.status-text {
    font-size: 0.95em;
    color: #c0392b;
    font-weight: bold;
}

.cancel-btn {
    padding: 10px 16px;
    background-color: #8a0000;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    white-space: nowrap;
    font-size: 14px;
    transition: background-color 0.2s ease;
    align-self: flex-start;
    margin: auto;
}

.cancel-btn:hover {
    background-color: #a60000;
}

.main-content-inner {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin: 2px 10px 20px 80px;
}

.main-content-left {
    flex: 1;
    min-width: 30px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: Arial, sans-serif;
    color: #333;;
}

.main-content-left h2 {
    margin-bottom: 20px;
    font-size: 1.8rem;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
    color: #8a0000;
}

.main-content-right {
    flex: 3;
    min-width: 550px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: sans-serif;
}

h2 {
    font-size: 2rem;
    color: #333;
    border-bottom: 2px solid #ddd;
    padding-bottom: 10px;
    color: #8a0000;
}
.dashboard-card {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    transition: transform 0.2s, box-shadow 0.2s;
}
.dashboard-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}

.dashboard-card h3 {
    margin: 0 0 10px;
    font-size: 1.2rem;
    color: #8a0000;
}

.dashboard-card p {
    font-size: 1.6rem;
    font-weight: bold;
    color: black;
    margin: 0;
    background: whitesmoke;
}

.dashboard-card ul {
    list-style: none;
    padding: 0;
    margin: 0;
    
}

.dashboard-card ul li {
    background: whitesmoke;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 0.95rem;
    color: #333;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.dashboard-card ul li button {
    background:  #8a0000;
    border: none;
    color: white;
    border-radius: 50%;
    width: 24px;
    height: 24px;
    cursor: pointer;
    font-weight: bold;
    line-height: 20px;
}

.dashboard-card ul li button:hover {
    background: #D4AF37;
}

.booking-buttons {
    display: flex; 
    flex-direction: column;
    gap: 15px;
}

.booking-btn {
    padding: 12px 20px;
    font-size: 1.1em;
    color: #8a0000;
    background-color: whitesmoke;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    text-align: left;
    display: flex;
    justify-content: space-between;
    width: 100%;
}

.booking-btn:hover {
    transform: scale(1.02);
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
}

#selected-slot {
    margin-top: 30px;
    padding: 20px;
    background:whitesmoke;
    border-radius: 10px;
    box-shadow: 0 0 8px rgba(0, 0, 0, 0.1);
}

#selected-slot p strong{
    color: #8a0000;
}

#selected-slot p {
    font-size: 1.1em;
    margin: 10px 0;
}


button#confirm-btn {
    padding: 12px 20px;
    background-color: #28a745; /* Green for confirmation */
    color: #fff;
    font-size: 1.1em;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 15px;
    width: 100%;
}

button#confirm-btn:disabled {
    background-color: #ccc;
    cursor: not-allowed;
}

button#confirm-btn:hover:enabled {
    background-color: #218838;
    transform: scale(1.05);
}

/* ---------------- Responsive Styles ---------------- */
@media (max-width: 1024px) {
    .main-content-inner {
        margin: 2px 10px 20px 20px; /* reduce left margin */
        flex-direction: column;
    }

    .main-content-left,
    .main-content-right {
        flex: 1 1 100%;
        margin-bottom: 20px;
    }
}

@media (max-width: 768px) {
    .main-content {
        padding: 20px;
    }

    .main-content-left,
    .main-content-right {
        padding: 20px;
    }

    h2,
    .main-content-left h2,
    .main-content-right h2 {
        font-size: 1.5rem;
    }

    .dashboard-card h3 {
        font-size: 1rem;
    }

    .dashboard-card p {
        font-size: 1.2rem;
    }

    .booking-btn {
        font-size: 1rem;
        padding: 10px 16px;
    }

    button#confirm-btn {
        font-size: 1rem;
        padding: 10px 16px;
    }
}

@media (max-width: 480px) {
    .main-content {
        margin-left: 0;
    }

    .main-content-inner {
        margin: 0 10px 20px 10px;
    }

    .dashboard-card p {
        font-size: 1rem;
    }

    .dashboard-card ul li {
        font-size: 0.85rem;
    }

    .booking-btn {
        font-size: 0.95rem;
        padding: 8px 12px;
    }

    button#confirm-btn {
        font-size: 0.95rem;
        padding: 10px;
    }
}
</style>

<div class="main-wrapper1">

  <!-- Left content -->
  <div class="main-content-left1">
    <h2>Appointment Statistics</h2>
    <p>Overview of JTKEIS appointment bookings on a week:</p>
    <canvas id="appointmentChart"></canvas>
  </div>

  <!-- Right content -->
  <div class="main-content-right1">
    <h2>Monthly Summary</h2>
    <p>Overall appointment trends of JTKEIS:</p>
    <canvas id="summaryChart"></canvas>
  </div>

</div>

<script>
  const ctx = document.getElementById('appointmentChart').getContext('2d');
  const appointmentChart = new Chart(ctx, {
      type: 'bar',
      data: {
          labels: ['May 5', 'May 6', 'May 7', 'May 8'],
          datasets: [{
              label: 'Booked Slots',
              data: [3, 5, 2, 4],
              backgroundColor: '#8a0000',
              borderRadius: 8,
              barThickness: 'flex',
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
              y: { beginAtZero: true, ticks: { stepSize: 1 } }
          },
          plugins: {
              legend: { display: false },
              tooltip: { enabled: true }
          }
      }
  });

  const ctx2 = document.getElementById('summaryChart').getContext('2d');
  const summaryChart = new Chart(ctx2, {
      type: 'line',
      data: {
          labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4'],
          datasets: [{
              label: 'Appointments per Week',
              data: [10, 12, 8, 15],
              backgroundColor: 'rgba(138, 0, 0, 0.2)',
              borderColor: '#8a0000',
              fill: true,
              tension: 0.3,
              pointBackgroundColor: '#8a0000'
          }]
      },
      options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
              y: { beginAtZero: true }
          }
      }
  });
</script>

<style>
/* Wrapper for the entire layout */
.main-wrapper1 {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 20px;
}

/* Left Content */
.main-content-left1 {
    flex: 2;
    min-width: 500px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: sans-serif;
    height: 450px;
    display: flex;
    flex-direction: column;
    margin-left: 70px;
    overflow: hidden; /* 🟢 prevents overflow */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.main-content-left1:hover{
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

/* Right Content */
.main-content-right1 {
    flex: 2;
    min-width: 500px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: sans-serif;
    height: 450px;
    display: flex;
    flex-direction: column;
    overflow: hidden; /* 🟢 prevents overflow */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.main-content-right1:hover{
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}


/* Chart Styles */
#appointmentChart, #summaryChart {
    flex: 1;               /* 🟢 make canvas fill remaining space */
    width: 100%;
    max-height: 100%;       /* 🟢 prevent overflow */
    object-fit: contain;    /* 🟢 make sure it fits without stretching */
    margin-bottom: 55px;
}

</style>

<div class="main-content">

<h2>Track Your Bookings</h2>

<!-- Add Task Modal (For All Users) -->
<div id="addTaskModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddTaskModal()">&times;</span>
       <h2 style="background-color: #80000a; color: #fff; padding: 12px 20px; border-radius: 8px 8px 0 0; text-align: center; margin: 0;">
  Add New Task
</h2>

        <form id="addTaskForm" method="POST">
            <input type="hidden" name="add_task" value="1">

            <!-- Task Fields -->
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" placeholder="Task Title" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" placeholder="Task Description" required></textarea>

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date" required>

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date" required>

            <!-- Client Details -->
            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name" placeholder="Full Name" required>

            <label for="client_address">Client Address:</label>
            <input type="text" id="client_address" name="client_address" placeholder="Address" required>

            <label for="client_phone">Client Phone:</label>
            <input type="tel" id="client_phone" name="client_phone" placeholder="+63XXXXXXXXXX" pattern="^\+?\d{7,15}$" title="Enter a valid phone number" required>

            <label for="client_email">Client Email:</label>
            <input type="email" id="client_email" name="client_email" placeholder="example@email.com" required>

            <!-- Submit Button -->
            <div class="modal-actions">
                <button type="submit" class="status-tag add">
                    <i class="fas fa-check"></i>
                </button>
                <button type="button" onclick="closeAddTaskModal()"
                    style="background-color: #8a0000; color: white; border: none; padding: 10px 20px; border-radius: 20px; cursor: pointer;">
                    <i class="fas fa-times"></i> Cancel
                </button>
            </div>
        </form>
    </div>
</div>


<!-- Search Form -->
<form method="GET" class="search-form">
    <input type="text" name="search" placeholder="Search by title or description..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
</form>

<!-- Task Cards -->
<div class="task-cards">
    <?php if (!empty($tasks)): ?>
<?php foreach ($tasks as $task): ?>
    <div class="task-card status-<?= htmlspecialchars($task['status']) ?>"  id="task-<?= htmlspecialchars($task['task_id']) ?>"
         data-task-id="<?= htmlspecialchars($task['task_id']) ?>"
         data-title="<?= htmlspecialchars($task['title']) ?>"
         data-description="<?= htmlspecialchars($task['description']) ?>"
         data-created="<?= htmlspecialchars(date('M d, Y', strtotime($task['created_at']))) ?>"
         data-status="<?= htmlspecialchars($task['status']) ?>"
         data-client-name="<?= htmlspecialchars($task['client_name'] ?? '') ?>"
         data-client-address="<?= htmlspecialchars($task['client_address'] ?? '') ?>"
         data-client-phone="<?= htmlspecialchars($task['client_phone'] ?? '') ?>"
         data-client-email="<?= htmlspecialchars($task['client_email'] ?? '') ?>"
         onclick="showTaskModal(this)">

     <div class="task-card"
     data-title="<?= htmlspecialchars($task['title']) ?>"
     data-description="<?= htmlspecialchars($task['description']) ?>"
     data-created="<?= date('M d, Y', strtotime($task['created_at'])) ?>"
     data-status="<?= htmlspecialchars($task['status']) ?>"
     onclick="showTaskModal(this)">
    <h3><?= htmlspecialchars($task['title']) ?></h3>
    <p class="description"><?= htmlspecialchars($task['description']) ?></p>
    <p class="created-at">Created: <?= date('M d, Y', strtotime($task['created_at'])) ?></p>

    <div class="status-section">
        <?php if ($isAdmin): ?>
            <label>Status:</label>
            <select class="editable-status" data-task-id="<?= $task['task_id'] ?>" data-current-status="<?= htmlspecialchars($task['status']) ?>" onclick="event.stopPropagation()">
                <option value="in-progress" <?= $task['status'] === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="done" <?= $task['status'] === 'done' ? 'selected' : '' ?>>Done</option>
            </select>
        <?php else: ?>
            <span class="status-tag"><?= ucfirst(htmlspecialchars($task['status'])) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($isAdmin): ?>
        <div class="actions">
            <a href="edit_task.php?id=<?= $task['task_id'] ?>" class="btn edit" onclick="event.stopPropagation()">Edit</a>
            <a href="delete_task.php?id=<?= $task['task_id'] ?>" class="btn done" onclick="event.stopPropagation(); return confirm('Are you sure you want to mark this task as Done?')">Mark as Done</a>
        </div>
    <?php endif; ?>
</div>

            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-tasks">No tasks found.</p>
    <?php endif; ?>
</div>

<style>
    /* Base Task Card Styling (if not already defined) */
.task-card {
    background-color: #fff;
    border: 1px solid #e0e0e0;
    border-left: 5px solid transparent; /* This will be overridden by status color */
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    transition: all 0.3s ease; /* Smooth transition for hover and status changes */
    cursor: pointer;
    overflow: hidden; /* To contain floated elements like badges */
}

.task-card:hover {
    box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    transform: translateY(-3px);
}

.task-card h3 {
    margin-top: 0;
    color: #333;
}

.task-card p {
    margin-bottom: 5px;
    color: #666;
}

/* Status-specific card colors */

.task-card.status-pending {
    /* Subtle grey/blue for 'to-do' - implies waiting */
  background-color: #ed8936; /* warm orange gradient */
    box-shadow: 0 4px 8px rgba(237, 137, 54, 0.4);
}

.task-card.status-in-progress {
  background: linear-gradient(135deg, #4299e1, #3182ce); /* crisp blue gradient */
    box-shadow: 0 4px 8px rgba(49, 130, 206, 0.4);
}

.task-card.status-done {
    background-color:  #28a745;
    box-shadow: 0 4px 8px rgba(56, 161, 105, 0.4);
}

</style>

<!-- Modal -->
<div id="taskModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeTaskModal()">&times;</span>

    <!-- Title Section -->
    <h2 id="modalTitle" class="contract-title">Active Booking</h2>

    <!-- Booking Details Section -->
   <div class="section-heading">
<h2 style="color: #80000a;">Booking Details</h2>

</div>
<style>
    .section-heading {
    text-align: center;
    margin-bottom: 20px;
}
</style>
<div class="contract-details">

    <!-- Booking Info -->
    <div class="detail-row">
        <span class="detail-label">Booking ID:</span>
        <span id="modalBookingID" class="detail-value"></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Booked On:</span>
        <span id="modalCreated" class="detail-value"></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Status:</span>
        <span id="modalStatus" class="detail-value"></span>
    </div>
    <div class="detail-row">
        <span class="detail-label">Description:</span>
        <span id="modalDescription" class="detail-value"></span>
    </div>

    <hr style="margin: 1em 0;">

   <div class="section-heading">
  <h2 style="color: #80000a;">Contact Details</h2>

</div>
<style>
    .section-heading {
    text-align: center;
    margin-bottom: 20px;
}
</style>

<div class="detail-row">
    <span class="detail-label">Client Name:</span>
    <span id="modalClientName" class="detail-value"></span>
</div>

<div class="detail-row">
    <span class="detail-label">Client Address:</span>
    <span id="modalClientAddress" class="detail-value"></span>
</div>

<div class="detail-row">
    <span class="detail-label">Client Phone:</span>
    <span id="modalClientPhone" class="detail-value"></span>
</div>

<div class="detail-row">
    <span class="detail-label">Client Email:</span>
    <span id="modalClientEmail" class="detail-value"></span>
</div>


 

  </div>
</div>

<script>
function showTaskModal(card) {
    const title = card.getAttribute('data-title');
    const description = card.getAttribute('data-description');
    const created = card.getAttribute('data-created');
    const status = card.getAttribute('data-status');
    const bookingID = card.getAttribute('data-task-id');

    // New client details
    const clientName = card.getAttribute('data-client-name');
    const clientAddress = card.getAttribute('data-client-address');
    const clientPhone = card.getAttribute('data-client-phone');
    const clientEmail = card.getAttribute('data-client-email');

    // Update modal content
    // Update modal content
// Update modal content
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalTitle').style.color = 'white';
    document.getElementById('modalTitle').style.fontSize = '3em'; // <--- ADD THIS LINE
    document.getElementById('modalDescription').innerText = description;
    document.getElementById('modalCreated').innerText = created;
    document.getElementById('modalStatus').innerText = status;
    document.getElementById('modalBookingID').innerText = bookingID;

    // Update new client details in the modal
    document.getElementById('modalClientName').innerText = clientName;
    document.getElementById('modalClientAddress').innerText = clientAddress;
    document.getElementById('modalClientPhone').innerText = clientPhone;
    document.getElementById('modalClientEmail').innerText = clientEmail;

    // Show the modal
    document.getElementById('taskModal').style.display = 'block';
}

function closeTaskModal() {
    document.getElementById('taskModal').style.display = 'none';
}

// Close modal if clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('taskModal');
    if (event.target == modal) {
        closeTaskModal();
    }
}
</script>

<Style>

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

    /* Main Content */
    .main-content1 {
    flex: 1;
    margin-left: 80px; /* Adjust for the sidebar width */
    border-radius: 18px;
    padding: 30px;
    color: black;
    max-width: 230px;
    min-width: 320px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15); /* added shadow */
}

/* Modal Styling */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.6);
}

/* Modal Content */
.modal-content {
  background: #fff;
  margin: 10% auto;
  padding: 30px;
  width: 80%;
  max-width: 600px;
  border-radius: 10px;
  position: relative;
}

/* Close Button */
.close {
  position: absolute;
  right: 20px;
  top: 10px;
  font-size: 30px;
  color: #aaa;
  cursor: pointer;
}

/* Contract Title */
.contract-title {
  font-size: 3em;
  background-color: #80000a;
  text-align: center;
  padding: 10px;
}

/* Contact Details Section */
.contract-details {
  margin-bottom: 30px;
  padding: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background-color: #f9f9f9;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

/* Each row of label + value */
.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 12px 0;
  padding: 8px 0;
  border-bottom: 1px solid #ddd;
}

/* Label styling */
.detail-label {
  font-weight: 600;
  color: #222;
  flex: 1;
}

/* Value styling */
.detail-value {
  flex: 2;
  text-align: right;
  font-style: italic;
  color: #444;
  word-break: break-word;
}

/* Optional: Last row without border */
.detail-row:last-child {
  border-bottom: none;
}
/* Contact Details Section */
.contract-details {
  margin-bottom: 30px;
  padding: 20px;
  border: 1px solid #e0e0e0;
  border-radius: 12px;
  background-color: #f9f9f9;
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
}

/* Each row of label + value */
.detail-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin: 12px 0;
  padding: 8px 0;
  border-bottom: 1px solid #ddd;
}

/* Label styling */
.detail-label {
  font-weight: 600;
  color: #222;
  flex: 1;
}

/* Value styling */
.detail-value {
  flex: 2;
  text-align: right;
  font-style: italic;
  color: #444;
  word-break: break-word;
}

/* Optional: Last row without border */
.detail-row:last-child {
  border-bottom: none;
}

    body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f5f7fa;
    color: #333;
}

.search-form {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.search-form input[type="text"] {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px 0 0 8px;
    width: 300px;
    font-size: 1em;
}

.search-form button {
    padding: 10px 20px;
    border: none;
    background:#8a0000;
    color: white;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    transition: background 0.3s;
}

.search-form button:hover {
    background:#660000;
}

.task-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 20px;
    padding: 0 10px;
}

.task-card {
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
    height: 350px;
    cursor: pointer;
    background-color: white;
    color: black;
}


.task-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.task-card h3 {
    margin: 0;
    font-size: 1.4em;
    color: #8a0000;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.task-card .description {
    flex-grow: 1;
    color: #666;
    margin: 12px 0;
    font-size: 0.95em;
    line-height: 1.4;
    overflow-y: auto;
}

.task-card .created-at {
    font-size: 0.85em;
    color: #aaa;
    margin-bottom: 10px;
}

.status-section {
    margin-bottom: 15px;
}


.actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.actions .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9em;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

.actions .edit {
    background: #4a90e2;
    color: white;
}

.actions .done {
    background: #5cb85c;
    color: white;
}

.actions .btn:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.no-tasks {
    text-align: center;
    font-style: italic;
    color: #777;
    font-size: 1.1em;
    margin-top: 30px;
}

</Style>

  </div>
</div>

<Style>

/* Modal Styling */
.modal {
  display: none;
  position: fixed;
  z-index: 1000;
  left: 0; top: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.6);
}

/* Modal Content */
.modal-content {
  background: #fff;
  margin: 10% auto;
  padding: 30px;
  width: 80%;
  max-width: 600px;
  border-radius: 10px;
  position: relative;
}

/* Close Button */
.close {
  position: absolute;
  right: 20px;
  top: 10px;
  font-size: 30px;
  color: #aaa;
  cursor: pointer;
}

/* Contract Title */
.contract-title {
  font-size: 1.8em;
  color: #005f8d;
  text-align: center;
  margin-bottom: 20px;
}

/* Contract Details */
.contract-details {
  margin-bottom: 20px;
}

.detail-row {
  display: flex;
  justify-content: space-between;
  margin: 10px 0;
}

.detail-label {
  font-weight: bold;
  color: #333;
}

.detail-value {
  font-style: italic;
  color: #555;
}

/* Signature Section */
.contract-signature {
  margin-top: 20px;
  text-align: center;
  font-size: 1.1em;
  color: #444;
}

.contract-signature strong {
  color: #333;
}


    body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: whitesmoke;
    color: #333;
}

.search-form {
    display: flex;
    justify-content: center;
    margin-bottom: 20px;
}

.search-form input[type="text"] {
    padding: 10px 15px;
    border: 1px solid #ddd;
    border-radius: 8px 0 0 8px;
    width: 300px;
    font-size: 1em;
}

.search-form button {
    padding: 10px 20px;
    border: none;
    background: #4a90e2;
    color: white;
    border-radius: 0 8px 8px 0;
    cursor: pointer;
    transition: background 0.3s;
}

.search-form button:hover {
    background: #357ab8;
}

.task-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 25px;
    margin-top: 20px;
    padding: 0 10px;
}

.task-card {
    border-radius: 16px;
    padding: 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    overflow: hidden;
    height: 350px;
    cursor: pointer;
    background-color: white;
    color: black;
}


.task-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

.task-card h3 {
    margin: 0;
    font-size: 1.4em;
    color: #8a0000;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
}

.task-card .description {
    flex-grow: 1;
    color: #666;
    margin: 12px 0;
    font-size: 0.95em;
    line-height: 1.4;
    overflow-y: auto;
}

.task-card .created-at {
    font-size: 0.85em;
    color: #aaa;
    margin-bottom: 10px;
}

.status-section {
    margin-bottom: 15px;
}


.actions {
    display: flex;
    justify-content: space-between;
    gap: 10px;
}

.actions .btn {
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    font-size: 0.9em;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: background 0.3s, transform 0.2s;
}

.actions .edit {
    background: #4a90e2;
    color: white;
}

.actions .done {
    background: #5cb85c;
    color: white;
}

.actions .btn:hover {
    transform: translateY(-2px);
    opacity: 0.9;
}

.no-tasks {
    text-align: center;
    font-style: italic;
    color: #777;
    font-size: 1.1em;
    margin-top: 30px;
}

</Style>

    <!-- Add Task Modal (For All Users) -->
    <div id="addTaskModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddTaskModal()">&times;</span>
            <h2>Add New Task</h2>
            <form id="addTaskForm" method="POST">
                <input type="hidden" name="add_task" value="1">
                <label for="title">Title:</label>
                <input type="text" id="title" name="title" placeholder="Task Title" required>
                <label for="description">Description:</label>
                <textarea id="description" name="description" placeholder="Task Description" required></textarea>
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>
                <!-- Show Status Field Only for Admins -->
                <?php if ($isAdmin): ?>
                    <label for="status">Status:</label>
                    <select name="status" id="status" required>
                        <option value="pending">pending</option>
                        <option value="in-progress">in-progress</option>
                        <option value="done">done</option>
                    </select>
                    
                <?php endif; ?>
                <!-- Submit Button -->
                <div class="modal-actions">
                    <button type="submit" class="status-tag add">
                        <i class="fas fa-check"></i>
                    </button>
                    <button type="button" class="status-tag cancel" onclick="closeAddTaskModal()">
                        <i class="fas fa-times"></i> Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Open Add Task Modal
        function openAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'block';
        }

        // Close Add Task Modal
        function closeAddTaskModal() {
            document.getElementById('addTaskModal').style.display = 'none';
        }

        // Client-Side Validation
document.querySelector('#addTaskForm').addEventListener('submit', function (event) {
    const title = document.getElementById('title').value.trim();
    const description = document.getElementById('description').value.trim();
    const startDate = document.getElementById('start_date').value;
    const endDate = document.getElementById('end_date').value;

    const clientName = document.getElementById('client_name').value.trim();
    const clientAddress = document.getElementById('client_address').value.trim();
    const clientPhone = document.getElementById('client_phone').value.trim();
    const clientEmail = document.getElementById('client_email').value.trim();

          // Get today's date in YYYY-MM-DD format
            const today = new Date().toISOString().split('T')[0];

              if (!title || !description || !startDate || !endDate || !clientName || !clientAddress || !clientPhone || !clientEmail) {
        alert('Please fill out all fields.');
        event.preventDefault();
        return;
    }

            if (!title || !description || !startDate || !endDate) {
                alert('Please fill out all fields.');
                event.preventDefault(); // Prevent form submission
                return;
            }
            if (startDate < today) {
                alert('Start date cannot be in the past.');
                event.preventDefault(); // Prevent form submission
                return;
            }
            if (endDate < today) {
                alert('End date cannot be in the past.');
                event.preventDefault(); // Prevent form submission
                return;
            }
            if (endDate < startDate) {
                alert('End date cannot be earlier than the start date.');
                event.preventDefault(); // Prevent form submission
                return;
            }
        });

        // Inline Editing for Status
        document.addEventListener('DOMContentLoaded', function () {
            const statusDropdowns = document.querySelectorAll('.editable-status');
            statusDropdowns.forEach(dropdown => {
                dropdown.addEventListener('change', function () {
                    const taskId = this.dataset.taskId;
                    const newStatus = this.value;

                    // Send AJAX request to update the status
                    fetch('update_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            task_id: taskId,
                            status: newStatus
                        })
                    })
                        .then(response => {
                            if (!response.ok) {
                                throw new Error('Network response was not ok');
                            }
                            return response.json();
                        })
                        .then(data => {
                            if (data.success) {
                                alert('Status updated successfully!');
                            } else {
                                throw new Error(data.error || 'Failed to update status.');
                            }
                        })
                        .catch(error => {
                            console.error('Error updating status:', error);
                            alert('An error occurred while updating the status.');
                            // Revert to the previous status
                            this.value = this.dataset.currentStatus;
                        });
                });
            });
        });
    </script>
</body>
</html>

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