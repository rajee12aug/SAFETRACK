<?php
session_start();
include("db_connection.php");

// Check if worker is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'worker') {
    header("Location: login.html");
    exit();
}

// Fetch worker info
$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];
$role = $_SESSION['role'];

// Get attendance records
$attendance = [];
$query = "SELECT * FROM attendance WHERE user_id = '$user_id'";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $attendance[] = $row;
}

// Dummy warnings
$warnings = rand(0, 3); // simulate 0–3 warnings

?>
<!DOCTYPE html>
<html>
<head>
    <title>Worker Dashboard</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .dashboard-container {
            width: 90%;
            margin: auto;
            padding: 20px;
        }
        .section {
            background: #f9f9f9;
            border-radius: 8px;
            margin-bottom: 20px;
            padding: 15px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table td, table th {
            border: 1px solid #ccc;
            padding: 8px;
        }
        .todo-input {
            width: 80%;
            padding: 6px;
        }
        .todo-btn {
            padding: 6px 10px;
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        .todo-list li {
            padding: 6px;
            list-style: none;
        }
        .home-link {
            float: right;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="dashboard-container">
    <h2>Welcome, <?php echo $name; ?> 👷</h2>

    <div class="home-link">
        <a href="welcome.php" class="button">← Back to Welcome</a>
    </div>

    <!-- Worker Details -->
    <div class="section">
        <h3>👤 Your Profile</h3>
        <p><strong>Name:</strong> <?php echo $name; ?></p>
        <p><strong>Worker ID:</strong> <?php echo $_SESSION['worker_id']; ?></p>
        <p><strong>Warnings:</strong> <?php echo $warnings; ?></p>
        <p><strong>Total Attendance:</strong> <?php echo count($attendance); ?></p>
    </div>

    <!-- Attendance Table -->
    <div class="section">
        <h3>🕒 Attendance Records</h3>
        <?php if (count($attendance) > 0): ?>
            <table>
                <tr>
                    <th>Date & Time</th>
                    <th>Zone</th>
                </tr>
                <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?php echo $record['timestamp']; ?></td>
                        <td><?php echo $record['zone']; ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else: ?>
            <p>No attendance records yet.</p>
        <?php endif; ?>
    </div>

    <!-- To-Do List -->
    <div class="section">
        <h3>✅ Your To-Do List</h3>
        <input type="text" id="todoInput" class="todo-input" placeholder="Enter task">
        <button onclick="addTodo()" class="todo-btn">Add</button>
        <ul id="todoList" class="todo-list"></ul>
    </div>

    <!-- Additional Info -->
    <div class="section">
        <h3>📝 Update Your Info</h3>
        <form>
            <label>Phone Number:</label><br>
            <input type="text" placeholder="Enter phone"><br><br>
            <label>Department:</label><br>
            <input type="text" placeholder="e.g. Maintenance"><br><br>
            <button type="submit">Save</button>
        </form>
    </div>
</div>

<script>
function addTodo() {
    var input = document.getElementById("todoInput").value;
    if (input.trim() !== "") {
        var li = document.createElement("li");
        li.textContent = input;
        document.getElementById("todoList").appendChild(li);
        document.getElementById("todoInput").value = "";
    }
}
</script>

</body>
</html>
