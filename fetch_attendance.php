<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id']) || !isset($_POST['course_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id = $_POST['course_id'];

// Fetch attendance records for the selected course
$query = "SELECT attendance_date, check_in_time, status 
          FROM attendance_records 
          WHERE user_id = ? AND course_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();

$attendance_records = [];
while ($row = $result->fetch_assoc()) {
    $attendance_records[] = $row;
}

if (count($attendance_records) > 0) {
    echo json_encode(['status' => 'success', 'attendance_records' => $attendance_records]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'No attendance records found']);
}
?>