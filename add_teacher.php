<?php
include "db.php";
session_start(); // Ensure session_start is at the top of the file

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $department = $_POST['department'];

    // Handle image upload
    $facial_data = "";
    if (isset($_FILES['facial_data']) && $_FILES['facial_data']['error'] == 0) {
        $file = $_FILES['facial_data'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png'];

        if (in_array($file_ext, $allowed_ext)) {
            $unique_file_name = uniqid('', true) . "." . $file_ext;
            $upload_path = 'teachersPics/' . $unique_file_name;

            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                $facial_data = $unique_file_name;
            } else {
                $_SESSION['error'] = 'Error uploading the image.';
                header("Location: add_teacher.php");
                exit();
            }
        } else {
            $_SESSION['error'] = 'Invalid file type.';
            header("Location: add_teacher.php");
            exit();
        }
    }

    // Check if teacher already exists
    $stmt = $conn->prepare("SELECT * FROM teachers WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['error'] = 'Teacher with this email already exists!';
        header("Location: add_teacher.php");
        exit();
    } else {
        // Insert the new teacher
        $stmt = $conn->prepare("INSERT INTO teachers (name, email, phone_number, department, facial_data) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $phone_number, $department, $facial_data);

        if ($stmt->execute()) {
            $_SESSION['success'] = 'Teacher added successfully!';
        } else {
            $_SESSION['error'] = 'Error adding teacher: ' . $conn->error;
        }
        header("Location: add_teacher.php");
        exit();
    }

    $stmt->close();
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Add Teacher</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.0/dist/tailwind.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+Bhaijaan+2:wght@400..800&display=swap" rel="stylesheet" />
    <style>
        body {
            background: #f8f9fa;
            font-family: "Baloo Bhaijaan 2", sans-serif;
        }
    </style>
</head>
<body class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <!-- Main Content -->
    <div class="flex-grow p-6 overflow-y-auto mt-20">
        <div class="bg-white shadow-lg rounded-lg p-8 max-w-3xl mx-auto">
            <h2 class="text-3xl font-semibold text-center mb-6">Add Teacher</h2>

            <!-- Form -->
            <form method="POST" action="add_teacher.php" enctype="multipart/form-data">
                <div class="space-y-6">
                    <!-- Teacher Name and Email -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="name" class="text-lg font-medium text-gray-700">Teacher Name</label>
                            <input type="text" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="name" name="name" required />
                        </div>

                        <div class="flex-1">
                            <label for="email" class="text-lg font-medium text-gray-700">Email</label>
                            <input type="email" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="email" name="email" required />
                        </div>
                    </div>

                    <!-- Phone Number and Department -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="phone_number" class="text-lg font-medium text-gray-700">Phone Number</label>
                            <input type="text" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="phone_number" name="phone_number" required />
                        </div>

                        <div class="flex-1">
                            <label for="department" class="text-lg font-medium text-gray-700">Department</label>
                            <input type="text" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="department" name="department" required />
                        </div>
                    </div>

                    <!-- Teacher Picture Upload -->
                    <div class="flex space-x-6">
                        <div class="flex-1">
                            <label for="facial_data" class="text-lg font-medium text-gray-700">Upload Teacher Picture</label>
                            <input type="file" class="w-full p-4 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 transition" id="facial_data" name="facial_data" accept="image/*" />
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="w-full bg-gray-800 text-white py-4 px-6 rounded-lg shadow-md hover:bg-black focus:outline-none focus:ring-2 focus:ring-yellow-500 transition duration-200">
                        Add Teacher
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- SweetAlert2 Toast -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        <?php if (isset($_SESSION['success'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: '<?php echo $_SESSION['success']; ?>',
                showConfirmButton: false,
                timer: 6000
            });
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: '<?php echo $_SESSION['error']; ?>',
                showConfirmButton: false,
                timer: 6000
            });
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>
    </script>
</body>
