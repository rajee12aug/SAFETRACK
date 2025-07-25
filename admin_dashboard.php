<?php
session_start();
include 'db_connect.php'; // Include your database connection file

// Check if admin is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect to login if not logged in
    exit();
}

$admin_name = htmlspecialchars($_SESSION['name']); // Admin's username from session
$admin_id = htmlspecialchars($_SESSION['id']);   // Admin's ID from session

$filter_worker_id = isset($_GET['worker_id']) ? htmlspecialchars(trim($_GET['worker_id'])) : '';
$filter_zone_name = isset($_GET['zone']) ? htmlspecialchars(trim($_GET['zone'])) : '';
$filter_date = isset($_GET['date']) ? htmlspecialchars(trim($_GET['date'])) : '';

$attendance_records = [];
$message = ''; // For general messages like "No records found" or errors

// Handle status messages from other pages (if any, though delete is removed)
$status_message_text = '';
$status_message_type = '';
if (isset($_SESSION['status_message'])) {
    $status_message_text = htmlspecialchars($_SESSION['status_message']['text']);
    $status_message_type = htmlspecialchars($_SESSION['status_message']['type']);
    unset($_SESSION['status_message']); // Clear the message after displaying it
}


// --- Query to fetch attendance records ---
$sql = "SELECT a.id, a.worker_id, u.name AS worker_name, z.zone_name, a.timestamp, a.checklist_passed
        FROM attendance a
        JOIN users u ON a.worker_id = u.worker_id
        JOIN zones z ON a.zone_id = z.id
        WHERE 1=1"; // Start with a true condition to easily append filters

$params = [];
$types = '';

if (!empty($filter_worker_id)) {
    $sql .= " AND a.worker_id = ?";
    $params[] = $filter_worker_id;
    $types .= 's'; // Worker ID is VARCHAR (assuming from your users table setup)
}

if (!empty($filter_zone_name) && $filter_zone_name !== '-- All Zones --') { // Filter by specific zone, exclude "All Zones"
    // First, get the zone_id from the zone_name
    $zone_id_for_filter = null;
    $stmt_get_zone_id = $conn->prepare("SELECT id FROM zones WHERE zone_name = ?");
    if ($stmt_get_zone_id) {
        $stmt_get_zone_id->bind_param("s", $filter_zone_name);
        $stmt_get_zone_id->execute();
        $stmt_get_zone_id->bind_result($fetched_zone_id);
        if ($stmt_get_zone_id->fetch()) {
            $zone_id_for_filter = $fetched_zone_id;
        }
        $stmt_get_zone_id->close();
    }

    if ($zone_id_for_filter !== null) {
        $sql .= " AND a.zone_id = ?";
        $params[] = $zone_id_for_filter;
        $types .= 'i'; // Zone ID is INT
    } else {
        // If zone name was provided but not found in zones table, no records will match
        $message = "Selected zone '" . htmlspecialchars($filter_zone_name) . "' not found in database. No records displayed.";
        // Set attendance_records to empty to prevent table from showing
        $attendance_records = [];
        goto end_query_execution; // Skip further query execution
    }
}

if (!empty($filter_date)) {
    $sql .= " AND DATE(a.timestamp) = ?"; // Compare only the date part
    $params[] = $filter_date;
    $types .= 's'; // Date is string
}

$sql .= " ORDER BY a.timestamp DESC"; // Order by most recent first

$stmt = $conn->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $attendance_records[] = $row;
        }
    } else {
        if (empty($message)) { // Only set message if no other error/info message exists
            $message = "No attendance records found for the selected filters.";
        }
    }
    $stmt->close();
} else {
    $message = "Database query error: " . $conn->error;
}

end_query_execution:
$conn->close(); // Close connection after main query


// --- Fetch all zones for the dropdown filter ---
$all_zones = [];
// Re-establish connection for the zone query
include 'db_connect.php';
$result_zones = $conn->query("SELECT zone_name FROM zones ORDER BY zone_name ASC");
if ($result_zones) {
    while ($row = $result_zones->fetch_assoc()) {
        $all_zones[] = $row['zone_name'];
    }
}
$conn->close(); // Close connection after zone query
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="admin-dashboard-container">
        <h1>Admin Dashboard - Attendance Logs</h1>
        <p class="welcome-message">Welcome, <?php echo $admin_name; ?> (Admin ID: <?php echo $admin_id; ?>)</p>

        <?php if (!empty($status_message_text)): ?>
            <div class="message <?php echo $status_message_type; ?>">
                <?php echo $status_message_text; ?>
            </div>
        <?php endif; ?>

        <div class="section-box">
            <h3>Filter Attendance Records</h3>
            <form action="admin_dashboard.php" method="GET" class="filter-form">
                <div>
                    <label for="filter_worker_id">Worker ID:</label>
                    <input type="text" id="filter_worker_id" name="worker_id" value="<?php echo htmlspecialchars($filter_worker_id); ?>">
                </div>
                <div>
                    <label for="filter_zone">Zone:</label>
                    <select id="filter_zone" name="zone">
                        <option value="-- All Zones --" <?php echo ($filter_zone_name === '-- All Zones --' || empty($filter_zone_name)) ? 'selected' : ''; ?>>-- All Zones --</option>
                        <?php foreach ($all_zones as $zone_option): ?>
                            <option value="<?php echo htmlspecialchars($zone_option); ?>" <?php echo ($filter_zone_name === $zone_option) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($zone_option); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="filter_date">Date:</label>
                    <input type="date" id="filter_date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-success">Apply Filters</button>
                    <button type="button" class="btn btn-warning" onclick="window.location.href='admin_dashboard.php'">Reset Filters</button>
                </div>
            </form>
        </div>


        <div class="section-box">
            <h3>Attendance Records</h3>
            <?php if (!empty($message)): ?>
                <p class="message <?php echo strpos($message, 'No attendance records') !== false ? 'info' : 'error'; ?>"><?php echo $message; ?></p>
            <?php endif; ?>

            <?php if (!empty($attendance_records)): ?>
                <div class="attendance-table-container">
                    <table class="attendance-table">
                        <thead>
                            <tr>
                                <th>Worker ID</th>
                                <th>Worker Name</th>
                                <th>Zone</th>
                                <th>Timestamp</th>
                                <th>Checklist Passed</th>
                                </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($attendance_records as $record): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($record['worker_id']); ?></td>
                                    <td><?php echo htmlspecialchars($record['worker_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['zone_name']); ?></td>
                                    <td><?php echo htmlspecialchars($record['timestamp']); ?></td>
                                    <td><?php echo ($record['checklist_passed'] == 1) ? 'Yes' : 'No'; ?></td>
                                    </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: // This block handles cases where attendance_records is empty and no specific message was set by the filter logic ?>
                <?php if (empty($message)): // Only show generic message if filter logic didn't set one ?>
                    <p class="no-records-message">No attendance records found.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <a href="logout.php" class="logout-link">Logout</a>
    </div>
</body>
</html>