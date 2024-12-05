session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
exit();
}

$student_id = $_SESSION['student_id'];
$course_id = $_POST['course_id'];

// Fetch course timing from the database
$course_query = "SELECT start_time, end_time FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
echo json_encode(['status' => 'error', 'message' => 'Course not found']);
exit();
}

// Convert current time (in 12-hour format) to 24-hour format
$current_time = new DateTime();
$current_time_24 = $current_time->format('H:i'); // 24-hour format

// Convert course times (in 24-hour format) to DateTime objects
$start_time = DateTime::createFromFormat('H:i:s', $course['start_time']); // Assuming 'start_time' is 'HH:mm:ss'
$end_time = DateTime::createFromFormat('H:i:s', $course['end_time']); // Assuming 'end_time' is 'HH:mm:ss'

// Log times for debugging
error_log("Current Time (24hr): " . $current_time_24);
error_log("Course Start Time: " . $course['start_time']);
error_log("Course End Time: " . $course['end_time']);

// Convert current time to DateTime object for comparison
$current_time_obj = DateTime::createFromFormat('H:i', $current_time_24); // Compare using 'H:i'

// Compare current time with course timing
if ($current_time_obj < $start_time || $current_time_obj> $end_time) {
    echo json_encode([
    'status' => 'error',
    'message' => 'Time exceeded',
    'current_time' => $current_time_24,
    'start_time' => $course['start_time'],
    'end_time' => $course['end_time']
    ]);
    exit();
    }

    // Check if attendance is already marked
    $check_query = "SELECT * FROM attendance_records WHERE user_id = ? AND course_id = ? AND status = 'Present'";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    $attendance = $check_stmt->get_result()->fetch_assoc();

    if ($attendance) {
    echo json_encode(['status' => 'error', 'message' => 'Attendance already marked']);
    exit();
    }

    // Mark attendance
    $insert_query = "INSERT INTO attendance_records (user_id, course_id, status) VALUES (?, ?, 'Present')";
    $insert_stmt = $conn->prepare($insert_query);
    $insert_stmt->bind_param("ii", $student_id, $course_id);

    if ($insert_stmt->execute()) {
    echo json_encode(['status' => 'success', 'message' => 'Attendance marked successfully']);
    } else {
    echo json_encode(['status' => 'error', 'message' => 'Error marking attendance']);
    }
    <style>
    body {
        background: #f8f9fa;
        font-family: "Baloo Bhaijaan 2",
            sans-serif;
        font-optical-sizing: auto;
    }

    .container {
        margin-left: 260px;
    }

    .row {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-start;
    }

    .course-card {
        background-color: #e0fbfc;
        color: black !important;
        border: 1px solid #e0fbfc;
        padding: 15px;

        border-radius: 8px;
        margin-bottom: 15px;
        transition: transform 0.3s;
    }

    .course-card:hover {
        transform: scale(1.05);

    }

    .course-details {
        font-size: 14px;
    }

    /* Sidebar Styles */
    .sidebar {
        min-width: 230px;
        background-color: #000000;
        color: #e0fbfc;
        position: fixed;
        top: 0;
        left: 0;
        height: 100%;
        padding: 20px;
    }

    .sidebar img {
        width: 100%;
        max-width: 200px;
        margin-bottom: 20px;
    }

    .sidebar ul {
        padding-left: 0;
        list-style: none;
    }

    .sidebar ul li {
        margin-bottom: 10px;
    }

    .sidebar ul li a {
        color: #e0fbfc;
        text-decoration: none;
        font-size: 18px;
    }

    .sidebar ul li a:hover {
        color: black;
    }


    .btn-info {
        background-color: black !important;
        color: #e0fbfc;
        border: none;
        padding: 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: transform 0.3s;
    }

    .btn-info:hover {
        color: #e0fbfc;
        font-size: large;
        transform: translateY(-5px);


    }

    .btn-info[disabled] {
        background-color: #28a745 !important;
        /* Green color when disabled */
        color: white !important;
        cursor: not-allowed;
    }

    @media (max-width: 768px) {
        .sidebar {
            display: none;
        }

        .navbar-toggler {
            display: block;
        }

        .navbar-mobile.active {
            display: block;
        }

        .container {
            margin-left: 0;
        }

        .no-scroll {
            overflow: hidden;
        }


    }
</style>