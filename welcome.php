<?php
session_start();

if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.html");
    exit();
}

// Ensure worker_id is available from the session
$worker_name = htmlspecialchars($_SESSION['name']);
// Assuming worker_id is also stored in the session upon login
$worker_id = htmlspecialchars($_SESSION['worker_id'] ?? 'N/A'); // Fallback for worker_id if not set

// This part for 'status_message' is usually set by a previous script (e.g., after login/attendance mark)
$status_message_text = '';
$status_message_type = '';
if (isset($_SESSION['status_message'])) {
    $status_message_text = htmlspecialchars($_SESSION['status_message']['text']);
    $status_message_type = htmlspecialchars($_SESSION['status_message']['type']);
    unset($_SESSION['status_message']); // Clear the message after displaying it
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Welcome - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* These styles can be added to your existing style.css or kept here for welcome.php specific styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        .container {
            background-color: #fff;
            padding: 30px 40px;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            text-align: center;
            width: 100%;
            max-width: 400px; /* Adjust as needed */
        }
        h2 {
            color: #007bff;
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        p {
            margin-bottom: 10px;
            color: #555;
            line-height: 1.5;
        }
        .btn-link { /* Style for the button-like link */
            display: inline-block; /* Makes it behave like a block for padding/margin, but stays inline */
            background-color: #007bff;
            color: white;
            padding: 12px 25px; /* Increased padding for a larger button */
            border-radius: 5px;
            text-decoration: none; /* Remove underline */
            margin-top: 25px; /* More space from paragraph */
            font-size: 1.1em; /* Larger font size */
            transition: background-color 0.3s ease, transform 0.2s ease; /* Smooth hover effects */
        }
        .btn-link:hover {
            background-color: #0056b3; /* Darker blue on hover */
            transform: translateY(-2px); /* Slight lift effect */
        }
        .logout-link {
            display: block;
            text-align: center;
            margin-top: 20px; /* Space between button and logout */
            font-size: 0.95em;
            color: #dc3545;
            text-decoration: none;
            transition: color 0.2s ease;
        }
        .logout-link:hover {
            text-decoration: underline;
            color: #c82333;
        }
        /* Styles for status messages (e.g., "Attendance marked successfully!") */
        .message {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 5px;
            font-weight: bold;
            text-align: center;
            border: 1px solid transparent; /* Default border */
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
            border-color: #c3e6cb;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }
        .message.info { /* If you use info messages */
            background-color: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }
    </style>
</head>
<body>
<div class="container">
    <?php if (!empty($status_message_text)): ?>
        <p class="message <?php echo $status_message_type; ?>">
            <?php echo $status_message_text; ?>
        </p>
    <?php endif; ?>

    <h2>Welcome, <?php echo $worker_name; ?> (Worker ID: <?php echo $worker_id; ?>)!</h2>
    <p>You have successfully logged in as a worker.</p>
    <p>Please select your safety zone to proceed to the checklist.</p>

    <a href="zone_selection.php" class="btn-link">Proceed to Zone Selection</a>

    <a href="logout.php" class="logout-link">Logout</a>
</div>
</body>
</html>