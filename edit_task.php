<?php
session_start();
require 'connect.php';
require 'functions.php';

// Verify admin access
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

$taskId = $_GET['id'] ?? null;

if (!$taskId) {
    $_SESSION['error'] = "Invalid task ID.";
    header("Location: project.php");
    exit();
}

try {
    // Fetch task data
    $stmt = $db->prepare("SELECT * FROM tasks WHERE task_id = ?");
    $stmt->execute([$taskId]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch as associative array

    if (!$task) {
        $_SESSION['error'] = "Task not found.";
        header("Location: project.php");
        exit();
    }
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    die("An error occurred. Please try again.");
}

// --- NEW DELETE TASK LOGIC ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && $taskId) {
    try {
        $stmt = $db->prepare("DELETE FROM tasks WHERE task_id = ?");
        $stmt->execute([$taskId]);

        $_SESSION['success'] = "Task deleted successfully!";
        header("Location: project.php"); // Redirect after deletion
        exit();
    } catch (PDOException $e) {
        error_log("Delete failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete task. Please try again.";
        header("Location: edit_task.php?id=" . $taskId); // Redirect back to edit page with error
        exit();
    }
}
// --- END NEW DELETE TASK LOGIC ---


// Handle form submission (UPDATE task)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $clientName = sanitizeInput($_POST['client_name'] ?? '');       // Get client name
    $clientAddress = sanitizeInput($_POST['client_address'] ?? ''); // Get client address
    $clientPhone = sanitizeInput($_POST['client_phone'] ?? '');     // Get client phone
    $clientEmail = sanitizeInput($_POST['client_email'] ?? '');     // Get client email
    $startDate = $_POST['start_date'];                        // Get start date
    $endDate = $_POST['end_date'];                            // Get end date
    $status = sanitizeInput($_POST['status'] ?? ($task['status'] ?? 'pending')); // Get status (if editable), default to existing or 'pending'

    try {
        $stmt = $db->prepare("
            UPDATE tasks
            SET
                title = ?,
                description = ?,
                client_name = ?,
                client_address = ?,
                client_phone = ?,
                client_email = ?,
                start_date = ?,
                end_date = ?,
                status = ?
            WHERE task_id = ?
        ");
        $stmt->execute([
            $title,
            $description,
            $clientName,
            $clientAddress,
            $clientPhone,
            $clientEmail,
            $startDate,
            $endDate,
            $status,
            $taskId
        ]);
        $_SESSION['success'] = "Updated successfully!";
        header("Location: project.php");
        exit();
    } catch (PDOException $e) {
        error_log("Update failed: " . $e->getMessage());
        $_SESSION['error'] = "Update failed. Please try again.";
        header("Location: edit_task.php?id=" . $taskId); // Redirect back to edit page with error
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Task</title>
    <link rel="stylesheet" href="admin_dashboard.css">
    <style>
        /* New Modal Styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex; /* Use flexbox for centering */
            justify-content: center; /* Center horizontally */
            align-items: center; /* Center vertically */
            min-height: 100vh; /* Full viewport height */
            background-color: rgba(0, 0, 0, 0.7); /* Dark overlay effect */
        }

        .main-content {
            /* This div now acts as the modal content box */
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            width: 90%; /* Adjust width as needed */
            max-width: 600px; /* Max width for larger screens */
            box-sizing: border-box; /* Include padding in width */
            position: relative; /* For potential close button positioning */
            margin: 20px auto; /* Adjust margin for smaller screens to ensure it doesn't touch edges */
        }

        h1 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 28px;
        }

        /* Form Styling */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px; /* Spacing between form elements */
        }

        label {
            font-weight: bold;
            color: #555;
            margin-bottom: -10px; /* Pull label closer to input */
        }

        input[type="text"],
        input[type="email"],
        input[type="date"],
        textarea,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box; /* Important for width calculation */
        }

        textarea {
            resize: vertical; /* Allow vertical resizing */
            min-height: 80px;
        }
/* Base Button Styles */
button[type="submit"],
.btn { /* This applies to all buttons and anchor tags with class 'btn' */
    padding: 12px 25px; /* Generous padding */
    border: none;
    border-radius: 6px; /* Slightly more rounded corners */
    cursor: pointer;
    font-size: 16px;
    font-weight: bold; /* Make text bolder */
    margin-top: 10px; /* Space above each button */
        transition: background-color 0.3s ease,
                color 0.3s ease,      /* Add if text color changes */
                border-color 0.3s ease, /* Add if border color changes (e.g., for outlined buttons) */
                transform 0.3s ease,  /* Make transform transition smoother for hover */
                opacity 0.3s ease,
                box-shadow 0.3s ease; /* Add transition for box-shadow */
    text-decoration: none; /* For anchor tags acting as buttons */
    text-align: center;
    display: inline-block; /* Essential for anchor tags to behave like block buttons */
    width: auto; /* Buttons size based on content */
    box-sizing: border-box; /* Include padding in width */
}

