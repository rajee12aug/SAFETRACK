<?php
// db_connect.php
$servername = "localhost";
$username = "root"; // Your MySQL username
$password = ""; // Your MySQL password (empty by default for XAMPP/WAMP)
$dbname = "safetrack"; // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>