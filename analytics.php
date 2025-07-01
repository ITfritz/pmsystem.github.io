<?php
session_start();
require 'connect.php'; // Make sure this connects to your database
require 'functions.php'; // Make sure this contains isAdmin() and sanitizeInput()

// Verify admin access
if (!isAdmin()) {
    header("Location: login.php");
    exit();
}

// Function to fetch the first_name based on user ID
function getfirst_nameFromDatabase($userId) {
    global $db; // Assuming $db is your PDO connection from connect.php

    $query = "SELECT first_name FROM users WHERE user_id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

    if ($stmt->execute()) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            return $user['first_name'];
        } else {
            return null;
        }
    } else {
        return null;
    }
}

// Set first_name if not already in session
if (!isset($_SESSION['first_name'])) {
    if (isset($_SESSION['user_id'])) {
        $_SESSION['first_name'] = getfirst_nameFromDatabase($_SESSION['user_id']);
    } else {
        $_SESSION['first_name'] = 'Guest'; // fallback
    }
}

$isAdmin = isAdmin(); // Check if user is admin

// The "Add Task" form submission handling. This seems to be from project.php.
// If analytics.php is strictly for viewing analytics, you might want to remove this block.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_task'])) {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];

    $status = $isAdmin ? $_POST['status'] : 'pending';

    $userId = $_SESSION['user_id']; // Logged-in user's ID

    $today = date('Y-m-d');
    if ($startDate < $today || $endDate < $today || $endDate < $startDate) {
        $_SESSION['error'] = "Invalid date inputs. Start and End dates cannot be in the past, and End date cannot be before Start date.";
        header("Location: analytics.php");
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
        $_SESSION['error'] = "Failed to add task: " . $e->getMessage();
    }

    header("Location: analytics.php");
    exit();
}

// Handle search (also seems to be from project.php)
$search = $_GET['search'] ?? '';
$whereClause = $search ? "AND (title LIKE :search OR description LIKE :search)" : "";
$params = $search ? ['search' => "%$search%"] : [];

