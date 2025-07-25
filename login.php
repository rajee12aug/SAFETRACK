<?php
session_start();

$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $selected_role = trim($_POST['role'] ?? '');
    $entered_password = trim($_POST['password'] ?? '');
    $entered_id = ''; // This will hold either worker_id or admin_id

    if (empty($selected_role) || empty($entered_password)) {
        $error_message = "Role, ID, and password are required.";
    } else {
        include 'db_connect.php'; // Include your database connection

        $stmt = null;
        $db_id_column = ''; // Will be 'admin_id' or 'worker_id'
        $db_entered_id = ''; // The ID to bind to the query
        $bind_type = '';

        if ($selected_role === 'worker') {
            $entered_id = trim($_POST['worker_id'] ?? '');
            $db_id_column = 'worker_id';
            $bind_type = 's'; // Assuming worker_id is VARCHAR
            $db_entered_id = $entered_id;
        } elseif ($selected_role === 'admin') {
            $entered_id = trim($_POST['admin_id'] ?? '');
            $db_id_column = 'admin_id';
            $bind_type = 'i'; // Assuming admin_id is INT
            $db_entered_id = (int)$entered_id; // Cast to int for binding
        } else {
            $error_message = "Invalid role selected.";
        }

        if (empty($entered_id)) {
            $error_message = "Please enter your ID.";
        } elseif (empty($error_message)) { // Proceed only if no prior error
            // Prepare and execute the query to fetch user data based on role and ID
            // We still fetch 'username' here because it's used for $_SESSION['name'] in welcome.php
            $sql = "SELECT admin_id, username, password, role, worker_id FROM users WHERE role = ? AND {$db_id_column} = ?";
            $stmt = $conn->prepare($sql);

            if ($stmt) {
                // Bind parameters dynamically based on role and ID type
                $stmt->bind_param("s" . $bind_type, $selected_role, $db_entered_id);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows == 1) {
                    $stmt->bind_result($admin_id, $db_username, $hashed_password, $db_role, $db_worker_id);
                    $stmt->fetch();

                    // Verify password (using password_verify for hashed passwords)
                    if (password_verify($entered_password, $hashed_password)) {
                        // Password is correct, set session variables
                        $_SESSION['loggedin'] = true;
                        $_SESSION['id'] = $admin_id; // Using admin_id as the general user ID in session
                        $_SESSION['name'] = $db_username; // Still uses 'username' for display name
                        $_SESSION['role'] = $db_role;
                        $_SESSION['worker_id'] = $db_worker_id; // Store worker_id if it's a worker

                        // Redirect based on role
                        if ($db_role === 'admin') {
                            header("Location: admin_dashboard.php"); // Redirect to admin dashboard
                            exit();
                        } elseif ($db_role === 'worker') {
                            header("Location: welcome.php"); // Redirect to welcome page for workers
                            exit();
                        }
                    } else {
                        $error_message = "Invalid ID or password.";
                    }
                } else {
                    $error_message = "Invalid ID or password.";
                }
                $stmt->close();
            } else {
                $error_message = "Database query error: " . $conn->error;
            }
        }
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - SafeTrack</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($error_message)): ?>
            <p class="error"><?php echo $error_message; ?></p>
        <?php endif; ?>
        <form action="login.php" method="POST">
            <label for="role_select">Select Role:</label>
            <select name="role" id="role_select" required>
                <option value="">-- Select Role --</option>
                <option value="worker">Worker</option>
                <option value="admin">Admin</option>
            </select>

            <div id="worker_id_field" style="display: none;">
                <label for="worker_id">Worker ID:</label>
                <input type="text" id="worker_id" name="worker_id">
            </div>

            <div id="admin_id_field" style="display: none;">
                <label for="admin_id">Admin ID:</label>
                <input type="text" id="admin_id" name="admin_id">
            </div>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <button type="submit">Login</button>
        </form>
        <p>Don't have an account? <a href="register.php">Register here</a></p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role_select');
            const workerIdField = document.getElementById('worker_id_field');
            const adminIdField = document.getElementById('admin_id_field');
            const workerIdInput = document.getElementById('worker_id');
            const adminIdInput = document.getElementById('admin_id');

            function toggleIdFields() {
                // Hide both fields and remove 'required'
                workerIdField.style.display = 'none';
                adminIdField.style.display = 'none';
                workerIdInput.removeAttribute('required');
                adminIdInput.removeAttribute('required');
                
                // Set value to empty to avoid sending old data
                workerIdInput.value = '';
                adminIdInput.value = '';

                // Show the relevant field and add 'required'
                if (roleSelect.value === 'worker') {
                    workerIdField.style.display = 'block';
                    workerIdInput.setAttribute('required', 'required');
                } else if (roleSelect.value === 'admin') {
                    adminIdField.style.display = 'block';
                    adminIdInput.setAttribute('required', 'required');
                }
            }

            // Add event listener for changes in role selection
            roleSelect.addEventListener('change', toggleIdFields);

            // Call once on page load to set initial state
            toggleIdFields();
        });
    </script>
</body>
</html>