<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require 'db.php'; // Include your database connection file
$timeout_duration = 3600; // 60 minutes in seconds

// Check if the session is expired
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Update last activity time
$_SESSION['last_activity'] = time();

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Function to check and reconnect to the database if necessary
function checkDbConnection($conn)
{
    if ($conn->ping()) {
        return true; // Connection is alive
    } else {
        global $servername, $username, $password, $dbname;
        $conn = new mysqli($servername, $username, $password, $dbname);
        return $conn->connect_error ? false : true;
    }
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!checkDbConnection($conn)) {
        die("Failed to connect to the database.");
    }

    $username = $_POST['username'];
    $password = $_POST['password'];
    $course_id = $_POST['course_id'];
    $role = 'student';
    $security_question = $_POST['security_question'];
    $security_answer = $_POST['security_answer'];
    $teacher_id = $_POST['teacher_id']; // Added teacher_id

    // Check if the user exists in the users table
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user_result = $stmt->get_result();

    $upload_dir = 'uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $facial_data_path = null;
    if (isset($_FILES['facial_data']) && $_FILES['facial_data']['error'] == 0) {
        $file_tmp_name = $_FILES['facial_data']['tmp_name'];
        $file_name = basename($_FILES['facial_data']['name']);
        $unique_file_name = uniqid() . '_' . $file_name;
        $target_path = $upload_dir . $unique_file_name;

        if (move_uploaded_file($file_tmp_name, $target_path)) {
            $facial_data_path = $unique_file_name;
        } else {
            $_SESSION['error'] = 'Failed to upload facial data image.';
            header('Location: enroll_student.php');
            exit();
        }
    }

    if ($user_result->num_rows > 0) {
        $user = $user_result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $user_id = $user['id'];
            $stmt = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
            $stmt->bind_param("ii", $user_id, $course_id);
            $stmt->execute();
            $enrollment_result = $stmt->get_result();

            if ($enrollment_result->num_rows == 0) {
                $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, teacher_id, facial_data, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->bind_param("iiis", $user_id, $course_id, $teacher_id, $facial_data_path); // Added teacher_id

                if ($stmt->execute()) {
                    $_SESSION['success'] = 'Student enrolled successfully!';
                } else {
                    $_SESSION['error'] = 'Error enrolling student: ' . $conn->error;
                }
            } else {
                $_SESSION['error'] = 'Error: Student is already enrolled in this course!';
            }
        } else {
            $_SESSION['error'] = 'Error: Incorrect password!';
        }
    } else {
        // If user doesn't exist, create a new user
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare("INSERT INTO users (username, password, role, security_question, security_answer) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $username, $hashed_password, $role, $security_question, $security_answer);

        if ($stmt->execute()) {
            $user_id = $conn->insert_id;

            $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, teacher_id, facial_data, created_at) VALUES (?, ?, ?, ?, NOW())");
            $stmt->bind_param("iiis", $user_id, $course_id, $teacher_id, $facial_data_path); // Added teacher_id

            if ($stmt->execute()) {
                $_SESSION['success'] = 'Student enrolled successfully!';
            } else {
                $_SESSION['error'] = 'Error enrolling student: ' . $conn->error;
            }
        } else {
            $_SESSION['error'] = 'Error adding user: ' . $conn->error;
        }
    }

    $stmt->close();
    header('Location: enroll_student.php');
    exit();
}

// Fetch courses and security questions for the dropdown
$courses = [];
$security_questions = [];
$teachers = [];

if ($result = $conn->query("SELECT * FROM courses")) {
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
} else {
    die("Error fetching courses: " . $conn->error);
}

