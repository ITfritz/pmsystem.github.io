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

$search = isset($_GET['search']) ? $_GET['search'] : '';


// Set first_name if not already in session
if (!isset($_SESSION['first_name'])) {
    function getfirst_nameFromDatabase($userId) {
        global $db;

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

    if (isset($_SESSION['user_id'])) {
        $_SESSION['first_name'] = getfirst_nameFromDatabase($_SESSION['user_id']);
    } else {
        error_log("User ID not found in session.");
        $_SESSION['first_name'] = 'Guest';
    }
}

$isAdmin = isAdmin();

// Handle form submission (Add Logistics Entry)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_logistics'])) {
    $clientEmail = sanitizeInput($_POST['client_email']);
    $materials = sanitizeInput($_POST['materials']);
    $shipmentStatus = sanitizeInput($_POST['shipment_status']);
    $shipmentDate = $_POST['shipment_date'];
    $deliveryDate = $_POST['delivery_date'] ?? null;
    $location = sanitizeInput($_POST['dropdown']);

    try {
        $stmt = $db->prepare("
            INSERT INTO logistics (client_email, materials, shipment_status, shipment_date, delivery_date, location)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$clientEmail. $materials, $shipmentStatus, $shipmentDate, $deliveryDate, $location]);

        $_SESSION['success'] = "Logistics entry added successfully!";
    } catch (PDOException $e) {
        error_log("Logistics addition failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to add logistics entry.";
    }

    header("Location: userlogistics.php");
    exit();
}

