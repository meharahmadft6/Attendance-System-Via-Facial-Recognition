<?php
session_start();
require 'db.php';

// Set session timeout period (e.g., 30 minutes)
$timeout_duration = 3600; // 30 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();

    header("Location: ../login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if the user is logged in as teacher
if (!isset($_SESSION['teacher_id'])) {
    header("Location: ../login.php");
    exit();
}

$teacher_id = $_SESSION['teacher_id'];

// Query to fetch the courses assigned to the teacher
$courses_query = "
    SELECT c.id, c.course_name 
    FROM courses c
    JOIN assigned_courses ac ON c.id = ac.course_id
    WHERE ac.teacher_id = ?
";

// Query to fetch the number of students assigned to the teacher's courses
// Correct the SQL query for students count
// Query to get the count of students for the assigned courses
$students_query = "
    SELECT COUNT(DISTINCT enrollments.user_id) AS count
    FROM enrollments
    JOIN courses ON enrollments.course_id = courses.id
    JOIN assigned_courses ON courses.id = assigned_courses.course_id
    WHERE enrollments.teacher_id = ?  -- Make sure to match the teacher_id in enrollments table
";


// Execute the query to count students
$stmt = $conn->prepare($students_query);
if (!$stmt) {
    die("Error preparing query for students: " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);  // Bind the teacher_id
$stmt->execute();
$students_result = $stmt->get_result()->fetch_assoc()['count'];  // Fetch the student count


// Execute query for courses assigned to the teacher
$stmt = $conn->prepare($courses_query);
if (!$stmt) {
    die("Error preparing query for courses: " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses_result = $stmt->get_result();
$courses_count = $courses_result->num_rows; // Get the number of courses assigned

// Query for notifications
$notifications_query = "
    SELECT message, created_at 
    FROM notifications 
    WHERE user_type = 'teacher' 
    AND user_id = ? 
    AND start_date <= NOW()  -- Ensure notifications have started
    AND end_date >= NOW()    -- Ensure notifications have not ended
    ORDER BY created_at DESC
";

// Execute query for notifications
$stmt = $conn->prepare($notifications_query);
if (!$stmt) {
    die("Error preparing query for notifications: " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$notifications_result = $stmt->get_result();
$notifications = $notifications_result->fetch_all(MYSQLI_ASSOC);

// Fetch the teacher's facial_data (image path)
$image_query = "SELECT facial_data FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($image_query);
if (!$stmt) {
    die("Error preparing query for teacher image: " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$image_result = $stmt->get_result();
$teacher_image = $image_result->fetch_assoc()['facial_data'];

// Set a default image if no image path is found
if (!$teacher_image) {
    $teacher_image = "teacher.png"; // Ensure a default.jpg exists in teachersPics folder
}

// Assuming teacher's name is stored in the database under 'name' column
$teacher_query = "SELECT name FROM teachers WHERE teacher_id = ?";
$stmt = $conn->prepare($teacher_query);
if (!$stmt) {
    die("Error preparing query for teacher name: " . $conn->error);
}
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$teacher_name_result = $stmt->get_result()->fetch_assoc();
$teacher_name = $teacher_name_result['name'] ?? 'Teacher'; // Default to 'Teacher' if no name is found
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Teacher Dashboard</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        function toggleModal() {
            const modal = document.getElementById('notification-modal');
            modal.classList.toggle('hidden');
        }

        // Automatically open the modal on page load
        window.onload = function() {
            const modal = document.getElementById('notification-modal');
            if (modal) modal.classList.remove('hidden');
        };
    </script>
    <style>
        body {
            font-family: 'Baloo Bhai 2', 'Poppins', sans-serif;
        }

        /* Responsive styling for the profile image */
        .profile-img {
            width: 150px; /* Default size */
            height: 150px;
        }

        @media (max-width: 768px) {
            .profile-img {
                width: 70px; /* Smaller size for mobile */
                height: 70px;
                margin-left:50px;
            }
            .text-4xl{
                font-size:2rem !important;
                margin-left:20px;
            }
        }
    </style>
</head>

<body class="bg-gray-100 font-sans">
    <div class="flex flex-col md:flex-row h-screen">
        <!-- Sidebar -->
        <?php include 'sidebar.php'; ?>

        <!-- Main Content -->
        <div class="flex-grow p-6 md:ml-64">
            <!-- Top Section -->
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-4xl font-bold text-center w-full" style="color: #15c3c6;">Welcome, <?php echo htmlspecialchars($teacher_name); ?></h2>
                <div class="relative">
                    <!-- Profile Image -->
                    <img src="../teachersPics/<?php echo htmlspecialchars($teacher_image); ?>" 
                         alt="Teacher" 
                         class="profile-img rounded-full border-2 border-gray-300 shadow-lg object-cover mr-10 mt-5">
                </div>
            </div>

            <!-- Dashboard Content -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec] text-center">
                    <img src="../assets/book.png" alt="Courses" class="h-12 mb-4 mx-auto">
                    <h5 class="text-lg font-semibold">Assigned Courses</h5>
                    <p class="text-2xl font-bold"><?php echo $courses_count; ?></p>
                </div>
                <div class="bg-[#e0fbfc] text-gray-800 shadow-lg rounded-lg p-6 hover:shadow-xl transform hover:-translate-y-1 transition hover:bg-[#7ae8ec] text-center">
                    <img src="../assets/add-user.png" alt="Students" class="h-12 mb-4 mx-auto">
                    <h5 class="text-lg font-semibold">Assigned Students</h5>
                    <p class="text-2xl font-bold"><?php echo $students_result; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifications Modal -->
    <div id="notification-modal" class="fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center hidden opacity-0 transition-opacity duration-300">
        <div id="notification-content" class="bg-white rounded-lg p-6 w-11/12 md:w-1/2 shadow-lg transform scale-95 transition-transform duration-300">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold text-[#333]">Notifications</h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-red-500">
                    &times;
                </button>
            </div>
            <ul class="space-y-4">
                <?php if (count($notifications) > 0): ?>
                    <?php foreach ($notifications as $notification): ?>
                        <li class="bg-[#f9f9f9] p-4 rounded shadow-sm hover:shadow-md">
                            <p class="text-gray-800"><?php echo htmlspecialchars($notification['message']); ?></p>
                            <span class="text-sm text-gray-500 block mt-2">
                                <?php echo date('F j, Y, g:i a', strtotime($notification['created_at'])); ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="text-gray-500 text-center">No notifications available.</li>
                <?php endif; ?>
            </ul>
            <div class="text-center mt-6">
                <button onclick="closeModal()" class="px-6 py-2 bg-[#7ae8ec] text-gray-800 font-semibold rounded hover:bg-[#66d3d5]">Close</button>
            </div>
        </div>
    </div>

</body>
<script>
    let modalTimeout;

    function toggleModal() {
        const modal = document.getElementById('notification-modal');
        const content = document.getElementById('notification-content');

        if (modal.classList.contains('hidden')) {
            // Show modal
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.classList.remove('opacity-0');
                content.classList.remove('scale-95');
            }, 10);

            // Automatically close modal after 10 seconds
            modalTimeout = setTimeout(() => {
                closeModal();
            }, 10000);
        } else {
            // Close modal
            closeModal();
        }
    }

    function closeModal() {
        const modal = document.getElementById('notification-modal');
        const content = document.getElementById('notification-content');

        // Smooth hide animations
        modal.classList.add('opacity-0');
        content.classList.add('scale-95');

        // Hide completely after animation ends
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);

        // Clear auto-close timeout
        clearTimeout(modalTimeout);
    }

    // Close modal when clicking outside
    document.addEventListener('click', (event) => {
        const modal = document.getElementById('notification-modal');
        const content = document.getElementById('notification-content');

        if (modal && !content.contains(event.target) && !event.target.closest('#notification-icon')) {
            closeModal();
        }
    });

    // Automatically open the modal on page load
    window.onload = function () {
        toggleModal();
    };
</script>

</html>
