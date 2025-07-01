<?php
// Database connection parameters - update these with your real credentials
$host = 'localhost';
$user = 'root';
$password = ''; 
$database = 'projectmanagementsy';

// Create connection
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query top 3 most recent orders
$sql = "SELECT clientName, date, time, status, description, created_at FROM orders ORDER BY created_at DESC LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Your Requests - Pending Orders</title>
    <style>
        /* Modern styling based on your CSS */
        body {
            font-family: 'Poppins', sans-serif;
            background: #f9f9f9;
            color: #8a0000;
            padding: 20px;
            margin: 0;
        }
        .main-content-right {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        h2 {
            font-size: 2rem;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
            color: #8a0000;
        }
        #pendingCountMessage {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }
        ul#pendingBookingsList {
            list-style-type: none;
            padding: 0;
        }
        .pending-booking-item {
            display: flex;
            align-items: center;
            gap: 24px;
            padding: 24px 28px;
            background: whitesmoke;
            border-radius: 12px;
            margin-bottom: 15px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .pending-booking-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(0,0,0,0.12);
            background-color: #f7f7f7;
        }
        .icon {
            font-size: 90px;
            color: #8a0000;
            flex-shrink: 0;
        }
        .booking-details {
            display: flex;
            flex-direction: column;
            gap: 6px;
            flex: 1;
            color: #333;
            font-size: 18px;
            word-break: break-word;
            padding: 0 20px 0 0;
        }
        .status-text {
            font-weight: bold;
            color: #c0392b;
            font-size: 0.95em;
        }
        .cancel-btn {
            background-color: #8a0000;
            color: #fff;
            border: none;
            border-radius: 6px;
            padding: 10px 16px;
            font-size: 14px;
            cursor: pointer;
            white-space: nowrap;
            align-self: flex-start;
            transition: background-color 0.2s ease;
        }
        .cancel-btn:hover {
            background-color: #a60000;
        }
        /* Responsive */
        @media (max-width: 768px) {
            .pending-booking-item {
                flex-direction: column;
                align-items: flex-start;
            }
            .icon {
                font-size: 70px;
                margin-bottom: 12px;
            }
            .cancel-btn {
                align-self: stretch;
                width: 100%;
                text-align: center;
                padding: 12px 0;
            }
        }
    </style>
    <!-- Google Material Icon font for user icon -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
</head>
<body>
    <div class="main-content-right">
        <h2>Your Requests</h2>
        <p id="pendingCountMessage">
            <?php 
            if ($result && $result->num_rows > 0) {
                echo "You have " . $result->num_rows . " pending booking" . ($result->num_rows > 1 ? "s" : "") . ":";
            } else {
                echo "Book now! You have no pending bookings.";
            }
            ?>
        </p>
        <ul id="pendingBookingsList">
            <?php 
            if ($result && $result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    ?>
                    <li class="pending-booking-item">
                        <span class="material-icons icon" aria-hidden="true" title="User Icon">account_circle</span>
                        <div class="booking-details">
                            <div><strong>Client: </strong><?php echo htmlspecialchars($row['clientName']); ?></div>
                            <div><strong>Date: </strong><?php echo htmlspecialchars($row['date']); ?></div>
                            <div><strong>Time: </strong><?php echo htmlspecialchars($row['time']); ?></div>
                            <div><strong>Description: </strong><?php echo htmlspecialchars($row['description']); ?></div>
                            <div><span class="status-text"><?php echo htmlspecialchars($row['status']); ?></span></div>
                        </div>
                        <button class="cancel-btn" onclick="cancelBooking('<?php echo addslashes($row['clientName']); ?>', '<?php echo addslashes($row['date']); ?>')">Cancel</button>
                    </li>
                    <?php
                }
            }
            $conn->close();
            ?>
        </ul>
    </div>

    <script>
        // Example stub function for Cancel button, modify per your backend API needs
        function cancelBooking(clientName, date) {
            if(confirm(`Are you sure you want to cancel the booking for ${clientName} on ${date}?`)) {
                alert('Cancel logic to be implemented.');
                // Here you'd do an AJAX call or form submit to cancel the booking in backend
            }
        }
    </script>
</body>
</html>

