<?php
session_start();
require_once 'connect.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$isAdmin = isAdmin();

try {
    $db = DatabaseConnection::getInstance()-> getConnection();
    $stmt = $db->prepare("SELECT first_name, last_name, email, age, sex FROM users WHERE user_id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }
} catch (PDOException $e) {
    logSecurityEvent('Profile Fetch Error', $e->getMessage());
    die("An error occurred while fetching your profile.");
}

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!validateCsrfToken($_POST['csrf_token'])) {
        die("Invalid CSRF token");
    }

    $first_name = sanitizeInput($_POST['first_name']);
    $last_name = sanitizeInput($_POST['last_name']);
    $email = sanitizeInput($_POST['email']);
    $age = filter_var($_POST['age'], FILTER_VALIDATE_INT);
    $sex = sanitizeInput($_POST['sex']);
    $errors = [];

    if (!validateEmail($email)) {
        $errors[] = "Invalid email format.";
    }

    if ($email !== $user['email']) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "Email already exists.";
        }
    }

    if (empty($errors)) {
        try {
            $stmt = $db->prepare("
                UPDATE users 
                SET first_name = ?, last_name = ?, email = ?, age = ?, sex = ?
                WHERE user_id = ?
            ");
            $result = $stmt->execute([$first_name, $last_name, $email, $age, $sex, $userId]);

            if ($result) {
                $_SESSION['success'] = "<h3>Profile updated successfully.</h3> <br> This tab will automatically close.";
                $user = ['first_name' => $first_name, 'last_name' => $last_name, 'email' => $email, 'age' => $age, 'sex' => $sex];
            } else {
                $_SESSION['error'] = "Failed to update profile.";
            }
        } catch (PDOException $e) {
            logSecurityEvent('Profile Update Error', $e->getMessage());
            $_SESSION['error'] = "An error occurred while updating your profile.";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode(", ", $errors);
    }

    header("Location: usersettings.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


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
        <p class="modal-subtext">Profile updated successfully! This tab will close automatically.</p>
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



<!-- Main Header -->
<header class="main-header">
    <div class="header-content">
        <div>
        <h1>Settings</h1>

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

</style>


<div class="main-content">
<div class="main-wrapper1">

<!-- Left content -->
<div class="main-content-left1">
  <h1> Profile </h1>

<a href="userdashboard.php" class="back-button">Back</a>

<?php
    // Safely assign user data
    $firstName = htmlspecialchars($user['first_name'] ?? '');
    $lastName  = htmlspecialchars($user['last_name'] ?? '');
    $email     = htmlspecialchars($user['email'] ?? '');
    $age       = htmlspecialchars($user['age'] ?? '');
    $sex       = htmlspecialchars($user['sex'] ?? '');
?>

<!-- Profile display section -->
<div class="profile-view">
    <!-- 🟢 USER ICON -->
    <div class="user-icon">
        <i class="fas fa-user-circle"></i>
    </div>

    <p><strong>First Name:</strong> <?= $firstName ?></p>
    <p><strong>Last Name:</strong> <?= $lastName ?></p>
    <p><strong>Email:</strong> <?= $email ?></p>
    <p><strong>Age:</strong> <?= $age ?></p>
    <p><strong>Sex:</strong> <?= ucfirst($sex) ?></p>

    <button class="edit-toggle" onclick="toggleEdit()">Edit Profile</button>
</div>

<style>
    .user-icon {
    text-align: center;
    margin-bottom: 20px;
}

.user-icon i {
    font-size: 80px; /* adjust size as needed */
    color: #555; /* icon color */
}

</style>

<!-- Profile editing form -->
<div class="profile-edit" style="display: none;">
    <form method="POST" action="usersettings.php">
        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken(); ?>">

        <div class="form-group">
            <label for="first_name"><b>First Name</b></label>
            <input type="text" id="first_name" name="first_name" placeholder="First Name" value="<?= $firstName ?>" required>
        </div>

        <div class="form-group">
            <label for="last_name"><b>Last Name</b></label>
            <input type="text" id="last_name" name="last_name" placeholder="Last Name" value="<?= $lastName ?>" required>
        </div>

        <div class="form-group">
            <label for="email"><b>Email Address</b></label>
            <input type="email" id="email" name="email" placeholder="Email" value="<?= $email ?>" required>
        </div>

        <div class="form-group">
            <label for="age"><b>Age</b></label>
            <input type="number" id="age" name="age" placeholder="Age" value="<?= $age ?>" required>
        </div>

        <div class="form-group">
            <label for="sex"><b>Sex</b></label>
            <select id="sex" name="sex" required>
                <option value="">Select Sex</option>
                <option value="male" <?= $sex === 'male' ? 'selected' : '' ?>>Male</option>
                <option value="female" <?= $sex === 'female' ? 'selected' : '' ?>>Female</option>
                <option value="other" <?= $sex === 'other' ? 'selected' : '' ?>>Other</option>
            </select>
        </div>

        <div class="button-group">
            <button type="submit" class="edit-toggle">Update Profile</button>
            <button type="button" class="cancel-button" onclick="toggleEdit()">Cancel</button>
        </div>
    </form>
</div>

        </form>
    </div>
</div>

</style>


    <style>
        /* 🟢 USER ICON STYLE */
.user-icon {
    display: flex;
    justify-content: left;
    align-items: left;
    margin-bottom: 20px;
}

.user-icon img {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background-color: #f3f4f6;
    object-fit: cover;
    align-items: left;
}

    
    </style>
</body>
</html>


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




/* Back Button */
a.back-button {
    display: inline-block;
    margin-bottom: 25px;
    background-color: #ff4444;
    color: #fff;
    padding: 10px 18px;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s ease;
    width: 5.5%;
}
a.back-button:hover {
    background-color: #d63737;
}

/* Profile View */
.profile-view p {
    font-size: 18px;
    margin-bottom: 10px;
}
.profile-view strong {
    color: #333;
}

/* Edit/Cancel Buttons */
.edit-toggle,
.cancel-button,
button[type="submit"] {
    display: inline-block;
    padding: 12px 20px;
    font-size: 1rem;
    font-weight: bold;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.edit-toggle {
    background-color: #28a745;
    color: white;
}
.edit-toggle:hover {
    background-color: #218838;
}

.cancel-button {
    background-color: #dc3545;
    color: white;
}
.cancel-button:hover {
    background-color: #c82333;
}

/* Logout Button */
a.logout-button {
    display: block;
    text-align: center;
    background-color: #ff4444;
    color: white;
    padding: 12px;
    border-radius: 6px;
    font-weight: bold;
    text-decoration: none;
    transition: background-color 0.3s ease;
}
a.logout-button:hover {
    background-color: #c82333;
}

/* Form Styles */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
    max-width: 500px;
    width: 100%;
}

form label {
    font-size: 16px;
    color: #333;
    font-weight: 500;
}

form input,
form select {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    border-radius: 6px;
    border: 1px solid #d1d5db;
    background-color: #f3f4f6;
    transition: border-color 0.3s;
}

form input:focus,
form select:focus {
    border-color: #ff4444;
    outline: none;
}

/* Button Group */
.button-group {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
    margin-top: 10px;
}

/* Responsive Adjustments */
@media (max-width: 768px) {
    .button-group {
        flex-direction: column;
    }

    form {
        gap: 12px;
    }
}

</style>

<script>
    function toggleEdit() {
    const view = document.querySelector('.profile-view');
    const edit = document.querySelector('.profile-edit');
    const isEditing = edit.style.display === 'block';
    view.style.display = isEditing ? 'block' : 'none';
    edit.style.display = isEditing ? 'none' : 'block';
}

</script>
</div>

</div>

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
    flex: 1;
    min-width: 500px;
    background: white;
    padding: 25px;
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    font-family: sans-serif;
    height: 600px;
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

</style>

<div class="main-content">
<div class="main-wrapper2">

<!-- Left content -->
<div class="main-content-left2">
  <h1>Language</h1>
  <canvas id="appointmentChart"></canvas>
</div>

<!-- Right content -->
<div class="main-content-right2">
  <h1>Theme</h1>
  <canvas id="summaryChart"></canvas>
</div>

</div>

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
    flex: 1;
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
</style>