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
    <!-- FullCalendar CSS -->
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.css' rel='stylesheet' />
<!-- FullCalendar JS -->
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/main.min.js'></script>




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
        <h1>Calendar</h1>

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
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .calendar-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1000px;
            padding: 20px;
            box-sizing: border-box;
            margin: 20px auto;
            overflow: hidden;
            user-select: none;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 24px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .calendar-header button {
            font-size: 1.5rem;
            background:#8a0000;
            border: none;
            border-radius: 6px;
            color: white;
            padding: 6px 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            user-select: none;
        }
        .calendar-header button:hover {
            background: #D4AF37;
        }
        #monthYear {
            flex-grow: 1;
            text-align: center;
            color: #333;
            min-width: 150px;
            font-weight: 600;
            margin: 10px 0;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        .day-name {
            text-align: center;
            font-weight: bold;
            padding: 10px 0;
            background: #8a0000;
            color: white;
            border-radius: 6px;
            user-select: none;
        }
        .day-cell {
            text-align: center;
            padding: 12px 6px 8px 6px;
            background: #f9f9f9;
            cursor: pointer;
            border-radius: 10px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            box-sizing: border-box;
            transition: background-color 0.25s ease;
            position: relative;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .day-cell:hover {
            background:#D4AF37;
        }
        .current-day {
            background:#D4AF37;
            box-shadow: 0 0 8px 3px #D4AF37;
            font-weight: bold;
        }
        .note {
            margin-top: 4px;
            padding: 6px 8px;
            background: #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
            word-break: break-word;
            flex-grow: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #8a0000;
        }
        .note-text {
            flex-grow: 1;
            text-align: left;
            overflow-wrap: break-word;
        }
        .delete-note {
            cursor: pointer;
            color: red;
            font-weight: bold;
            margin-left: 8px;
            user-select: none;
            border: none;
            background: transparent;
            font-size: 14px;
            line-height: 1;
            padding: 0;
            min-width: auto;
        }
        @media (max-width: 650px) {
            .calendar-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .day-name {
                font-size: 14px;
                padding: 6px 0;
            }
            .day-cell {
                min-height: 70px;
                padding: 10px 5px 6px 5px;
            }
            .calendar-header {
                font-size: 20px;
            }
            .calendar-header button {
                font-size: 1.25rem;
                padding: 5px 12px;
            }
            #monthYear {
                margin: 6px 0;
                min-width: unset;
                font-size: 18px;
            }
        }
        @media (max-width: 380px) {
            .calendar-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .day-name {
                display: none;
            }
            .day-cell {
                min-height: 60px;
                font-size: 14px;
                padding: 8px 4px 6px 4px;
            }
        }

        /* Modal styles */
        .modal {
    display: none; /* hidden by default */
    position: fixed;
    z-index: 9999;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0,0,0,0.5);
    user-select: text;
}

.modal.modal-show {
    display: flex; /* switch to flex to center */
    align-items: center;
    justify-content: center; 
}
.modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 12px;
    max-width: 400px;
    box-sizing: border-box;
    position: relative;
    box-shadow: 0 8px 16px rgba(0,0,0,0.3);
}
/* rest of your modal-content styles unchanged */
.modal-content h2 {
    margin-top: 0;
    margin-bottom: 15px;
    font-weight: 600;
    color: #333;
}
.modal-textarea {
    width: 100%;
    height: 120px;
    resize: vertical;
    padding: 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-sizing: border-box;
    font-family: inherit;
    margin-bottom: 20px;
}
.modal-buttons {
    text-align: right;
}
.modal-buttons button {
    font-size: 1rem;
    border: none;
    border-radius: 8px;
    padding: 8px 18px;
    margin-left: 10px;
    cursor: pointer;
    user-select: none;
    transition: background-color 0.25s ease;
}
.modal-buttons button.save-btn {
    background-color: green;
    color: white;
    transition: transform 0.2s ease-in-out; /* Smooth transition */
}

