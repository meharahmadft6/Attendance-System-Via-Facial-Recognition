<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch the courses the student is enrolled in
$query = "SELECT courses.id, courses.course_name, courses.start_date, courses.end_date, courses.day, courses.start_time,courses.end_time 
          FROM courses
          INNER JOIN enrollments ON courses.id = enrollments.course_id 
          WHERE enrollments.user_id = $student_id";
$courses = $conn->query($query);

if (!$courses) {
    die("Error fetching courses: " . $conn->error);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Records</title>
    <link rel="shortcut icon" href="../assets/Devas2.png" type="favicon" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
    <style>
    /* General Page Styles */
    body {
        font-family: "Baloo Bhaijaan 2", sans-serif;
        background-color: #f4f6f9;
    }

    .container {
        margin-top: 50px;
    }

    /* Sidebar and Main Content Layout */
    .row {
        display: flex;
        flex-wrap: wrap;
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


    .content {
        flex: 1;
        padding: 20px;
        margin-left: 280px;
    }

    /* Course Box Styling */
    .card {
        background-color: #e0fbfc;
        color: black !important;
        border: 1px solid #e0fbfc;
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
    }

    .card:hover {
        transform: translateY(-10px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }


    .view-attendance {
        background-color: black;
        color: #e0fbfc;
        border: none;
        padding: 10px 20px;
        font-size: 16px;
        border-radius: 5px;
        transition: background-color 0.3s ease;
    }

    .view-attendance:hover {
        background-color: black;
    }
    </style>
     <script src="https://cdn.tailwindcss.com"></script>
</head>

<body>
    <?php include 'sidebar.php'; ?>
    <div class="content">
        <h2 class="fs-1 fw-bold text-center mt-3 mb-5">Enrolled Courses with Attendance Shedule</h2>
        <div class="row">
            <?php while ($course = $courses->fetch_assoc()) { ?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title"><?= $course['course_name'] ?></h5>
                        <button class="btn view-attendance">
                            <a href="attendance_details.php?course_id=<?= $course['id'] ?>"
                                class="text-white text-decoration-none">View Attendance</a>
                        </button>
                    </div>
 
                </div>
            </div>
            <?php } ?>
        </div>



</body>

</html>