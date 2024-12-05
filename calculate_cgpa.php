<?php
require 'db.php'; // Include your database connection

function calculateCGPA($total_marks) {
    // Grade and CGPA calculation
    if ($total_marks >= 85) {
        return ['grade' => 'A', 'cgpa' => 4.0];  // A - 4.0 CGPA
    } elseif ($total_marks >= 80) {
        return ['grade' => 'A-', 'cgpa' => 3.67];  // A- - 3.67 CGPA
    } elseif ($total_marks >= 75) {
        return ['grade' => 'B+', 'cgpa' => 3.33];  // B+ - 3.33 CGPA
    } elseif ($total_marks >= 70) {
        return ['grade' => 'B', 'cgpa' => 3.0];   // B - 3.0 CGPA
    } elseif ($total_marks >= 65) {
        return ['grade' => 'B-', 'cgpa' => 2.67];  // B- - 2.67 CGPA
    } elseif ($total_marks >= 50) {
        return ['grade' => 'C', 'cgpa' => 2.0];   // C - 2.0 CGPA
    } else {
        return ['grade' => 'F', 'cgpa' => 0.0];    // F - 0.0 CGPA
    }
}

function fetchAndCalculateCGPA($student_id, $course_id) {
    global $conn;

    // Fetch grades for the specific student and course
    $query = "
        SELECT total_quiz, total_assignment, total_mid, total_final
        FROM grades
        WHERE student_id = ? AND course_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        // Add the marks
        $total_marks = $row['total_quiz'] + $row['total_assignment'] + $row['total_mid'] + $row['total_final'];

        // Calculate CGPA
        $cgpa_data = calculateCGPA($total_marks);
        $cgpa = $cgpa_data['cgpa'];
        $grade = $cgpa_data['grade'];

        // Update the database with CGPA and grade
        $update_query = "
            UPDATE grades
            SET cgpa = ?, status = ?
            WHERE student_id = ? AND course_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $status = 'updated'; // Example status; adjust if needed
        $update_stmt->bind_param('dsii', $cgpa, $status, $student_id, $course_id);
        $update_stmt->execute();

        // Redirect to grading page after success
        header("Location: grading.php?course_id=" . $course_id);
        exit(); // Ensure no further code is executed after redirection
    } else {
        // No data found, handle error
        echo "Error: No data found for the given student and course.";
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_cgpa'])) {
    $student_id = $_POST['student_id'];
    $course_id = $_POST['course_id'];

    fetchAndCalculateCGPA($student_id, $course_id);
}
?>