.modal-buttons button.save-btn:hover {
    background-color: darkgreen; /* Optional: Change background color on hover */
    transform: scale(1.1); /* Scale the button slightly */
}

.modal-buttons button.cancel-btn {
    background-color: #8a0000;
    color: white;
    transition: transform 0.2s ease-in-out; /* Smooth transition */
}
.modal-buttons button.cancel-btn:hover {
    background-color:rgb(101, 0, 0);
    color: white;
    transform: scale(1.1); /* Scale the button slightly */
}
.modal-buttons button.delete-btn {
    background-color: red;
    color: white;
    float: left;
    transition: transform 0.2s ease-in-out; /* Smooth transition */
}
.modal-buttons button.delete-btn:hover {
    color: white;
    transform: scale(1.1); /* Scale the button slightly */
}
/* Close icon */
.modal-close {
    position: absolute;
    top: 12px;
    right: 15px;
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    user-select: none;
    transition: transform 0.3s ease-in-out; /* Smooth transition for the spin */
}

.modal-close:hover {
    color: #8a0000;
    transform: rotate(180deg); /* Apply a 180-degree spin */
}

    </style>
</head>
<body>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .calendar-container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 95%;
            max-width: 1000px;
            padding: 20px;
            box-sizing: border-box;
            margin: 20px auto;
            overflow: hidden;
            user-select: none;
        }
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 24px;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .calendar-header button {
            font-size: 1.5rem;
            background: #8a0000;
            border: none;
            border-radius: 6px;
            color: white;
            padding: 6px 15px;
            cursor: pointer;
            transition: background 0.3s ease;
            user-select: none;
        }
        .calendar-header button:hover {
            background: #D4AF37;
        }
        #monthYear {
            flex-grow: 1;
            text-align: center;
            color: #333;
            min-width: 150px;
            font-weight: 600;
            margin: 10px 0;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 8px;
        }
        .day-name {
            text-align: center;
            font-weight: bold;
            padding: 10px 0;
            background: #8a0000;
            color: white;
            border-radius: 6px;
            user-select: none;
        }
        .day-cell {
            text-align: center;
            padding: 12px 6px 8px 6px;
            background: #f9f9f9;
            cursor: pointer;
            border-radius: 10px;
            min-height: 80px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            align-items: center;
            box-sizing: border-box;
            transition: background-color 0.25s ease;
            position: relative;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        .day-cell:hover {
            background: #e6f0ff;
        }
        .current-day {
            background: #D4AF37;
            box-shadow: 0 0 8px 3px #D4AF37;
            font-weight: bold;
        }
        .day-cell.past-day {
            background: #ddd;
            color: #999;
            cursor: default;
        }
        .day-cell.past-day:hover {
            background: #ddd;
        }
        .note {
            margin-top: 4px;
            padding: 6px 8px;
            background: #e0e0e0;
            border-radius: 6px;
            font-size: 13px;
            width: 100%;
            word-break: break-word;
            flex-grow: 1;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .note-text {
            flex-grow: 1;
            text-align: left;
            overflow-wrap: break-word;
        }
        .delete-note {
            cursor: pointer;
            color: red;
            font-weight: bold;
            margin-left: 8px;
            user-select: none;
            border: none;
            background: transparent;
            font-size: 14px;
            line-height: 1;
            padding: 0;
            min-width: auto;
        }
        @media (max-width: 650px) {
            .calendar-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .day-name {
                font-size: 14px;
                padding: 6px 0;
            }
            .day-cell {
                min-height: 70px;
                padding: 10px 5px 6px 5px;
            }
            .calendar-header {
                font-size: 20px;
            }
            .calendar-header button {
                font-size: 1.25rem;
                padding: 5px 12px;
            }
            #monthYear {
                margin: 6px 0;
                min-width: unset;
                font-size: 18px;
            }
        }
        @media (max-width: 380px) {
            .calendar-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 6px;
            }
            .day-name {
                display: none;
            }
            .day-cell {
                min-height: 60px;
                font-size: 14px;
                padding: 8px 4px 6px 4px;
            }
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.5);
            user-select: text;
            align-items: center;
            justify-content: center;
            display: flex;
        }
        .modal.modal-hide {
            display: none !important;
        }
        .modal-content {
            background-color: #fff;
            padding: 20px;
            border-radius: 12px;
            max-width: 400px;
            box-sizing: border-box;
            position: relative;
            box-shadow: 0 8px 16px rgba(0,0,0,0.3);
        }
        .modal-content h2 {
            margin-top: 0;
            margin-bottom: 15px;
            font-weight: 600;
            color: #333;
        }
        .modal-textarea {
            width: 100%;
            height: 120px;
            resize: vertical;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
            margin-bottom: 20px;
        }
        .modal-buttons {
            text-align: right;
        }
        .modal-buttons button {
            font-size: 1rem;
            border: none;
            border-radius: 8px;
            padding: 8px 18px;
            margin-left: 10px;
            cursor: pointer;
            user-select: none;
            transition: background-color 0.25s ease;
        }
        .modal-buttons button.save-btn {
    background-color: green;
    color: white;
    transition: transform 0.2s ease-in-out; /* Smooth transition */
}

