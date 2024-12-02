<?php
session_start();
require 'db.php';

// Session timeout settings
$timeout_duration = 1800; // 30 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
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

// Fetch enrolled students with course details including start_time and end_time
$query = "
    SELECT users.username AS student_name, courses.course_name, 
           courses.start_time, courses.end_time, courses.day, enrollments.created_at
    FROM enrollments 
    JOIN users ON enrollments.user_id = users.id 
    JOIN courses ON enrollments.course_id = courses.id
    ORDER BY enrollments.created_at ASC
";

$result = $conn->query($query);

// Check for query errors
if (!$result) {
    die("Error in query: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/manager.png" type="image/x-icon" />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-100 font-sans">

<!-- Include Sidebar (You can include your existing sidebar.php here) -->
<?php include 'sidebar.php'; ?>

<div class="lg:ml-64 p-6 transition-all duration-300"> <!-- Use lg:ml-64 for large screens -->
    <h2 class="text-center text-4xl font-bold mb-8">Enrolled Students</h2>

    <!-- Search Bar Section -->
    <div class="mb-2 flex justify-between items-center">
        <div class="relative w-1/3">
            <input 
                type="text" 
                id="search-bar" 
                class="w-full px-4 py-2 border border-teal-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 transition duration-300 ease-in-out"
                placeholder="Search Student..."
                oninput="filterTable()"
            />
            <span class="absolute top-0 right-0 mt-3 mr-4 text-teal-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 18a7 7 0 100-14 7 7 0 000 14zM21 21l-4.35-4.35" />
                </svg>
            </span>
        </div>
        <div class="flex justify-end mb-6">
        <a href="enroll_student.php" class="bg-[#e0fbfc] hover:bg-white text-black font-medium py-2 px-4 rounded-lg flex items-center space-x-2">
            <span>Enroll Student</span>
            <img src="./assets/ebook.png" alt="Enroll" class="w-6 h-6">
        </a>
    </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" id="students-list">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($enrollment = $result->fetch_assoc()): ?>
                <?php
                    // Calculate course duration in minutes
                    $start_time = new DateTime($enrollment['start_time']);
                    $end_time = new DateTime($enrollment['end_time']);
                    $duration = $start_time->diff($end_time);
                    $duration_minutes = ($duration->h * 60) + $duration->i;
                ?>
                <div class="bg-teal-100 text-black p-6 rounded-lg shadow-lg hover:shadow-xl transform hover:translate-y-1 transition duration-300 student-item">
                    <h4 class="text-2xl font-semibold"><?php echo htmlspecialchars($enrollment['student_name']); ?></h4>
                    <p><strong>Course Name:</strong> <?php echo htmlspecialchars($enrollment['course_name']); ?></p>
                    <p><strong>Attendance Timing:</strong> <span><?php echo htmlspecialchars($duration_minutes . " minutes"); ?></span></p>
                    <p><strong>Course Day:</strong> <?php echo htmlspecialchars($enrollment['day']); ?></p>
                    <p><strong>Enrollment Date:</strong> <?php echo htmlspecialchars($enrollment['created_at']); ?></p>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-span-full text-center text-gray-600">
                <p>No enrolled students found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Function to filter table based on search input
function filterTable() {
    const searchTerm = document.getElementById('search-bar').value.toLowerCase();
    const students = document.getElementsByClassName('student-item');

    for (let i = 0; i < students.length; i++) {
        const studentName = students[i].getElementsByTagName('h4')[0].textContent.toLowerCase();
        if (studentName.includes(searchTerm)) {
            students[i].style.display = '';
        } else {
            students[i].style.display = 'none';
        }
    }
}
</script>

</body>
</html>

<?php
$conn->close();
?>
