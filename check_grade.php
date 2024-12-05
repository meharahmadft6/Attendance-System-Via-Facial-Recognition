<?php
session_start();
require 'db.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch the courses the student is enrolled in
$query = "
    SELECT g.*, c.course_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error preparing grade query: " . $conn->error);
}

$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades_result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Grades</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<?php include 'sidebar.php'; ?>
<body class="bg-gray-100 font-sans antialiased">
    <div class="flex">
        <div class="flex-1 ml-64 mt-10 px-4">  <!-- Ensure margin left is set to account for sidebar -->
            <h1 class="text-4xl font-bold text-center text-gray-800 mb-8">Your Grades</h1>

            <!-- Accordion Container -->
            <?php while ($grade = $grades_result->fetch_assoc()) { ?>
            <div class="bg-white shadow-md rounded-lg mb-6 overflow-hidden">
                <!-- Accordion Header -->
                <div class="cursor-pointer p-4 flex justify-between items-center bg-black text-white"
                    onclick="toggleAccordion('accordion-<?= $grade['id'] ?>')">
                    <h2 class="text-xl font-bold"><?= htmlspecialchars($grade['course_name']) ?></h2>
                    <svg xmlns="http://www.w3.org/2000/svg" 
                         fill="none" 
                         viewBox="0 0 24 24" 
                         stroke-width="2" 
                         stroke="currentColor" 
                         class="w-6 h-6 transition-transform duration-300 transform"
                         id="icon-accordion-<?= $grade['id'] ?>">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <!-- Accordion Content -->
                <div id="accordion-<?= $grade['id'] ?>" class="max-h-0 overflow-hidden transition-all duration-700 ease-in-out p-4 bg-gray-50 rounded-lg">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div class="p-4 bg-white shadow rounded-lg">
                            <h3 class="font-semibold text-gray-600">Quiz Grades</h3>
                            <p>Quiz 1: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['quiz_1']) : '0.0' ?></p>
                            <p>Quiz 2: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['quiz_2']) : '0.0' ?></p>
                            <p>Quiz 3: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['quiz_3']) : '0.0' ?></p>
                            <p>Quiz 4: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['quiz_4']) : '0.0' ?></p>
                            <p class="font-bold">Total Quiz: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['total_quiz']) : '0.0' ?></p>
                        </div>
                        <div class="p-4 bg-white shadow rounded-lg">
                            <h3 class="font-semibold text-gray-600">Assignment Grades</h3>
                            <p>Assignment 1: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['assignment_1']) : '0.0' ?></p>
                            <p>Assignment 2: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['assignment_2']) : '0.0' ?></p>
                            <p>Assignment 3: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['assignment_3']) : '0.0' ?></p>
                            <p>Assignment 4: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['assignment_4']) : '0.0' ?></p>
                            <p class="font-bold">Total Assignments: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['total_assignment']) : '0.0' ?></p>
                        </div>
                        <div class="p-4 bg-white shadow rounded-lg">
                            <h3 class="font-semibold text-gray-600">Exams</h3>
                            <p>Midterm: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['mid']) : '0.0' ?></p>
                            <p>Final: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['final']) : '0.0' ?></p>
                            <p class="font-bold">Total Midterm: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['total_mid']) : '0.0' ?></p>
                            <p class="font-bold">Total Final: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['total_final']) : '0.0' ?></p>
                        </div>
                        <div class="p-4 bg-white shadow rounded-lg">
                            <h3 class="font-semibold text-gray-600">Overall</h3>
                            <p>CGPA: <?= $grade['status'] === 'show' ? htmlspecialchars($grade['cgpa']) : '0.0' ?></p>
                        </div>
                    </div>
                    <div class="mt-4 text-right">
                        <a href="download_result.php?course_id=<?= $grade['course_id'] ?>" 
                           class="bg-black hover:bg-teal-500 text-white font-bold py-2 px-4 rounded">
                            Download Result
                        </a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>

    <script>
        function toggleAccordion(id) {
            const content = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            const currentMaxHeight = content.style.maxHeight;
            content.style.transition = 'max-height 1s ease-in-out';  // Smooth transition for max-height
            content.classList.toggle('max-h-0');
            if (currentMaxHeight === '0px' || !currentMaxHeight) {
                content.style.maxHeight = content.scrollHeight + 'px'; // Expand to the content height
            } else {
                content.style.maxHeight = '0'; // Collapse the content
            }
            icon.classList.toggle('rotate-180');
        }
    </script>
</body>

</html>