// Fetch tasks from the database (also seems to be from project.php)
// If analytics.php is purely for analytics, you might remove this part.
try {
    $userId = $_SESSION['user_id'];
    $query = "
        SELECT task_id, title, description, created_at, status
        FROM tasks
        WHERE 1=1
    ";
    if (!$isAdmin) {
        $query .= " AND user_id = :user_id";
        $params['user_id'] = $userId;
    }
    $query .= " $whereClause ORDER BY created_at DESC";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($tasks)) {
        // error_log("No tasks found for the user."); // Uncomment for debugging
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while fetching tasks: " . $e->getMessage();
    $tasks = [];
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isAdmin ? 'Manage Project' : 'My Tasks' ?> | Analytics</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="analytics.css">
</head>
<body>
    <?php include 'admin_sidebar.php'; ?>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="success-message"><?= $_SESSION['success'] ?></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="error-message"><?= $_SESSION['error'] ?></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

<header class="main-header">
    <div class="header-content">
        <div>
            <h1>Analytics</h1>
            <p>JT Kitchen Equipment Installation Services | Admin Dashboard</p>
        </div>
        <div class="header-right">
            <span id="currentDateTime"></span>
            <div class="profile-menu">
                <div>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <i class="fas fa-user-circle" id="profileIcon" onclick="toggleMenu()" style="font-size: 44px;"></i>
                    </div>
                </div>
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

<div class="main-wrapper1">
    <div class="main-content-left1">    
    <h2>Weekly Appointment Summary</h2>
    <div class="chart-container">
        <canvas id="appointmentChart"></canvas>
    </div>
    <div class="buttons">
        <button onclick="openAddAppointmentDataModal()" class="add-data-button">Add Appointment Data</button>
        <button onclick="updateChart('week')" class="view-button">Weekly</button>
        <button onclick="updateChart('month')" class="view-button">Monthly</button>
        <button onclick="updateChart('year')" class="view-button">Yearly</button>
    </div>
</div>

    <div class="main-content-right1">
        <h2>Equipments</h2>
        <div class="chart-container">
            <canvas id="toolsChart"></canvas>
        </div>
        <div class="buttons">
            <button onclick="openAddToolDataModal()" class="add-data-button">Add Tool Data</button>
        </div>
    </div>
</div>

<div class="main-wrapper2">
    <div class="analytics-card">
        <h2>Total Expenses</h2>
        <div class="total-amount" id="expenses-amount">₱ 0.00</div>
        <div class="chart-container">
            <canvas id="expensesChart"></canvas>
        </div>
        <div class="buttons">
            <button onclick="updateExpenses('week')">A Week</button>
            <button onclick="updateExpenses('month')">A Month</button>
            <button onclick="updateExpenses('year')">A Year</button>
            <button onclick="updateExpenses('all')">All</button>
            <button onclick="openAddExpenseDataModal()" class="add-data-button">Add Expense</button>
        </div>
    </div>

    <div class="analytics-card">
        <h2>Total Profit</h2>
        <div class="total-amount" id="profit-amount">₱ 0.00</div>
        <div class="chart-container">
            <canvas id="profitChart"></canvas>
        </div>
        <div class="buttons">
            <button onclick="updateProfit('week')">A Week</button>
            <button onclick="updateProfit('month')">A Month</button>
            <button onclick="updateProfit('year')">A Year</button>
            <button onclick="updateProfit('all')">All</button>
            <button onclick="openAddProfitDataModal()" class="add-data-button">Add Profit</button>
        </div>
    </div>
</div>

 <!-- Add Appointment Data Modal -->
    <div id="addAppointmentDataModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddAppointmentDataModal()">&times;</span>
            <h2>Add Appointment Data</h2>
            <form id="addAppointmentDataForm">
                <label for="appointment_date">Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" required>
                <label for="booked_slots">Booked Slots:</label>
                <input type="number" id="booked_slots" name="bookings" min="0" required>
                <div class="modal-actions">
                    <button type="submit" class="status-tag add">Add Data</button>
                    <button type="button" class="status-tag cancel" onclick="closeAddAppointmentDataModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

 <!-- Add Tool Data Modal -->
    <div id="addToolDataModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeAddToolDataModal()">&times;</span>
            <h2>Add Tool Data</h2>
            <form id="addToolDataForm">
                <label for="tool_name">Tool Name:</label>
                <input type="text" id="tool_name" name="tool_name" required>
                <label for="usage_count">Usage Count:</label>
                <input type="number" id="usage_count" name="usage_count" min="0" required>
                <div class="modal-actions">
                    <button type="submit" class="status-tag add">Add Data</button>
                    <button type="button" class="status-tag cancel" onclick="closeAddToolDataModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

<div id="addProfitDataModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddProfitDataModal()">&times;</span>
        <h2>Add Profit Data</h2>
        <form id="addProfitDataForm">
            <label for="profit_date">Date:</label>
            <input type="date" id="profit_date" name="profit_date" required>
            <label for="profit_amount">Amount:</label>
            <input type="number" id="profit_amount" name="profit_amount" step="0.01" min="0" required>
            <div class="modal-actions">
                <button type="submit" class="status-tag add">Add Profit</button>
                <button type="button" class="status-tag cancel" onclick="closeAddProfitDataModal()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
const username = <?php echo json_encode($_SESSION['first_name'] ?? 'Guest'); ?>;

document.addEventListener('DOMContentLoaded', function() {
    const welcomeElement = document.getElementById('welcome');
    if (welcomeElement) {
        const welcomeMessage = `Welcome, ${username}!`;
        welcomeElement.textContent = welcomeMessage;
    }
});

// Get the current date and time
function updateDateTime() {
    const now = new Date();
    const options = {
        weekday: 'long',
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    };
    const formattedDateTime = now.toLocaleDateString('en-US', options);
    document.getElementById('currentDateTime').textContent = formattedDateTime;
}

updateDateTime();
setInterval(updateDateTime, 1000);

// Toggle dropdown menu
function toggleMenu() {
    const menu = document.getElementById('dropdownMenu');
    menu.style.display = (menu.style.display === 'block') ? 'none' : 'block';
}

// Hide dropdown if clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('dropdownMenu');
    const icon = document.getElementById('profileIcon');
    if (dropdown && icon && !dropdown.contains(event.target) && !icon.contains(event.target)) {
        dropdown.style.display = 'none';
    }
});

// General functions to open/close modals
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

// --- Specific Modal Functions (new) ---
// Task Data Modal (New functions for add_task modal)
function openAddTaskModal() {
    openModal('addTaskModal');
}
function closeAddTaskModal() {
    closeModal('addTaskModal');
}

// Appointment Data Modal
function openAddAppointmentDataModal() {
    openModal('addAppointmentDataModal');
}
function closeAddAppointmentDataModal() {
    closeModal('addAppointmentDataModal');
}

// Tools Data Modal
function openAddToolDataModal() {
    openModal('addToolDataModal');
}
function closeAddToolDataModal() {
    closeModal('addToolDataModal');
}

// Expenses Data Modal
function openAddExpenseDataModal() {
    openModal('addExpenseDataModal');
}
function closeAddExpenseDataModal() {
    closeModal('addExpenseDataModal');
}