// Fetch all logistics entries for the logged-in user based on email
try {

    $email = $_SESSION['email'] ?? null; // Get user email from session

    if ($email) {
        $stmt = $db->prepare("
            SELECT * FROM logistics
            WHERE client_email = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$email]);
        $logisticsEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        http_response_code(403);
        echo json_encode(['error' => 'User  not authenticated.']);
        exit();
    }
} catch (PDOException $e) {
    error_log("Database query failed: " . $e->getMessage());
    die("An error occurred while fetching logistics entries.");
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $isAdmin ? 'Manage Logistics' : 'Logistics' ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">


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
        <h1>Logistics</h1>
            <p>JT Kitchen Equipment Installation Services | User Dashboard</p>
        </div>
        <div class="header-right">
            <span id="currentDateTime"></span>
            <div class="profile-menu">

            <div>
            <div style="display: flex; flex-direction: column; align-items: center;">
    <i class="fas fa-user-circle" onclick="toggleMenu()" style="font-size: 44px;"></i>
</div>


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


 <!-- Add Task Modal (For All Users) -->
<div id="addTaskModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeAddTaskModal()">&times;</span>
        <h2>Add New Task</h2>
        <form id="addTaskForm" method="POST">
            <input type="hidden" name="add_task" value="1">
            
            <label for="client_name">Client Name:</label>
            <input type="text" id="client_name" name="client_name" placeholder="Client Name" required>

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
        const clientName = document.getElementById('client_name').value.trim();
        const title = document.getElementById('title').value.trim();
        const description = document.getElementById('description').value.trim();
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;

        // Get today's date in YYYY-MM-DD format
        const today = new Date().toISOString().split('T')[0];

        if (!clientName || !title || !description || !startDate || !endDate) {
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
/* Close Button */
        .modal-content .close {
            position: absolute;
            top: -15px; /* Move it outside the modal */
            right: -15px; /* Move it outside the modal */
            font-size: 20px; /* Make it larger */
            background-color: #ff4444; /* Keep the background color of the button */
            color: white; /* Initial color of the 'X' */
            border: none;
            padding: 10px 15px;
            border-radius: 50%; /* Circular button */
            cursor: pointer;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Soft shadow for button */
            z-index: 1001;
            transition: transform 0.5s ease, color 0.3s ease, background-color 0.3s ease; /* Smooth transition for transform, color, and background */
        }

        .modal-content .close:hover {
            background-color: #cc0000; /* Darker red on hover */
            color: #D32F2F; /* Change color of the 'X' on hover */
            transform: rotate(180deg); /* Rotate by 180 degrees on hover */
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

<div class="main-content">

<h2>Track Your Bookings</h2>

 <!-- Add Logistics Button (Only for Admins) -->
<?php if ($isAdmin): ?>
    <button class="add-button status-tag add" onclick="openAddLogisticsModal()">
        <i class="fas fa-plus"></i> <!-- Move the icon inside the button -->
    </button>
<?php endif; ?>


        <!-- Logistics List as a Table -->
        <table class="logistics-table">
            <thead>
                <tr>
                    <th>Client Email</th>
                    <th>Materials</th>
                    <th>Status</th>
                    <th>Shipment Date</th>
                    <th>Delivery Date</th>
                    <th>Location</th>
                    <?php if ($isAdmin): ?><th>Actions</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logisticsEntries as $entry): ?>
                    <tr>
                        
                        <td><?= htmlspecialchars($entry['client_email']) ?></td>
                     

                        <td><?= htmlspecialchars($entry['materials']) ?></td>
                        <td>
                            <?php
                            $status = htmlspecialchars($entry['shipment_status']);
                            $statusClass = '';
                            if ($status === 'pending') {
                                $statusClass = 'status-pending';
                            } elseif ($status === 'in_transit') {
                                $statusClass = 'status-in-transit';
                            } elseif ($status === 'delivered') {
                                $statusClass = 'status-delivered';
                            }
                            ?>
                            <span class="status-tag <?= $statusClass ?>"><?= ucfirst($status) ?></span>
                        </td>
                        <td><?= htmlspecialchars($entry['shipment_date']) ?></td>
                        <td><?= htmlspecialchars($entry['delivery_date']) ?></td>
                        <td><?= htmlspecialchars($entry['location']) ?></td>
                        <?php if ($isAdmin): ?>
                            <td class="actions">
                                <button class="edit-button status-tag admin" onclick="showEditLogisticsModal(<?= $entry['logistics_id'] ?>)">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="delete-button status-tag delete" onclick="deleteLogisticsEntry(<?= $entry['logistics_id'] ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Add Logistics Modal (Only for Admins) -->
    <?php if ($isAdmin): ?>
        <div id="addLogisticsModal" class="modal">
            <div class="modal-content">
                <span class="close" onclick="closeAddLogisticsModal()">&times;</span>
                <h2>Add Logistics Entry</h2>
                <form id="addLogisticsForm" method="POST">
                    <label for="materials">Order Name:</label>
                    <input type="text" id="materials" name="materials" placeholder="Order Name" required>
                    <label for="clientEmail">Client Email:</label>
                    <input type="email" id="clientEmail" name="client_email" placeholder="Client Email" required>
                    <label for="shipmentStatus">Shipment Status:</label>
                    <select id="shipmentStatus" name="shipment_status" required>
                        <option value="pending">Pending</option>
                        <option value="in_transit">In Transit</option>
                        <option value="delivered">Delivered</option>
                    </select>
                    <label for="shipmentDate">Shipment Date:</label>
                    <input type="date" id="shipmentDate" name="shipment_date" required>
                    <label for="deliveryDate">Delivery Date:</label>
                    <input type="date" id="deliveryDate" name="delivery_date">
                    <label for="location">Location:</label>
                    <input type="text" id="location" name="location" placeholder="Enter location" required>
                    <button type="submit">Add Logistics Entry</button>
                </form>
            </div>
        </div>
    <?php endif; ?>

    <!-- Edit Logistics Modal (Only for Admins) -->
    <?php if ($isAdmin): ?>
    <div id="editLogisticsModal" class="modal">
        <div class="modal-content">
            <!-- Close Button -->
            <span class="close" onclick="closeEditLogisticsModal()">&times;</span>
            
            <!-- Modal Title -->
            <h2>Edit Logistics Entry</h2>
            
            <!-- Form -->
            <form id="updateLogisticsForm" method="POST">
                <!-- Hidden Input for Logistics ID -->
                <input type="hidden" id="logisticsId" name="logistics_id">

                <!-- Tracking Number Field -->
                <label for="updatedmaterials">Materials:</label>
                <input type="text" id="updatedmaterials" name="materials" required>

                <!-- Tracking Number Field -->
                <label for="updatedClientEmail">Client Email:</label>
                <input type="text" id="updatedClientEmail" name="client_email" required>

                <!-- Shipment Status Dropdown -->
                <label for="updatedShipmentStatus">Shipment Status:</label>
                <select id="updatedShipmentStatus" name="shipment_status" required>
                    <option value="pending">Pending</option>
                    <option value="in_transit">In Transit</option>
                    <option value="delivered">Delivered</option>
                </select>

                <!-- Shipment Date Field -->
                <label for="updatedShipmentDate">Shipment Date:</label>
                <input type="date" id="updatedShipmentDate" name="shipment_date" required>

                <!-- Delivery Date Field -->
                <label for="updatedDeliveryDate">Delivery Date:</label>
                <input type="date" id="updatedDeliveryDate" name="delivery_date">

                <label for="location">Location:</label>
                    <input type="text" id="location" name="location" placeholder="Enter location" required>

                <!-- Save Button -->
                <button type="submit">Save Changes</button>
            </form>
        </div>
    </div>
<?php endif; ?>


    <script>
        // Open Add Logistics Modal
        function openAddLogisticsModal() {
            document.getElementById('addLogisticsModal').style.display = 'block';
        }

        // Close Add Logistics Modal
        function closeAddLogisticsModal() {
            document.getElementById('addLogisticsModal').style.display = 'none';
        }

        // Show Edit Logistics Modal
        function showEditLogisticsModal(logisticsId) {
    fetch(`get_logistics.php?logistics_id=${logisticsId}`)
        .then(response => response.json())
        .then(logistics => {
            if (logistics.error) {
                alert(logistics.error);
                return;
            }
            document.getElementById('logisticsId').value = logistics.logistics_id;
            document.getElementById('updatedClientEmail').value = logistics.client_email;
            document.getElementById('updatedmaterials').value = logistics.materials;
            document.getElementById('updatedShipmentStatus').value = logistics.shipment_status;
            document.getElementById('updatedShipmentDate').value = logistics.shipment_date;
            document.getElementById('updatedDeliveryDate').value = logistics.delivery_date || '';
            document.getElementById('location').value = logistics.location || '';
            document.getElementById('editLogisticsModal').style.display = 'block';
        })
        .catch(error => {
            console.error('Error fetching logistics data:', error);
            alert('An error occurred while loading the logistics entry.');
        });
}

        // Close Edit Logistics Modal
        function closeEditLogisticsModal() {
            document.getElementById('editLogisticsModal').style.display = 'none';
        }

        // Handle Add Logistics Form Submission
        <?php if ($isAdmin): ?>
            document.getElementById('addLogisticsForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                try {
                    const response = await fetch('add_logistics.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (response.ok) {
                        alert('Logistics entry added successfully.');
                        window.location.reload();
                    } else {
                        alert('Failed to add logistics entry.');
                    }
                } catch (error) {
                    console.error('Error adding logistics entry:', error);
                    alert('An error occurred while adding the logistics entry.');
                }
            });
        <?php endif; ?>

        // Handle Update Logistics Form Submission
        <?php if ($isAdmin): ?>
            document.getElementById('updateLogisticsForm')?.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(e.target);
                try {
                    const response = await fetch('update_logistics.php', {
                        method: 'POST',
                        body: formData
                    });
                    if (response.ok) {
                        alert('Logistics entry updated successfully.');
                        window.location.reload();
                    } else {
                        alert('Failed to update logistics entry.');
                    }
                } catch (error) {
                    console.error('Error updating logistics entry:', error);
                    alert('An error occurred while updating the logistics entry.');
                }
            });
        <?php endif; ?>

        // Function to delete a logistics entry
        <?php if ($isAdmin): ?>
            function deleteLogisticsEntry(logisticsId) {
                if (!confirm('Are you sure you want to delete this logistics entry?')) return;
                fetch('delete_logistics.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ logistics_id: logisticsId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.success);
                        window.location.reload();
                    } else {
                        alert(data.error || 'Failed to delete the logistics entry.');
                    }
                })
                .catch(error => {
                    console.error('Error deleting logistics entry:', error);
                    alert('An error occurred while deleting the logistics entry.');
                });
            }
        <?php endif; ?>

       // JavaScript to open/close the modal
