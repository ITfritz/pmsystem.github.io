<?php
session_start();
require_once 'connect.php';
require_once 'functions.php'; // Ensure this includes truncateName()

// Initialize database connection
$db = DatabaseConnection::getInstance()->getConnection();

// Redirect if not logged in
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $client_name = sanitizeInput($_POST['name']);
    $description = sanitizeInput($_POST['description']);
    $user_id = $_SESSION['user_id'];

    try {
        $stmt = $db->prepare("INSERT INTO requests (user_id, client_name, description) 
                             VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $client_name, $description]);
        header("Location: request.php");
        exit();
    } catch (PDOException $e) {
        logSecurityEvent('Request Error', $e->getMessage());
        $error = "Error creating request. Try again later.";
    }
}

// Fetch user's requests
$stmt = $db->prepare("SELECT * FROM requests WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard</title>
        <link rel="stylesheet" href="request.css">
        <script defer src="request.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    </head>
    <body>

        <!-- Sidebar -->
        <div class="sidebar">
            <h2>INSTALLATION SERVICE</h2>
            <ul>
                <li button onclick="window.location.href='qwe.php'">
                    <i class="fas fa-home"></i> Dashboard </button>
                <li button onclick="window.location.href='to do.html'"> 
                    <i class="fas fa-tasks"></i> To do  </button> 
                <li button onclick="window.location.href='request.php'">
                    <i class="fas fa-envelope"></i> Request </button>
                <li button onclick="window.location.href='project.html'">
                    <i class="fas fa-folder"></i> Projects </button>
                <li><i class="fas fa-info-circle"></i> About us</li>
            </ul>
        
            
            <div class="profile" onclick="window.location.href='profile.html'" style="cursor: pointer;">
                <i class="fas fa-user-circle"></i> Profile
                <li onclick="window.location.href='logout.php'">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </li>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">
    <h1>Requests</h1>

    <!-- Create Request Section -->
    <div class="request create-request">
        <h3>CREATE REQUEST</h3>
        <form id="requestForm" method="POST">
            <input type="text" name="name" placeholder="Client Name" required>
            <textarea name="description" placeholder="Work Description" rows="4" required></textarea>
            <button type="submit" class="submit-btn">Submit Request</button>
        </form>
    </div>

    <!-- My Requests Section -->
    <div class="request my-requests">
            <h3>MY REQUESTS</h3>
            <ul>
                <?php foreach ($requests as $request): ?>
                    <li class="request-item">
                        <div class="client-container">
                            <div class="client-name"><?= htmlspecialchars($request['client_name']) ?></div>
                            <div class="request-date"><?= date('M d, Y', strtotime($request['created_at'])) ?></div>
                        </div>
                        <div class="description-container">
                            <div class="request-description">
                                <?= htmlspecialchars($request['description']) ?>
                            </div>
                        </div>
                        <div class="request-actions">
                        <button class="status-tag <?= strtolower(str_replace(' ', '-', $request['status'])) ?>"
                                data-request-id="<?= $request['request_id'] ?>"
                                onclick="toggleStatus(this)">
                            <?= str_replace('-', ' ', $request['status']) ?>
                        </button>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

            <!-- Toggle Button -->
            <div class="scroll-indicator" id="toggle-btn">
                <i class="fas fa-chevron-up"></i>
            </div>
        </div>
    </body>
    </html>