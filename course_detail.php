<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
date_default_timezone_set('Asia/Karachi'); // Change to PKT

// Handle attendance marking when the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['course_id'])) {
    $course_id = $_POST['course_id'];

    // Fetch course timing and day from the database
    $course_query = "SELECT start_time, end_time, day, total_lectures FROM courses WHERE id = ?";
    $stmt = $conn->prepare($course_query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();

    if (!$course) {
        echo json_encode(['status' => 'error', 'message' => 'Course not found']);
        exit();
    }

    // Get the current day and time in PKT
    $current_time = new DateTime(); // Current date and time
    $current_day = $current_time->format('l'); // Current day of the week
    $current_time_24 = $current_time->format('H:i:s'); // Current time in 24-hour format

    // Check if today is the scheduled day for the course
    if ($current_day !== $course['day']) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Attendance cannot be marked today',
            'scheduled_day' => $course['day'],
            'current_day' => $current_day
        ]);
        exit();
    }

    // Convert course times to DateTime objects
    $start_time = DateTime::createFromFormat('H:i:s', $course['start_time']);
    $end_time = DateTime::createFromFormat('H:i:s', $course['end_time']);
    $current_time_obj = DateTime::createFromFormat('H:i:s', $current_time_24);

    // Check if current time is within the course time range
    if ($current_time_obj < $start_time || $current_time_obj > $end_time) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Time exceeded',
            'current_time' => $current_time_24,
            'start_time' => $course['start_time'],
            'end_time' => $course['end_time']
        ]);
        exit();
    }

    // Check if attendance is already marked for the student today
    $check_query = "SELECT * FROM attendance_records WHERE user_id = ? AND course_id = ? AND attendance_date = ? AND status = 'Present'";
    $check_stmt = $conn->prepare($check_query);
    $attendance_date = $current_time->format('Y-m-d'); // Get today's date
    $check_stmt->bind_param("iis", $student_id, $course_id, $attendance_date);
    $check_stmt->execute();
    $attendance = $check_stmt->get_result()->fetch_assoc();

    if ($attendance) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Attendance already marked for today'
        ]);
        exit();
    }

    // Insert attendance record
    $insert_query = "INSERT INTO attendance_records (user_id, course_id, attendance_date, check_in_time, status) 
                     VALUES (?, ?, ?, ?, 'Present')";
    $insert_stmt = $conn->prepare($insert_query);
    $attendance_date = $current_time->format('Y-m-d'); // Current date in 'Y-m-d' format
    $check_in_time = $current_time->format('H:i:s'); // Current time in 'H:i:s' format
    $insert_stmt->bind_param("iiss", $student_id, $course_id, $attendance_date, $check_in_time);

    if ($insert_stmt->execute()) {
        // Increment attended_classes in the enrollments table
        $increment_classes_query = "UPDATE enrollments SET attended_classes = attended_classes + 1 
                                    WHERE user_id = ? AND course_id = ?";
        $increment_classes_stmt = $conn->prepare($increment_classes_query);
        $increment_classes_stmt->bind_param("ii", $student_id, $course_id);
        $increment_classes_stmt->execute();

        // Fetch total lectures for the course
        $total_lectures = $course['total_lectures'];

        // Fetch the updated attended_classes for this student and course
        $attended_classes_query = "SELECT attended_classes FROM enrollments WHERE user_id = ? AND course_id = ?";
        $attended_classes_stmt = $conn->prepare($attended_classes_query);
        $attended_classes_stmt->bind_param("ii", $student_id, $course_id);
        $attended_classes_stmt->execute();
        $attended_classes_result = $attended_classes_stmt->get_result()->fetch_assoc();
        $attended_classes = $attended_classes_result['attended_classes'];

        // Calculate the attendance percentage dynamically
        $attendance_percentage = ($attended_classes / $total_lectures) * 100;

        // Update the attendance percentage in the enrollments table
        $update_percentage_query = "UPDATE enrollments SET attendance_percentage = ? 
                                    WHERE user_id = ? AND course_id = ?";
        $update_percentage_stmt = $conn->prepare($update_percentage_query);
        $update_percentage_stmt->bind_param("dii", $attendance_percentage, $student_id, $course_id);
        $update_percentage_stmt->execute();

        echo json_encode([
            'status' => 'success',
            'message' => 'Attendance marked successfully',
            'attendance_percentage' => $attendance_percentage
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Error marking attendance'
        ]);
    }

    exit();
}


// Fetch the courses and their attendance percentage directly from the enrollments table
$query = "
    SELECT DISTINCT 
        courses.id AS course_id,
        courses.course_name,
        courses.start_date,
        courses.end_date,
        courses.start_time,
        courses.end_time,
        courses.day,
        courses.total_lectures,
        enrollments.attendance_percentage,
        enrollments.attended_classes,
        enrollments.created_at AS enrollment_date
    FROM enrollments
    INNER JOIN courses ON enrollments.course_id = courses.id
    WHERE enrollments.user_id = $student_id
    ORDER BY courses.end_time DESC
";


$courses = $conn->query($query);

