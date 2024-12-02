<?php
// Set the content type to JSON
header('Content-Type: application/json');

// Database connection settings
$host = 'localhost';
$dbname = 'attendance_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]);
    exit;
}

// Read the incoming JSON data
$inputData = json_decode(file_get_contents("php://input"), true);

if (isset($inputData['email'])) {
    $email = $inputData['email'];

    // Debugging: Log received email
    error_log("Received email: " . $email);

    // Query to get the facial_data for the given email
    $sql = "SELECT facial_data FROM teachers WHERE email = :email";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($result && isset($result['facial_data'])) {
        echo json_encode([
            'success' => true,
            'facial_data' => $result['facial_data']
        ]);
    } else {
        // Debugging: Check why no data was found
        $checkSql = "SELECT * FROM teachers";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->execute();
        $allRecords = $checkStmt->fetchAll(PDO::FETCH_ASSOC);

        error_log("All records in 'teachers' table: " . json_encode($allRecords));

        echo json_encode(['success' => false, 'message' => 'Facial data not found for this email.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Email is required.']);
}
?>