const modal = document.getElementById('myModal');
const openModalBtn = document.querySelector('.open-modal'); // Assuming you have a button to open the modal
const closeModalBtn = document.querySelector('.close');

openModalBtn.addEventListener('click', () => {
    modal.style.display = 'flex';
});

closeModalBtn.addEventListener('click', () => {
    modal.style.display = 'none';
});


    </script>
</body>
</html>

<style>

/* Main Content */
.main-content {
    flex: 1;
    margin-left: 100px; /* Adjust for the sidebar width */
    background: whitesmoke;
    border-radius: 18px;
  /* Box Shadow without left side */
    box-shadow: 4px 0 10px rgba(0, 0, 0, 0.2); /* Only shadow on the right side */
    padding: 30px;
    color: #8a0000;
    max-width: 1400px;
    min-width: 320px;
    backdrop-filter: blur(10px); /* Frosted glass effect */
    transition: all 0.3s ease;
}

.main-content {
    transition: margin-left 0.3s ease;
}

/* Dashboard Sections */
.dashboard-section {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 30px;
    margin-bottom: 30px;
}

/* Container for status label */
.status-label {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.9rem;
    color: white;
    text-transform: capitalize;
    min-width: 90px;
    text-align: center;
    box-shadow: 0 2px 6px rgba(0,0,0,0.12);
    user-select: none;
    transition: background-color 0.3s ease, box-shadow 0.3s ease;
}