// Profit Data Modal
function openAddProfitDataModal() {
    openModal('addProfitDataModal');
}
function closeAddProfitDataModal() {
    closeModal('addProfitDataModal');
}

// Assuming you have a Chart.js chart instance named appointmentChart
const ctx = document.getElementById('appointmentChart').getContext('2d');
let appointmentChart;

function initAppointmentChart() {
    appointmentChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [], // Will be filled by fetchAppointmentData
            datasets: [{
                label: 'Booked Slots',
                data: [], // Will be filled by fetchAppointmentData
                backgroundColor: 'rgba(138, 0, 0, 0.7)',
                borderColor: 'rgba(138, 0, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Appointment Statistics',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Appointments'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Date'
                    }
                }
            }
        }
    });
}

async function updateChart(period) {
    console.log("Updating chart for period: " + period);
    try {
        const response = await fetch(`fetch_appointment_data.php?period=${period}`);
        const data = await response.json();
        if (data.success) {
            appointmentChart.data.labels = data.labels;
            appointmentChart.data.datasets[0].data = data.values;
            appointmentChart.update();
        } else {
            console.error('Error fetching appointment data:', data.message);
        }
    } catch (error) {
        console.error('Failed to fetch appointment data:', error);
    }
}

// --- Chart.js Initialization and Data Fetching ---
// Function to fetch and update Appointment Chart
async function fetchAppointmentData() {
    try {
        const response = await fetch('fetch_appointment_data.php');
        const data = await response.json();
        if (data.success) {
            appointmentChart.data.labels = data.labels;
            appointmentChart.data.datasets[0].data = data.values;
            appointmentChart.update();
        } else {
            console.error('Error fetching appointment data:', data.message);
        }
    } catch (error) {
        console.error('Failed to fetch appointment data:', error);
    }
}

// Initialize Tools Chart
const ctxTools = document.getElementById('toolsChart').getContext('2d');
let toolsChart;

function initToolsChart() {
    toolsChart = new Chart(ctxTools, {
        type: 'bar',
        data: {
            labels: [], // Will be filled by fetchToolsData
            datasets: [{
                label: 'Tools Usage',
                data: [], // Will be filled by fetchToolsData
                backgroundColor: 'rgba(138, 0, 0, 0.7)',
                borderColor: 'rgba(138, 0, 0, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                title: {
                    display: true,
                    text: 'Equipment Usage Statistics',
                    font: {
                        size: 16
                    }
                },
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Number of Usage'
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Equipment'
                    }
                }
            }
        }
    });
}

// Function to fetch and update Tools Chart
async function fetchToolsData() {
    try {
        const response = await fetch('fetch_tools_data.php');
        const data = await response.json();
        if (data.success) {
            toolsChart.data.labels = data.labels;
            toolsChart.data.datasets[0].data = data.values;
            toolsChart.update();
        } else {
            console.error('Error fetching tools data:', data.message);
        }
    } catch (error) {
        console.error('Failed to fetch tools data:', error);
    }
}

// Initialize Appointment Chart
initAppointmentChart();
// Initialize Tools Chart
initToolsChart();

// Call fetch functions on page load
document.addEventListener('DOMContentLoaded', () => {
    fetchAppointmentData();
    fetchToolsData();

    // Handle form submissions for modals using Fetch API
    document.getElementById('addAppointmentDataForm').addEventListener('submit', async function(event) {
        event.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        console.log(data); // Debugging: Log the form data

        // Validate data
        if (!data.appointment_date || !data.bookings || data.bookings < 0) {
            alert('Invalid input data. Please check the form and try again.');
            return;
        }

        try {
            const response = await fetch('add_appointment_data.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            if (result.success) {
                alert('Appointment data added successfully!');
                closeAddAppointmentDataModal();
                fetchAppointmentData(); // Refresh chart
            } else {
                alert('Error: ' + result.message);
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while adding appointment data.');
        }
    });

    document.getElementById('addToolDataForm').addEventListener('submit', async function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());
    
    console.log("Submitting:", data); // Debug log
    
    try {
        const response = await fetch('add_tool_data.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        console.log("Server response:", result); // Debug log
        
        if (result.success) {
            alert('Tool data added successfully!');
            closeAddToolDataModal();
            fetchToolsData();
        } else {
            // Show the actual error message from server
            alert('Error: ' + (result.message || 'Unknown error occurred'));
        }
    } catch (error) {
        console.error('Fetch error:', error);
        alert('Network error: ' + error.message);
    }
});
});
</script>
</body>
</html>