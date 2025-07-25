<?php
session_start();

// Ensure user is logged in and is a worker
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit();
}

// Check if we have the necessary data for attendance from session
if (!isset($_SESSION['worker_id_for_attendance']) || !isset($_SESSION['zone_id_for_attendance'])) {
    // If not, redirect back to zone selection to restart the process
    header("Location: zone_selection.php");
    exit();
}

$worker_id = $_SESSION['worker_id_for_attendance'];
$zone_id = $_SESSION['zone_id_for_attendance'];
$zone_name = htmlspecialchars($_SESSION['zone_name_for_attendance']); // For display on this page

$success_message = '';
$error_message = '';

// Check if the form on THIS page (camera verification) has been submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_camera'])) {
    // This block will only execute AFTER the "Verify Face" button is clicked

    include 'db_connect.php'; // Include your database connection

    // Assume checklist submission was successful from previous page (safety_checklist.php)
    $checklist_passed = 1;

    // Insert attendance record into the attendance table
    $stmt_insert_attendance = $conn->prepare("INSERT INTO attendance (worker_id, zone_id, checklist_passed, timestamp) VALUES (?, ?, ?, NOW())");

    if ($stmt_insert_attendance) {
        $stmt_insert_attendance->bind_param("sii", $worker_id, $zone_id, $checklist_passed); // 's' for worker_id, 'i' for zone_id, 'i' for checklist_passed

        if ($stmt_insert_attendance->execute()) {
            $success_message = "Attendance was marked successfully!";
        } else {
            $error_message = "Error marking attendance: " . $stmt_insert_attendance->error;
        }
        $stmt_insert_attendance->close();
    } else {
        $error_message = "Database error (attendance query preparation): " . $conn->error;
    }
    $conn->close();

    // Store message in session to display on welcome page
    if (!empty($success_message)) {
        $_SESSION['status_message'] = ['type' => 'success', 'text' => $success_message];
    } elseif (!empty($error_message)) {
        $_SESSION['status_message'] = ['type' => 'error', 'text' => $error_message];
    }

    // Clear the temporary attendance data from session
    unset($_SESSION['worker_id_for_attendance']);
    unset($_SESSION['zone_id_for_attendance']);
    unset($_SESSION['zone_name_for_attendance']);

    // Redirect to welcome page to show the status message
    header("Location: welcome.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Camera Verification - SafeTrack</title>
    <link rel="stylesheet" type="text/css" href="style.css">
    <style>
        /* Specific styles for the camera feed */
        .camera-feed-container {
            width: 100%;
            max-width: 400px; /* Limit width */
            margin: 20px auto;
            border: 2px solid #555;
            border-radius: 8px;
            overflow: hidden;
            background-color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 300px; /* Ensure a minimum height even if video isn't loaded */
            position: relative; /* For error messages */
        }
        video#cameraVideo {
            width: 100%;
            height: auto;
            display: block; /* Remove extra space below video */
        }
        #cameraErrorMessage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            background-color: rgba(0, 0, 0, 0.7);
            padding: 10px;
            border-radius: 5px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Camera Verification for <?php echo $zone_name; ?></h2>
    <p>Please allow camera access and verify your presence.</p>

    <div class="camera-feed-container">
        <video id="cameraVideo" autoplay playsinline></video>
        <div id="cameraErrorMessage" style="display: none;"></div>
    </div>

    <form action="camera_verification.php" method="post">
        <input type="hidden" name="verify_camera" value="1">
        <button type="submit" id="verifyButton">Verify Face & Mark Attendance</button>
    </form>

    <br>
    <p><a href="welcome.php">Return to Welcome Page</a> | <a href="logout.php">Logout</a></p>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const cameraVideo = document.getElementById('cameraVideo');
        const cameraErrorMessage = document.getElementById('cameraErrorMessage');
        const verifyButton = document.getElementById('verifyButton');

        // Function to start camera stream
        async function startCamera() {
            try {
                // Request access to the user's camera
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                // Attach the stream to the video element
                cameraVideo.srcObject = stream;
                cameraVideo.onloadedmetadata = () => {
                    cameraVideo.play();
                };
                cameraErrorMessage.style.display = 'none'; // Hide any previous error messages
                verifyButton.disabled = false; // Enable the button once camera is ready

            } catch (err) {
                console.error("Error accessing camera: ", err);
                cameraVideo.style.display = 'none'; // Hide video element
                cameraErrorMessage.textContent = "Camera access denied or not available. Please allow camera permissions.";
                cameraErrorMessage.style.display = 'block'; // Show error message
                verifyButton.disabled = true; // Disable the button if camera fails

                // More specific error messages for user
                if (err.name === 'NotAllowedError') {
                    cameraErrorMessage.textContent = "Camera access denied. Please allow camera permissions in your browser settings.";
                } else if (err.name === 'NotFoundError') {
                    cameraErrorMessage.textContent = "No camera found on this device.";
                } else {
                    cameraErrorMessage.textContent = "An error occurred accessing the camera. Please try again.";
                }
            }
        }

        // Start camera when the page loads
        startCamera();
    });
</script>
</body>
</html>