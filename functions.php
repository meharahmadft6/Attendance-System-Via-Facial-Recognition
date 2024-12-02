<?php
require 'db.php';

function getUserByUsername($conn, $username) {
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function createUser($conn, $username, $hashed_password, $role) {
    $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $role);
    return $stmt->execute() ? $conn->insert_id : false;
}

function enrollStudent($conn, $user_id, $course_id, $facial_data) {
    $stmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, facial_data, created_at) VALUES (?, ?, ?, NOW())");
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }

    $stmt->bind_param("iis", $user_id, $course_id, $facial_data);

    if (!$stmt->execute()) {
        error_log("Execute failed: " . $stmt->error); // Log specific error
        return false;
    }
    return true;
}


function getCourses($conn) {
    $result = $conn->query("SELECT * FROM courses");
    return $result->fetch_all(MYSQLI_ASSOC);
}

function checkEnrollment($conn, $user_id, $course_id) {
    $stmt = $conn->prepare("SELECT * FROM enrollments WHERE user_id = ? AND course_id = ?");
    $stmt->bind_param("ii", $user_id, $course_id);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}
?>