// Fetch security questions from the users table
if ($result = $conn->query("SELECT DISTINCT security_question FROM users")) {
    while ($row = $result->fetch_assoc()) {
        $security_questions[] = $row['security_question'];
    }
} else {
    die("Error fetching security questions: " . $conn->error);
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <script src="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.js"></script>
</head>

<body class="bg-gray-100 font-sans">

    <!-- Include Sidebar (Assuming you have a sidebar.php file) -->
    <?php include 'sidebar.php'; ?>

    <div class="container mx-auto p-6 bg-white rounded-lg shadow-lg mt-10 max-w-lg">
        <div class="text-center text-2xl font-bold text-gray-800 mb-6">Enroll Student</div>

        <?php if (isset($_SESSION['error'])): ?>
            <div id="error" class="alert alert-danger bg-red-500 text-white p-2 rounded mb-4"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <div  id="success" class="alert alert-success bg-green-500 text-white p-2 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
        <?php endif; ?>
        <script>
    // Function to hide the messages after a delay
    function hideMessage(id) {
        setTimeout(function() {
            const messageElement = document.getElementById(id);
            if (messageElement) {
                messageElement.style.display = 'none';
            }
        }, 3000); // 3000 ms = 3 seconds
    }

    // Check if the success or error message exists, and hide them after 3 seconds
    <?php if ($success): ?>
        hideMessage('success');
    <?php endif; ?>

    <?php if ($error): ?>
        hideMessage('error');
    <?php endif; ?>
</script>
        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-4">
                <label for="username" class="block text-sm font-semibold text-gray-700">Username</label>
                <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="username" name="username" required>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                <input type="password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="password" name="password" required>
            </div>

            <div class="mb-4">
                <label for="course_id" class="block text-sm font-semibold text-gray-700">Select Course</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="course_id" name="course_id" required>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="mb-4">
                <label for="teacher_id" class="block text-sm font-semibold text-gray-700">Select Teacher</label>
                <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="teacher_id" name="teacher_id" required>
                    <option value="" disabled selected>Select a teacher</option>
                </select>
            </div>

            <div class="mb-4 flex space-x-4">
                <div class="w-1/2">
                    <label for="security_question" class="block text-sm font-semibold text-gray-700">Security Question</label>
                    <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="security_question" name="security_question" required>
                        <?php foreach ($security_questions as $question): ?>
                            <option value="<?php echo htmlspecialchars($question); ?>"><?php echo htmlspecialchars($question); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="w-1/2">
                    <label for="security_answer" class="block text-sm font-semibold text-gray-700">Security Answer</label>
                    <input type="text" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-600" id="security_answer" name="security_answer" required>
                </div>
            </div>

            <div class="mb-4">
                <label for="facial_data" class="block text-sm font-semibold text-gray-700">Upload Picture</label>
                <div class="relative border-2 border-dashed border-gray-300 p-6 text-center rounded-lg">
                    <img id="picture-preview" class="mx-auto mb-4 max-w-full max-h-60 hidden rounded-lg" alt="Picture Preview">
                    <label for="facial_data" class="cursor-pointer text-indigo-600">Click here to upload your picture</label>
                    <input type="file" class="hidden" id="facial_data" name="facial_data" accept="image/*" required>
                </div>
            </div>

            <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-600">Enroll</button>
        </form>
    </div>

    <script>
        // JavaScript for image preview
        document.getElementById('facial_data').addEventListener('change', function(event) {
            const file = event.target.files[0];
            const preview = document.getElementById('picture-preview');
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                preview.src = '';
                preview.style.display = 'none';
            }
        });

        // JavaScript to load teachers based on course selection
        document.getElementById('course_id').addEventListener('change', function () {
            const courseId = this.value;
            const teacherDropdown = document.getElementById('teacher_id');
            teacherDropdown.innerHTML = '<option value="" disabled selected>Loading...</option>';

            fetch(`fetch_teachers.php?course_id=${courseId}`)
                .then(response => response.json())
                .then(data => {
                    teacherDropdown.innerHTML = '<option value="" disabled selected>Select a teacher</option>';
                    data.forEach(teacher => {
                        const option = document.createElement('option');
                        option.value = teacher.teacher_id;
                        option.textContent = teacher.name;
                        teacherDropdown.appendChild(option);
                    });
                })
                .catch(error => {
                    console.error('Error fetching teachers:', error);
                    teacherDropdown.innerHTML = '<option value="" disabled selected>Error loading teachers</option>';
                });
        });
    </script>
</body>

</html>
