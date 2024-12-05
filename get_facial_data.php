<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized access. Please log in.']);
    exit();
}

$student_id = $_SESSION['student_id'];

if (!isset($_GET['course_id']) || empty($_GET['course_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Course ID is required.']);
    exit();
}

$course_id = $_GET['course_id'];

try {
    // Fetch facial data query
    $facialDataQuery = "
        SELECT e.facial_data
        FROM enrollments e
        INNER JOIN users u ON e.user_id = u.id
        WHERE e.course_id = ? AND e.user_id = ?
    ";

    $stmt = $conn->prepare($facialDataQuery);
    $stmt->bind_param('ii', $course_id, $student_id);
    $stmt->execute();
    $facialDataResult = $stmt->get_result();

    if (!$facialDataResult) {
        echo json_encode(['status' => 'error', 'message' => 'Database query failed.']);
        exit();
    }

    if ($facialDataResult->num_rows > 0) {
        $row = $facialDataResult->fetch_assoc();
        $facialData = $row['facial_data'];
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Facial data not found for the provided course and student.'
        ]);
        exit();
    }

    // Attendance lock check query
    $attendanceQuery = "
        SELECT 
            e.attended_classes, 
            c.total_lectures, 
            c.start_date, 
            c.end_date
        FROM enrollments e
        INNER JOIN courses c ON e.course_id = c.id
        WHERE e.course_id = ? AND e.user_id = ?
    ";

    $stmt = $conn->prepare($attendanceQuery);
    $stmt->bind_param('ii', $course_id, $student_id);
    $stmt->execute();
    $attendanceResult = $stmt->get_result();

    if (!$attendanceResult) {
        echo json_encode(['status' => 'error', 'message' => 'Database query failed for attendance check.']);
        exit();
    }

    if ($attendanceResult->num_rows > 0) {
        $row = $attendanceResult->fetch_assoc();
        $attendedClasses = (int)$row['attended_classes'];
        $totalLectures = (int)$row['total_lectures'];

        // Attendance lock checks
        if ($attendedClasses >= $totalLectures) {
            echo json_encode([
                'status' => 'error',
                'message' => 'Attendance locked: All lectures attended.'
            ]);
            exit();
        }


        // Success response if no lock conditions are met
        echo json_encode([
            'status' => 'success',
            'facialData' => $facialData,
            'attended_classes' => $attendedClasses,
            'total_lectures' => $totalLectures
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'No enrollment found for attendance check.'
        ]);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
