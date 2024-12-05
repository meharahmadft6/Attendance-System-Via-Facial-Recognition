<?php
session_start();
require 'db.php';

// Include the FPDF library
require_once('../fpdf.php');

// Grade calculation function
function calculateGrade($totalMarks) {
    if ($totalMarks >= 85) {
        return 'A';
    } elseif ($totalMarks >= 80 && $totalMarks < 85) {
        return 'A-';
    } elseif ($totalMarks >= 75 && $totalMarks < 80) {
        return 'B';
    } elseif ($totalMarks >= 70 && $totalMarks < 75) {
        return 'B-';
    } elseif ($totalMarks >= 65 && $totalMarks < 70) {
        return 'C';
    } elseif ($totalMarks >= 60 && $totalMarks < 65) {
        return 'C-';
    } else {
        return 'F';
    }
}
ob_start(); // Start output buffering
error_reporting(0); // Disable all errors
ini_set('display_errors', '0'); // Do not display errors

if (!isset($_SESSION['student_id'])) {
    header("Location: ../login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : die("Course ID not provided.");

// Fetch the course and grades for the student
$query = "
    SELECT g.*, c.course_name
    FROM grades g
    JOIN courses c ON g.course_id = c.id
    WHERE g.student_id = ? AND g.course_id = ?";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Error preparing grade query: " . $conn->error);
}

$stmt->bind_param("ii", $student_id, $course_id);
$stmt->execute();
$result = $stmt->get_result();

$totalMarks = 0;
$courseCount = 0;
$allGrades = [];

if ($result->num_rows > 0) {
    $pdf = new FPDF();
    $pdf->AddPage();

    // Set background color to black
    $pdf->SetFillColor(0, 0, 0); // Black
    $pdf->Rect(0, 0, 210, 297, 'F'); // Fill the entire page

    // Set font for the title (White text)
    $pdf->SetFont('Arial', 'B', 24);
    $pdf->SetTextColor(255, 255, 255); // White
    $pdf->Cell(0, 40, 'FACE TENDANCE', 0, 1, 'C'); // Big centered title

    // Logo (optional, adjust path as needed)
    $pdf->Image('../assets/sidebar.png', 10, 10, 30); // Add a logo at the top-left

    // Line separator (White color)
    $pdf->SetLineWidth(0.5);
    $pdf->Line(10, 50, 200, 50); // Horizontal line under title
    $pdf->Ln(10); // Line break

    // Set font for data (White text)
    $pdf->SetFont('Arial', '', 16);

    while ($grade = $result->fetch_assoc()) {
        if ($grade === null || !isset($grade['course_name'])) {
            continue; // Skip if no grade data or missing course name
        }

        // Add Course Name
        $pdf->Cell(0, 10, 'Course: ' . htmlspecialchars($grade['course_name']), 0, 1, 'L');
        
        // Line separator (White color)
        $pdf->SetLineWidth(0.5);
        $pdf->Line(10, 50, 200, 50); // Horizontal line under title
        $pdf->Ln(10); // Line break

        // Start table for grades
        $pdf->Ln(10);
        $pdf->SetFont('Arial', 'B', 12);
        $pdf->Cell(45, 10, 'Quiz 1', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Quiz 2', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Quiz 3', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Quiz 4', 1, 1, 'C', true);

        // Data row for quiz scores
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['quiz_1']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['quiz_2']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['quiz_3']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['quiz_4']) : 'N/A'), 1, 1, 'C');

        // Add assignment grades in the same table format
        $pdf->Ln(5);
        $pdf->Cell(45, 10, 'Assignment 1', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Assignment 2', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Assignment 3', 1, 0, 'C', true);
        $pdf->Cell(45, 10, 'Assignment 4', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['assignment_1']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['assignment_2']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['assignment_3']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['assignment_4']) : 'N/A'), 1, 1, 'C');

        // Add exam grades in the same table format
        $pdf->Ln(5);
        $pdf->Cell(45, 10, 'Midterm', 1, 0, 'C', true);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['mid']) : 'N/A'), 1, 0, 'C');
        $pdf->Cell(45, 10, 'Final', 1, 0, 'C', true);
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(45, 10, ($grade['status'] === 'show' ? htmlspecialchars($grade['final']) : 'N/A'), 1, 1, 'C');

        // Calculate total marks and grade
        $courseTotal = $grade['total_quiz'] + $grade['total_assignment'] + $grade['total_mid'] + $grade['total_final'];
        $totalMarks += $courseTotal;
        $courseCount++;

        $gradeLetter = calculateGrade($courseTotal);
        $allGrades[] = $gradeLetter;

        // Display the calculated grade for the course
        $pdf->Ln(10);
        // Check if the status is 'show' before displaying CGPA and Final Grade
if ($grade['status'] === 'show' && $grade['final'] > 1) {
    // Display CGPA
    $pdf->Cell(0, 10, 'Course Grade: ' . $gradeLetter, 0, 1, 'C');
    $pdf->Cell(0, 10, 'CGPA: ' . ($grade['cgpa']), 0, 1, 'C');
    $pdf->Cell(0, 10, 'Final Grade: ' . calculateGrade($totalMarks), 0, 1, 'C');
    
} else {
    $pdf->Cell(0, 10, 'Course Grade: N/A', 0, 1, 'C');
    // If the status is 'hide', don't show CGPA and Final Grade

}

    }

    // Calculate overall CGPA (for example: average of grade points)
    $gradePoints = ['A' => 4.0, 'A-' => 3.7, 'B+' => 3.3, 'B' => 3.0, 'C' => 2.0, 'C-' => 1.7, 'F' => 0.0];
    $totalGradePoints = 0;

    foreach ($allGrades as $gradeLetter) {
        $totalGradePoints += $gradePoints[$gradeLetter];
    }

    $cgpa = $totalGradePoints / $courseCount;

    // Display CGPA
    $pdf->Ln(10);
  // Check if the status is 'show' before displaying CGPA and Final Grade

 



    // Output the PDF
    $pdf->Output('D', 'grade_result_' . htmlspecialchars($grade['course_name']) . '.pdf');
    exit();
} else {
    echo "No grades found for this student in this course.";
}
?>
