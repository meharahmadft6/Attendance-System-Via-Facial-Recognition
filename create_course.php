<?php
session_start();
require 'db.php'; 

// Set session timeout period (e.g., 30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $course_name = $_POST['course_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $day = $_POST['day']; // Day field

    // Calculate total lectures based on the start_date, end_date, and day
    $total_lectures = calculateLectures($start_date, $end_date, $day);

    // Check if the course name already exists
    $stmt = $conn->prepare("SELECT * FROM courses WHERE course_name = ?");
    $stmt->bind_param("s", $course_name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Course already exists
        $_SESSION['error'] = 'Error: Course name already exists!';
        header("Location: create_course.php");
        exit();
    } else {
        // Insert the course into the database without the instructor field
        $stmt = $conn->prepare("INSERT INTO courses (course_name, start_date, end_date, start_time, end_time, day, total_lectures) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $course_name, $start_date, $end_date, $start_time, $end_time, $day, $total_lectures);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = 'Course created successfully!';
            header("Location: create_course.php");
            exit();
        } else {
            $_SESSION['error'] = 'Error creating course: ' . $conn->error;
            header("Location: create_course.php");
            exit();
        }
    }

    $stmt->close();
}

// Function to calculate the total number of lectures
function calculateLectures($start_date, $end_date, $day) {
    // Convert dates to DateTime objects
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    // If the start date is after the end date, return 0 lectures
    if ($start > $end) {
        return 0;
    }

    // Day mapping (1=Monday, 2=Tuesday, ..., 7=Sunday)
    $day_map = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];

    // Get the numerical representation of the day
    $target_day = $day_map[$day];

    // Get the numerical representation of the start date's day
    $start_day = (int)$start->format('N');

    // Calculate the difference between the target day and the start day
    $diff = $target_day - $start_day;
    
    // If the target day is before the start date's day, adjust by adding 7 days
    if ($diff < 0) {
        $diff += 7;
    }

    // Set the start date to the first occurrence of the target day
    $start->modify("+$diff day");

    // If the first occurrence of the target day is after the end date, return 0 lectures
    if ($start > $end) {
        return 0;
    }

    // Calculate the total number of weeks between the start and end dates
    $interval = $start->diff($end);
    $total_weeks = floor($interval->days / 7);

    // Add 1 for the first occurrence of the target day
    $total_lectures = $total_weeks + 1;

    return $total_lectures;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background: #f8f9fa;
            font-family: "Baloo Bhaijaan 2", sans-serif;
        }
    </style>
</head>

<body class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow p-6 overflow-y-auto mt-20">
        <div class="bg-white shadow-lg rounded-lg p-8 max-w-3xl mx-auto">
            <h2 class="text-3xl font-semibold text-center mb-6">Create Course</h2>

           

            <form id="courseForm" method="POST" action="create_course.php">
                <div class="space-y-6">
                    <!-- Course Name and Start Date (Two Fields in One Row) -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="course_name" class="text-lg font-medium text-gray-700">Course Name</label>
                            <input type="text" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="course_name" name="course_name" required />
                        </div>

                        <div class="flex-1">
                            <label for="start_date" class="text-lg font-medium text-gray-700">Start Date</label>
                            <input type="date" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="start_date" name="start_date" required />
                        </div>
                    </div>

                    <!-- End Date and Start Time (Two Fields in One Row) -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="end_date" class="text-lg font-medium text-gray-700">End Date</label>
                            <input type="date" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="end_date" name="end_date" required />
                        </div>

                        <div class="flex-1">
                            <label for="start_time" class="text-lg font-medium text-gray-700">Start Time</label>
                            <input type="time" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="start_time" name="start_time" required />
                        </div>
                    </div>

                    <!-- End Time and Day (Two Fields in One Row) -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="end_time" class="text-lg font-medium text-gray-700">End Time</label>
                            <input type="time" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="end_time" name="end_time" required />
                        </div>

                        <div class="flex-1">
                            <label for="day" class="text-lg font-medium text-gray-700">Select Day</label>
                            <select class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="day" name="day" required>
                                <option value="">Choose...</option>
                                <option value="Monday">Monday</option>
                                <option value="Tuesday">Tuesday</option>
                                <option value="Wednesday">Wednesday</option>
                                <option value="Thursday">Thursday</option>
                                <option value="Friday">Friday</option>
                                <option value="Saturday">Saturday</option>
                                <option value="Sunday">Sunday</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-gray-800 text-white py-4 px-6 rounded-lg shadow-md hover:bg-black focus:outline-none focus:ring-2 focus:ring-yellow-500 transition duration-200">
                        Create Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
<!-- SweetAlert2 Toast -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?php echo $_SESSION['success']; ?>',
                showConfirmButton: false,
                timer: 6000
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '<?php echo $_SESSION['error']; ?>',
                showConfirmButton: false,
                timer: 6000
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</html>
