<?php
session_start();

// Ensure user is logged in and is a worker
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'worker') {
    header("Location: login.php");
    exit();
}

// Check if zone_id is set in session from zone_selection.php
if (!isset($_SESSION['zone_id_for_attendance']) || !isset($_SESSION['zone_name_for_attendance'])) {
    $_SESSION['status_message'] = ['type' => 'error', 'text' => 'Please select a zone first.'];
    header("Location: zone_selection.php");
    exit();
}

$zone_id = $_SESSION['zone_id_for_attendance'];
$zone_name = htmlspecialchars($_SESSION['zone_name_for_attendance']);
$worker_id = htmlspecialchars($_SESSION['worker_id']); // Assuming worker_id is also in session

$checklist_items = [];
$error_message = '';

include 'db_connect.php';

// Fetch checklist items specific to the selected zone
$stmt_get_checklist = $conn->prepare("SELECT id, item_text FROM zone_checklist_items WHERE zone_id = ?");

if ($stmt_get_checklist) {
    $stmt_get_checklist->bind_param("i", $zone_id);
    $stmt_get_checklist->execute();
    $result_checklist = $stmt_get_checklist->get_result();

    if ($result_checklist->num_rows > 0) {
        while ($row = $result_checklist->fetch_assoc()) {
            $checklist_items[] = $row;
        }
    } else {
        $error_message = "No checklist items defined for this zone. Please contact administration.";
    }
    $stmt_get_checklist->close();
} else {
    $error_message = "Database error: " . $conn->error;
}
$conn->close();

// If no checklist items or an error occurred, prevent form submission
// For now, we will display the message and disable the submit button if no items
if (empty($checklist_items) && empty($error_message)) { // Only if no specific error but no items
    $error_message = "No checklist items available for this zone. Please contact administration.";
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>Safety Checklist - SafeTrack</title>
    <link rel="stylesheet" type="text/css" href="style.css">
</head>
<body>
<div class="container">
    <h2>Safety Checklist for <?php echo $zone_name; ?></h2>
    <p>Please confirm all safety measures before proceeding.</p>

    <?php if (!empty($error_message)): ?>
        <p class="error"><?php echo $error_message; ?></p>
    <?php endif; ?>

    <form action="camera_verification.php" method="post">
        <?php if (!empty($checklist_items)): ?>
            <?php foreach ($checklist_items as $item): ?>
                <label class="checkbox-label">
                    <input type="checkbox" name="checklist_item[]" value="<?php echo $item['id']; ?>" required>
                    <?php echo htmlspecialchars($item['item_text']); ?>
                </label>
            <?php endforeach; ?>
            <p>
                <button type="submit">Submit Checklist & Proceed to Camera Verification</button>
            </p>
        <?php else: ?>
            <button type="submit" disabled>
                <?php echo !empty($error_message) ? 'Checklist Unavailable' : 'No Checklist Available'; ?>
            </button>
        <?php endif; ?>

        <p><a href="welcome.php">Return to Welcome Page</a> | <a href="logout.php">Logout</a></p>
    </form>
</div>
</body>
</html>