<?php
session_start();
if (!isset($_SESSION['name']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.html");
    exit();
}

include('db_connect.php');

$zone = $_POST['zone'] ?? '';
$checklist = $_POST['checklist'] ?? [];

if (empty($zone) || count($checklist) < 4) {
    echo "❌ Please complete all mandatory safety checks.";
    exit();
}

$user_id = $_SESSION['id'];
$answers = implode(", ", $checklist);
$date = date('Y-m-d H:i:s');

$stmt = $conn->prepare("INSERT INTO attendance (user_id, zone, checklist, timestamp) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $zone, $answers, $date);
$stmt->execute();

echo "<h2>✅ Attendance Marked Successfully!</h2>";
echo "<p>Zone: $zone</p>";
echo "<p>Checklist: $answers</p>";
echo "<a href='welcome.php'>Back to Home</a>";
?>
