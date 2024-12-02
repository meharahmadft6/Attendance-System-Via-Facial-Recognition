<?php
require 'db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$email = $data['email'];

$stmt = $conn->prepare("SELECT facial_data FROM teachers WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $teacher = $result->fetch_assoc();
    echo json_encode(['success' => true, 'facialDataPath' => $teacher['facial_data']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Email not found.']);
}
?>
