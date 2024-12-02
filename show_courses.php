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

// Fetch courses from the database
$query = "SELECT * FROM courses";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .scale-on-hover:hover {
            transform: scale(1.05);
            transition: transform 0.3s ease-in-out;
        }

        .modal-enter {
            animation: fadeIn 0.3s ease-out forwards;
        }

        .modal-exit {
            animation: fadeOut 0.3s ease-in-out forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10%);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10%);
            }
        }
    </style>
</head>

<body class="font-[Baloo+Bhaijaan+2] bg-gray-50 min-h-screen flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-center text-4xl font-bold mb-6">Courses</h2>
        
        <div class="flex justify-end mb-4">
            <a href="create_course.php" class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg flex items-center space-x-2">
                <span>Add Course</span>
                <img src="./assets/ebook.png" alt="Add Course" class="w-6 h-6">
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($course = $result->fetch_assoc()): ?>     
                    <div class="bg-[#e0fbfc] shadow-md rounded-lg p-6 relative">
                        <!-- Icons at the top-right -->
                        <div class="absolute top-3 right-3 flex space-x-4">
                            <a href="#" data-id="<?php echo $course['id']; ?>"
                               data-name="<?php echo htmlspecialchars($course['course_name']); ?>"
                               data-start-date="<?php echo htmlspecialchars($course['start_date']); ?>"
                               data-end-date="<?php echo htmlspecialchars($course['end_date']); ?>"
                               data-start-time="<?php echo htmlspecialchars($course['start_time']); ?>"
                               data-end-time="<?php echo htmlspecialchars($course['end_time']); ?>"
                               data-day="<?php echo htmlspecialchars($course['day']); ?>"
                               class="text-blue-500 hover:text-blue-700 transition"
                               data-bs-toggle="modal" data-bs-target="#updateCourseModal">
                                <img src="./assets/social.png" alt="Update Icon" class="w-10 h-10">
                            </a>
                            <a href="#" class="delete-course text-red-500 hover:text-red-700 transition"
                               data-id="<?php echo $course['id']; ?>">
                                <img src="./assets/trash.png" alt="Delete Icon" class="w-10 h-10">
                            </a>
                        </div>

                        <!-- Course Content -->
                        <h4 class="text-xl font-semibold mb-3 mt-10"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                        <p><strong>Start Date:</strong> <?php echo htmlspecialchars($course['start_date']); ?></p>
                        <p><strong>End Date:</strong> <?php echo htmlspecialchars($course['end_date']); ?></p>
                        <p><strong>Start Time:</strong> <?php echo htmlspecialchars($course['start_time']); ?></p>
                        <p><strong>End Time:</strong> <?php echo htmlspecialchars($course['end_time']); ?></p>
                        <p><strong>Day:</strong> <?php echo htmlspecialchars($course['day']); ?></p>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center col-span-full text-gray-600">No courses found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden" id="updateCourseModal">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6 modal-enter">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Update Course</h3>
                <button class="text-gray-500 hover:text-gray-700" id="closeModal">&times;</button>
            </div>
            <form id="updateCourseForm" method="POST" action="update_course.php">
                <input type="hidden" name="course_id" id="course_id">
                <div class="mb-4">
                    <label for="course_name" class="block text-sm font-medium">Course Name</label>
                    <input type="text" class="w-full border rounded-lg p-2" id="course_name" name="course_name" required>
                </div>
                <div class="mb-4">
                    <label for="start_date" class="block text-sm font-medium">Start Date</label>
                    <input type="date" class="w-full border rounded-lg p-2" id="start_date" name="start_date" required>
                </div>
                <div class="mb-4">
                    <label for="end_date" class="block text-sm font-medium">End Date</label>
                    <input type="date" class="w-full border rounded-lg p-2" id="end_date" name="end_date" required>
                </div>
                <div class="mb-4">
                    <label for="start_time" class="block text-sm font-medium">Start Time</label>
                    <input type="time" class="w-full border rounded-lg p-2" id="start_time" name="start_time" required>
                </div>
                <div class="mb-4">
                    <label for="end_time" class="block text-sm font-medium">End Time</label>
                    <input type="time" class="w-full border rounded-lg p-2" id="end_time" name="end_time" required>
                </div>
                <div class="mb-4">
                    <label for="day" class="block text-sm font-medium">Day</label>
                    <input type="text" class="w-full border rounded-lg p-2" id="day" name="day" required>
                </div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                    Update Course
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById('updateCourseModal');
        const closeModal = document.getElementById('closeModal');
        const deleteButtons = document.querySelectorAll('.delete-course');

        // Show modal on click
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-bs-toggle="modal"]')) {
                const button = e.target.closest('[data-bs-toggle="modal"]');
                document.getElementById('course_id').value = button.getAttribute('data-id');
                document.getElementById('course_name').value = button.getAttribute('data-name');
                document.getElementById('start_date').value = button.getAttribute('data-start-date');
                document.getElementById('end_date').value = button.getAttribute('data-end-date');
                document.getElementById('start_time').value = button.getAttribute('data-start-time');
                document.getElementById('end_time').value = button.getAttribute('data-end-time');
                document.getElementById('day').value = button.getAttribute('data-day');
                modal.classList.remove('hidden');
            }
        });

        // Hide modal on close
        closeModal.addEventListener('click', () => modal.classList.add('hidden'));

        // SweetAlert for delete confirmation
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const courseId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'delete_course.php?id=' + courseId;
                    }
                });
            });
        });
    </script>
</body>

</html>
