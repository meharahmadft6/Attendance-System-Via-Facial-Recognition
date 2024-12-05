<?php
session_start();
require 'db.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Ensure course_id is provided
if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
    die("Invalid course ID.");
}

$course_id = $_GET['course_id'];

// Fetch course details
$course_query = "
    SELECT c.course_name
    FROM courses c
    JOIN assigned_courses ac ON c.id = ac.course_id
    WHERE ac.teacher_id = ? AND c.id = ?";
$course_stmt = $conn->prepare($course_query);
$course_stmt->bind_param('ii', $teacher_id, $course_id);
$course_stmt->execute();
$course_result = $course_stmt->get_result();

if ($course_result->num_rows == 0) {
    die("Course not found or you do not have permission to grade it.");
}

$course = $course_result->fetch_assoc();

// Fetch course distribution marks (percentage allocation)
$distribution_query = "
    SELECT quiz_percentage, assignment_percentage, mid_percentage, final_percentage
    FROM course_distribution
    WHERE course_id = ?";
$distribution_stmt = $conn->prepare($distribution_query);
$distribution_stmt->bind_param('i', $course_id);
$distribution_stmt->execute();
$distribution_result = $distribution_stmt->get_result();

if ($distribution_result->num_rows == 0) {
    die("Course distribution marks not found.");
}

$distribution = $distribution_result->fetch_assoc();

// Fetch students for this course
$students_query = "
    SELECT u.id, u.username
    FROM users u
    JOIN enrollments e ON e.user_id = u.id
    WHERE e.course_id = ? AND e.teacher_id = ? AND e.attendance_percentage >= 50";

