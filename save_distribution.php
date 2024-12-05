<?php
session_start();
require 'db.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// Get the teacher ID from session
$teacher_id = $_SESSION['teacher_id'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize and fetch the form data
    $course_id = $_POST['course_id'];
    $quiz_percentage = $_POST['quiz_percentage'];
    $assignment_percentage = $_POST['assignment_percentage'];
    $mid_percentage = $_POST['mid_percentage'];
    $final_percentage = $_POST['final_percentage'];

    // Validation: Ensure total distribution is 100%
    $total_percentage = $quiz_percentage + $assignment_percentage + $mid_percentage + $final_percentage;
    
    if ($total_percentage != 100) {
        echo "<script>Swal.fire('Error', 'Total distribution must be 100%.', 'error');</script>";
        exit();
    }

    // Prepare the insert query for the distribution table
    $query = "
        INSERT INTO course_distribution (course_id, teacher_id, quiz_percentage, assignment_percentage, mid_percentage, final_percentage)
        VALUES (?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            quiz_percentage = ?, assignment_percentage = ?, mid_percentage = ?, final_percentage = ?";
    
    $stmt = $conn->prepare($query);
    if ($stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }

    // Bind the parameters
    $stmt->bind_param(
        'iiiiiissss',
        $course_id, $teacher_id, $quiz_percentage, $assignment_percentage, $mid_percentage, $final_percentage,
        $quiz_percentage, $assignment_percentage, $mid_percentage, $final_percentage
    );

    // Execute the query
    if ($stmt->execute()) {
        // If the insert was successful, redirect back to the course page
        header("Location: teacher_courses.php?course_id=" . $course_id);
        exit();
    } else {
        echo "Error saving course distribution: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>
