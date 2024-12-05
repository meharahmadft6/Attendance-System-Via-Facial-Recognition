<?php
session_start();
require 'db.php';

// Ensure teacher is logged in
if (!isset($_SESSION['teacher_id'])) {
    header("Location: login.php");
    exit();
}

// Get teacher's ID from session
$teacher_id = $_SESSION['teacher_id'];

// Fetch initial student data (without search filter)
$query = "
    SELECT u.id AS student_id, u.username AS student_name, e.facial_data, e.attendance_percentage, 
           c.course_name, c.id AS course_id, e.attended_classes
    FROM users u
    JOIN enrollments e ON e.user_id = u.id
    JOIN courses c ON c.id = e.course_id
    JOIN assigned_courses ac ON ac.course_id = c.id
    WHERE ac.teacher_id = ? AND e.teacher_id = ac.teacher_id
";



$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher's Enrolled Students</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script> <!-- Include jQuery -->
    <style>
        .scale-on-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease-in-out;
        }
    </style>
</head>
<body class="font-sans bg-gray-50 min-h-screen flex flex-col">

    <?php include "sidebar.php"; ?>

    <!-- Main Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-4xl font-bold text-center mb-8 text-teal-600">Enrolled Students</h2>

        <!-- Search Bar Section -->
        <div class="mb-4 flex justify-between items-center">
            <div class="relative w-full sm:w-1/3">
                <input 
                    type="text" 
                    id="search-bar" 
                    class="w-full px-4 py-2 border border-teal-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 transition duration-300 ease-in-out"
                    placeholder="Search by student or course..."
                    oninput="searchStudents()"
                />
                <span class="absolute top-0 right-0 mt-3 mr-4 text-teal-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 18a7 7 0 100-14 7 7 0 000 14zM21 21l-4.35-4.35" />
                    </svg>
                </span>
            </div>
        </div>

        <!-- Display Students -->
        <div id="student-list" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-3 gap-6">
    <?php if ($result->num_rows > 0): ?>
        <?php while ($student = $result->fetch_assoc()): ?>
            <?php
                // Check if student_id and course_id exist
                $student_id = isset($student['student_id']) ? $student['student_id'] : '';
                $course_id = isset($student['course_id']) ? $student['course_id'] : '';
                $student_name = isset($student['student_name']) ? $student['student_name'] : 'Unknown Student';
            ?>
            <div class="bg-teal-100 shadow-lg rounded-lg p-6 flex items-center scale-on-hover transition-transform duration-300 hover:shadow-xl">
                <!-- Left Side: Student Info -->
                <div class="flex flex-col space-y-3 mr-6">
                    <h4 class="text-xl font-semibold text-teal-700">
                        
                            <?php echo htmlspecialchars($student_name); ?>
                
                    </h4>       
                    <p><strong>Course:</strong> <?php echo htmlspecialchars($student['course_name']); ?></p>
                    <p><strong>Attendance:</strong> <?php echo htmlspecialchars($student['attendance_percentage']); ?>%</p>
                    <p><strong>Lectures Conducted:</strong> <?php echo htmlspecialchars($student['attended_classes']); ?></p>
                </div>

                <!-- Right Side: Facial Data (Image) -->
                <div class="flex justify-center items-center w-32 h-32 rounded-full overflow-hidden border-4 border-teal-600">
                    <?php if (!empty($student['facial_data'])): ?>
                        <img src="../uploads/<?php echo htmlspecialchars($student['facial_data']); ?>" alt="Facial Data" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-gray-500">No Image Available</span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p class="col-span-full text-center text-gray-500">No students match your search.</p>
    <?php endif; ?>
</div>


    <!-- JavaScript to filter search without page reload -->
    <script>
        function searchStudents() {
            let searchQuery = document.getElementById("search-bar").value;

            // Make AJAX request to PHP to get filtered data
            $.ajax({
                url: "search_students.php", // PHP script to handle search logic
                type: "GET",
                data: { search: searchQuery },
                success: function(response) {
                    // Replace the student list with the updated response
                    $("#student-list").html(response);
                }
            });
        }
    </script>

</body>
</html>

<?php
$stmt->close();
$conn->close();
?>
