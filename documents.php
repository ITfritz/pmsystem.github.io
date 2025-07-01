<?php
session_start();
require 'connect.php';
require 'functions.php';

// Set the $isAdmin variable
$isAdmin = isAdmin();

// Verify admin access
if (!$isAdmin) {
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

    // Call the function and set the session variable
    if (isset($_SESSION['user_id'])) {
        $_SESSION['first_name'] = getfirst_nameFromDatabase($_SESSION['user_id']);
    } else {
        error_log("User ID not found in session.");
        $_SESSION['first_name'] = 'Guest';  // fallback
    }
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $userId = $_SESSION['user_id'];
    $docType = $_POST['docType'];

    // Create uploads directory if it doesn't exist
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Generate unique filename to prevent overwrites
    $fileExt = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid() . '.' . $fileExt;
    $uploadPath = $uploadDir . $fileName;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        try {
            $stmt = $db->prepare("
                INSERT INTO uploads (user_id, doc_type, file_path, uploaded_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $docType, $uploadPath]);

            $_SESSION['success'] = "File uploaded successfully!";
        } catch (PDOException $e) {
            error_log("File upload failed: " . $e->getMessage());
            $_SESSION['error'] = "Failed to upload file.";
            // Remove the uploaded file if DB insertion failed
            if (file_exists($uploadPath)) {
                unlink($uploadPath);
            }
        }
    } else {
        $_SESSION['error'] = "Failed to upload file.";
    }

    header("Location: documents.php");
    exit();
}

