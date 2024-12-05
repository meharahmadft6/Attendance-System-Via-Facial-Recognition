<?php
require 'db.php';

// Get teacher's ID from session
session_start();
if (!isset($_SESSION['teacher_id'])) {
    die("Teacher not logged in");
}

$teacher_id = $_SESSION['teacher_id'];

// Get the search query if exists
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Query to fetch filtered students based on search term
$query = "
    SELECT u.id AS student_id, u.username AS student_name, e.facial_data, e.attendance_percentage, c.course_name, 
           c.total_lectures
    FROM users u
    JOIN enrollments e ON e.user_id = u.id
    JOIN courses c ON c.id = e.course_id
    JOIN assigned_courses ac ON ac.course_id = c.id
    WHERE ac.teacher_id = ? 
    AND (u.username LIKE ? OR c.course_name LIKE ?)
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

// Add wildcard to search term
$search_term = "%" . $search_query . "%";
$stmt->bind_param('iss', $teacher_id, $search_term, $search_term);
$stmt->execute();
$result = $stmt->get_result();

// Check if any students match the search
if ($result->num_rows > 0) {
    while ($student = $result->fetch_assoc()) {
        echo "
        <div class='bg-teal-100 shadow-lg rounded-lg p-6 flex items-center scale-on-hover transition-transform duration-300 hover:shadow-xl'>
            <div class='flex flex-col space-y-3 mr-6'>
                <h4 class='text-xl font-semibold text-teal-700'>" . htmlspecialchars($student['student_name']) . "</h4>
                <p><strong>Course:</strong> " . htmlspecialchars($student['course_name']) . "</p>
                <p><strong>Attendance:</strong> " . htmlspecialchars($student['attendance_percentage']) . "%</p>
                <p><strong>Total Lectures:</strong> " . htmlspecialchars($student['total_lectures']) . "</p>
            </div>
            <div class='flex justify-center items-center w-32 h-32 rounded-full overflow-hidden border-4 border-teal-600'>
                <img src='../uploads/" . htmlspecialchars($student['facial_data']) . "' alt='Facial Data' class='w-full h-full object-cover'>
            </div>
        </div>";
    }
} else {
    echo "<p class='col-span-full text-center text-gray-500'>No students match your search.</p>";
}

$stmt->close();
$conn->close();
?>