/* Hover Effects (apply to all buttons) */
button[type="submit"]:hover,
.btn:hover {
    opacity: 0.9; /* Slight dimming on hover */
    transform: translateY(-3px); /* Lifts the button slightly */
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.2); /* Adds a shadow for a "lifting" effect */
    /* background-color changes will be specific to each button type */
}

button[type="submit"]:active,
.btn:active {
    transform: translateY(2px); /* Subtle press effect */
}

/* --- Specific Button Colors --- */

/* Primary Action: Save Changes */
button[type="submit"] {
    background-color:   #28a745;
    color: white;
}

/* Danger Action: Delete Task */
.btn-danger {
    padding: 12px 25px; /* Generous padding */
    border: none;
    border-radius: 6px; /* Slightly more rounded corners */
    cursor: pointer;
    font-size: 16px;
    font-weight: bold; /* Make text bolder */
    margin-top: 10px; /* Space above each button */
    transition: background-color 0.3s ease, transform 0.1s ease, opacity 0.3s ease;
    text-decoration: none; /* For anchor tags acting as buttons */
    text-align: center;
    display: inline-block; /* Essential for anchor tags to behave like block buttons */
    width: auto; /* Buttons size based on content */
    box-sizing: border-box; /* Include padding in width */
    background-color: #80000a;
    color: white;
}

/* Secondary Action: Back to Tasks */
.btn-back {
    padding: 12px 25px; /* Generous padding */
    border: none;
    border-radius: 6px; /* Slightly more rounded corners */
    cursor: pointer;
    font-size: 16px;
    font-weight: bold; /* Make text bolder */
    margin-top: 10px; /* Space above each button */
    transition: background-color 0.3s ease, transform 0.1s ease, opacity 0.3s ease;
    text-decoration: none; /* For anchor tags acting as buttons */
    text-align: center;
    display: inline-block; /* Essential for anchor tags to behave like block buttons */
    width: auto; /* Buttons size based on content */
    box-sizing: border-box; /* Include padding in width */
    background-color: gray;
    color: white;
}

    </style>
    <script>
        // JavaScript for delete confirmation
        function confirmDelete() {
            return confirm('Are you sure you want to delete this task? This action cannot be undone.');
        }

        // Add a simple close button functionality if desired
        // You would need to add a clickable element for this, e.g., an <a> tag outside the form but inside .main-content
        // Example: <a href="project.php" class="close-modal-btn">&times;</a>
        /*
        document.addEventListener('DOMContentLoaded', function() {
            const closeModalBtn = document.querySelector('.close-modal-btn');
            if (closeModalBtn) {
                closeModalBtn.addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent default link behavior
                    window.location.href = 'project.php'; // Redirect back to task list
                });
            }
        });
        */
    </script>
</head>
<body>
    <div class="main-content">
        <h1>Edit Task: <?= htmlspecialchars($task['title']) ?></h1>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST">
            <label for="title">Title:</label>
            <input type="text" id="title" name="title"
                   value="<?= htmlspecialchars($task['title'] ?? '') ?>" required>

            <label for="description">Description:</label>
            <textarea id="description" name="description" required><?= htmlspecialchars($task['description'] ?? '') ?></textarea>

            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name"
                   value="<?= htmlspecialchars($task['client_name'] ?? '') ?>" placeholder="Client Name">

            <label for="client_address">Address:</label>
            <input type="text" id="client_address" name="client_address"
                   value="<?= htmlspecialchars($task['client_address'] ?? '') ?>" placeholder="Client Address">

            <label for="client_phone">Phone:</label>
            <input type="text" id="client_phone" name="client_phone"
                   value="<?= htmlspecialchars($task['client_phone'] ?? '') ?>" placeholder="Client Phone">

            <label for="client_email">Email:</label>
            <input type="email" id="client_email" name="client_email"
                   value="<?= htmlspecialchars($task['client_email'] ?? '') ?>" placeholder="Client Email">

            <label for="start_date">Start Date:</label>
            <input type="date" id="start_date" name="start_date"
                   value="<?= htmlspecialchars($task['start_date'] ?? '') ?>" required>

            <label for="end_date">End Date:</label>
            <input type="date" id="end_date" name="end_date"
                   value="<?= htmlspecialchars($task['end_date'] ?? '') ?>" required>

            <label for="status">Status:</label>
            <select id="status" name="status">
                <option value="pending" <?= ($task['status'] ?? '') === 'to-do' ? 'selected' : '' ?>>Pending</option>
                <option value="in-progress" <?= ($task['status'] ?? '') === 'in-progress' ? 'selected' : '' ?>>In Progress</option>
                <option value="done" <?= ($task['status'] ?? '') === 'done' ? 'selected' : '' ?>>Done</option>
            </select>

            <button type="submit">Save Changes</button>

            <a href="edit_task.php?id=<?= htmlspecialchars($taskId) ?>&action=delete"
               onclick="return confirmDelete()" class="btn btn-danger">Delete Task</a>

                           <a href="project.php" class="btn btn-back">Back to Tasks</a>
        </form>
    </div>
    </body>
</html>