// Fetch user's uploaded files
$uploadedFiles = [];
try {
    $stmt = $db->prepare("SELECT upload_id, doc_type, file_path FROM uploads WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $uploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching uploaded files: " . $e->getMessage());
}

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $uploadId = $_POST['upload_id'];
    
    try {
        // First get the file path
        $stmt = $db->prepare("SELECT file_path FROM uploads WHERE upload_id = ? AND user_id = ?");
        $stmt->execute([$uploadId, $_SESSION['user_id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // Delete from database
            $delStmt = $db->prepare("DELETE FROM uploads WHERE upload_id = ?");
            $delStmt->execute([$uploadId]);
            
            // Delete the actual file
            if (file_exists($file['file_path'])) {
                unlink($file['file_path']);
            }
            
            $_SESSION['success'] = "File deleted successfully!";
        }
    } catch (PDOException $e) {
        error_log("File deletion failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete file.";
    }
    
    header("Location: documents.php");
    exit();
}

// Fetch all uploaded files from all users
$allUploadedFiles = [];
try {
    $stmt = $db->prepare("
        SELECT u.upload_id, u.user_id, u.doc_type, u.file_path, u.uploaded_at, 
               us.first_name, us.last_name 
        FROM uploads u
        JOIN users us ON u.user_id = us.user_id
        ORDER BY u.uploaded_at DESC
    ");
    $stmt->execute();
    $allUploadedFiles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("Error fetching uploaded files: " . $e->getMessage());
}
// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $uploadId = $_POST['upload_id'];
    
    try {
        // First get the file path
        $stmt = $db->prepare("SELECT file_path FROM uploads WHERE upload_id = ? AND user_id = ?");
        $stmt->execute([$uploadId, $_SESSION['user_id']]);
        $file = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($file) {
            // Delete from database
            $delStmt = $db->prepare("DELETE FROM uploads WHERE upload_id = ?");
            $delStmt->execute([$uploadId]);
            
            // Delete the actual file
            if (file_exists($file['file_path'])) {
                if (unlink($file['file_path'])) {
                    error_log("File deleted successfully: " . $file['file_path']);
                } else {
                    error_log("Failed to delete file: " . $file['file_path']);
                    throw new Exception("Failed to delete file from server");
                }
            } else {
                error_log("File not found: " . $file['file_path']);
            }
            
            $_SESSION['success'] = "File deleted successfully!";
        } else {
            $_SESSION['error'] = "File not found or you don't have permission to delete it.";
        }
    } catch (PDOException $e) {
        error_log("File deletion failed: " . $e->getMessage());
        $_SESSION['error'] = "Failed to delete file from database.";
    } catch (Exception $e) {
        error_log("File deletion failed: " . $e->getMessage());
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: documents.php");
    exit();
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
            <h1>Documents</h1>
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

<div class="main-content">

    <h1>Required Documents for Booking</h1>
    <p>Please download, fill out, and upload the following documents to complete your booking process.</p>

    <div class="documents-list">
    <!-- Document 1 -->
    <div class="document-item">
        <h3>1. Booking Application Form</h3>
        <p>This form contains your basic details and booking specifications.</p>
        <a href="Field Service Report 1.pdf" download class="btn">Download Form</a>
        <div class="upload-section">
            <?php 
            $bookingForm = array_filter($allUploadedFiles, function($file) {
                return $file['doc_type'] === 'booking-form';
            });
            $hasBookingForm = !empty($bookingForm);
            ?>
            <span>Status: <span id="status-booking-form" class="status <?= $hasBookingForm ? 'uploaded' : 'pending' ?>">
                <?= $hasBookingForm ? 'Uploaded' : 'Pending' ?>
            </span></span>
            
            <form method="post" enctype="multipart/form-data" style="display: inline;">
                <input type="hidden" name="docType" value="booking-form">
                <input type="file" name="file" id="uploadBookingForm" style="display: none;" onchange="this.form.submit()">
                <button type="button" onclick="document.getElementById('uploadBookingForm').click()">Upload Completed Form</button>
            </form>
            
            <?php if ($hasBookingForm): ?>
                <div class="uploaded-files-list">
                    <h4>Uploaded Files:</h4>
                    <ul>
                        <?php foreach ($bookingForm as $file): ?>
                            <li>
                                <span class="file-info">
                                    <?= htmlspecialchars($file['first_name'] . ' ' . $file['last_name']) ?> - 
                                    <?= date('M j, Y g:i a', strtotime($file['uploaded_at'])) ?>
                                </span>
                                <button onclick="openUploadedFileModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                    View
                                </button>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="upload_id" value="<?= $file['upload_id'] ?>">
                                    <button type="submit" name="delete_file" class="delete-btn">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Document 2 -->
    <div class="document-item">
        <h3>2. Terms & Conditions Agreement</h3>
        <p>Read and sign this document to acknowledge the company's policies and terms.</p>
        <a href="Incidental Charges Form.pdf" download class="btn">Download Terms</a>
        <div class="upload-section">
            <?php 
            $termsAgreement = array_filter($allUploadedFiles, function($file) {
                return $file['doc_type'] === 'terms';
            });
            $hasTerms = !empty($termsAgreement);
            ?>
            <span>Status: <span id="status-terms" class="status <?= $hasTerms ? 'uploaded' : 'pending' ?>">
                <?= $hasTerms ? 'Uploaded' : 'Pending' ?>
            </span></span>
            
            <form method="post" enctype="multipart/form-data" style="display: inline;">
                <input type="hidden" name="docType" value="terms">
                <input type="file" name="file" id="uploadTerms" style="display: none;" onchange="this.form.submit()">
                <button type="button" onclick="document.getElementById('uploadTerms').click()">Upload Signed Agreement</button>
            </form>
            
            <?php if ($hasTerms): ?>
                <div class="uploaded-files-list">
                    <h4>Uploaded Files:</h4>
                    <ul>
                        <?php foreach ($termsAgreement as $file): ?>
                            <li>
                                <span class="file-info">
                                    <?= htmlspecialchars($file['first_name'] . ' ' . $file['last_name']) ?> - 
                                    <?= date('M j, Y g:i a', strtotime($file['uploaded_at'])) ?>
                                </span>
                                <button onclick="openUploadedFileModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                    View
                                </button>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="upload_id" value="<?= $file['upload_id'] ?>">
                                    <button type="submit" name="delete_file" class="delete-btn">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Document 3 -->
    <div class="document-item">
        <h3>3. Proof of Identity</h3>
        <p>Submit a valid government-issued ID as proof of identity.</p>
        <div class="upload-section">
            <?php 
            $proofId = array_filter($allUploadedFiles, function($file) {
                return $file['doc_type'] === 'id';
            });
            $hasId = !empty($proofId);
            ?>
            <span>Status: <span id="status-id" class="status <?= $hasId ? 'uploaded' : 'pending' ?>">
                <?= $hasId ? 'Uploaded' : 'Pending' ?>
            </span></span>
            
            <form method="post" enctype="multipart/form-data" style="display: inline;">
                <input type="hidden" name="docType" value="id">
                <input type="file" name="file" id="uploadID" style="display: none;" onchange="this.form.submit()">
                <button type="button" onclick="document.getElementById('uploadID').click()">Upload ID</button>
            </form>
            
            <?php if ($hasId): ?>
                <div class="uploaded-files-list">
                    <h4>Uploaded Files:</h4>
                    <ul>
                        <?php foreach ($proofId as $file): ?>
                            <li>
                                <span class="file-info">
                                    <?= htmlspecialchars($file['first_name'] . ' ' . $file['last_name']) ?> - 
                                    <?= date('M j, Y g:i a', strtotime($file['uploaded_at'])) ?>
                                </span>
                                <button onclick="openUploadedFileModal('<?= htmlspecialchars($file['file_path']) ?>')">
                                    View
                                </button>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="upload_id" value="<?= $file['upload_id'] ?>">
                                    <button type="submit" name="delete_file" class="delete-btn">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- File View Modal -->
<div id="uploadedFileModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeUploadedFileModal()">&times;</span>
        <h2>Uploaded File</h2>
        <div id="uploadedFileDisplay"></div>
    </div>
</div>

<style>
.uploaded-files-list {
    margin-top: 15px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
}

.uploaded-files-list ul {
    list-style-type: none;
    padding: 0;
    margin: 0;
}

.uploaded-files-list li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px;
    border-bottom: 1px solid #eee;
}

.uploaded-files-list li:last-child {
    border-bottom: none;
}

.file-info {
    flex-grow: 1;
    margin-right: 15px;
    font-size: 0.9em;
    color: #555;
}

.uploaded-files-list button {
    padding: 5px 10px;
    margin-left: 5px;
    font-size: 0.8em;
    border: none;
    border-radius: 4px;
    cursor: pointer;
}

.uploaded-files-list button:not(.delete-btn) {
    background-color: #007bff;
    color: white;
}

.delete-btn {
    background-color: #dc3545;
    color: white;
}

.uploaded-files-list h4 {
    margin: 0 0 10px 0;
    color: #333;
    font-size: 1em;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
    z-index: 1000;
    overflow: auto;
}

.modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    position: relative;
}

.modal-content .close {
    position: absolute;
    top: 10px;
    right: 20px;
    font-size: 28px;
    font-weight: bold;
    color: #aaa;
    cursor: pointer;
}

.modal-content .close:hover {
    color: #000;
}

#uploadedFileDisplay {
    margin-top: 20px;
    min-height: 300px;
}

#uploadedFileDisplay iframe {
    width: 100%;
    height: 500px;
    border: none;
}

