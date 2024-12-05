<?php
session_start();
require 'db.php';

// Ensure the user is logged in as a teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch student_id, course_id, and teacher_id from query parameters
$student_id = $_GET['student_id'];
$course_id = $_GET['course_id'];
$teacher_id = $_GET['teacher_id'];

// Check if course_id is provided
if (empty($course_id)) {
    die("Course ID is missing.");
}
// Fetch detailed student information
$query = "
    SELECT u.id AS student_id, u.username AS student_name, e.attendance_percentage, 
           e.attended_classes, c.total_lectures, g.total_quiz, g.total_assignment, g.total_mid, 
           g.total_final, g.cgpa, g.status, c.course_name
    FROM users u
    JOIN enrollments e ON e.user_id = u.id
    JOIN grades g ON g.student_id = u.id AND g.course_id = e.course_id
    JOIN courses c ON c.id = e.course_id
    WHERE u.id = ? AND c.id = ? AND e.teacher_id = ?
";
$stmt = $conn->prepare($query);
$stmt->bind_param('iii', $student_id, $course_id, $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
} else {
    die("No details found for the specified student.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Student Details</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
</head>
<body class="bg-gray-50 font-sans min-h-screen flex flex-col">

    <?php include "sidebar.php"; ?>

    <!-- Main Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-4xl font-bold text-center mb-8 text-teal-600">Student Details</h2>

        <div class="bg-white shadow-lg rounded-lg p-6">
            <!-- Student Info -->
            <div class="mb-6">
                <h3 class="text-2xl font-semibold text-teal-600 mb-4">Personal Information</h3>
                <h3 class="text-xl font-semibold text-teal-600 mb-2">
                    <?php echo htmlspecialchars($student['student_name']); ?>
                </h3>
                
            </div>

            <!-- Course and Grades -->
            <div class="mb-6">
                <h3 class="text-2xl font-semibold text-teal-600 mb-4">Course and Grades</h3>
                <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_name']); ?></p>
                <p><strong>Total Quiz Marks:</strong> <?php echo htmlspecialchars($student['total_quiz']); ?></p>
                <p><strong>Total Assignment Marks:</strong> <?php echo htmlspecialchars($student['total_assignment']); ?></p>
                <p><strong>Midterm Marks:</strong> <?php echo htmlspecialchars($student['total_mid']); ?></p>
                <p><strong>Final Marks:</strong> <?php echo htmlspecialchars($student['total_final']); ?></p>
                <p><strong>CGPA:</strong> <?php echo htmlspecialchars($student['cgpa']); ?></p>
                <p><strong>Status:</strong> <?php echo htmlspecialchars($student['status']); ?></p>
            </div>

            <!-- Attendance Info -->
            <div class="mb-6">
                <h3 class="text-2xl font-semibold text-teal-600 mb-4">Attendance</h3>
                <p><strong>Attendance Percentage:</strong> <?php echo htmlspecialchars($student['attendance_percentage']); ?>%</p>
                <p><strong>Lectures Attended:</strong> <?php echo htmlspecialchars($student['attended_classes']); ?></p>
                <p><strong>Total Lectures:</strong> <?php echo htmlspecialchars($student['total_lectures']); ?></p>
            </div>
        </div>
    </div>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
