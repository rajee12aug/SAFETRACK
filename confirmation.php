<?php
session_start();
include 'db.php';

if (!isset($_SESSION['worker_id']) || $_SESSION['role'] !== 'Worker') {
    header("Location: login.html");
    exit();
}

$worker_id = $_SESSION['worker_id'];
$zone = $_SESSION['zone'] ?? '';
$checklist = $_POST['checklist'] ?? [];

if (!$zone || empty($checklist)) {
    echo "Incomplete checklist or zone not set.";
    exit();
}

$items = implode(", ", $checklist);
$datetime = date("Y-m-d H:i:s");

$sql = "INSERT INTO attendance (worker_id, zone, checklist, timestamp) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssss", $worker_id, $zone, $items, $datetime);
$stmt->execute();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Confirmation - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="form-container">
    <h2>✅ Attendance Marked Successfully</h2>
    <p>Thank you for completing your safety checklist.</p>
    <a href="login.html">Logout</a>
</div>
</body>
</html>
