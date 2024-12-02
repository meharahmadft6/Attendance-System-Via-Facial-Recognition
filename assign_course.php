<?php
require 'db.php';

// Fetch teachers
$teachers_query = "SELECT teacher_id, name FROM teachers";
$teachers_result = $conn->query($teachers_query);

// Fetch courses
$courses_query = "SELECT id, course_name FROM courses";
$courses_result = $conn->query($courses_query);

// Initialize success or error messages
$success_message = "";
$error_message = "";

// Handle form submission for assigning courses
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['course_id'])) {
    $teacher_id = $_POST['teacher'];
    $course_id = $_POST['course'];

    // Validate inputs
    if (!$teacher_id || !$course_id) {
        $error_message = "Please select both a teacher and a course.";
    } else {
        // Check if the assignment already exists
        $check_query = "SELECT * FROM assigned_courses WHERE teacher_id = ? AND course_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $teacher_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $error_message = "This course is already assigned to the selected teacher.";
        } else {
            // Insert the assignment into the database
            $insert_query = "INSERT INTO assigned_courses (teacher_id, course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("ii", $teacher_id, $course_id);

            if ($stmt->execute()) {
                $success_message = "Course assigned successfully!";
            } else {
                $error_message = "Error assigning course: " . $stmt->error;
            }
        }
    }
}
// Handle form submission for updating assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assigned_course_id']) && isset($_POST['course_id'])) {
    $teacher_id = $_POST['teacher'];
    $course_id = $_POST['course_id'];  // The course_id selected
    $assigned_course_id = $_POST['assigned_course_id'];  // The ID of the record in assigned_courses table

    // Validate the inputs
    if (!$teacher_id || !$course_id || !$assigned_course_id) {
        $error_message = "All fields must be selected.";
    } else {
        // Check if the combination already exists before updating
        $check_query = "SELECT * FROM assigned_courses WHERE teacher_id = ? AND course_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $teacher_id, $course_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Check if the same teacher is already assigned to this course
        if ($result->num_rows > 0) {
            $error_message = "This course is already assigned to the selected teacher.";
        } else {
            // Update the teacher assignment for the course using the assigned_course_id
            $update_query = "UPDATE assigned_courses SET teacher_id = ? WHERE id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("ii", $teacher_id, $assigned_course_id);  // Use assigned_course_id here

            if ($stmt->execute()) {
                $success_message = "Teacher assignment updated successfully!";
            } else {
                $error_message = "Error updating assignment: " . $stmt->error;
            }
        }
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
</head>
<body class="bg-gray-100 flex flex-col lg:flex-row">
    
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>
    
    <div class="flex-grow p-4 mt-8 lg:mt-2 lg:ml-64"> <!-- Responsive margin-left for larger screens -->
<!-- Success and error messages -->
<?php if ($success_message): ?>
    <div id="success-message" class="bg-green-100 text-green-800 p-3 rounded mb-4">
        <?php echo $success_message; ?>
    </div>
<?php endif; ?>

<?php if ($error_message): ?>
    <div id="error-message" class="bg-red-100 text-red-800 p-3 rounded mb-4">
        <?php echo $error_message; ?>
    </div>
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
    <?php if ($success_message): ?>
        hideMessage('success-message');
    <?php endif; ?>

    <?php if ($error_message): ?>
        hideMessage('error-message');
    <?php endif; ?>
</script>
   <!-- Search Bar with Animation -->
   <div class="mb-2 flex justify-between items-center ">
    <!-- Search Bar Section (Right-aligned) -->
    <div class="p-4">
        <button onclick="openModal()" class="bg-teal-500 text-white px-4 py-2 rounded mt-auto ">Assign Course</button>
    </div>
    <div class="relative w-1/3">
        <input 
            type="text" 
            id="search-bar" 
            class="w-full px-4 py-2 border border-teal-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-teal-500 transition duration-300 ease-in-out"
            placeholder="Search Teacher..."
            oninput="filterTable()"
        />
        <span class="absolute top-0 right-0 mt-3 mr-4 text-teal-400">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 18a7 7 0 100-14 7 7 0 000 14zM21 21l-4.35-4.35" />
            </svg>
        </span>
    </div>

   
</div>


    
        <!-- Assigned Courses Table -->
        <div class="overflow-x-auto mx-auto max-w-7xl bg-white shadow-md rounded-lg">
            <table class="min-w-full table-auto text-center">
                <thead class="bg-teal-400 text-white">
                    <tr>
                        <th class="py-2 px-4">Teacher Name</th>
                        <th class="py-2 px-4">Teacher Email</th>
                        <th class="py-2 px-4">Course Name</th>
                        <th class="py-2 px-4">Day</th>
                        <th class="py-2 px-4">Total Lectures</th>
                        <th class="py-2 px-4"></th>
                    </tr>
                </thead>
                <tbody>
    <?php
    // Fetch the assigned courses with teacher and course details
    $assigned_courses_query = "SELECT ac.id, t.name AS teacher_name, t.email AS teacher_email, c.course_name, c.start_date, c.end_date, c.start_time, c.end_time, c.day, c.total_lectures, ac.teacher_id
        FROM assigned_courses ac
        JOIN teachers t ON ac.teacher_id = t.teacher_id
        JOIN courses c ON ac.course_id = c.id";
    $assigned_courses_result = $conn->query($assigned_courses_query);

    while ($row = $assigned_courses_result->fetch_assoc()) {
    ?>
        <tr class="teacher-row"> <!-- Add the class here -->
            <td class="py-2 px-4"><?php echo $row['teacher_name']; ?></td>
            <td class="py-2 px-4"><?php echo $row['teacher_email']; ?></td>
            <td class="py-2 px-4"><?php echo $row['course_name']; ?></td>
            <td class="py-2 px-4"><?php echo $row['day']; ?></td>
            <td class="py-2 px-4"><?php echo $row['total_lectures']; ?></td>
            <td class="py-2 px-4">
                <button onclick="openUpdateModal(<?php echo $row['id']; ?>, <?php echo $row['teacher_id']; ?>)" class="bg-teal-500 text-white px-4 py-2 rounded">Update Course</button>
            </td>
        </tr>
    <?php } ?>
</tbody>

            </table>
        </div>

        <!-- Assign Course Modal -->
        <div id="assign-course-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
            <div class="bg-white p-6 rounded-lg w-full max-w-sm">
                <h2 class="text-2xl font-bold mb-4">Assign Course</h2>
                <form method="POST" action="">
                    <!-- Teacher Dropdown -->
                    <div class="mb-4">
                        <label for="teacher" class="block text-gray-700">Select Teacher</label>
                        <select id="teacher" name="teacher" class="w-full px-4 py-2 border rounded" required>
                            <option value="" disabled selected>Select a teacher</option>
                            <?php while ($teacher = $teachers_result->fetch_assoc()): ?>
                                <option value="<?php echo $teacher['teacher_id']; ?>"><?php echo $teacher['name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <!-- Course Dropdown -->
                    <div class="mb-4">
                        <label for="course" class="block text-gray-700">Select Course</label>
                        <select id="course" name="course" class="w-full px-4 py-2 border rounded" required>
                            <option value="" disabled selected>Select a course</option>
                            <?php while ($course = $courses_result->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo $course['course_name']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <div class="flex justify-between">
                        <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded" onclick="closeModal()">Cancel</button>
                        <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded">Assign</button>
                    </div>
                </form>
            </div>
        </div>

  <!-- Update Assign Course Modal -->
<div id="update-assign-course-modal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
    <div class="bg-white p-6 rounded-lg w-full max-w-sm">
        <h2 class="text-2xl font-bold mb-4">Update Course Assignment</h2>
        <form method="POST" action="">

            <!-- Hidden Field to store the Course ID (for course in courses table) -->
            <input type="hidden" name="course_id" id="course_id" value="">
            
            <!-- Hidden Field to store the Assigned Course ID (for record in assigned_courses table) -->
            <input type="hidden" name="assigned_course_id" id="assigned_course_id" value="">

            <!-- Teacher Dropdown (Only Teacher can be changed) -->
            <div class="mb-4">
                <label for="teacher" class="block text-gray-700">Select Teacher</label>
                <select id="teacher" name="teacher" class="w-full px-4 py-2 border rounded" required>
                    <option value="" disabled selected>Select a teacher</option>
                    <?php
                    // Reset the teachers query for update modal
                    $teachers_result->data_seek(0); // Reset the result pointer
                    while ($teacher = $teachers_result->fetch_assoc()): ?>
                        <option value="<?php echo $teacher['teacher_id']; ?>">
                            <?php echo $teacher['name']; ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>

            <div class="flex justify-between">
                <button type="button" class="bg-gray-500 text-white px-4 py-2 rounded" onclick="closeUpdateModal()">Cancel</button>
                <button type="submit" class="bg-teal-500 text-white px-4 py-2 rounded">Update</button>
            </div>
        </form>
    </div>
</div>


         </div>

    <script>
        // Open the Assign Course Modal
        function openModal() {
            document.getElementById('assign-course-modal').classList.remove('hidden');
        }

        // Close the Assign Course Modal
        function closeModal() {
            document.getElementById('assign-course-modal').classList.add('hidden');
        }

// Open the update modal with pre-selected values
function openUpdateModal(assignedCourseId, courseId, teacherId) {
    // Set the correct course_id and assigned_course_id in the hidden input field
    document.getElementById('course_id').value = courseId;  // course_id of the course
    document.getElementById('assigned_course_id').value = assignedCourseId;  // ID of the assignment in the assigned_courses table
    console.log('Course ID in hidden field:', document.getElementById('course_id').value);

    // Set the selected teacher in the teacher dropdown
    const teacherSelect = document.getElementById('teacher');
    for (let option of teacherSelect.options) {
        if (option.value == teacherId) {
            option.selected = true;
        }
    }

    // Open the modal
    document.getElementById('update-assign-course-modal').classList.remove('hidden');
}

// Close the update modal
function closeUpdateModal() {
    document.getElementById('update-assign-course-modal').classList.add('hidden');
}
function filterTable() {
            const searchTerm = document.getElementById("search-bar").value.toLowerCase();
            const rows = document.querySelectorAll(".teacher-row");

            rows.forEach(row => {
                const teacherName = row.cells[0].textContent.toLowerCase();
                const teacherEmail = row.cells[1].textContent.toLowerCase();

                if (teacherName.includes(searchTerm) || teacherEmail.includes(searchTerm)) {
                    row.style.display = "";
                } else {
                    row.style.display = "none";
                }
            });
        }

    </script>
    
</body>
</html>
