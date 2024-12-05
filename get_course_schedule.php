<?php
// Example of get_course_schedule.php
require 'db.php';

if (!isset($_GET['course_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Course ID is required']);
    exit();
}

$course_id = $_GET['course_id'];

// Fetch course details
$query = "SELECT day, start_time, end_time FROM courses WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if ($course) {
    echo json_encode($course);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Course not found']);
}
?>
