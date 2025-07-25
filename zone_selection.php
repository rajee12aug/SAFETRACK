<?php
session_start();

// Check if user is logged in and is a worker
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit();
}

$worker_name = htmlspecialchars($_SESSION['name']);
$worker_id = htmlspecialchars($_SESSION['worker_id']); // Use worker_id from session

$message = ''; // Initialize message variable

// Handle form submission from this page
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_zone_name = trim($_POST['zone'] ?? ''); // Get the zone NAME from the form

    if (empty($selected_zone_name)) {
        $message = "Please select a zone.";
    } else {
        include 'db_connect.php'; // Include your database connection to get zone_id

        $zone_id_to_store = null; // Renamed for clarity as it's just for storage now

        // Get the zone_id from the 'zones' table based on the selected zone name
        $stmt_get_zone_id = $conn->prepare("SELECT id FROM zones WHERE zone_name = ?");
        if ($stmt_get_zone_id) {
            $stmt_get_zone_id->bind_param("s", $selected_zone_name);
            $stmt_get_zone_id->execute();
            $stmt_get_zone_id->bind_result($fetched_zone_id);
            if ($stmt_get_zone_id->fetch()) {
                $zone_id_to_store = $fetched_zone_id;
            }
            $stmt_get_zone_id->close();
        } else {
            $message = "Database error (getting zone ID): " . $conn->error;
        }
        $conn->close(); // Close connection after fetching zone ID

        if ($zone_id_to_store === null) {
            $message = "Selected zone name not found in the 'zones' table. Please check your zones table data.";
        } else {
            // Store details in session for attendance marking AFTER camera verification
            $_SESSION['zone_name_for_attendance'] = $selected_zone_name;
            $_SESSION['zone_id_for_attendance'] = $zone_id_to_store;
            $_SESSION['worker_id_for_attendance'] = $worker_id; // Store worker ID explicitly for next steps

            // Redirect to your checklist file
            header("Location: safety_checklist.php");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Safety Zone Selection - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Safety Zone Selection</h2>
        <p>Welcome, <?php echo $worker_name; ?> (Worker ID: <?php echo $worker_id; ?>)!</p>
        
        <?php if (!empty($message)): ?>
            <p class="error"><?php echo $message; ?></p>
        <?php endif; ?>

        <form action="zone_selection.php" method="POST">
            <label for="zone_select">Select Zone:</label>
            <select name="zone" id="zone_select" required>
                <option value="">-- Choose a Zone --</option>
                <option value="Turbine 1">Turbine 1</option>
                <option value="Turbine 2">Turbine 2</option>
                <option value="Control Room">Control Room</option>
                <option value="Generator Area">Generator Area</option>
                <option value="Maintenance Bay">Maintenance Bay</option>
                </select>
            <button type="submit">Proceed to Checklist</button>
        </form>

        <br>
        <p>Return to <a href="welcome.php">Welcome Page</a></p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>