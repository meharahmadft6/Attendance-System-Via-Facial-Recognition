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

// Fetch the course ID(s) assigned to this teacher from the `assigned_courses` table
$query = "
    SELECT c.id, c.course_name, c.start_date, c.end_date, c.start_time, c.end_time, c.day, c.total_lectures 
    FROM courses c 
    JOIN assigned_courses ac ON c.id = ac.course_id 
    WHERE ac.teacher_id = ?";
    
$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param('i', $teacher_id);
$stmt->execute();
$result = $stmt->get_result();

// Handle course details if course is selected
$course_details = null;
$students = [];

if (isset($_GET['course_id'])) {
    if (!isset($_GET['course_id']) || !is_numeric($_GET['course_id'])) {
        die("Invalid course ID.");
    }
    $course_id = $_GET['course_id'];

    // Fetch course details for the selected course
    $course_query = "
        SELECT c.id, c.course_name, c.start_date, c.end_date, c.start_time, c.end_time, c.day, c.total_lectures 
        FROM courses c
        WHERE c.id = ?";
    
    $course_stmt = $conn->prepare($course_query);
    if ($course_stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }
    
    $course_stmt->bind_param('i', $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();

    if ($course_result->num_rows > 0) {
        $course_details = $course_result->fetch_assoc();

        // Fetch students enrolled in this course, including facial data from the enrollments table
        $student_query = "
        SELECT u.username AS student_name, u.id AS student_id, e.facial_data, e.created_at AS enrollment_date 
        FROM users u
        JOIN enrollments e ON e.user_id = u.id
        WHERE e.course_id = ? AND e.teacher_id = ?";
    
        $student_stmt = $conn->prepare($student_query);
        if ($student_stmt === false) {
            die("Query preparation failed: " . $conn->error);
        }
        
        // Bind both course_id and teacher_id as parameters
        $student_stmt->bind_param('ii', $course_id, $teacher_id);  // 'ii' means two integers: course_id and teacher_id
        $student_stmt->execute();
        $students = $student_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    $course_stmt->close();
    $student_stmt->close();
}

// Handle the course update request
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $day = $_POST['day'];

    // Prepare the update query
    $update_query = "
        UPDATE courses 
        SET start_time = ?, end_time = ?, day = ? 
        WHERE id = ?";
    
    $stmt = $conn->prepare($update_query);
    if ($stmt === false) {
        die("Query preparation failed: " . $conn->error);
    }

    // Bind the parameters and execute the query
    $stmt->bind_param('sssi', $start_time, $end_time, $day, $course_id);
    if ($stmt->execute()) {
        // Redirect back to the course page after updating
        header("Location: teacher_courses.php");
        exit();
    } else {
        echo "Error updating course time: " . $stmt->error;
    }
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="../assets/face.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.min.css" rel="stylesheet">

<!-- SweetAlert2 JS -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.0/dist/sweetalert2.min.js"></script>

    <style>
        .scale-on-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease-in-out;
        }
        body {
            font-family: 'Baloo Bhai 2', 'Poppins', sans-serif;
        }
        .modal {
    display: none; /* Hidden by default */
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent background */
    overflow: auto;
    z-index: 1000;
    justify-content: center;
    align-items: center;
}

.modal-content {
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    width: 80%; /* You can adjust this to fit your needs */
    max-width: 900px; /* Restrict the width */
    max-height: 80%; /* Restrict the height */
    overflow-y: auto; /* Enable vertical scrolling */
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}


    </style>
    <script>
        // Open modal function when page loads with course_id in URL
        window.onload = function() {
    const courseId = new URLSearchParams(window.location.search).get('course_id');
    if (courseId) {
        document.getElementById("courseModal").style.display = "flex";
    }
}

// Open Edit Modal
function openEditModal(courseId, startTime, endTime) {
    document.getElementById('editCourseId').value = courseId;
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_time').value = endTime;
    document.getElementById('editModal').style.display = 'flex';
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

    </script>
</head>
<body class="font-sans bg-gray-50 min-h-screen flex">

    <?php include "sidebar.php" ?> 
    <!-- Main Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-4xl font-bold text-center mb-6"> Assigned Courses</h2>
        
        <!-- Display Courses -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($course = $result->fetch_assoc()): ?>
                    <div class="bg-[#e0fbfc] shadow-lg rounded-lg p-6 relative scale-on-hover">
                        <h3 class="text-xl font-semibold mb-2">
                            <a href="?course_id=<?php echo $course['id']; ?>" class="text-teal-600 hover:underline">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </a>
                        </h3>
                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($course['start_date']); ?></p>
                        <p><strong>End Date:</strong> <?php echo htmlspecialchars($course['end_date']); ?></p>
                        <p><strong>Start Time:</strong> <?php echo htmlspecialchars($course['start_time']); ?></p>
                        <p><strong>End Time:</strong> <?php echo htmlspecialchars($course['end_time']); ?></p>
                        <p><strong>Day:</strong> <?php echo htmlspecialchars($course['day']); ?></p>
                        <p><strong>Total Lectures:</strong> <?php echo htmlspecialchars($course['total_lectures']); ?></p>

                        <!-- Edit Icon -->
                        <button class="absolute top-4 right-4 text-black " onclick="openEditModal(<?php echo $course['id']; ?>, '<?php echo $course['start_time']; ?>', '<?php echo $course['end_time']; ?>')">
                            <img src="../assets/social.png" class="w-8 h-8" alt="">
                        </button>
                        <button class="mt-4 bg-teal-500 hover:bg-teal-600 text-white py-2 px-4 rounded-lg">
    <a href="grading.php?course_id=<?php echo $course['id']; ?>" class="text-white">Go to Grading</a>
</button>
<button class="mt-4 bg-teal-500 hover:bg-teal-600 text-white py-2 px-4 rounded-lg"
        onclick="openDistributionModal(<?php echo $course['id']; ?>)">
    Set Course Distribution
</button>

                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-full text-center text-gray-500">No courses assigned to you yet.</p>
            <?php endif; ?>
        </div>

        <?php if ($course_details): ?>
            <div class="modal" id="courseModal">
    <div class="modal-content">
        <button onclick="closeModal('courseModal')" class="text-gray-500 p-2">Close</button>
        <h2 class="text-3xl font-bold text-center mb-4"><?php echo htmlspecialchars($course_details['course_name']); ?> - Student List</h2>
        <p class="text-center mb-4"><strong>Course Start Date:</strong> <?php echo htmlspecialchars($course_details['start_date']); ?> | <strong>Course End Date:</strong> <?php echo htmlspecialchars($course_details['end_date']); ?></p>

        <h3 class="text-2xl mb-6 text-center">Enrolled Students</h3>
        <?php if (count($students) > 0): ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($students as $student): ?>
                    <div class="bg-teal-100 shadow-lg rounded-lg p-6 flex items-center justify-between">
                        <!-- Left Side: Name, Enrollment Date, and Student ID -->
                        <div class="flex flex-col justify-between">
                            <h4 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($student['student_name']); ?></h4>
                            <p><strong>Enrollment Date:</strong> <?php echo htmlspecialchars($student['enrollment_date']); ?></p>
                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($student['student_id']); ?></p>
                        </div>

                        <!-- Right Side: Large Facial Image -->
                        <div class="ml-4">
                            <img src="../uploads/<?php echo htmlspecialchars($student['facial_data']); ?>" alt="Facial Data" class="w-35 h-32 object-cover rounded-full">
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p class="text-center text-gray-500">No students enrolled in this course yet.</p>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>


      <!-- Edit Modal -->
<div id="editModal" class="modal">
    <div class="modal-content" style="width: 50%; max-width: 600px;">
        <button onclick="closeModal('editModal')" class="text-gray-500 p-2">Close</button>
        <h2 class="text-3xl font-bold text-center mb-4">Edit Course Time</h2>

        <form action="" method="POST">
            <input type="hidden" name="course_id" id="editCourseId">
            
            <div class="mb-4">
                <label for="start_time" class="block text-sm font-medium mb-2">Start Time</label>
                <input type="time" name="start_time" id="start_time" class="w-full border rounded-lg p-2" required>
            </div>

            <div class="mb-4">
                <label for="end_time" class="block text-sm font-medium mb-2">End Time</label>
                <input type="time" name="end_time" id="end_time" class="w-full border rounded-lg p-2" required>
            </div>
            <div class="mb-4">
                <label for="day" class="block text-sm font-medium mb-2">Day</label>
                <input type="text" name="day" id="day" class="w-full border rounded-lg p-2" required>
            </div>

            <button type="submit" class="w-full bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-4 rounded-lg">
                Update Time
            </button>
        </form>
    </div>
</div>

<!-- Distribution Modal -->
<div id="distributionModal" class="modal">
    <div class="modal-content" style="width: 50%; max-width: 600px;">
        <button onclick="closeModal('distributionModal')" class="text-gray-500 p-2">Close</button>
        <h2 class="text-3xl font-bold text-center mb-4">Set Course Distribution</h2>

        <form action="save_distribution.php" method="POST">
            <input type="hidden" name="course_id" id="distributionCourseId">

            <div class="mb-4">
                <label for="quiz_percentage" class="block text-sm font-medium mb-2">Quiz Percentage</label>
                <input type="number" name="quiz_percentage" id="quiz_percentage" class="w-full border rounded-lg p-2" required min="0" max="100">
            </div>

            <div class="mb-4">
                <label for="assignment_percentage" class="block text-sm font-medium mb-2">Assignment Percentage</label>
                <input type="number" name="assignment_percentage" id="assignment_percentage" class="w-full border rounded-lg p-2" required min="0" max="100">
            </div>

            <div class="mb-4">
                <label for="mid_percentage" class="block text-sm font-medium mb-2">Mid Percentage</label>
                <input type="number" name="mid_percentage" id="mid_percentage" class="w-full border rounded-lg p-2" required min="0" max="100">
            </div>

            <div class="mb-4">
                <label for="final_percentage" class="block text-sm font-medium mb-2">Final Percentage</label>
                <input type="number" name="final_percentage" id="final_percentage" class="w-full border rounded-lg p-2" required min="0" max="100">
            </div>

            <button type="submit" class="w-full bg-teal-500 hover:bg-teal-600 text-white font-medium py-2 px-4 rounded-lg">
                Save Distribution
            </button>
        </form>
    </div>
</div>

    </div>

</body>
<script>
  // Open Edit Modal
function openEditModal(courseId, startTime, endTime) {
    document.getElementById('editCourseId').value = courseId;
    document.getElementById('start_time').value = startTime;
    document.getElementById('end_time').value = endTime;
    document.getElementById('editModal').style.display = 'flex';
}

// Close Modal
function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
// Open Course Distribution Modal
function openDistributionModal(courseId) {
    console.log('Open Course Distribution Modal',courseId);
    document.getElementById('distributionCourseId').value = courseId;
    
    document.getElementById('distributionModal').style.display = 'flex';
}

 
</script>
</html>

<?php
$stmt->close();
$conn->close();
?>
