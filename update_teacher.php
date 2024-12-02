<?php
require 'db.php';

// Ensure the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the teacher's data from the form
    $teacher_id = $_POST['teacher_id'];
    $teacher_name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone_number'];
    $department = $_POST['department'];

    // Initialize the variable for the image filename
    $facial_data = "";

    // Fetch the current image name from the database
    $current_image_query = "SELECT facial_data FROM teachers WHERE teacher_id = ?";
    $stmt = $conn->prepare($current_image_query);
    if ($stmt === false) {
        die("Error preparing query: " . $conn->error);
    }
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_image = $result->fetch_assoc()['facial_data'];
    $stmt->close();

    // Handle the image upload if there is a new image
    if (isset($_FILES['facial_data']) && $_FILES['facial_data']['error'] == 0) {
        $file = $_FILES['facial_data'];
        $file_name = $file['name'];
        $file_tmp_name = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Allowed file types
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            // Generate a unique filename to avoid overwriting
            $unique_file_name = uniqid('', true) . "." . $file_ext;
            $upload_path = 'teachersPics/' . $unique_file_name;

            // Move the file to the directory
            if (move_uploaded_file($file_tmp_name, $upload_path)) {
                // Store the new file name in the variable
                $facial_data = $unique_file_name;

                // Optionally delete the old image file if needed
                if (!empty($current_image) && file_exists('teachersPics/' . $current_image)) {
                    unlink('teachersPics/' . $current_image);
                }
            } else {
                echo "Error uploading the image.";
                exit();
            }
        } else {
            echo "Invalid file type. Only JPG, JPEG, and PNG are allowed.";
            exit();
        }
    } else {
        // If no new image is uploaded, retain the current image
        $facial_data = $current_image;
    }

    // Prepare and execute the update query
    $query = "UPDATE teachers SET name = ?, email = ?, phone_number = ?, department = ?, facial_data = ? WHERE teacher_id = ?";
    $stmt = $conn->prepare($query);

    if ($stmt === false) {
        die("Error preparing query: " . $conn->error);
    }

    // Bind parameters and execute the query
    $stmt->bind_param("sssssi", $teacher_name, $email, $phone, $department, $facial_data, $teacher_id);

    if ($stmt->execute()) {
        // Redirect to the list of teachers page after successful update
        header("Location: show_teachers.php");
        exit();
    } else {
        echo "Error executing query: " . $stmt->error;
    }
    $stmt->close();
}
?>
