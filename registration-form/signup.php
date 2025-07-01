<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$signup_status_message = ''; // Initialize message for signup success/failure
$signup_redirect_url = ''; // Initialize redirect URL

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Database connection
    try {
        $db = new PDO('mysql:host=localhost;dbname=projectmanagementsystem', 'root', '');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        die("Database connection failed: " . $e->getMessage());
    }

    // Sanitize inputs
    $first_name = htmlspecialchars(trim($_POST['first_name']));
    $last_name = htmlspecialchars(trim($_POST['last_name']));
    $email = htmlspecialchars(trim($_POST['email']));
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $token = bin2hex(random_bytes(16)); // Generate a secure token
    // Assuming age and sex are also part of your database schema
    $age = isset($_POST['age']) ? (int) $_POST['age'] : 0;
    $sex = htmlspecialchars(trim($_POST['sex'] ?? ''));


    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error'] = "Invalid email format.";
    }

    // Optional: Restrict to specific domains (e.g., only Gmail)
    $allowed_domains = ['gmail.com', 'yahoo.com', 'outlook.com'];
    $email_domain = substr(strrchr($email, "@"), 1);
    if (!in_array($email_domain, $allowed_domains)) {
        $_SESSION['error'] = "Email domain not allowed.";
    }

    // Optional: Check for blacklisted emails
    $blacklisted_emails = ['test@example.com', 'spam@domain.com'];
    if (in_array($email, $blacklisted_emails)) {
        $_SESSION['error'] = "This email is not allowed to register.";
    }

    // Check if email already exists, but only if no previous errors
    if (!isset($_SESSION['error'])) {
        $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $_SESSION['error'] = "Email already exists.";
        }
    }

    // Proceed with signup only if no errors
    if (!isset($_SESSION['error'])) {
        // Insert user into the database, including age and sex
        $stmt = $db->prepare("INSERT INTO users (first_name, last_name, email, password, email_token, age, sex) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($stmt->execute([$first_name, $last_name, $email, $password, $token, $age, $sex])) {
            // Send confirmation email
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'venste.acc@gmail.com'; // Replace with your email
                $mail->Password   = 'cublnjqjffwhrbey'; // Use an App Password if 2FA is enabled
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('venste.acc@gmail.com', 'JT Kitchen Equipment Installation Services'); // Replace with your name
                $mail->addAddress($email);
                $mail->isHTML(true);

               // --- UPDATED EMAIL STRUCTURE FOR CONFIRMATION TOKEN ---
                $mail->Subject = 'Action Required: Verify Your Email for JT Kitchen Equipment Installation Services';
                $mail->Body    = "
                    <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                        <h2 style='color: #80000a;'>Verify Your Email Address</h2>
                        <p>Hello " . htmlspecialchars($first_name) . ",</p>
                        <p>Thank you for registering with JT Kitchen Equipment Installation Services. To activate your account, please use the verification token below:</p>
                        <div style='background-color: #80000a; padding: 15px; border-radius: 5px; text-align: center; margin: 20px 0;'>
                            <strong style='font-size: 24px; color:rgb(255, 255, 255); letter-spacing: 2px;'>" . htmlspecialchars($token) . "</strong>
                        </div>
                        <p>Please copy this token and paste it into the designated field on our <a href='http://localhost/projectmanagementsystem4/registration-form/verify_email.php' style='color: #cc0000; text-decoration: none;'>email verification page</a> to complete your registration.</p>
                        <p style='font-size: 0.9em; color: #777;'>This token is valid for 10 minutes only due to security reasons.</p>
                        <p>If you did not sign up for an account, please ignore this email.</p>
                        <p>Thank you for choosing JT Kitchen Equipment Installation Services!</p>
                        <p>Best regards,<br>The JTKEIS Team</p>
                           <p style='font-size: 0.8em; color: #999; text-align: center;'>
                                    This is an automated email. Please do not reply.
                                </p>
                    </div>
                ";
                $mail->AltBody = "Hello " . htmlspecialchars($first_name) . ",\n\n"
                               . "Thank you for registering with JT Kitchen Equipment Installation Services. To activate your account, please use the verification token below:\n\n"
                               . "Verification Token: " . htmlspecialchars($token) . "\n\n"
                               . "Please copy this token and paste it into the designated field on our email verification page: http://localhost/projectmanagementsystem0/registration-form/verify_email.php\n\n"
                               . "This token is valid for a limited time for security reasons.\n\n"
                               . "If you did not sign up for an account, please ignore this email.\n\n"
                               . "Thank you for choosing JT Kitchen Equipment Installation Services!\n"
                               . "Best regards,\nThe JTKEIS Team";

                $mail->send();

                // Set flags for the frontend JavaScript to handle redirection with animation
                $signup_status_message = "Signup successful! A verification token has been sent to your email. Redirecting to verification page...";
                $signup_redirect_url = "/projectmanagementsystem4/registration-form/verify_email.php";

            } catch (Exception $e) {
                // If email sending fails, set an error message. The user won't be redirected immediately.
                $signup_status_message = "Signup successful, but confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}. Please try verifying later.";
                // You might log this error or provide an option to resend the email
            }
        } else {
            $_SESSION['error'] = "Signup failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - JT Kitchen Equipment Installation Services</title>
    <!-- Font Awesome for eye icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts for Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* General Styling */
        body {
            font-family: 'Inter', sans-serif;
            background-image: linear-gradient(to bottom, rgba(0, 0, 0, 0), rgba(0, 0, 0, 0.7)), url(login-signup.png);
            background-size: cover;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            overflow: hidden; /* Kept as per your original code */
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            width: 100%;
            text-align: center;
        }

        /* Logo styling */
        .logo-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 100px; /* Adjust the size of the logo */
            height: auto;
        }

        /* Title and description */
        h2 {
            font-size: 22px;
            color: #333;
            margin-bottom: 10px;
        }

        p {
            font-weight: lighter;
            margin-top: 20px;
            font-size: 18px;
            color: #cc0000;
        }

        /* Error/Success message styling */
        .alert {
            padding: 10px;
            margin: 10px 0; /* Consistent margin with login.php */
            border-radius: 4px;
            font-size: 14px; /* Explicitly set font size */
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
        }

        /* Input fields and labels */
        input[type="text"], input[type="email"], input[type="password"], input[type="number"], select {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 16px;
        }

        /* Focused input fields */
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="number"]:focus, select:focus {
            border-color: #cc0000;
            outline: none;
        }

        /* Password visibility toggle */
        .toggle-password {
            cursor: pointer;
            font-size: 18px;
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #888;
        }

        /* Ensure the password container has relative positioning */
        .password-container {
            position: relative;
        }

        .name-container input:last-child {
            margin-right: 0;
        }

        /* Container for age and sex fields */
        .age-sex-container {
            display: flex;
            justify-content: space-between;
            gap: 12px;
        }

        /* Button */
        button {
            background-color: #cc0000;
            color: white;
            border: none;
            padding: 14px;
            width: 100%;
            font-size: 16px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }

        button:hover {
            background-color: rgb(180, 2, 2);
        }

        /* Links */
        a {
            display: inline-block;
            margin-top: 15px;
            color: #cc0000;
            text-decoration: none;
            font-size: 14px;
        }

        a:hover {
            text-decoration: underline;
        }

        /* Responsive design */
        @media (max-width: 600px) {
            .container {
                width: 90%;
                padding: 20px;
            }

            /* Adjusting name input fields for smaller screens */
            .name-container input {
                width: 100%;
                margin-right: 0;
            }

            /* Adjusting age and sex fields for smaller screens */
            .age-sex-container {
                flex-direction: column;
            }
        }

        /* Loading Overlay Styles (copied from verify_email.php) */
        #loadingOverlay {
            position: fixed;
            inset: 0;
            background-color: rgba(0, 0, 0, 0.75);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        #loadingOverlay.visible {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #cc0000;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Logo Image -->
        <div class="logo-container">
            <img src="no-bg logo.png" alt="Logo" class="logo">
        </div>

        <h2>JT KITCHEN EQUIPMENT INSTALLATION SERVICES</h2>
        <p>Sign up to continue</p>

        <?php
        // Display existing session errors
        if (isset($_SESSION['error'])) {
            echo "<div class='alert error'>" . htmlspecialchars($_SESSION['error']) . "</div>";
            unset($_SESSION['error']);
        }
        // Display signup status message and trigger redirect
        if (!empty($signup_status_message)): ?>
            <div id="signupMessage" class="alert <?php echo (strpos($signup_status_message, 'successful') !== false) ? 'success' : 'error'; ?>"
                 <?php if (!empty($signup_redirect_url)): ?>data-redirect-url="<?php echo $signup_redirect_url; ?>"<?php endif; ?>>
                <?php echo $signup_status_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="signup.php">
            <div class="name-container">
                <input type="text" id="first-name" name="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                <input type="text" id="last-name" name="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                <input type="email" id="email" name="email" placeholder="Email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>

            <div class="age-sex-container">
                <input type="number" id="age" name="age" placeholder="Age" value="<?php echo htmlspecialchars($_POST['age'] ?? ''); ?>" required>
                <select id="sex" name="sex" required>
                    <option value="">Select Sex</option>
                    <option value="male" <?php echo ($_POST['sex'] ?? '') === 'male' ? 'selected' : ''; ?>>Male</option>
                    <option value="female" <?php echo ($_POST['sex'] ?? '') === 'female' ? 'selected' : ''; ?>>Female</option>
                    <option value="other" <?php echo ($_POST['sex'] ?? '') === 'other' ? 'selected' : ''; ?>>Other</option>
                </select>
            </div>

            <div class="password-container">
                <input type="password" id="password" name="password" placeholder="Password" value="<?php echo htmlspecialchars($_POST['password'] ?? ''); ?>" required>
                <span class="toggle-password" data-field="password"><i class="fas fa-eye"></i></span>
            </div>

            <div class="password-container">
                <input type="password" id="confirm-password" name="confirm_password" placeholder="Confirm Password" value="<?php echo htmlspecialchars($_POST['confirm_password'] ?? ''); ?>" required>
                <span class="toggle-password" data-field="confirm-password"><i class="fas fa-eye"></i></span>
            </div>

            <button type="submit">Sign Up</button>
        </form>

        <a href="/projectmanagementsystem4/registration-form/login.php">Already have an account? Login</a>
    </div>
