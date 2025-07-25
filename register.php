<?php
session_start(); // Start session for potential messages
include('db_connect.php'); // Include your database connection

$message = ''; // Initialize message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = strtolower(trim($_POST['role'] ?? '')); // Use null coalescing and trim/lower
    $id = trim($_POST['id'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? ''); // Added confirm password field

    // Server-side validation for empty fields and password mismatch
    if (empty($role) || empty($id) || empty($name) || empty($password) || empty($confirm_password)) {
        $message = "Please fill all the fields.";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match.";
    } elseif (strlen($password) < 6) { // Example: minimum password length
        $message = "Password must be at least 6 characters long.";
    } else {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Determine the ID column based on the selected role
        $id_column = ($role === "admin") ? "admin_id" : "worker_id";

        // Check if ID already exists for the selected role to prevent duplicate registrations
        $check_sql = "SELECT COUNT(*) FROM users WHERE $id_column = ?";
        $check_stmt = $conn->prepare($check_sql);
        if (!$check_stmt) {
             $message = "Database error (check ID prepare): " . $conn->error;
        } else {
            $check_stmt->bind_param("s", $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_row()[0];
            $check_stmt->close();

            if ($check_result > 0) {
                $message = "Registration failed. This ID already exists for a " . htmlspecialchars($role) . ".";
            } else {
                // If ID does not exist, proceed with user insertion
                $sql = "INSERT INTO users ($id_column, name, password, role) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);

                if (!$stmt) {
                    $message = "Database error (insert prepare): " . $conn->error;
                } else {
                    // Bind parameters and execute the insert query
                    $stmt->bind_param("ssss", $id, $name, $hashed_password, $role);

                    if ($stmt->execute()) {
                        // Redirect to login page on successful registration with a success message
                        header("Location: login.php?message=" . urlencode("✅ You have successfully registered as a " . htmlspecialchars($role) . ". Please login."));
                        exit(); // Crucial: stop script execution after redirect
                    } else {
                        // Error during insertion
                        $message = "❌ Registration failed: " . $stmt->error;
                    }
                    $stmt->close(); // Close the statement
                }
            }
        }
    }
}
// Close database connection only if it was opened and not already closed by statement
// This line should typically be at the end of the script execution, outside the conditional POST block
// or handled carefully within an 'else' if a redirect happens.
if (isset($conn) && $conn) {
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>SafeTrack Registration</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Optional: Basic styling for error messages */
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .container {
            width: 400px;
            margin: 50px auto;
            padding: 30px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .container h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }
        .container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }
        .container input[type="text"],
        .container input[type="password"],
        .container select {
            width: calc(100% - 20px); /* Adjust for padding */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding in width */
        }
        .container button {
            width: 100%;
            padding: 12px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .container button:hover {
            background-color: #0056b3;
        }
        .container p {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        .container p a {
            color: #007bff;
            text-decoration: none;
        }
        .container p a:hover {
            text-decoration: underline;
        }
    </style>
    <script>
        // JavaScript to dynamically show/hide Worker ID or Admin ID field
        function toggleIDField() {
            const role = document.getElementById("role").value;
            const workerField = document.getElementById("worker_id_field");
            const adminField = document.getElementById("admin_id_field");
            const workerIdInput = document.getElementById("worker_id");
            const adminIdInput = document.getElementById("admin_id");

            // Hide both containers initially, remove 'required', and DISABLE them
            workerField.style.display = "none";
            adminField.style.display = "none";
            workerIdInput.removeAttribute("required");
            adminIdInput.removeAttribute("required");
            workerIdInput.setAttribute("disabled", "true"); // <-- Crucial change: Disable hidden input
            adminIdInput.setAttribute("disabled", "true");   // <-- Crucial change: Disable hidden input

            // Clear values to prevent accidental submission of old value if role changes
            // Note: This also clears values when the form is reloaded due to a server-side error.
            // You might want to adjust this if you prefer to retain entered ID on error.
            workerIdInput.value = "";
            adminIdInput.value = "";


            // Show the relevant ID field, set it as required, and ENABLE it
            if (role === "worker") {
                workerField.style.display = "block";
                workerIdInput.setAttribute("required", "true");
                workerIdInput.removeAttribute("disabled"); // <-- Crucial change: Enable visible input
            } else if (role === "admin") {
                adminField.style.display = "block";
                adminIdInput.setAttribute("required", "true");
                adminIdInput.removeAttribute("disabled"); // <-- Crucial change: Enable visible input
            }
            // If "Select Role" is chosen, both remain hidden, not required, and disabled
        }

        // Call toggleIDField on page load to set the initial state correctly
        window.onload = function () {
            // Call toggleIDField initially to set the correct disabled/enabled state based on current role selection
            toggleIDField();

            // Handle pre-filling of ID if form was submitted (e.g., due to validation error)
            const selectedRole = document.getElementById('role').value;
            <?php if (isset($_POST['id'])): ?>
                const submittedId = "<?php echo htmlspecialchars($_POST['id']); ?>";
                if (selectedRole === "worker" && document.getElementById('worker_id')) {
                    document.getElementById('worker_id').value = submittedId;
                } else if (selectedRole === "admin" && document.getElementById('admin_id')) {
                    document.getElementById('admin_id').value = submittedId;
                }
            <?php endif; ?>
        };
    </script>
</head>
<body>
    <div class="container">
        <h2>Register for SafeTrack</h2>
        <?php if (!empty($message)): ?>
            <p class="error"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <label for="role">Role:</label>
            <select name="role" id="role" onchange="toggleIDField()" required>
                <option value="">Select Role</option>
                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                <option value="worker" <?php echo (isset($_POST['role']) && $_POST['role'] === 'worker') ? 'selected' : ''; ?>>Worker</option>
            </select>

            <div id="worker_id_field" style="display:none;">
                <label for="worker_id">Worker ID:</label>
                <input type="text" id="worker_id" name="id" placeholder="Enter your Worker ID">
            </div>

            <div id="admin_id_field" style="display:none;">
                <label for="admin_id">Admin ID:</label>
                <input type="text" id="admin_id" name="id" placeholder="Enter your Admin ID">
            </div>

            <label for="name">Name:</label>
            <input type="text" id="name" name="name" required placeholder="Enter your name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">

            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required placeholder="Enter your password">

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">

            <button type="submit">Register</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</body>
</html>