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
            <h1>Analytics</h1>
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
    <i class="fas fa-cogs"></i>
    <span style="font-size: 12px; margin-top: 6px;">Settings</span>
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
                        <option value="to-do">to-do</option>
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

            // Get today's date in YYYY-MM-DD format
            const today = new Date().toISOString().split('T')[0];

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

<style>
/* Wrapper for the entire layout */
.main-wrapper2 {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    padding: 20px;
}

/* Left Content */
.main-content-left2 {
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

.main-content-left2:hover{
    transform: translateY(-5px);
    box-shadow: 0 12px 30px rgba(0,0,0,0.15);
}

/* Right Content */
.main-content-right2 {
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
.main-content-right2:hover{
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