if (!$courses) {
    die("Error: " . $conn->error); // Additional error handling
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Enrolled Courses</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="shortcut icon" href="../assets/Devas2.png" type="favicon" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
    <!-- Add Toastify CSS here -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.6.1/toastify.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.6.1/toastify.min.css" rel="stylesheet">

</head>

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
<body>
<?php include 'sidebar.php'; ?>
<div class="container mt-5">
    <h3 class="text-center fs-1 fw-bold mb-3">Enrolled Courses</h3>
    <?php if ($courses && $courses->num_rows > 0): ?>
        <div class="row">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="col-lg-4 col-sm-12 col-md-6 col-xs-12 mb-1 me-3 ms-3">
                    <div class="course-card">
                        <h5 class="text-center fs-4 fw-bold"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                        <div class="course-details">
                            <p class="fs-5"><strong>Timing:</strong>
                                <?php echo htmlspecialchars($course['start_time']) . ' - ' . htmlspecialchars($course['end_time']); ?>
                            </p>
                            <p class="fs-5"><strong>Day:</strong>
                                <?php echo htmlspecialchars($course['day']); ?></p>
                            <p class="fs-5"><strong>Total Lectures:</strong>
                                <?php echo htmlspecialchars($course['total_lectures']); ?></p>
                            <?php
                            if (!function_exists('getAttendanceColor')) {
                                function getAttendanceColor($percentage)
                                {
                                    if ($percentage >= 80) {
                                        return 'green';
                                    } elseif ($percentage >= 75 && $percentage <= 80) {
                                        return 'yellow';
                                    } else {
                                        return 'red';
                                    }
                                }
                            }
                            ?>
                            <p class="fs-5">
                                <strong>Attendance Percentage:</strong>
                                <span style="color: <?php echo getAttendanceColor($course['attendance_percentage']); ?>;">
                                    <?php echo htmlspecialchars($course['attendance_percentage']); ?>%
                                </span>
                            </p>
                            <p class="fs-5"><strong>Attended Classes:</strong>
                                <?php echo htmlspecialchars($course['attended_classes']); ?>
                            </p>
                            <button class="btn btn-info" onclick="markAttendance(<?php echo $course['course_id']; ?>)">
                                Mark Attendance
                            </button>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <p>No courses enrolled.</p>
    <?php endif; ?>
</div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastify-js/1.6.1/toastify.min.js"></script>
    <script>
function markAttendance(courseId) {
    // Access webcam and capture the image
    navigator.mediaDevices.getUserMedia({ video: true }).then((stream) => {
        const video = document.createElement('video');
        video.srcObject = stream;
        video.play();

        // Wait for the video to load metadata before capturing the frame
        video.onloadedmetadata = () => {
            const canvas = document.createElement('canvas');
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            const context = canvas.getContext('2d');
            context.drawImage(video, 0, 0, canvas.width, canvas.height);

            // Stop the video stream
            stream.getTracks().forEach((track) => track.stop());

            // Convert the captured frame to base64
            const capturedImage = canvas.toDataURL('image/jpeg');

            // Get the stored facial data and attendance details for the student
            fetch('get_facial_data.php?course_id=' + courseId)
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'success') {
                        Toastify({
                            text: data.message || 'Unable to fetch course data',
                            duration: 3000,
                            backgroundColor: "#dc3545",
                            close: true,
                            gravity: "top",
                            position: "right"
                        }).showToast();
                        return;
                    }

                    // Check for attendance lock conditions
                    const { attended_classes, total_lectures, facialData, start_date, end_date } = data;

                    const currentDate = new Date();
                    const startDate = new Date(start_date);
                    const endDate = new Date(end_date);

                    if (attended_classes >= total_lectures) {
                        Toastify({
                            text: 'Attendance locked: All lectures attended.',
                            duration: 3000,
                            backgroundColor: "#dc3545",
                            close: true,
                            gravity: "top",
                            position: "right"
                        }).showToast();
                        return;
                    }

                    if (currentDate >= startDate && currentDate <= endDate) {
                        Toastify({
                            text: 'Attendance locked: The course is currently active.',
                            duration: 3000,
                            backgroundColor: "#dc3545",
                            close: true,
                            gravity: "top",
                            position: "right"
                        }).showToast();
                        return;
                    }

                    // Proceed with face verification
                    fetch('http://127.0.0.1:5000/verify_student', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            studentId: "<?php echo $student_id; ?>",
                            capturedImage: capturedImage,
                            facialData: facialData
                        })
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.verified) {
                            // Call the PHP attendance marking logic
                            fetch('course_detail.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: 'course_id=' + courseId
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Server error');
                                }
                                return response.json();
                            })
                            .then(attendanceResult => {
                                Toastify({
                                    text: attendanceResult.message,
                                    duration: 3000,
                                    backgroundColor: "#28a745",
                                    close: true,
                                    gravity: "top",
                                    position: "right"
                                }).showToast();
                            })
                            .catch(error => {
                                Toastify({
                                    text: 'Error marking attendance: ' + error.message,
                                    duration: 3000,
                                    backgroundColor: "#dc3545",
                                    close: true,
                                    gravity: "top",
                                    position: "right"
                                }).showToast();
                            });
                        } else {
                            Toastify({
                                text: 'Face verification failed!',
                                duration: 3000,
                                backgroundColor: "#dc3545",
                                close: true,
                                gravity: "top",
                                position: "right"
                            }).showToast();
                        }
                    })
                    .catch(error => {
                        Toastify({
                            text: 'Error with face verification: ' + error.message,
                            duration: 3000,
                            backgroundColor: "#dc3545",
                            close: true,
                            gravity: "top",
                            position: "right"
                        }).showToast();
                    });
                })
                .catch(error => {
                    console.error('Error fetching facial data:', error);
                    Toastify({
                        text: 'Unable to fetch facial data',
                        duration: 3000,
                        backgroundColor: "#ffc107",
                        close: true,
                        gravity: "top",
                        position: "right"
                    }).showToast();
                });
        };
    }).catch(error => {
        console.error('Webcam access denied:', error);
        Toastify({
            text: 'Unable to access the webcam',
            duration: 3000,
            backgroundColor: "#ffc107",
            close: true,
            gravity: "top",
            position: "right"
        }).showToast();
    });
}



    </script>


</body>

</html>