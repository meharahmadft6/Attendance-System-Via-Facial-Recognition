<?php
session_start();
require 'db.php';

// Check if the user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on role
    if ($_SESSION['role'] === 'admin') {
        header("Location: dashboard.php");
    } elseif ($_SESSION['role'] === 'student') {
        header("Location: student_dashboard.php");
    } elseif ($_SESSION['role'] === 'teacher') {
        header("Location: teacher_dashboard.php");
    }
    exit();
}

$error_message = '';

// Handle Login request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // If it's a login request
    if (isset($_POST['username']) && isset($_POST['password'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // First, check in the users table (students and admins)
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // User found in the users table (student or admin)
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // Store user details in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role']; // Store the user's role

                // Redirect based on role
                if ($user['role'] === 'student') {
                    $_SESSION['student_id'] = $user['id'];
                    header("Location: student_system/student_dashboard.php");
                } elseif ($user['role'] === 'admin') {
                    $_SESSION['admin_id'] = $user['id'];
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error_message = "Invalid username or password.";
            }
        } 
    }

    // Forgot Password - Get Security Question
    if (isset($_POST['forgotUsername']) && !isset($_POST['password'])) {
        $username = $_POST['forgotUsername'];

        // Check for student or teacher
        $stmt = $conn->prepare("SELECT security_question FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            echo json_encode(['status' => 'success', 'question' => $user['security_question']]);
        }
        exit();
    }

    // Verify Answer
    if (isset($_POST['answer']) && isset($_POST['username'])) {
        $username = $_POST['username'];
        $answer = $_POST['answer'];

        // Check if it's a student or teacher
        $stmt = $conn->prepare("SELECT security_answer FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            // No need to hash as answer is stored plainly
            if ($answer === $user['security_answer']) {
                echo json_encode(['status' => 'success']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Incorrect answer']);
            }
        }
        exit();
    }

    // Update Password
    if (isset($_POST['newPassword']) && isset($_POST['username'])) {
        $username = $_POST['username'];
        
        // Hash the new password
        $new_password = password_hash($_POST['newPassword'], PASSWORD_DEFAULT);

        // Update in users table (for students/admins)
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
        $stmt->bind_param("ss", $new_password, $username);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Password update failed']);
        }

        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FaceTendance</title>
    <link rel="shortcut icon" href="./assets/face.png" type="image/x-icon">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap"
        rel="stylesheet" />
</head>
<style>
     body {
            font-family: 'Baloo Bhai 2', 'Poppins', sans-serif;
        }

    /* Fade In Animation */
.fade-in {
    animation: fadeIn 0.3s ease-in-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

/* Fade Out Animation */
.fade-out {
    animation: fadeOut 0.3s ease-in-out;
}

@keyframes fadeOut {
    from {
        opacity: 1;
    }
    to {
        opacity: 0;
    }
}

/* Zoom In Animation for Webcam Modal */
.zoom-in {
    animation: zoomIn 0.3s ease-in-out;
}

@keyframes zoomIn {
    from {
        transform: scale(0.8);
        opacity: 0;
    }
    to {
        transform: scale(1);
        opacity: 1;
    }
}

</style>
<body class="bg-gradient-to-br from-blue-900 to-gray-900 min-h-screen flex items-center justify-center">
    <!-- Login Container -->
    <div class="bg-white shadow-lg rounded-lg w-full max-w-4xl flex">
        <!-- Left Side: Logo Section -->
        <div class="w-1/2 bg-gradient-to-br flex flex-col items-center justify-center p-8 rounded-l-lg">
            <img src="./assets/face (1).png" alt="FaceTendance Logo" class="h-80 w-70">
          </div>

        <!-- Right Side: Login Form -->
        <div class="w-1/2 p-8 flex flex-col justify-center">
            <div class="mb-6 text-center">
                <h3 class="text-2xl font-bold text-gray-800">Welcome</h3>
                <p class="text-gray-500">Please login to your Dashboard.</p>
            </div>
            <?php if (!empty($error_message)): ?>
            <script>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo $error_message; ?>'
                });
            </script>
            <?php endif; ?>
            <form method="POST" action="" class="space-y-4">
                <input type="text" name="username" placeholder="Username" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <input type="password" name="password" placeholder="Password" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                    <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg">
                    Login
                </button>
                <a href="#" class="block text-center text-sm text-blue-500 hover:underline"
                    onclick="toggleModal()">Forgotten your password?</a>               
                    <a href="#" id="teacherLoginLink" class="block text-end text-xl text-blue-500 hover:underline">Teacher?</a>
            </form>
        </div>
    </div>
    
    <!-- Teacher Login Modal -->
    <div id="teacherModal" class="hidden fixed inset-0 bg-gray-700 bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-lg w-96 p-6">
            <h3 class="text-lg font-bold mb-4">Teacher Login</h3>
            <form id="teacherForm">
                <input type="email" id="teacherEmail" name="email" placeholder="Enter your email" required
                    class="w-full px-4 py-2 mb-4 border rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <button type="submit"
                    class="w-full bg-blue-500 text-white py-2 rounded-lg hover:bg-blue-600">Submit</button>
            </form>
            <button id="closeTeacherModal" class="mt-4 text-red-500 hover:underline">Cancel</button>
        </div>
    </div>
<!-- Webcam Section -->
<div id="webcamContainer" class="hidden fixed inset-0 bg-gray-700 bg-opacity-50 flex flex-col items-center justify-center">
        <video id="webcam" class="w-120 h-100 border rounded-lg mb-4" autoplay muted></video>
        <canvas id="overlay" class="w-120 h-100 absolute"></canvas>
        <button id="captureBtn" class="w-48 bg-blue-500 hover:bg-blue-600 text-white font-semibold py-3 rounded-lg mt-4">
            Capture
        </button>
    </div>

    <!-- Forgot Password Modal -->
    <div id="forgotModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg w-full max-w-md p-6">
            <div class="flex justify-between items-center mb-4">
                <h5 class="text-lg font-bold text-gray-800">Forgot Password</h5>
                <button class="text-gray-500 hover:text-gray-800" onclick="toggleModal()">✕</button>
            </div>
            <form id="forgotPasswordForm" class="space-y-4">
                <!-- Username Input -->
                <input type="text" id="forgotUsername" name="username" placeholder="Enter Username" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <button type="submit"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg">
                    Get Security Question
                </button>
            </form>

            <!-- Security Question Section -->
            <div id="securityQuestionSection" class="hidden space-y-4 mt-4">
                <p id="securityQuestionText" class="text-gray-600"></p>
                <input type="text" id="securityAnswer" name="answer" placeholder="Answer" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <button type="button" id="verifyAnswerBtn"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg">
                    Verify Answer
                </button>
            </div>

            <!-- New Password Section -->
            <div id="newPasswordSection" class="hidden space-y-4 mt-4">
                <input type="password" id="newPassword" name="newPassword" placeholder="Enter New Password" required
                    class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
                <button type="button" id="updatePasswordBtn"
                    class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold py-2 rounded-lg">
                    Update Password
                </button>
            </div>
        </div>
    </div>
    <script>
    const teacherLoginLink = document.getElementById("teacherLoginLink");
    const teacherModal = document.getElementById("teacherModal");
    const closeTeacherModal = document.getElementById("closeTeacherModal");
    const webcamContainer = document.getElementById("webcamContainer");
    const webcam = document.getElementById("webcam");
    const captureBtn = document.getElementById("captureBtn");

    // Show the teacher login modal when the link is clicked
    teacherLoginLink.addEventListener("click", () => {
        teacherModal.classList.remove("hidden");
        teacherModal.classList.add("fade-in");
    });

    // Close the teacher login modal
    closeTeacherModal.addEventListener("click", () => {
        teacherModal.classList.add("hidden");
    });

    // Fetch facial data and show webcam container
    document.getElementById("teacherForm").addEventListener("submit", async (e) => {
        e.preventDefault();
        const email = document.getElementById("teacherEmail").value;

        const response = await fetch("get-teacher-facial-data.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email }),
        });

        const data = await response.json();
        if (data.success && data.facial_data) {
            teacherModal.classList.add("hidden");
            webcamContainer.classList.remove("hidden");
            startWebcam();
            window.teacherFacialData = data.facial_data; // Save the stored image path
        } else {
            Swal.fire("Error", data.message, "error");
        }
    });

    // Start the webcam
    function startWebcam() {
        navigator.mediaDevices.getUserMedia({ video: true })
            .then((stream) => {
                webcam.srcObject = stream;
                webcam.play();
            })
            .catch((error) => {
                console.error("Error accessing webcam:", error);
                Swal.fire("Error", "Could not access webcam.", "error");
            });
    }

    // Capture and send the image
    captureBtn.addEventListener("click", async () => {
        try {
            const canvas = document.createElement("canvas");
            canvas.width = webcam.videoWidth;
            canvas.height = webcam.videoHeight;
            const ctx = canvas.getContext("2d");
            ctx.drawImage(webcam, 0, 0, canvas.width, canvas.height);

            const base64Image = canvas.toDataURL("image/jpeg");
            const email = document.getElementById("teacherEmail").value;

            const response = await fetch("http://127.0.0.1:5000/verify", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify({
                    email: email,
                    capturedImage: base64Image,
                    facialData: window.teacherFacialData,
                }),
            });

            const result = await response.json();

            if (result.verified) {
            // Show success alert using Swal
            Swal.fire("Success", "Facial recognition successful!", "success").then(async () => {
                // Now, set the session in the PHP server using the teacher's email
                console.log("sessions loaded");
                console.log('Teacher Email:', email); // Check what is being sent
                const sessionResponse = await fetch('set_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email: email }) // Ensure teacherEmail is a string
                });

                const textResponse = await sessionResponse.text(); // Get the raw response as text
                console.log(textResponse); // This will show you what is actually being returned
                const sessionResult = JSON.parse(textResponse); // Then parse it into JSON

                // If session is set successfully, redirect to PHP teacher dashboard
                if (sessionResult.message === "Teacher session set successfully") {
                    window.location.href = "teacher_system/teacher_dashboard.php"; // Redirect to teacher dashboard
                } else {
                    // If session setting failed, show the error
                    Swal.fire("Error", sessionResult.error, "error");
                }
            });
        } else {
            // Show error alert using Swal if recognition fails
            Swal.fire("Error", "Facial recognition failed!", "error");
        }
        
        } catch (error) {
            console.error("Error during verification:", error);
            Swal.fire("Error", "Verification failed.", "error");
        }
    });