email
    <!-- Loading Overlay -->
    <div id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script>
        // Password visibility toggle script
        document.querySelectorAll('.toggle-password').forEach((eyeIcon) => {
            eyeIcon.addEventListener('click', function () {
                const field = document.getElementById(this.getAttribute('data-field'));
                const icon = this.querySelector('i');

                if (field.type === "password") {
                    field.type = "text";
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    field.type = "password";
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });

        // Loading animation and redirect script
        document.addEventListener('DOMContentLoaded', function() {
            const signupMessageDiv = document.getElementById('signupMessage');
            const loadingOverlay = document.getElementById('loadingOverlay');

            // Check if a success message is displayed and if there's a redirect URL
            if (signupMessageDiv) {
                const redirectUrl = signupMessageDiv.dataset.redirectUrl;

                if (redirectUrl) {
                    // Add a small delay before showing the spinner to ensure the message is visible briefly
                    setTimeout(() => {
                        loadingOverlay.classList.add('visible'); // Show the loading overlay

                        // Redirect after the spinner has been visible for some time
                        setTimeout(() => {
                            window.location.href = redirectUrl;
                        }, 3000); // 2 seconds for the spinner to be shown before redirecting
                    }, 500); // 0.5 seconds delay before spinner appears
                }
            }
        });
    </script>
</body>
</html>
