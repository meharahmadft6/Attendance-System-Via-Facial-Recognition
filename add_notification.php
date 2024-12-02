<?php
session_start();
require 'db.php';

// Ensure the user is an admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Fetch all students and teachers to populate the dropdown
$students_query = "SELECT id, username FROM users WHERE role = 'student'";
$students_result = $conn->query($students_query);

$teachers_query = "SELECT teacher_id, name FROM teachers";
$teachers_result = $conn->query($teachers_query);

// Handle the form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notification_for = $_POST['notification_for']; // Single or everyone
    $user_type = $_POST['user_type'];
    $message = $_POST['message'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    if ($notification_for === 'everyone') {
        // Handle sending notifications to everyone based on user type
        if ($user_type === 'student') {
            $query = "INSERT INTO notifications (user_type, user_id, message, start_date, end_date) 
                      SELECT 'student', id, ?, ?, ? FROM users WHERE role = 'student'";
        } else {
            $query = "INSERT INTO notifications (user_type, user_id, message, start_date, end_date) 
                      SELECT 'teacher', teacher_id, ?, ?, ? FROM teachers";
        }
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sss", $message, $start_date, $end_date);
    } else {
        // Handle sending notifications to a specific user
        $user_id = $_POST['user_id'];
        $query = "INSERT INTO notifications (user_type, user_id, message, start_date, end_date) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sisss", $user_type, $user_id, $message, $start_date, $end_date);
    }

    if ($stmt->execute()) {
        // Redirect or display success message
        header("Location: dashboard.php");
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
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="font-[Baloo+Bhaijaan+2] bg-gray-50 min-h-screen flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-center text-4xl font-bold mb-6">Add Notification</h2>
        
        <form action="add_notification.php" method="POST">
            <div class="mb-4">
                <label for="notification_for" class="block text-sm font-medium mb-2">Send To</label>
                <select name="notification_for" id="notification_for" class="w-full border rounded-lg p-2" onchange="toggleUserSelection()" required>
                    <option value="everyone">Everyone</option>
                    <option value="single">Single Person</option>
                </select>
            </div>

            <div id="user-selection" class="mb-4 hidden">
                <label for="user_type" class="block text-sm font-medium mb-2">User Type</label>
                <select name="user_type" id="user_type" class="w-full border rounded-lg p-2" onchange="updateUserDropdown()" required>
                    <option value="student">Student</option>
                    <option value="teacher">Teacher</option>
                </select>

                <label for="user_id" class="block text-sm font-medium mt-4 mb-2">User</label>
                <select name="user_id" id="user_id" class="w-full border rounded-lg p-2">
                    <?php while ($student = $students_result->fetch_assoc()): ?>
                        <option class="student" value="<?php echo $student['id']; ?>"><?php echo $student['username']; ?></option>
                    <?php endwhile; ?>
                    <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                        <option class="teacher hidden" value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['name']; ?></option>
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
            if (notificationFor === 'single') {
                userSelection.classList.remove('hidden');
            } else {
                userSelection.classList.add('hidden');
            }
        }

        function updateUserDropdown() {
            const userType = document.getElementById('user_type').value;
            const studentOptions = document.querySelectorAll('.student');
            const teacherOptions = document.querySelectorAll('.teacher');

            if (userType === 'student') {
                studentOptions.forEach(option => option.classList.remove('hidden'));
                teacherOptions.forEach(option => option.classList.add('hidden'));
            } else {
                teacherOptions.forEach(option => option.classList.remove('hidden'));
                studentOptions.forEach(option => option.classList.add('hidden'));
            }
        }
    </script>
</body>
</html>
