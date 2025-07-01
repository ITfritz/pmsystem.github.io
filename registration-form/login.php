<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Initialize attempts and lockout timestamp if not set
if (!isset($_SESSION['attempts'])) {
    $_SESSION['attempts'] = 0;
}
if (!isset($_SESSION['lockout_time'])) {
    $_SESSION['lockout_time'] = 0;
}

$max_attempts = 3;

// Ensure the code only runs if the request method is POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // --- Database Connection ---
    // This try-catch block specifically handles potential PDO (database) connection errors.
    try {
        $db = new PDO('mysql:host=localhost;dbname=projectmanagementsystem;charset=utf8mb4', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        // Log the database connection error for debugging purposes (e.g., in your server logs)
        error_log("Database connection failed: " . $e->getMessage());
        // Set a user-friendly error message in the session
        $_SESSION['error'] = "An internal error occurred. Please try again later.";
        // Redirect back to the login page
        header("Location: /projectmanagementsystem4/registration-form/login.php");
        exit(); // Terminate script execution after redirect
    }

    // --- Input Sanitization ---
    // Sanitize and trim whitespace from the email input
    $email = htmlspecialchars(trim($_POST['email']));
    // The password is not sanitized with htmlspecialchars here because it will be verified directly
    // against a hashed password, and special characters are part of a valid password.
    $password = $_POST['password'];

    // --- User Authentication ---
    // Prepare a SQL statement to fetch user details, including `is_blocked`, `is_verified`, and `role`.
    // It's good practice to select only the columns you need for security and performance.
    $stmt = $db->prepare("SELECT user_id, email, password, role, is_verified, is_blocked FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Fetch the result as an associative array

    // Check if a user with the provided email exists
    if ($user) {
        // Check if the user is blocked
        if ($user['is_blocked']) {
            $_SESSION['error'] = "Your account has been blocked. Please contact the administrator.";
            header("Location: /projectmanagementsystem4/registration-form/login.php");
            exit();
        }

        // Verify the provided password against the hashed password stored in the database
        if (password_verify($password, $user['password'])) {
            // Check if the user's email is verified OR if the user is an admin OR a superadmin.
            // This is the combined condition for allowing login.
            if ($user['is_verified'] || $user['role'] === 'admin' || $user['role'] === 'superadmin') {
                // --- Successful Login Actions ---
                // Regenerate session ID to prevent session fixation attacks.
                // The `true` parameter deletes the old session file immediately.
                session_regenerate_id(true);

                // Store essential user data in the session
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['logged_in'] = true; // A flag to indicate the user is logged in

                // Reset attempts on successful login
                $_SESSION['attempts'] = 0;

                // Redirect based on the user's role AFTER successful login
                if ($user['role'] === 'superadmin') {
                    // Redirect super administrators to their dedicated dashboard
                    header("Location: /projectmanagementsystem4/super_admin_dashboard.php");
                } elseif ($user['role'] === 'admin') {
                    // Redirect administrators to the admin dashboard
                    header("Location: /projectmanagementsystem4/admin_dashboard.php");
                } else {
                    // Redirect regular users to the user dashboard (e.g., project.php or userdashboard.php)
                    header("Location: /projectmanagementsystem4/userdashboard.php");
                }
                exit(); // Terminate script execution after redirect
            } else {
                // --- Email Not Verified (for non-admin/non-superadmin users only) ---
                // This block is reached if:
                // 1. The user is NOT verified (is_verified is false)
                // AND
                // 2. The user is NOT an admin (role is not 'admin')
                // AND
                // 3. The user is NOT a superadmin (role is not 'superadmin')
                $_SESSION['error'] = "Please verify your email before logging in.";
                header("Location: /projectmanagementsystem4/registration-form/login.php");
                exit(); // Terminate script execution
            }
        } else {
            // --- Invalid Password ---
            // Increment the attempt counter
            $_SESSION['attempts']++;

            // Block the user after three failed attempts
            if ($_SESSION['attempts'] >= $max_attempts) {
                try {
                    $stmt = $db->prepare("UPDATE users SET is_blocked = 1 WHERE email = ?");
                    $stmt->execute([$email]);
                    $_SESSION['error'] = "Your account has been blocked due to too many failed login attempts. Please contact the administrator.";
                } catch (PDOException $e) {
                    error_log("Failed to block user: " . $e->getMessage());
                    $_SESSION['error'] = "An internal error occurred. Please try again later.";
                }
            } else {
                $_SESSION['error'] = "Invalid email or password.";
            }

            header("Location: /projectmanagementsystem4/registration-form/login.php");
            exit(); // Terminate script execution
        }
    } else {
        // --- User Not Found ---
        // Increment the attempt counter
        $_SESSION['attempts']++;

        // Block the user after three failed attempts
        if ($_SESSION['attempts'] >= $max_attempts) {
            try {
                $stmt = $db->prepare("UPDATE users SET is_blocked = 1 WHERE email = ?");
                $stmt->execute([$email]);
                $_SESSION['error'] = "Your account has been blocked due to too many failed login attempts. Please contact the administrator.";
            } catch (PDOException $e) {
                error_log("Failed to block user: " . $e->getMessage());
                $_SESSION['error'] = "An internal error occurred. Please try again later.";
            }
        } else {
            $_SESSION['error'] = "Invalid email or password.";
        }

        header("Location: /projectmanagementsystem4/registration-form/login.php");
        exit(); // Terminate script execution
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - JT Kitchen Equipment Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">

    <style>
        body {
            font-family: sans-serif;
            background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.7)), url(login-signup.png);
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
        }

        .header {
            margin-bottom: 20px;
        }

        .logo {
            width: 150px;
            height: auto;
            display: block;
            margin: 0 auto 10px auto;
        }

        h1 {
            font-size: 22px;
            color: #333;
        }

        h2 {
            font-weight: lighter;
            font-size: 18px;
            color: #cc0000;
        }

        input[type="text"], input[type="password"], button {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #cc0000;
            outline: none;
        }

        .show-password {
            display: flex;
            align-items: center;
            gap: 5px;
            margin-bottom: 10px;
        }

        button {
            background-color: #cc0000;
            color: white;
            border: none;
            cursor: pointer;
            font-size: 16px;
            padding: 12px;
            border-radius: 5px;
        }

        button:hover {
            background-color: rgb(180, 2, 2);
        }

        .alert {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        p a {
            color: #cc0000;
            text-decoration: none;
        }

        p a:hover {
            text-decoration: underline;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="no-bg logo.png" alt="JT Kitchen Logo" class="logo">
        <h1>JT KITCHEN EQUIPMENT INSTALLATION SERVICE</h1>
    </div>
    <h2>Login to continue</h2>

    <?php
    if (isset($_SESSION['error'])) {
        echo "<p class='alert error'>" . htmlspecialchars($_SESSION['error']) . "</p>";
        unset($_SESSION['error']);
    }
    if (isset($_SESSION['signup_success'])) {
        echo "<p class='alert success'>" . htmlspecialchars($_SESSION['signup_success']) . "</p>";
        unset($_SESSION['signup_success']);
    }
    ?>

    <form id="loginForm" action="/projectmanagementsystem4/registration-form/login.php" method="POST">
        <input type="text" id="email" name="email" required placeholder="Enter your email">
        <input type="password" id="password" name="password" required placeholder="Enter your password">
        <div class="show-password">
            <input type="checkbox" id="show-password">
            <label for="show-password">Show Password</label>
        </div>
        <button type="submit">Login</button>
    </form>
    <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
    <p>Need help? <a href="contact_support.php">Contact Support</a></p>
</div>

<!-- The Modal -->
<div id="myModal" class="modal">
    <div class="modal-content">
        <span class="close">&times;</span>
        <h2>Your account is locked</h2>
        <p>Please wait <span id="timer"></span> seconds before trying again.</p>
        <p>If you need assistance, please <a href="contact_support.php" target="_blank">contact support</a>.</p>
    </div>
</div>

<script>
    const passwordInput = document.getElementById('password');
    const showPasswordCheckbox = document.getElementById('show-password');

    showPasswordCheckbox.addEventListener('change', function () {
        passwordInput.type = this.checked ? 'text' : 'password';
    });

    // Get the modal
    var modal = document.getElementById("myModal");

    // Get the <span> element that closes the modal
    var span = document.getElementsByClassName("close")[0];

    // Get the timer element
    var timerElement = document.getElementById("timer");

    // Get the lockout period from the session
    var lockoutPeriod = <?php echo isset($_SESSION['lockout_period']) ? $_SESSION['lockout_period'] : 0; ?>;

    // When the user clicks on <span> (x), close the modal
    span.onclick = function() {
        modal.style.display = "none";
    }

    // When the user clicks anywhere outside of the modal, close it
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    }

    // Show the modal if the lockout period is set
    if (lockoutPeriod > 0 || new URLSearchParams(window.location.search).has('showModal')) {
        modal.style.display = "block";
        var timeLeft = lockoutPeriod;
        var countdown = setInterval(function() {
            timerElement.textContent = timeLeft;
            if (timeLeft <= 0) {
                clearInterval(countdown);
                modal.style.display = "none";
            }
            timeLeft--;
        }, 1000);
    }
</script>
</body>
</html>