#uploadedFileDisplay img {
    max-width: 100%;
    max-height: 500px;
    display: block;
    margin: 0 auto;
}
</style>

<script>
function openUploadedFileModal(fileUrl) {
    try {
        console.log("Attempting to open file:", fileUrl);
        
        // Check if it's a PDF
        if (fileUrl.toLowerCase().endsWith('.pdf')) {
            document.getElementById('uploadedFileDisplay').innerHTML = 
                `<iframe src="${fileUrl}" width="100%" height="500px"></iframe>`;
        } 
        // Check if it's an image
        else if (fileUrl.toLowerCase().match(/\.(jpeg|jpg|png|gif|bmp)$/)) {
            document.getElementById('uploadedFileDisplay').innerHTML = 
                `<img src="${fileUrl}" style="max-width: 100%; max-height: 500px; display: block; margin: 0 auto;">`;
        }
        // Check for DOCX (Word) files
        else if (fileUrl.toLowerCase().endsWith('.docx')) {
            document.getElementById('uploadedFileDisplay').innerHTML = 
                `<p>Word documents cannot be previewed. <a href="${fileUrl}" download>Download file</a></p>`;
        }
        // For other file types, show a download link
        else {
            document.getElementById('uploadedFileDisplay').innerHTML = 
                `<p>This file type cannot be previewed. <a href="${fileUrl}" download>Download file</a></p>`;
        }
        
        document.getElementById('uploadedFileModal').style.display = 'block';
    } catch (error) {
        console.error("Error opening file modal:", error);
        alert("Error opening file. Please check console for details.");
    }
}

function closeUploadedFileModal() {
    document.getElementById('uploadedFileModal').style.display = 'none';
    document.getElementById('uploadedFileDisplay').innerHTML = '';
}

window.onclick = function(event) {
    const modal = document.getElementById('uploadedFileModal');
    if (event.target == modal) {
        closeUploadedFileModal();
    }
}

