<?php
require 'vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
try {
    $db = new PDO('mysql:host=localhost;dbname=projectmanagementsystem', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$message = ''; // Initialize a message variable
$redirect_url = ''; // Initialize redirect URL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process the token submitted via POST request
    if (isset($_POST['token']) && !empty(trim($_POST['token']))) {
        $token = htmlspecialchars(trim($_POST['token'])); // Sanitize and trim the token

        // Verify the token
        $stmt = $db->prepare("SELECT * FROM users WHERE email_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if the user is already verified
            if ($user['is_verified']) {
                $message = "Email is already verified. Redirecting to login...";
                $redirect_url = "login.php";
            } else {
                // Update user to set is_verified to 1
                $stmt = $db->prepare("UPDATE users SET is_verified = 1, email_token = NULL WHERE email_token = ?"); // Set token to NULL after use for security
                if ($stmt->execute([$token])) {
                    // Email verified successfully in database, now send confirmation email
                    $mail = new PHPMailer(true);
                    try {
                        $mail->isSMTP();
                        $mail->Host       = 'smtp.gmail.com';
                        $mail->SMTPAuth   = true;
                        $mail->Username   = 'venste.acc@gmail.com'; // Replace with your email
                        $mail->Password   = 'cublnjqjffwhrbey'; // Use an App Password if 2FA is enabled
                        $mail->SMTPSecure = 'tls';
                        $mail->Port       = 587;

                        $mail->setFrom('venste.acc@gmail.com', 'JT Kitchen Installation Services'); // Ensure this matches your SMTP username
                        $mail->addAddress($user['email']); // Send to the verified user's email
                        $mail->isHTML(true);

                        // --- IMPROVED EMAIL STRUCTURE FOR VERIFICATION CONFIRMATION ---
                        $mail->Subject = 'Your JT Kitchen Account is Now Verified!';
                        $mail->Body    = "
                            <div style='font-family: Arial, sans-serif; line-height: 1.6; color: #333;'>
                                <h2 style='color: #28a745;'>Email Verification Complete!</h2>
                                <p>Dear " . htmlspecialchars($user['first_name'] ?? 'User') . ",</p>
                                <p>We're excited to inform you that your email address for your JT Kitchen Equipment Installation Services account has been successfully verified.</p>
                                <p>You can now log in and start using your account. </p>
                                <p style='text-align: center; margin: 30px 0;'>
                                    <a href='http://localhost/projectmanagementsystem4/registration-form/login.php'
                                       style='display: inline-block; padding: 12px 25px; background-color: #80000A; color: #ffffff; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                                       Log In to Your Account
                                    </a>
                                </p>
                                <p>Thank you for choosing JT Kitchen Equipment Installation Services!</p>
                                <p>Best regards,<br>The JTKEIS Team</p>
                                <hr style='border: 0; height: 1px; background: #eee; margin: 20px 0;'>
                                <p style='font-size: 0.8em; color: #999; text-align: center;'>
                                    This is an automated email. Please do not reply.
                                </p>
                            </div>
                        ";
                        $mail->AltBody = "Dear " . ($user['first_name'] ?? 'User') . ",\n\n"
                                       . "Your email address for your JT Kitchen Equipment Installation Services account has been successfully verified!\n\n"
                                       . "You can now log in to your account using your credentials.\n\n"
                                       . "Log in here: http://localhost/projectmanagementsystem4/registration-form/login.php\n\n"
                                       . "Thank you for choosing JT Kitchen Equipment Installation Services!\n"
                                       . "Best regards,\nThe JTKEIS Team\n\n"
                                       . "--- (End of Message) ---\n"
                                       . "This is an automated email. Please do not reply.";
                        // --- END OF IMPROVED EMAIL STRUCTURE ---
                        $mail->send();
                        $message = "Verification Successful!. A confirmation email has been sent. Redirecting to login...";
                        $redirect_url = "login.php";

                    } catch (Exception $e) {
                        // Log the PHPMailer error but still proceed with successful verification message
                        error_log("Confirmation email could not be sent. Mailer Error: {$mail->ErrorInfo}");
                        $message = "Email verified successfully! However, the confirmation email could not be sent. Redirecting to login...";
                        $redirect_url = "login.php";
                    }
                } else {
                    $message = "Failed to verify email. Please try again.";
                }
            }
        } else {
            $message = "Invalid token. Please check the token and try again.";
        }
    } else {
        $message = "No token provided. Please enter your verification token.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Email - JT Kitchen Equipment Installation</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
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

        .logo-container { /* Added for consistent logo centering */
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .logo {
            width: 100px; /* Consistent logo size with signup page */
            height: auto;
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

        input[type="text"], button {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }

        input[type="text"]:focus {
            border-color: #cc0000;
            outline: none;
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
            border-radius: 4px;
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

        @media (max-width: 600px) {
            .container {
                width: 90%;
            }
        }

        /* Loading Overlay Styles */
        #loadingOverlay {
            position: fixed;
            inset: 0; /* Equivalent to top:0; right:0; bottom:0; left:0; */
            background-color: rgba(0, 0, 0, 0.75); /* Semi-transparent dark background */
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 50; /* Ensure it's above other content */
            opacity: 0; /* Start hidden */
            visibility: hidden;
            transition: opacity 0.3s ease-in-out, visibility 0.3s ease-in-out;
        }

        #loadingOverlay.visible {
            opacity: 1;
            visibility: visible;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3); /* Light border for the circle */
            border-top: 4px solid #cc0000; /* Red part of the spinner */
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
    <div class="header">
        <div class="logo-container">
            <img src="no-bg logo.png" alt="JT Kitchen Logo" class="logo">
        </div>
        <h1>JT KITCHEN EQUIPMENT INSTALLATION SERVICE</h1>
    </div>

    <h2>Verify Your Email to Continue</h2>

    <?php if (!empty($message)): ?>
        <div id="phpMessage" class="alert <?php echo (strpos($message, 'successfully') !== false || strpos($message, 'Redirecting') !== false) ? 'success' : 'error'; ?>"
             <?php if (!empty($redirect_url)): ?>data-redirect-url="<?php echo $redirect_url; ?>"<?php endif; ?>>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="verify_email.php">
        <input type="text" id="token" name="token" required placeholder="Enter your verification token">
        <button type="submit">Verify Email</button>
    </form>

    <p>Return to <a href="login.php">Login</a></p>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay">
    <div class="spinner"></div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const phpMessageDiv = document.getElementById('phpMessage');
        const loadingOverlay = document.getElementById('loadingOverlay');

        // Check if a success message is displayed and if there's a redirect URL
        if (phpMessageDiv) {
            const redirectUrl = phpMessageDiv.dataset.redirectUrl;

            if (redirectUrl) {
                // Add a small delay before showing the spinner to ensure the message is visible briefly
                setTimeout(() => {
                    loadingOverlay.classList.add('visible'); // Show the loading overlay

                    // Redirect after the spinner has been visible for some time
                    setTimeout(() => {
                        window.location.href = redirectUrl;
                    }, 2000); // 2 seconds for the spinner to be shown before redirecting
                }, 500); // 0.5 seconds delay before spinner appears
            }
        }
    });
</script>

</body>
</html>
