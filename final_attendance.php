<?php
session_start();

if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.html");
    exit();
}

date_default_timezone_set("Asia/Kolkata");
$timestamp = date("Y-m-d H:i:s");
$zone = $_SESSION['zone'];
$name = $_SESSION['name'];
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Marked - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="container">
    <h2>Attendance Successfully Marked!</h2>
    <p><strong>Worker:</strong> <?php echo htmlspecialchars($name); ?></p>
    <p><strong>Zone:</strong> <?php echo htmlspecialchars($zone); ?></p>
    <p><strong>Timestamp:</strong> <?php echo $timestamp; ?></p>

    <a href="logout.php">Logout</a>
</div>
</body>
</html>