$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param('ii', $course_id, $teacher_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Handle form submission for grading
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $student_id = $_POST['student_id'];  // Get selected student ID
    $grade_type = $_POST['grade_type'];  // Get the grade type (quiz_1, assignment_1, etc.)
    $grade_value = $_POST['grade_value'];   // Get the grade value

  // Define the maximum marks for each quiz, assignment, etc., based on the distribution percentages
$max_quiz_marks = 100 * $distribution['quiz_percentage'] / 100;
$max_assignment_marks = 100 * $distribution['assignment_percentage'] / 100;
$max_mid_marks = 100 * $distribution['mid_percentage'] / 100;
$max_final_marks = 100 * $distribution['final_percentage'] / 100;

// Calculate the per-quiz limit (if 4 quizzes, each quiz has a limit of max_quiz_marks / 4)
$max_quiz_per_quiz = $max_quiz_marks / 4;
$max_assignment_per_assignment = $max_assignment_marks / 4; // Assuming 4 assignments

// Validate the entered grade for each quiz
if (($grade_type == 'quiz_1' || $grade_type == 'quiz_2' || $grade_type == 'quiz_3' || $grade_type == 'quiz_4') && $grade_value > $max_quiz_per_quiz) {
    die("Error: Marks for this quiz cannot exceed $max_quiz_per_quiz.");
}

// Validate the entered grade for each assignment
if (($grade_type == 'assignment_1' || $grade_type == 'assignment_2' || $grade_type == 'assignment_3' || $grade_type == 'assignment_4') && $grade_value > $max_assignment_per_assignment) {
    die("Error: Marks for this assignment cannot exceed $max_assignment_per_assignment.");
}

// Validate the midterm and final marks against their maximum
if (($grade_type == 'mid') && $grade_value > $max_mid_marks) {
    die("Error: Marks for the midterm cannot exceed $max_mid_marks.");
}

if (($grade_type == 'final') && $grade_value > $max_final_marks) {
    die("Error: Marks for the final exam cannot exceed $max_final_marks.");
}

// Check if a row for this student and course already exists
$check_grade_query = "SELECT id FROM grades WHERE student_id = ? AND course_id = ?";
$check_grade_stmt = $conn->prepare($check_grade_query);
$check_grade_stmt->bind_param('ii', $student_id, $course_id);
$check_grade_stmt->execute();
$check_grade_result = $check_grade_stmt->get_result();

if ($check_grade_result->num_rows == 0) {
    // No row for this student/course, so insert new row
    $insert_query = "
        INSERT INTO grades (student_id, course_id, $grade_type)
        VALUES (?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param('iii', $student_id, $course_id, $grade_value);
    $insert_stmt->execute();
} else {
    // Row exists, so update the specific grade type for this student
    $update_query = "
        UPDATE grades 
        SET $grade_type = ? 
        WHERE student_id = ? AND course_id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('iii', $grade_value, $student_id, $course_id);
    $update_stmt->execute();
}

// Fetch the updated grades and calculate totals
$grades_query = "
    SELECT quiz_1, quiz_2, quiz_3, quiz_4,
           assignment_1, assignment_2, assignment_3, assignment_4,
           mid, final
    FROM grades
    WHERE student_id = ? AND course_id = ?";
$grades_stmt = $conn->prepare($grades_query);
$grades_stmt->bind_param('ii', $student_id, $course_id);
$grades_stmt->execute();
$grades_result = $grades_stmt->get_result();
$grades = $grades_result->fetch_assoc();

// Calculate totals for each category
$total_quiz = $grades['quiz_1'] + $grades['quiz_2'] + $grades['quiz_3'] + $grades['quiz_4'];
$total_assignment = $grades['assignment_1'] + $grades['assignment_2'] + $grades['assignment_3'] + $grades['assignment_4'];
$total_mid = $grades['mid'];
$total_final = $grades['final'];

// Calculate CGPA
$total_marks = $total_quiz + $total_assignment + $total_mid + $total_final;

// Update the grades table with totals and CGPA
$update_totals_query = "
    UPDATE grades
    SET total_quiz = ?, total_assignment = ?, total_mid = ?, total_final = ?, cgpa = ?
    WHERE student_id = ? AND course_id = ?";
$update_totals_stmt = $conn->prepare($update_totals_query);
$update_totals_stmt->bind_param('iiiidii', $total_quiz, $total_assignment, $total_mid, $total_final, $cgpa, $student_id, $course_id);
$update_totals_stmt->execute();

// Redirect to avoid resubmission on page refresh
header("Location: grading.php?course_id=" . $course_id);

exit();

}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grading - <?php echo htmlspecialchars($course['course_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .main-content {
            display: flex;
            flex-direction: column;
            margin-left: 250px; /* Adjust as per sidebar width */
        }
        .grading-table {
            margin-top: 20px;
            width: 100%;
            border-collapse: collapse;
        }
        .grading-table th, .grading-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        .grading-table th {
            background-color: #f2f2f2;
        }
        .form-container {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<?php include "sidebar.php"; ?>

<div class="main-content p-6">
    <h2 class="text-3xl font-bold text-center mb-6">Grading for <?php echo htmlspecialchars($course['course_name']); ?></h2>

    <!-- Display course distribution -->
    <div class="mb-6">
    <h3 class="text-2xl font-semibold text-teal-600 mb-4">Course Distribution</h3>
    <div class="bg-teal-50 p-6 rounded-lg shadow-md">
        <ul class="space-y-4">
            <li class="flex justify-between items-center">
                <span class="text-lg font-medium text-gray-700">Quiz:</span>
                <span class="text-lg font-bold text-teal-600"><?php echo $distribution['quiz_percentage']; ?>%</span>
            </li>
            <li class="flex justify-between items-center">
                <span class="text-lg font-medium text-gray-700">Assignments:</span>
                <span class="text-lg font-bold text-teal-600"><?php echo $distribution['assignment_percentage']; ?>%</span>
            </li>
            <li class="flex justify-between items-center">
                <span class="text-lg font-medium text-gray-700">Midterm:</span>
                <span class="text-lg font-bold text-teal-600"><?php echo $distribution['mid_percentage']; ?>%</span>
            </li>
            <li class="flex justify-between items-center">
                <span class="text-lg font-medium text-gray-700">Final:</span>
                <span class="text-lg font-bold text-teal-600"><?php echo $distribution['final_percentage']; ?>%</span>
            </li>
        </ul>
    </div>
</div>


    <div class="form-container">
        <form method="POST">
            <div class="mb-4">
                <label for="student_id" class="block text-lg font-semibold">Select Student</label>
                <select name="student_id" id="student_id" class="w-full p-2 border rounded">
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['username']); ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="grade_type" class="block text-lg font-semibold">Select Grade Type</label>
                <select name="grade_type" id="grade_type" class="w-full p-2 border rounded">
                    <option value="quiz_1">Quiz 1</option>
                    <option value="quiz_2">Quiz 2</option>
                    <option value="quiz_3">Quiz 3</option>
                    <option value="quiz_4">Quiz 4</option>
                    <option value="assignment_1">Assignment 1</option>
                    <option value="assignment_2">Assignment 2</option>
                    <option value="assignment_3">Assignment 3</option>
                    <option value="assignment_4">Assignment 4</option>
                    <option value="mid">Mid</option>
                    <option value="final">Final</option>
                </select>
            </div>

            <div class="mb-4">
                <label for="grade_value" class="block text-lg font-semibold">Enter Marks</label>
                <input type="float" name="grade_value" id="grade_value" class="w-full p-2 border rounded" required>
            </div>

            <button type="submit" class="bg-teal-500 hover:bg-teal-600 text-white py-2 px-4 rounded-lg">Update Marks</button>
        </form>
    </div>


    <h3 class="text-xl font-bold mt-6 mb-4">Student Grades</h3>
<table class="grading-table">
    <thead>
        <tr>
            <th>Student ID</th>
            <th>Quiz 1</th>
            <th>Quiz 2</th>
            <th>Quiz 3</th>
            <th>Quiz 4</th>
            <th>Assignment 1</th>
            <th>Assignment 2</th>
            <th>Assignment 3</th>
            <th>Assignment 4</th>
            <th>Mid</th>
            <th>Final</th>
            <th>CGPA</th>
            <th>Status</th> <!-- Add this column -->
            <th>Action</th> <!-- Add this column for toggling status -->
        </tr>
    </thead>
    <tbody>
    <?php
 $grades_query = "
 SELECT g.id, g.student_id, g.quiz_1, g.quiz_2, g.quiz_3, g.quiz_4,
        g.assignment_1, g.assignment_2, g.assignment_3, g.assignment_4,
        g.mid, g.final, g.total_quiz, g.total_assignment, g.total_mid, g.total_final, g.cgpa, g.status,
        e.attendance_percentage
 FROM grades g
 JOIN enrollments e ON g.student_id = e.user_id AND g.course_id = e.course_id
 WHERE g.course_id = ? AND e.attendance_percentage >= 50";


    $grades_stmt = $conn->prepare($grades_query);
    $grades_stmt->bind_param('i', $course_id);
    $grades_stmt->execute();
    $grades_result = $grades_stmt->get_result();

    while ($grade = $grades_result->fetch_assoc()):
    ?>
        <tr>
            <td><?php echo htmlspecialchars($grade['student_id']); ?></td>
            <td><?php echo $grade['quiz_1']; ?></td>
            <td><?php echo $grade['quiz_2']; ?></td>
            <td><?php echo $grade['quiz_3']; ?></td>
            <td><?php echo $grade['quiz_4']; ?></td>
            <td><?php echo $grade['assignment_1']; ?></td>
            <td><?php echo $grade['assignment_2']; ?></td>
            <td><?php echo $grade['assignment_3']; ?></td>
            <td><?php echo $grade['assignment_4']; ?></td>
            <td><?php echo $grade['mid']; ?></td>
            <td><?php echo $grade['final']; ?></td>
            <td><?php echo $grade['cgpa']; ?></td>
            <td><?php echo $grade['status'] == 'show' ? 'Visible' : 'Hidden'; ?></td>
            <td>
                <!-- Toggle Status -->
                <a href="toggle_status.php?course_id=<?php echo $course_id; ?>&toggle_status=1&student_id=<?php echo $grade['student_id']; ?>" 
                   class="bg-teal-500 hover:bg-blue-600 text-white py-1 px-3 rounded">
                   <?php echo $grade['status'] == 'show' ? 'Hide Marks' : 'Show Marks'; ?>
                </a>

                <!-- Calculate CGPA Form -->
                <form method="POST" action="calculate_cgpa.php" class="mt-2">
                    <input type="hidden" name="student_id" value="<?php echo $grade['student_id']; ?>" />
                    <input type="hidden" name="course_id" value="<?php echo $course_id; ?>" />
                    <button 
                        type="submit" 
                        name="calculate_cgpa" 
                        class="bg-blue-500 hover:bg-blue-600 text-white py-1 px-3 rounded"
                    >
                        Calculate CGPA
                    </button>
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</tbody>


</table>

            
</div>

</body>
</html>
