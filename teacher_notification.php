<?php
session_start();
require 'db.php';

// Ensure the user is a teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Fetch all courses associated with the teacher
$courses_query = "SELECT c.id AS course_id, c.course_name 
                  FROM courses c 
                  JOIN assigned_courses ac ON ac.course_id = c.id 
                  WHERE ac.teacher_id = ?";
$courses_stmt = $conn->prepare($courses_query);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses_result = $courses_stmt->get_result();

// Fetch all students enrolled in the teacher's courses
$students_query = "
    SELECT u.id AS student_id, u.username AS student_name, e.course_id
    FROM users u
    JOIN enrollments e ON e.user_id = u.id
    WHERE e.course_id IN (SELECT c.id FROM courses c JOIN assigned_courses ac ON ac.course_id = c.id WHERE ac.teacher_id = ?)
";
$students_stmt = $conn->prepare($students_query);
$students_stmt->bind_param("i", $teacher_id);
$students_stmt->execute();
$students_result = $students_stmt->get_result();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_for = $_POST['notification_for']; // Single or everyone
    $course_id = $_POST['course_id']; // If for everyone in a course
    $user_id = $_POST['user_id']; // Specific student ID
    $message = $_POST['message'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

   // After gathering data from the form
if ($notification_for === 'everyone') {
    $message = $_POST['message'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $course_id = (int) $_POST['course_id'];  // Ensure it's an integer

    // Prepare the query to insert into the notifications table, making sure the same student gets only one entry
    $query = "
    INSERT INTO notifications (user_type, user_id, message, start_date, end_date, status, created_at)
    SELECT 'student', u.id, ?, ?, ?, 'active', NOW()
    FROM users u
    JOIN enrollments e ON e.user_id = u.id 
    WHERE e.course_id = ? 
    AND NOT EXISTS (
        SELECT 1 
        FROM notifications n 
        WHERE n.user_id = u.id 
        AND n.message = ?
    )
";

$stmt = $conn->prepare($query);
if ($stmt === false) {
    die("Query preparation failed: " . $conn->error);
}

// Corrected bind_param with the proper type string
$stmt->bind_param("sssis", $message, $start_date, $end_date, $course_id, $message);

if ($stmt->execute()) {
    echo "Notification sent successfully!";
} else {
    echo "Error sending notification: " . $stmt->error;
}
}

     else {
        // Notify a specific student
        $query = "INSERT INTO notifications (user_type, user_id, message, start_date, end_date) 
                  VALUES ('student', ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isss", $user_id, $message, $start_date, $end_date);
    }

    if ($stmt->execute()) {
        // Redirect or display success message
        header("Location: teacher_dashboard.php");
    } else {
        echo "Error adding notification: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Notification</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="font-[Baloo+Bhaijaan+2] bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-center text-4xl font-bold mb-6">Add Notification</h2>
        
        <form action="teacher_notification.php" method="POST">
            <div class="mb-4">
                <label for="notification_for" class="block text-sm font-medium mb-2">Send To</label>
                <select name="notification_for" id="notification_for" class="w-full border rounded-lg p-2" onchange="toggleUserSelection()" required>
                    <option value="everyone">Everyone in a Course</option>
                    <option value="single">Single Student</option>
                </select>
            </div>

            <div id="course-selection" class="mb-4">
                <label for="course_id" class="block text-sm font-medium mb-2">Select Course</label>
                <select name="course_id" id="course_id" class="w-full border rounded-lg p-2" required>
                    <?php while ($course = $courses_result->fetch_assoc()): ?>
                        <option value="<?php echo $course['course_id']; ?>"><?php echo $course['course_name']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div id="user-selection" class="mb-4 hidden">
                <label for="user_id" class="block text-sm font-medium mb-2">Select Student</label>
                <select name="user_id" id="user_id" class="w-full border rounded-lg p-2">
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <option value="<?php echo $student['student_id']; ?>" data-course="<?php echo $student['course_id']; ?>">
                            <?php echo $student['student_name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="message" class="block text-sm font-medium mb-2">Message</label>
                <textarea name="message" id="message" class="w-full border rounded-lg p-2" rows="4" required></textarea>
            </div>

            <div class="mb-4">
                <label for="start_date" class="block text-sm font-medium mb-2">Start Date</label>
                <input type="datetime-local" name="start_date" id="start_date" class="w-full border rounded-lg p-2" required>
            </div>

            <div class="mb-4">
                <label for="end_date" class="block text-sm font-medium mb-2">End Date</label>
                <input type="datetime-local" name="end_date" id="end_date" class="w-full border rounded-lg p-2" required>
            </div>

            <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                Add Notification
            </button>
        </form>
    </div>

    <script>
        function toggleUserSelection() {
            const notificationFor = document.getElementById('notification_for').value;
            const userSelection = document.getElementById('user-selection');
            const courseSelection = document.getElementById('course-selection');

            if (notificationFor === 'single') {
                userSelection.classList.remove('hidden');
                courseSelection.classList.add('hidden');
            } else {
                userSelection.classList.add('hidden');
                courseSelection.classList.remove('hidden');
            }
        }
    </script>
</body>
</html>
