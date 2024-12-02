<?php
require 'db.php';

if (isset($_GET['id'])) {
    $course_id = $_GET['id'];

    // Prepare and execute delete query
    $query = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $course_id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        header("Location: show_courses.php"); // Redirect back to courses page
    } else {
        echo "Error deleting course.";
    }

    $stmt->close();
}
?>