.modal-buttons button.save-btn:hover {
    background-color: darkgreen; /* Optional: Change background color on hover */
    transform: scale(1.1); /* Scale the button slightly */
}

        .modal-buttons button.cancel-btn {
            background-color: #8a0000;
            color: white;
            transition: transform 0.2s ease-in-out; /* Smooth transition */
        }
        .modal-buttons button.cancel-btn:hover {
            background-color:rgb(108, 1, 1);
            transform: scale(1.1); /* Scale the button slightly */
        }
        .modal-buttons button.delete-btn {
            background-color:red;
            color: white;
            float: left;
            transition: transform 0.2s ease-in-out; /* Smooth transition */
        }
        .modal-buttons button.delete-btn:hover {
            transform: scale(1.1); /* Scale the button slightly */
        }
   /* Close icon */
.modal-close {
    position: absolute;
    top: 12px;
    right: 15px;
    font-size: 24px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
    user-select: none;
    transition: transform 0.3s ease-in-out; /* Smooth transition for the spin */
}

.modal-close:hover {
    color: #333;
    transform: rotate(180deg); /* Apply a 180-degree spin */
}

    </style>
</head>
<body>

<div class="calendar-container" role="application" aria-label="Project Calendar Planner">
    <div class="calendar-header">
        <button id="prevMonth" aria-label="Previous Month">&lt;</button>
        <div id="monthYear" aria-live="polite" aria-atomic="true"></div>
        <button id="nextMonth" aria-label="Next Month">&gt;</button>
    </div>
    <div class="calendar-grid" id="calendarGrid" tabindex="0">
        <!-- Day names will be inserted here -->
    </div>
</div>

<!-- Modal for note editing -->
<div id="noteModal" class="modal modal-hide" role="dialog" aria-modal="true" aria-labelledby="modalTitle" aria-describedby="modalDesc">
    <div class="modal-content">
        <span class="modal-close" id="modalCloseBtn" role="button" aria-label="Close modal">&times;</span>
        <h2 id="modalTitle">Add/Edit Note</h2>
        <textarea id="modalTextarea" class="modal-textarea" aria-describedby="modalDesc" placeholder="Enter your note here..."></textarea>
        <div class="modal-buttons">
            <button class="delete-btn" id="modalDeleteBtn" aria-label="Delete note">Delete</button>
            <button class="cancel-btn" id="modalCancelBtn">Cancel</button>
            <button class="save-btn" id="modalSaveBtn">Save</button>
        </div>
        <div id="modalDesc" style="display:none;">Use the textarea to enter or edit your note. Click Save to save, Cancel to discard changes, or Delete to remove the note.</div>
    </div>
