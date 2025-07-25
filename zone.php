<?php
session_start();
if (!isset($_SESSION['worker_id'])) {
    header("Location: login.html");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Zone Selection</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="form-container">
        <h2>Select Your Work Zone</h2>
        <form action="checklist.php" method="POST">
            <label for="zone">Choose Zone:</label>
            <select name="zone" id="zone" required>
                <option value="">--Select--</option>
                <option value="Zone A">Zone A</option>
                <option value="Zone B">Zone B</option>
                <option value="Zone C">Zone C</option>
            </select>
            <button type="submit">Proceed</button>
        </form>
    </div>
</body>
</html>
