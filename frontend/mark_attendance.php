<?php
session_start();
if (!isset($_SESSION["username"])) {
    http_response_code(403);
    exit("Unauthorized");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {

    $user_id = intval($_POST['user_id']);
    $date = date("Y-m-d");
    $time = date("H:i:s");

    // DB Connection
    $conn = new mysqli("localhost", "root", "", "smart_attendance_system", 3307);

    if ($conn->connect_error) {
        die("DB Connection failed: " . $conn->connect_error);
    }

    // Check if already marked today
    $stmt = $conn->prepare("SELECT id FROM attendance WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $user_id, $date);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        echo "Already marked";
    } else {
        // Mark Attendance
        $stmt = $conn->prepare("INSERT INTO attendance (user_id, date, time, status) VALUES (?, ?, ?, 'Present')");
        $stmt->bind_param("iss", $user_id, $date, $time);
        if ($stmt->execute()) {
            echo "Marked";
        } else {
            echo "Error marking attendance";
        }
    }

    $stmt->close();
    $conn->close();
} else {
    http_response_code(400);
    echo "Invalid request";
}
?>