</div>

<script>
    const calendarGrid = document.getElementById('calendarGrid');
    const monthYear = document.getElementById('monthYear');
    const prevMonthBtn = document.getElementById('prevMonth');
    const nextMonthBtn = document.getElementById('nextMonth');

    const modal = document.getElementById('noteModal');
    const modalCloseBtn = document.getElementById('modalCloseBtn');
    const modalTextarea = document.getElementById('modalTextarea');
    const modalSaveBtn = document.getElementById('modalSaveBtn');
    const modalCancelBtn = document.getElementById('modalCancelBtn');
    const modalDeleteBtn = document.getElementById('modalDeleteBtn');

    const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    // Insert day names once
    function insertDayNames() {
        dayNames.forEach(day => {
            const dayName = document.createElement('div');
            dayName.classList.add('day-name');
            dayName.textContent = day;
            dayName.setAttribute('aria-hidden', 'true');
            calendarGrid.appendChild(dayName);
        });
    }
    insertDayNames();

    let today = new Date();
    let currentMonth = today.getMonth();
    let currentYear = today.getFullYear();
    const todayDate = today.getDate();
    const todayMonth = today.getMonth();
    const todayYear = today.getFullYear();

    let notes = JSON.parse(localStorage.getItem('notes')) || {};

    function isDatePast(year, month, day) {
        // Create date objects only with Y,M,D to compare date only ignoring time
        const date = new Date(year, month, day);
        const now = new Date(todayYear, todayMonth, todayDate);
        return date < now;
    }

    // Helper: format keys consistently with leading zeros
    function formatDateKey(year, month, day) {
        const mm = String(month + 1).padStart(2, '0');
        const dd = String(day).padStart(2, '0');
        return `${year}-${mm}-${dd}`;
    }

    // Variables to keep track of the currently edited cell date
    let currentEditingKey = null;
    let currentEditingMonth = null;
    let currentEditingYear = null;
    let currentEditingDay = null;

    function renderCalendar(month, year) {
        // Remove previous day cells but keep day-name headers
        calendarGrid.querySelectorAll('.day-cell, .empty-cell').forEach(e => e.remove());

        // Show current viewed month and year
        const dateForMonth = new Date(year, month);
        const localeMonth = dateForMonth.toLocaleString('default', { month: 'long' });

        monthYear.textContent = `${localeMonth} ${year}`;

        const firstDayOfMonth = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Add empty cells for alignment before the first day
        for (let i = 0; i < firstDayOfMonth; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('empty-cell');
            calendarGrid.appendChild(emptyCell);
        }

        // Add day cells with notes and event handlers
        for (let day = 1; day <= daysInMonth; day++) {
            const cell = document.createElement('div');
            cell.classList.add('day-cell');
            const dateKey = formatDateKey(year, month, day);
            const past = isDatePast(year, month, day);

            // Highlight current day only if calendar is on current month/year
            if (year === todayYear && month === todayMonth && day === todayDate) {
                cell.classList.add('current-day');
                cell.setAttribute('aria-current', 'date');
            }

            if (past) {
                cell.classList.add('past-day');
            }

            // Day number
            const dayNumber = document.createElement('div');
            dayNumber.classList.add('day-number');
            dayNumber.textContent = day;
            cell.appendChild(dayNumber);

            // Show note if exists
            if (notes[dateKey]) {
                // Note container with text
                const noteDiv = document.createElement('div');
                noteDiv.classList.add('note');

                const noteText = document.createElement('div');
                noteText.classList.add('note-text');
                noteText.textContent = notes[dateKey];
                noteDiv.appendChild(noteText);

                cell.appendChild(noteDiv);
            }

            // Click handler for opening modal for add/edit note IF not past day
            if (!past) {
                cell.addEventListener('click', () => {
                    currentEditingKey = dateKey;
                    currentEditingYear = year;
                    currentEditingMonth = month;
                    currentEditingDay = day;
                    const existingNote = notes[dateKey] || '';
                    modalTextarea.value = existingNote;
                    updateModalDeleteButton(existingNote);
                    openModal();
                });
            }

            calendarGrid.appendChild(cell);
        }

        // Fill empty cells after last day to complete the last week row
        const totalCells = firstDayOfMonth + daysInMonth;
        const cellsToAdd = (7 - (totalCells % 7)) % 7;
        for (let i = 0; i < cellsToAdd; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.classList.add('empty-cell');
            calendarGrid.appendChild(emptyCell);
        }
    }

    function openModal() {
        modal.classList.remove('modal-hide');
        modal.style.display = 'flex';
        modalTextarea.focus();
        trapFocus(modal);
    }

    function closeModal() {
        modal.classList.add('modal-hide');
        modal.style.display = 'none';
        currentEditingKey = null;
        currentEditingMonth = null;
        currentEditingYear = null;
        currentEditingDay = null;
    }

    // Update the delete button visibility based on note content
    function updateModalDeleteButton(note) {
        if (note && note.length > 0) {
            modalDeleteBtn.style.display = 'inline-block';
        } else {
            modalDeleteBtn.style.display = 'none';
        }
    }

    // Save note from modal
    function saveNote() {
        const note = modalTextarea.value.trim();
        if (note === '') {
            if (notes[currentEditingKey]) {
                delete notes[currentEditingKey];
            }
        } else {
            notes[currentEditingKey] = note;
        }
        localStorage.setItem('notes', JSON.stringify(notes));
        renderCalendar(currentEditingMonth, currentEditingYear);
        closeModal();
    }

    // Delete note from modal
    function deleteNote() {
        if (confirm('Are you sure you want to delete this note?')) {
            if (notes[currentEditingKey]) {
                delete notes[currentEditingKey];
                localStorage.setItem('notes', JSON.stringify(notes));
                renderCalendar(currentEditingMonth, currentEditingYear);
                closeModal();
            }
        }
    }

    // Event listeners for modal buttons and close icon
    modalSaveBtn.addEventListener('click', saveNote);
    modalCancelBtn.addEventListener('click', () => {
        closeModal();
    });
    modalCloseBtn.addEventListener('click', () => {
        closeModal();
    });
    modalDeleteBtn.addEventListener('click', deleteNote);

    // Close modal on clicking outside modal content
    window.addEventListener('click', (event) => {
        if (event.target === modal) {
            closeModal();
        }
    });

    // Close modal on escape key
    window.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !modal.classList.contains('modal-hide')) {
            closeModal();
        }
    });

    prevMonthBtn.addEventListener('click', () => {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar(currentMonth, currentYear);
    });

    nextMonthBtn.addEventListener('click', () => {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentMonth, currentYear);
    });

    // Keyboard navigation for accessibility
    calendarGrid.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowLeft') {
            prevMonthBtn.click();
            e.preventDefault();
        } else if (e.key === 'ArrowRight') {
            nextMonthBtn.click();
            e.preventDefault();
        }
    });

    // Focus trap helper to keep focus inside modal
    function trapFocus(element) {
        const focusableElements = element.querySelectorAll('button, textarea, [tabindex]:not([tabindex="-1"])');
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];

        function handleTab(e) {
            if (e.key === 'Tab') {
                if (e.shiftKey) { // shift + tab
                    if (document.activeElement === firstFocusable) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else { // tab
                    if (document.activeElement === lastFocusable) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        }

        element.addEventListener('keydown', handleTab);

        // clean up event listener when modal closed
        function cleanup() {
            element.removeEventListener('keydown', handleTab);
            element.removeEventListener('transitionend', cleanup);
        }
        element.addEventListener('transitionend', cleanup);

        firstFocusable.focus();
    }

    // Initial render
    renderCalendar(currentMonth, currentYear);

</script>

</body>
</html>



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