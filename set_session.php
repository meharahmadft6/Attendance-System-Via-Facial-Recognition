<?php
// Start the session
session_start();

// Get the teacher's email from the POST request
$data = json_decode(file_get_contents('php://input'), true);

// Ensure that email is being passed correctly
if (isset($data['email']) && is_string($data['email'])) {
    $teacher_email = $data['email'];
} else {
    // If email is missing or invalid, return an error response
    echo json_encode(['error' => 'Invalid or missing email']);
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "attendance_system";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get the teacher's information using the email
$sql = "SELECT teacher_id, email FROM teachers WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $teacher_email);
$stmt->execute();
$result = $stmt->get_result();

// Check if teacher exists
if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();

    // Set session variables
    $_SESSION['user_id'] = $teacher['teacher_id'];
    $_SESSION['username'] = $teacher['email'];
    $_SESSION['role'] = 'teacher';
    $_SESSION['teacher_id'] = $teacher['teacher_id'];

    // Respond with success (JSON)
    echo json_encode(['message' => 'Teacher session set successfully']);
} else {
    // If no teacher found, send an error response
    echo json_encode(['error' => 'Teacher not found']);
}

$conn->close();
?>
