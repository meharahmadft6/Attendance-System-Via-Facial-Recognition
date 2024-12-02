<?php
require 'db.php';

// Check if 'id' is provided in the URL (i.e., the teacher to delete)
if (isset($_GET['id'])) {
    $teacher_id = $_GET['id'];

    // Prepare the delete query to remove the teacher by ID
    $query = "DELETE FROM teachers WHERE teacher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $teacher_id);

    // Execute the delete query
    if ($stmt->execute()) {
        // Redirect back to the teachers list page after successful deletion
        header("Location: show_teachers.php");
        exit();
    } else {
        // If there is an error, show a message
        echo "Error deleting teacher: " . $stmt->error;
    }
} else {
    // If no 'id' is passed, redirect to the teacher list
    header("Location: show_teachers.php");
    exit();
}
?>