/* Colors for each status */
.status-pending {
    background-color: #ed8936; /* warm orange gradient */
    box-shadow: 0 4px 8px rgba(237, 137, 54, 0.4);
}

.status-in-transit {

  background: linear-gradient(135deg, #4299e1, #3182ce); /* crisp blue gradient */
    box-shadow: 0 4px 8px rgba(49, 130, 206, 0.4);
}

.status-delivered {
    background-color:  #28a745;
    box-shadow: 0 4px 8px rgba(56, 161, 105, 0.4);
}

/* Optional: hover effect to gently brighten */
.status-label:hover {
    filter: brightness(1.1);
    box-shadow: 0 6px 12px rgba(0,0,0,0.2);
}




button.status-tag.add {
    position: fixed;
    bottom: 40px; /* Distance from bottom */
    right: 40px; /* Distance from right */
    background:green;
    color: transparent;
    width: 80px; /* Increase size */
    height: 80px; /* Increase size */
    border: none;
    border-radius: 20px;
    cursor: pointer;
    display: flex; /* Align icon properly */
    justify-content: center;
    align-items: center;
    font-size: 32px; /* Adjust for larger button */
    text-align: center;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3); /* Add slight shadow */
    transition: transform 0.2s ease-in-out;
}

button.status-tag.add:hover {
    transform: scale(1.1); /* Slight enlarge effect on hover */
}

button.status-tag.add i {
    font-size: 36px; /* Make icon bigger */
    color: white;
}


/* Success/Error Messages */
.success-message {
    background:green;
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}
.error-message {
    background: #ff4444;
    color: white;
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    text-align: center;
}

/* Modals Background */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.7); /* Semi-transparent dark background */
    z-index: 1000;
    backdrop-filter: blur(3px); /* Blur effect for background */
    justify-content: center;
    align-items: center;
}

/* Modal Content */
.modal-content {
    background: #fff;
    padding: 30px;
    width: 90%;
    max-width: 600px;
    border-radius: 10px;
    position: relative;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); /* Soft shadow around modal */
}

