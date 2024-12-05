<?php
session_start();
include 'db.php'; // Ensure this file establishes the database connection

if (isset($_GET['toggle_status']) && isset($_GET['student_id']) && isset($_GET['course_id'])) {
    $student_id = $_GET['student_id'];
    $course_id = $_GET['course_id'];

    try {
        // Fetch current status of the student
        $status_query = "SELECT status FROM grades WHERE student_id = ? AND course_id = ?";
        $status_stmt = $conn->prepare($status_query);
        if (!$status_stmt) {
            die("Error preparing status query: " . $conn->error);
        }

        $status_stmt->bind_param('ii', $student_id, $course_id);
        if (!$status_stmt->execute()) {
            die("Error executing status query: " . $status_stmt->error);
        }

        $status_result = $status_stmt->get_result();
        if ($status_result->num_rows > 0) {
            $status = $status_result->fetch_assoc()['status'];
            // Toggle the status between 'show' and 'hide'
            $new_status = ($status == 'show') ? 'hide' : 'show';

            // Update the student status in the database
            $update_status_query = "UPDATE grades SET status = ? WHERE student_id = ? AND course_id = ?";
            $update_status_stmt = $conn->prepare($update_status_query);
            if (!$update_status_stmt) {
                die("Error preparing update query: " . $conn->error);
            }

            $update_status_stmt->bind_param('sii', $new_status, $student_id, $course_id);
            if (!$update_status_stmt->execute()) {
                die("Error executing update query: " . $update_status_stmt->error);
            }

            // Set success message
            $_SESSION['status_message'] = "Student status successfully updated to '$new_status'.";
        } else {
            // Set error message if student is not found for this course
            $_SESSION['status_message'] = "Error: Student not found for this course.";
        }
    } catch (Exception $e) {
        // Catch any exceptions and set error message
        $_SESSION['status_message'] = "Error: " . $e->getMessage();
    }

    // Redirect back to avoid form resubmission on page refresh
    header("Location: grading.php?course_id=" . $course_id);
    exit();
} else {
    // Add error message if parameters are missing
    die("Missing required parameters.");
}
