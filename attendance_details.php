<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Get the course ID from the query parameter
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : null;

if (!$course_id) {
    echo "No course selected.";
    exit();
}

// Fetch student profile picture (facial data) and attended classes from the enrollments table
$profile_query = "SELECT facial_data FROM enrollments WHERE user_id = ? AND course_id = ?";
$stmt = $conn->prepare($profile_query);

if ($stmt === false) {
    die("Error preparing profile query: " . $conn->error);
}

$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$profile_result = $stmt->get_result()->fetch_assoc();
$profile_picture = $profile_result ? $profile_result['facial_data'] : 'default.jpg'; // Fallback to 'default.jpg' if no image found

// Fetch course details
$course_query = "SELECT * FROM courses WHERE id = ?";
$stmt = $conn->prepare($course_query);

if ($stmt === false) {
    die("Error preparing query: " . $conn->error);
}

$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    echo "Course not found.";
    exit();
}

// Fetch total lectures conducted for this course
$total_lectures_query = "
    SELECT COUNT(*) AS total_conducted 
    FROM attendance_records 
    WHERE course_id = ? AND user_id = ? AND status = 'Present'";
$total_lectures_stmt = $conn->prepare($total_lectures_query);

if ($total_lectures_stmt === false) {
    die("Error preparing total lectures query: " . $conn->error);
}

// Bind both the course ID and user ID
$total_lectures_stmt->bind_param("ii", $course_id, $student_id);
$total_lectures_stmt->execute();
$total_lectures_result = $total_lectures_stmt->get_result()->fetch_assoc();
$total_lectures_conducted = $total_lectures_result['total_conducted'] ?? 0; // Default to 0 if no lectures conducted

// Fetch attendance records for this course and the logged-in student
$attendance_query = "
    SELECT ar.attendance_date, ar.check_in_time, u.username, ar.status
    FROM attendance_records ar
    JOIN users u ON ar.user_id = u.id
    WHERE ar.course_id = ? AND ar.user_id = ?
    ORDER BY ar.attendance_date DESC";
$attendance_stmt = $conn->prepare($attendance_query);

if ($attendance_stmt === false) {
    die("Error preparing attendance query: " . $conn->error);
}

// Bind both the course ID and student ID
$attendance_stmt->bind_param("ii", $course_id, $student_id);
$attendance_stmt->execute();
$attendance_results = $attendance_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
</head>

<style>
body {
    font-family: "Baloo Bhaijaan 2", sans-serif;
    background-color: #f4f6f9;
}

.container {
    margin-top: 50px;
    margin-left: 280px;
}

.sidebar {
    width: 250px;
    padding: 20px;
    background-color: #333;
    color: white;
    height: 100vh;
}




.info-box {
    background-color: #e0fbfc;
    color: #333;
    padding: 15px;
    margin: 10px 0;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
    width: 90%;
}

.attendance-table {
    width: 80%;
    margin: 20px 0;
}

.attendance-table th,
.attendance-table td {
    text-align: center;
}


.profile-picture {
    position: absolute;
    top: 10px;
    /* Adjust positioning for better spacing */
    right: 20px;
    /* Adjust the positioning for better alignment */
    width: 100px;
    /* Slightly increase the size */
    height: 100px;
    /* Ensure the aspect ratio is consistent */
    border-radius: 50%;
    /* Perfect circle */
    object-fit: cover;
    /* Ensure the image fits well inside the circle */
    border: 3px solid #fff;
    /* Lighter border for contrast */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
    /* Add depth with shadow */
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    /* Smooth transition for hover effect */
}

.profile-picture:hover {
    transform: scale(1.1);
    /* Slight zoom effect on hover */
    box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
    /* Increase shadow on hover */
}
</style>

<body>
    <?php include 'sidebar.php'; ?>

    <!-- Display Profile Picture at Top-Right -->
    <img src="../uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture"
        class="profile-picture">

    <div class="container my-5">
        <h2 class="fs-1 text-center fw-bold mb-3">Course Information</h2>
        <div class="row me-5">
            <div class="col-md-4 col-sm-4">
                <div class="info-box">
                    <strong>Course Name:</strong> <?php echo htmlspecialchars($course['course_name']); ?>
                </div>
            </div>
            <div class="col-md-4 ">
                <div class="info-box">
                    <strong>Start Date:</strong> <?php echo htmlspecialchars($course['start_date']); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <strong>End Date:</strong> <?php echo htmlspecialchars($course['end_date']); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <strong>Start Time:</strong> <?php echo htmlspecialchars($course['start_time']); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <strong>End Time:</strong> <?php echo htmlspecialchars($course['end_time']); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <strong>Day:</strong> <?php echo htmlspecialchars($course['day']); ?>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box">
                    <strong>Total Lectures: </strong> <span class=" fw-bold text-primary"><?php echo htmlspecialchars($course['total_lectures']); ?></span>
                </div>
            </div>
            <div class="col-md-4">
    <div class="info-box">
        <strong>Total Lectures Conducted: </strong> 
        <span class="fw-bold text-primary">
            <?php echo htmlspecialchars($total_lectures_conducted); ?>
        </span>
    </div>
</div>

        </div>

        <h3 class="fs-1 text-center fw-bold mb-3 mt-3">Attendance Records</h3>
        <table class="table table-bordered attendance-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Attendance Date</th>
                    <th>Check-in Time</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($attendance = $attendance_results->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($attendance['username']); ?></td>
                    <td><?php echo htmlspecialchars($attendance['attendance_date']); ?></td>
                    <td><?php echo htmlspecialchars($attendance['check_in_time']); ?></td>
                    <td>
                        <?php 
                            if ($attendance['status'] === 'Present') {
                                echo '<span class="text-success">Present</span>';
                            } else {
                                echo '<span class="text-danger">Absent</span>';
                            }
                        ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>