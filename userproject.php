<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify user is logged in
if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Determine if the user is an admin
$isAdmin = isAdmin();

// Make sure your database connection ($db) and sanitizeInput() function are defined above this block.
// Also, ensure $_SESSION is started (session_start();)

// Handle form submission (Add Task) for all users
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    // Sanitize and retrieve common task details
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    // Retrieve new client details from the POST request
    // Using null coalescing operator (?? '') to prevent "Undefined array key" warnings
    // if these fields somehow aren't submitted by the form.
    $clientName = sanitizeInput($_POST['client_name'] ?? '');
    $clientAddress = sanitizeInput($_POST['client_address'] ?? '');
    $clientPhone = sanitizeInput($_POST['client_phone'] ?? '');
    $clientEmail = sanitizeInput($_POST['client_email'] ?? '');

    // Default status for non-admin users
    // Ensure $isAdmin is defined (e.g., from a session or user role check)
    $status = $isAdmin ? $_POST['status'] : 'pending'; // Non-admin tasks default to "pending"

    // Automatically assign the task to the logged-in user
    $userId = $_SESSION['user_id']; // Logged-in user's ID

    // Server-Side Date Validation
    $today = date('Y-m-d'); // Today's date in YYYY-MM-DD format

    if ($startDate < $today) {
        $_SESSION['error'] = "Start date cannot be in the past.";
        header("Location: userproject.php");
        exit();
    }

    if ($endDate < $today) {
        $_SESSION['error'] = "End date cannot be in the past.";
        header("Location: userproject.php");
        exit();
    }

    if ($endDate < $startDate) {
        $_SESSION['error'] = "End date cannot be earlier than the start date.";
        header("Location: userproject.php");
        exit();
    }

    // Check if the date difference is at least 5 days
    $start = new DateTime($startDate);
    $end = new DateTime($endDate);
    $interval = $start->diff($end)->days;

    if ($interval < 5) {
        $_SESSION['error'] = "The minimum days for a project to complete is 5 days.";
        header("Location: userproject.php");
        exit();
    }

    try {
        // Insert the task and new client details into the database
        $stmt = $db->prepare("
            INSERT INTO tasks (
                title,
                description,
                client,         /* This column likely stores the user/client ID */
                start_date,
                end_date,
                status,
                client_name,    /* New client details column */
                client_address, /* New client details column */
                client_phone,   /* New client details column */
                client_email    /* New client details column */
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $title,
            $description,
            $userId, // Value for the 'client' column (assuming it's a user ID)
            $startDate,
            $endDate,
            $status,
            $clientName,    // Value for client_name
            $clientAddress, // Value for client_address
            $clientPhone,   // Value for client_phone
            $clientEmail    // Value for client_email
        ]);

        // Set success message
        $_SESSION['success'] = "Task added successfully!";
    } catch (PDOException $e) {
        // Log the error for debugging (check your server's error logs)
        error_log("Task addition failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add task. Please try again.";
    }

    // Redirect to refresh the page regardless of success or failure
    header("Location: userproject.php");
    exit();
}

// Handle search
$search = $_GET['search'] ?? '';
$whereClause = $search ? "AND title LIKE :search OR description LIKE :search" : "";
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
    <link rel="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" href="projsect.css">
</head>
<body>
    <!-- Include the Sidebar -->
    <?php include 'user_sidebar.php'; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="modal-message success">
        <div class="checkmark-wrapper">
            <svg class="checkmark" viewBox="0 0 52 52">
                <circle class="checkmark-circle" cx="26" cy="26" r="25" fill="none"/>
                <path class="checkmark-check" fill="none" d="M14 27l7 7 17-17"/>
            </svg>
        </div>
        <p class="modal-subtext">Task Added successfully! This tab will close automatically.</p> 
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>


<script>
    setTimeout(() => {
        const modal = document.querySelector('.modal-message');
        if (modal) modal.style.display = 'none';
        window.close(); // optionally closes the tab if this is a popup
    }, 4000);
</script>

<style>
    
.modal-message {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border: 2px solid #8a0000;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.2);
    z-index: 9999;
    text-align: center;
    animation: fadeIn 0.4s ease-in-out;
    width: 90%;
    max-width: 420px;

}

.checkmark-wrapper {
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px;
    animation: bounceIn 0.6s ease-in-out;
}

.checkmark {
    width: 100px;
    height: 100px;
    stroke:   #28a745;
    stroke-width: 5;
    stroke-linecap: round;
    stroke-linejoin: round;
    fill: none;
    animation: popIn 0.5s ease-in-out;
    filter: drop-shadow(0 0 10px rgba(16, 185, 129, 0.4));
}

.checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: strokeCircle 0.6s ease-in-out forwards;
}

.checkmark-check {
    stroke-dasharray: 50;
    stroke-dashoffset: 50;
    animation: strokeCheck 0.4s 0.5s ease-in-out forwards;
}

.modal-subtext {
    font-size: 16px;
    color:black;
    font-weight: 500;
    margin-top: 10px;
}

/* Animations */
@keyframes strokeCircle {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes strokeCheck {
    to {
        stroke-dashoffset: 0;
    }
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translate(-50%, -60%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, -50%);
    }
}

@keyframes bounceIn {
    0% {
        transform: scale(0.3);
    }
    50% {
        transform: scale(1.1);
    }
    70% {
        transform: scale(0.9);
    }
    100% {
        transform: scale(1);
    }
}

