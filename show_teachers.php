<?php
session_start();
require 'db.php';

// Set session timeout period (e.g., 30 minutes)
$timeout_duration = 1800; // 30 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: admin_login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch teachers and course information
$query = "SELECT * 
          FROM teachers ";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <link rel="shortcut icon" href="./assets/manager.png" type="image/x-icon" />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
</head>

<body class="font-[Baloo+Bhaijaan+2] bg-gray-50 min-h-screen flex">

    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Content -->
    <div class="flex-grow p-6 md:ml-64">
        <h2 class="text-center text-4xl font-bold mb-6">Teachers</h2>
        <div class="flex justify-end mb-6">
            <a href="add_teacher.php"
               class="bg-[#e0fbfc] text-black font-medium py-2 px-4 rounded-lg flex items-center space-x-2">
                <span>Add Teacher</span>
                <img src="./assets/ebook.png" alt="Add Teacher" class="w-6 h-6">
            </a>
        </div>

        <!-- Teachers Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($teacher = $result->fetch_assoc()): ?>
                    <div class="bg-[#e0fbfc] shadow-md rounded-lg p-6 relative flex items-start">
                        <!-- Teacher Content (Left side) -->
                        <div class="flex-1 pr-6">
                            <div class="absolute top-3 right-3 flex space-x-4">
                                <a href="#" data-bs-toggle="modal" data-bs-target="#updateTeacherModal"
                                   data-id="<?php echo $teacher['teacher_id']; ?>"
                                   data-name="<?php echo htmlspecialchars($teacher['name']); ?>"
                                   data-email="<?php echo htmlspecialchars($teacher['email']); ?>"
                                   data-phone="<?php echo htmlspecialchars($teacher['phone_number']); ?>"
                                   data-department="<?php echo htmlspecialchars($teacher['department']); ?>"
                                   data-image="<?php echo $teacher['facial_data']; ?>">
                                    <img src="./assets/social.png" alt="Update Icon" class="w-8 h-8">
                                </a>
                                <a href="#" class="delete-teacher text-red-500 hover:text-red-700 transition"
                                   data-id="<?php echo $teacher['teacher_id']; ?>">
                                    <img src="./assets/trash.png" alt="Delete Icon" class="w-8 h-8">
                                </a>
                            </div>

                            <h4 class="text-xl font-semibold mb-3 mt-10"><?php echo htmlspecialchars($teacher['name']); ?></h4>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($teacher['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($teacher['phone_number']); ?></p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($teacher['department']); ?></p>
                    
                        </div>

                        <!-- Teacher Image (Right side) -->
                        <div class="w-32 h-32 flex-shrink-0 ml-auto mt-8">
                            <img src="teachersPics/<?php echo htmlspecialchars($teacher['facial_data']); ?>" 
                                 alt="Teacher Image" class="w-full h-full object-cover rounded-full">
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-center col-span-full text-gray-600">No teachers found.</p>
            <?php endif; ?>
        </div>
    </div>


    <!-- Modal for Updating Teacher -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden" id="updateTeacherModal">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-lg p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-semibold">Update Teacher</h3>
                <button class="text-gray-500 hover:text-gray-700" id="closeModal">&times;</button>
            </div>
            <form id="updateTeacherForm" method="POST" action="update_teacher.php" enctype="multipart/form-data">
                <input type="hidden" name="teacher_id" id="teacher_id">

                <!-- Image Preview Section -->
                <div class="mb-4 flex justify-center">
                    <div id="imageContainer" class="relative w-32 h-32">
                        <!-- Image preview will be inserted here -->
                        <img id="imagePreview" class="w-full h-full object-cover rounded-full cursor-pointer" src="" alt="Current Teacher Image" onclick="document.getElementById('facial_data').click();">
                        <!-- Hidden file input -->
                        <input type="file" id="facial_data" name="facial_data" accept="image/*" class="absolute inset-0 opacity-0 cursor-pointer" onchange="previewImage(event)">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="name" class="block text-sm font-medium">Teacher Name</label>
                    <input type="text" class="w-full border rounded-lg p-2" id="name" name="name" required>
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-sm font-medium">Email</label>
                    <input type="email" class="w-full border rounded-lg p-2" id="email" name="email" required>
                </div>
                <div class="mb-4">
                    <label for="phone_number" class="block text-sm font-medium">Phone</label>
                    <input type="text" class="w-full border rounded-lg p-2" id="phone_number" name="phone_number" required>
                </div>
                <div class="mb-4">
                    <label for="department" class="block text-sm font-medium">Department</label>
                    <input type="text" class="w-full border rounded-lg p-2" id="department" name="department" required>
                </div>
          
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg">
                    Update Teacher
                </button>
            </form>
        </div>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById('updateTeacherModal');
        const closeModal = document.getElementById('closeModal');
        const imagePreview = document.getElementById('imagePreview'); // For the image preview

        // Show modal on click
        document.addEventListener('click', function (e) {
            if (e.target.closest('[data-bs-toggle="modal"]')) {
                const button = e.target.closest('[data-bs-toggle="modal"]');
                const teacherId = button.getAttribute('data-id');
                const teacherName = button.getAttribute('data-name');
                const teacherEmail = button.getAttribute('data-email');
                const teacherPhone = button.getAttribute('data-phone');
                const teacherDepartment = button.getAttribute('data-department');
                const teacherImage = button.getAttribute('data-image');

                // Set form values
                document.getElementById('teacher_id').value = teacherId;
                document.getElementById('name').value = teacherName;
                document.getElementById('email').value = teacherEmail;
                document.getElementById('phone_number').value = teacherPhone;
                document.getElementById('department').value = teacherDepartment;
   
                // Set image preview in the modal if available
                if (teacherImage) {
                    imagePreview.src = 'teachersPics/' + teacherImage;
                } else {
                    imagePreview.src = '';
                }

                modal.classList.remove('hidden');
            }
        });

        // Hide modal on close
        closeModal.addEventListener('click', () => modal.classList.add('hidden'));

        // Image preview function
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function () {
                imagePreview.src = reader.result; // Set image preview
            };
            reader.readAsDataURL(event.target.files[0]);
        }

        // SweetAlert for teacher deletion
        const deleteButtons = document.querySelectorAll('.delete-teacher');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const teacherId = this.getAttribute('data-id');
                Swal.fire({
                    title: 'Are you sure?',
                    text: 'You won\'t be able to revert this!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'delete_teacher.php?id=' + teacherId;
                    }
                });
            });
        });
    </script>
</body>
</html>
