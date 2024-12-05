<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location:login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch enrolled courses count for the student
$query_courses = "
    SELECT COUNT(*) as count 
    FROM enrollments 
    WHERE enrollments.user_id = $student_id
";
$result_courses = $conn->query($query_courses);

if (!$result_courses) {
    die("Query failed of courses: " . $conn->error);
}

$total_courses = $result_courses->fetch_assoc()['count'];

// Fetch total attendance count for the student
$query_attendance = "
    SELECT COUNT(*) as count 
    FROM attendance_records 
    WHERE attendance_records.user_id = $student_id
";
$result_attendance = $conn->query($query_attendance);

if (!$result_attendance) {
    die("Query failed of attendance: " . $conn->error);
}

$total_attendance = $result_attendance->fetch_assoc()['count'];

// Fetch student profile picture (facial data) from the enrollments table
$profile_query = "SELECT facial_data FROM enrollments WHERE user_id = ?";
$name_query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($profile_query);
$stmt2 = $conn->prepare($name_query);

if ($stmt === false) {
    die("Error preparing profile query: " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$profile_result = $stmt->get_result()->fetch_assoc();
$profile_picture = $profile_result ? $profile_result['facial_data'] : 'default.jpg'; // Fallback 

$stmt2->bind_param("i", $student_id);
$stmt2->execute();
$result = $stmt2->get_result()->fetch_assoc();
$name_result = $result ? $result['username'] : 'User'; // Fallback 

// Fetch notifications for the student
$query_notifications = "
    SELECT message, created_at 
    FROM notifications 
    WHERE user_id = ? 
     AND start_date <= NOW()  -- Ensure notifications have started
    AND end_date >= NOW()    -- Ensure notifications have not ended
    ORDER BY created_at DESC
";
$stmt_notifications = $conn->prepare($query_notifications);

if ($stmt_notifications === false) {
    die("Error preparing notifications query: " . $conn->error);
}

$stmt_notifications->bind_param("i", $student_id);
$stmt_notifications->execute();
$result_notifications = $stmt_notifications->get_result();

$notifications = [];
while ($row = $result_notifications->fetch_assoc()) {
    $notifications[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="shortcut icon" href="../assets/Devas2.png" type="favicon" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <script src="https://cdn.tailwindcss.com"></script>
 
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
            font-family: "Baloo Bhaijaan 2", sans-serif;
        }
        .dashboard-box {
            background-color: #e0fbfc;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .dashboard-box h5 {
            font-size: 18px;
            margin-bottom: 15px;
        }
        .row {
            display: flex;
            justify-content: flex-start;
            margin-left: 100px;
            margin-top: 60px;
        }
        .profile-picture {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #fff;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .profile-picture:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.3);
        }
        .fw-bold {
            color: #15c3c6;
        }
        .btn-outline-primary .badge {
            font-size: 12px;
        }
    </style>

</head>

<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <img src="../uploads/<?php echo htmlspecialchars($profile_picture); ?>" alt="Profile Picture"
            class="profile-picture">
        <?php include 'sidebar.php'; ?>
        <!-- Dashboard Content -->
        <div class="container-fluid dashboard-content" style="margin-left: 190px; margin-right: 30px;">
            <h2 class="text-center fs-1 fw-bold ms-5 mt-5">Welcome to Your Dashboard,
                <span class="fw-bold"><?php echo htmlspecialchars($name_result); ?></span>
            </h2>
    
            <div class="row">
                <div class="col-12 col-md-4 me-3 mb-3">
                    <div class="dashboard-box course-box">
                        <h5>Total Courses Enrolled</h5>
                        <p><?php echo $total_courses; ?></p>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="dashboard-box course-box">
                        <h5>Total Attendance</h5>
                        <p><?php echo $total_attendance; ?></p>
                    </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
