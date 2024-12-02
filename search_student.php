<?php
// db.php should already be included
require 'db.php';

if (isset($_GET['username'])) {
    $username = $_GET['username'];
    $stmt = $conn->prepare("SELECT username FROM users WHERE username LIKE ? LIMIT 5");
    $searchTerm = "%" . $username . "%";
    $stmt->bind_param("s", $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $students = [];
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }

    echo json_encode($students);
} else {
    echo json_encode([]);
}
?>