// Enhanced delete functionality with debugging
document.addEventListener('DOMContentLoaded', function() {
    // Handle all delete buttons
    document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault(); // Always prevent default first
            
            // Get the form element
            const form = this.closest('form');
            const uploadId = form.querySelector('input[name="upload_id"]').value;
            
            console.log("Delete button clicked for upload ID:", uploadId);
            
            if (confirm('Are you sure you want to delete this file? This action cannot be undone.')) {
                // Add loading state
                const originalText = this.textContent;
                this.disabled = true;
                this.textContent = 'Deleting...';
                
                console.log("Proceeding with deletion of upload ID:", uploadId);
                
                // Create a temporary debug element
                const debugElement = document.createElement('div');
                debugElement.style.position = 'fixed';
                debugElement.style.bottom = '10px';
                debugElement.style.right = '10px';
                debugElement.style.padding = '10px';
                debugElement.style.backgroundColor = '#f8f9fa';
                debugElement.style.border = '1px solid #ddd';
                debugElement.style.borderRadius = '4px';
                debugElement.style.zIndex = '9999';
                debugElement.textContent = 'Submitting delete request...';
                document.body.appendChild(debugElement);
                
                // Submit the form after a brief delay to allow UI to update
                setTimeout(() => {
                    try {
                        form.submit();
                        debugElement.textContent = 'Delete request submitted';
                        setTimeout(() => debugElement.remove(), 2000);
                    } catch (submitError) {
                        console.error("Form submission error:", submitError);
                        debugElement.textContent = 'Error submitting form';
                        debugElement.style.backgroundColor = '#ffebee';
                        this.disabled = false;
                        this.textContent = originalText;
                        setTimeout(() => debugElement.remove(), 3000);
                    }
                }, 100);
            } else {
                console.log("Delete operation cancelled by user");
            }
        });
    });
    
    // Debug: Log all delete forms found
    const deleteForms = document.querySelectorAll('form[method="post"] input[name="delete_file"]');
    console.log(`Found ${deleteForms.length} delete forms in the document`);
    
    // Debug: Verify form submission handling
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function(e) {
            console.log("Form submitted:", this);
            // Don't prevent default - we want the form to submit
        });
    });
});

// Additional helper function to check if delete forms are properly set up
function debugDeleteForms() {
    const forms = document.querySelectorAll('form');
    forms.forEach((form, index) => {
        const hasDeleteInput = form.querySelector('input[name="delete_file"]');
        const hasUploadId = form.querySelector('input[name="upload_id"]');
        
        console.log(`Form ${index + 1}:`, {
            hasDeleteInput: !!hasDeleteInput,
            hasUploadId: !!hasUploadId,
            action: form.action || 'current URL',
            method: form.method
        });
    });
}

// Run the debug function when DOM is loaded
document.addEventListener('DOMContentLoaded', debugDeleteForms);
</script>

    <style>

    /* Uploaded File Modal */
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
.modal-content iframe {
    border: none;
}
        .status {
            font-weight: 600;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9rem;
            display: inline-block;
        }
        .status.pending { 
            color: #ff9800; 
            background-color: rgba(255, 152, 0, 0.1);
        }
        .status.uploaded { 
            color: #4caf50; 
            background-color: rgba(76, 175, 80, 0.1);
        }
        .status.approved { 
            color: #2196f3; 
            background-color: rgba(33, 150, 243, 0.1);
        }

        .document-item {
            margin-bottom: 24px;
            padding: 20px;
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            background: #fff;
        }
        .document-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .document-item h3 {
            margin-top: 0;
            font-size: 1.2rem;
            color: #333;
        }

        .document-item p {
            font-size: 0.95rem;
            color: #555;
            margin-bottom: 10px;
        }

        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            text-decoration: none;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
        .btn:hover {
            background: #0056b3;
        }

        .upload-section {
            margin-top: 12px;
        }

        .upload-section button {
            margin-top: 6px;
            padding: 8px 14px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9rem;
            transition: background 0.2s ease;
        }
        .upload-section button:hover {
            background: #218838;
        }

        /* Responsive */
        @media (max-width: 600px) {
            .document-item {
                padding: 16px;
            }
            .btn, .upload-section button {
                width: 100%;
                text-align: center;
            }
        }
    </style>

    <script>
        function markUploaded(docType) {
            const statusElement = document.getElementById('status-' + docType);
            statusElement.textContent = "Uploaded (Pending Review)";
            statusElement.classList.remove('pending');
            statusElement.classList.add('uploaded');
            alert(docType.replace('-', ' ').toUpperCase() + " uploaded!");
        }
    </script>

</div>
