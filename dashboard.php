<?php
session_start();
require 'db.php';

// Set session timeout period (e.g., 30 minutes)
$timeout_duration = 3600; // 30 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    // Destroy the session and redirect to the login page
    session_unset();
    session_destroy();
    header("Location:login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch counts from the database
$total_courses_query = "SELECT COUNT(*) as count FROM courses";
$total_students_query = "SELECT COUNT(*) as count FROM users WHERE role = 'student'";
$total_users_query = "SELECT COUNT(*) as count FROM users";
$total_enrollment_query = "SELECT COUNT(*) as count FROM enrollments";
$total_teacher_query = "SELECT COUNT(*) as count FROM teachers";

$total_courses_result = $conn->query($total_courses_query);
$total_students_result = $conn->query($total_students_query);
$total_users_result = $conn->query($total_users_query);
$total_enrollment_result = $conn->query($total_enrollment_query);
$total_teacher_result = $conn->query($total_teacher_query);

$total_courses = $total_courses_result->fetch_assoc()['count'];
$total_students = $total_students_result->fetch_assoc()['count'];
$total_users = $total_users_result->fetch_assoc()['count'];
$total_enrollments = $total_enrollment_result->fetch_assoc()['count'];
$total_teachers = $total_teacher_result->fetch_assoc()['count'];

// Clear session messages after fetching them
$error_message = isset($_SESSION['error']) ? $_SESSION['error'] : null;
$success_message = isset($_SESSION['success']) ? $_SESSION['success'] : null;
unset($_SESSION['error'], $_SESSION['success']); // Clear messages after displaying them
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Tailwind custom font setup if not configured */
        body {
            font-family: 'Poppins', sans-serif;
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <!-- Full Page Layout -->
    <div class="flex flex-col md:flex-row h-screen">

        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-grow p-6 overflow-auto md:ml-64"> <!-- Added md:ml-64 for larger screens -->
            <!-- Display success or error messages -->
            <?php if ($error_message): ?>
            <div class="mb-4 p-4 bg-red-100 text-red-700 rounded">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
            <div class="mb-4 p-4 bg-green-100 text-green-700 rounded">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <!-- Dashboard Header -->
            <h2 class="text-4xl font-bold text-center mb-8">Admin Dashboard</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <a href="show_courses.php" class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec]">
                    <img src="./assets/book.png" alt="Courses" class="h-12 mb-4">
                    <h5 class="text-lg font-semibold">Total Courses</h5>
                    <p class="text-2xl font-bold"><?php echo $total_courses; ?></p>
                </a>
                <div class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec]">
                    <img src="./assets/add-user.png" alt="Students" class="h-12 mb-4">
                    <h5 class="text-lg font-semibold">Enrolled Students</h5>
                    <p class="text-2xl font-bold"><?php echo $total_students; ?></p>
                </div>
                <div class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec]">
                    <img src="./assets/group.png" alt="Users" class="h-12 mb-4">
                    <h5 class="text-lg font-semibold">Total Users</h5>
                    <p class="text-2xl font-bold"><?php echo $total_users; ?></p>
                </div>
                <a href="view_enrolled_students.php" class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec]">
                    <img src="./assets/certificate.png" alt="Enrollments" class="h-12 mb-4">
                    <h5 class="text-lg font-semibold">Total Enrollments</h5>
                    <p class="text-2xl font-bold"><?php echo $total_enrollments; ?></p>
                </a>
                <a href="show_teachers.php" class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec]">
                    <img src="./assets/students.png" alt="Teachers" class="h-12 mb-4">
                    <h5 class="text-lg font-semibold">Total Teachers</h5>
                    <p class="text-2xl font-bold"><?php echo $total_teachers; ?></p>
                </a>
            </div>
        </div>

    </div>
</body>

</html>