</script>
    <script>
        // Toggle Modal for Forgot Password
        function toggleModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.toggle('hidden');
        }
    </script>

    <script>
        // Toggle Modal
        function toggleModal() {
            const modal = document.getElementById('forgotModal');
            modal.classList.toggle('hidden');
        }

        document.addEventListener('DOMContentLoaded', () => {
            const forgotPasswordForm = document.getElementById('forgotPasswordForm');
            const securityQuestionSection = document.getElementById('securityQuestionSection');
            const securityQuestionText = document.getElementById('securityQuestionText');
            const verifyAnswerBtn = document.getElementById('verifyAnswerBtn');
            const newPasswordSection = document.getElementById('newPasswordSection');
            const newPassword = document.getElementById('newPassword');
            const updatePasswordBtn = document.getElementById('updatePasswordBtn');

            // Forgot Password - Get Security Question
            forgotPasswordForm.addEventListener('submit', function (e) {
                e.preventDefault();
                const username = document.getElementById('forgotUsername').value;

                fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ 'forgotUsername': username })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            securityQuestionText.textContent = data.question;
                            securityQuestionSection.classList.remove('hidden');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    });
            });

            // Verify Security Answer
            verifyAnswerBtn.addEventListener('click', function () {
                const answer = document.getElementById('securityAnswer').value;
                const username = document.getElementById('forgotUsername').value;

                fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ 'username': username, 'answer': answer })
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            securityQuestionSection.classList.add('hidden');
                            newPasswordSection.classList.remove('hidden');
                        } else {
                            Swal.fire({ icon: 'error', title: 'Error', text: data.message });
                        }
                    });
            });

            // Update Password
            updatePasswordBtn.addEventListener('click', function () {
                const password = newPassword.value;
                const username = document.getElementById('forgotUsername').value;

                fetch('', {
                    method: 'POST',
                    body: new URLSearchParams({ 'username': username, 'newPassword': password })
                })
                    .then(response => response.json())
                    .then(data => {
                        Swal.fire({
                            icon: data.status === 'success' ? 'success' : 'error',
                            title: data.status === 'success' ? 'Password Updated' : 'Error',
                            text: data.message
                        }).then(() => {
                            if (data.status === 'success') {
                                toggleModal();
                            }
                        });
                    });
            });
        });
    </script>
</body>

</html>