@keyframes popIn {
    from {
        transform: scale(0);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}
</style>

<!-- Main Header -->
<header class="main-header">
    <div class="header-content">
        <div>
        <h1 style="font-size: 40px;">Projects</h1>

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
    height: 120px;
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

    

    <div class="main-content1">
        <h2>Add Task</h2>
        <div class="main-content-add-project">
            <button class="add-button status-tag add" onclick="openAddTaskModal()">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    </div>

<style>

.main-content-wrapper {
    display: flex;
    gap: 30px; /* space between the boxes */
    flex-wrap: wrap; /* make it responsive */
    justify-content: center; /* center the boxes horizontally */
    margin-bottom: 20px;
}


.main-content1 {
    width: 20%; /* Ensure 4 in a row */
    background-color: #f9f9f9;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.05);
    box-sizing: border-box;
    margin: 20px 0;
    text-align: center;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}


.main-content-add-project {
    margin-top: 15px;
}


.add-button.status-tag.add {
    background-color: #28a745; /* Green */
    color: white;
    border: none;
    border-radius: 8px;
    padding: 12px 20px;
    font-size: 16px;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transition: background-color 0.2s, transform 0.2s;

}

.add-button.status-tag.add i {
    font-size: 100px; /* Increase size here */
}

.add-button.status-tag.add:hover {
    background-color: #218838;
    transform: scale(1.05);
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

<Style>
    /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Antonio', sans-serif;
}

.main-content {
    transition: margin-left 0.3s ease;
}

</Style>

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
      data-id="<?= htmlspecialchars($task['task_id']) ?>" 
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
    document.getElementById('modalTitle').innerText = title; // This usually shows the task title
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

    <script>


// Open Add Task Modal
function openAddTaskModal() {
    document.getElementById('addTaskModal').style.display = 'block';
    
    // Set min dates when modal opens
    const today = new Date().toISOString().split('T')[0];
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    
    // Disable past dates for start date
    startDateInput.min = today;
    
    // When start date changes, update end date constraints
    startDateInput.addEventListener('change', function() {
        const startDate = new Date(this.value);
        const minEndDate = new Date(startDate);
        
        // End date must be at least 14 days after start date
        minEndDate.setDate(minEndDate.getDate() + 14);
        
        // Format date to YYYY-MM-DD
        const minEndDateStr = minEndDate.toISOString().split('T')[0];
        
        // Set end date minimum (no maximum)
        endDateInput.min = minEndDateStr;
        endDateInput.max = ''; // Remove any maximum constraint
        
        // Reset end date if it's before the new minimum
        if (endDateInput.value && endDateInput.value < minEndDateStr) {
            endDateInput.value = '';
        }
    });
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
    
    // Check if end date is at least 14 days after start date
    const startDateObj = new Date(startDate);
    const endDateObj = new Date(endDate);
    const diffTime = endDateObj - startDateObj;
    const diffDays = diffTime / (1000 * 60 * 60 * 24); 
    
    if (diffDays < 14) {
        alert('End date must be at least 14 days after the start date.');
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

<Style>
    /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Antonio', sans-serif;
}

/* Success/Error Messages */
.success-message,
.error-message {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
.success-message {
    background: #90EE90; /* Green for success */
    color: white;
}
.error-message {
    background: #ff4444; /* Red for errors */
    color: white;
}

/* Add Task Button */
.add-button {
    background: #00ff88; /* Green for add task */
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    margin-bottom: 20px;
    display: inline-block;
    transition: background 0.3s ease;
}
.add-button:hover {
    background:rgb(1, 173, 87);
}

/* Search Form */
.search-form {
    display: flex;
    gap: 10px;
    margin-top: 20px;
    margin-bottom: auto;
}
.search-form input {
    flex: 1;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: black;
}
.search-form button {
    color: white;
    padding: 10px 20px;
    margin-top: auto;
    margin-bottom: auto;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.search-form button:hover {
    background: #cc0000;
}

/* Table Styling */
.admin-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background: rgb(235, 212, 212) 50%;
    border-radius: 10px;
    overflow: hidden;
    font-size: 13px;
}
.admin-table th,
.admin-table td {
    padding: 15px;
    text-align: left;
    margin: auto;   

}
.admin-table th {
    background:    #8a0000 50%;
    text-align: center;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: whitesmoke;
}
.admin-table tr:hover {
    background: rgba(255, 255, 255, 0.1); /* Red hover effect */
}



/* Modals */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7);
    z-index: 1000;
}
.modal-content {
    background: white;
    margin: 5% auto;
    padding: 20px;
    width: 800px;
    max-width: 90%;
    border-radius: 10px;
    position: relative;
    max-height: 80vh;
    overflow-y: auto;
}
.modal-content .close {
    position: absolute;
    top: 10px;
    right: 15px;
    font-size: 24px;
    cursor: pointer;
    color: #8a0000;
}
.modal-content .close:hover {
    color: #8a0000;
}
.modal-content h2 {
    margin-bottom: 20px;
    color:white;
}
.modal-content form label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
}
.modal-content form input,
.modal-content form textarea,
.modal-content form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 8px;
}
.modal-content form button {
    background: green;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 20px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.modal-content form button:hover {
    background-color: darkred;
}

/* Responsive Design */
@media (max-width: 1024px) {
    body {
        flex-direction: column;
        align-items: center;
        padding: 10px;
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
        font-size: 14px;
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
.status-tag.done {
    background-color: #28a745; /* Green color */    
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    text-decoration: none;
    cursor: pointer;
    font-size: 10px;
}

.status-tag.done:hover {
    background-color:whitesmoke;
    color:  #28a745;
}
</Style>