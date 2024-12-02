<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
    echo json_encode(['error' => 'Invalid course ID']);
    exit();
}

$course_id = intval($_GET['course_id']);

try {
    // Query to fetch teachers assigned to the given course
    $stmt = $conn->prepare("
        SELECT teachers.teacher_id, teachers.name 
        FROM teachers
        INNER JOIN assigned_courses ON teachers.teacher_id = assigned_courses.teacher_id
        WHERE assigned_courses.course_id = ?
    ");
    $stmt->bind_param("i", $course_id);

    if ($stmt->execute()) {
        $result = $stmt->get_result();
        $teachers = [];

        while ($row = $result->fetch_assoc()) {
            $teachers[] = $row;
        }

        echo json_encode($teachers);
    } else {
        echo json_encode(['error' => 'Database query failed']);
    }

    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}

$conn->close();
