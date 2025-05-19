<?php
$db_host = 'localhost:3307';  // Add port number here
$db_user = 'root';
$db_pass = '2020084';  // Default XAMPP password is empty
$db_name = 'smart_attendance_system';

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>