/* Modal Header */
.modal-content h2 {
    background: #8a0000;
    color: white;
    padding: 15px;
    text-align: center;
    font-size: 24px;
    border-radius: 10px 10px 0 0; /* Rounded top corners */
    margin: 0;
    font-weight: bold;
}


/* Form styling */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    justify-content: center;
}

form label {
    font-size: 16px;
    color: #333;
}

form input, form select {
    padding: 10px;
    font-size: 16px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-top: 5px;
    background-color: #f9f9f9;
    transition: border-color 0.3s ease;
}

form input:focus, form select:focus {
    border-color: #ff4444; /* Red focus border */
    outline: none;
}

form button {
    background-color: #28a745; /* Green button */
    color: white;
    padding: 12px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    margin-top: 20px;
}

form button:hover {
    background-color: #218838; /* Darker green on hover */
}

/* Media Queries for responsiveness */
@media (max-width: 768px) {
    .modal-content {
        width: 95%;
    }

    .modal-content h2 {
        font-size: 22px; /* Adjust header size for smaller screens */
    }

    .modal-content .close {
        font-size: 30px; /* Adjust close button size for smaller screens */
        top: -15px; /* Adjust position for smaller screens */
        right: -15px; /* Adjust position for smaller screens */
    }

    form button {
        padding: 10px;
        font-size: 14px;
    }
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
    .main-content form {
        grid-template-columns: 1fr;
    }
    .main-content form label {
        grid-column: 1 / -1;
    }
    .main-content form input,
    .main-content form select,
    .main-content form button {
        grid-column: 1 / -1;
    }
}

/* Search Form */
.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}
.search-form input {
    flex: 1;
    padding: 10px;
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: white;
}
.search-form button {
    background: #ff4444;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background 0.3s ease;
}
.search-form button:hover {
    background: #cc0000;
}

.delete-button {
    background-color: red;
    color: white;
    border: none;
    padding: 5px 10px;
    cursor: pointer;
}

    .logistics-table {
        width: 100%;
        border-collapse: collapse;
        margin: 20px 0;
        font-size: 14px;
        min-width: 600px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        border-radius: 8px; /* Rounded corners */
        overflow: hidden;
        color: black;
    }

    /* Table Headers */
    .logistics-table th,
    .logistics-table td {
        padding: 12px 18px;
        text-align: left;
        border-bottom: 1px solid #e0e0e0;
        font-family: 'Arial', sans-serif;
        
    }

    .logistics-table th {
        background-color: #8a0000;
        color: white;
        font-weight: 600; /* Slightly lighter weight for a modern look */
        letter-spacing: 1px; /* Adds a bit of spacing for a cleaner look */
        text-transform: uppercase;
    }


/* Hover Effect */
.logistics-table tr:hover {
    background-color:#D4AF37; /* A subtle light blue background on hover */
}


    /* Table Cell Action Buttons */
    .logistics-table .actions {
        white-space: nowrap;
    }

    .logistics-table .edit-button,
    .logistics-table .delete-button {
        border: none;
        padding: 6px 12px;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .logistics-table .edit-button {
        color: #28a745;      
    }

    .logistics-table .delete-button {
        background-color: #8a0000;
    }

    /* Edit button hover effect */
    .logistics-table .edit-button:hover {

        transform: scale(1.05);
    }

    /* Delete button hover effect */
    .logistics-table .delete-button:hover {
    transform: scale(1.05);
    }

    /* Status Tags */
    .status-tag {
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 13px;
        font-weight: 600;
        display: inline-block;
        text-transform: uppercase;
    }

    .status-tag.admin {
        background-color: green; /* Bright red for admin */
        color: white;
    }

    .status-tag.delete {
        background-color: #8a0000; /* Dark red for delete */
        color: white;
    }

    /* Add some padding around the table for bettesr spacing */
    .logistics-table-container {
        padding: 20px;
        background-color: #fafafa;
        border-radius: 12px;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    }
</style>