<?php
// Include your database connection
require 'db.php';

// Function to calculate the total number of lectures
function calculateLectures($start_date, $end_date, $day) {
    // Convert dates to DateTime objects
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);

    // If the start date is after the end date, return 0 lectures
    if ($start > $end) {
        return 0;
    }

    // Day mapping (1=Monday, 2=Tuesday, ..., 7=Sunday)
    $day_map = ['Monday' => 1, 'Tuesday' => 2, 'Wednesday' => 3, 'Thursday' => 4, 'Friday' => 5, 'Saturday' => 6, 'Sunday' => 7];

    // Get the numerical representation of the day
    $target_day = $day_map[$day];

    // Get the numerical representation of the start date's day
    $start_day = (int)$start->format('N');

    // Calculate the difference between the target day and the start day
    $diff = $target_day - $start_day;
    
    // If the target day is before the start date's day, adjust by adding 7 days
    if ($diff < 0) {
        $diff += 7;
    }

    // Set the start date to the first occurrence of the target day
    $start->modify("+$diff day");

    // If the first occurrence of the target day is after the end date, return 0 lectures
    if ($start > $end) {
        return 0;
    }

    // Calculate the total number of weeks between the start and end dates
    $interval = $start->diff($end);
    $total_weeks = floor($interval->days / 7);

    // Add 1 for the first occurrence of the target day
    $total_lectures = $total_weeks + 1;

    return $total_lectures;
}

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get data from form
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $day = $_POST['day'];

    // Calculate the total number of lectures
    $total_lectures = calculateLectures($start_date, $end_date, $day);

    // Handle potential errors or large results
    if (is_numeric($total_lectures)) {
        // Prepare SQL statement for updating the course
        $stmt = $conn->prepare("UPDATE courses SET course_name=?, start_date=?, end_date=?, start_time=?, end_time=?, day=?, total_lectures=? WHERE id=?");
        $stmt->bind_param("ssssssii", $course_name, $start_date, $end_date, $start_time, $end_time, $day, $total_lectures, $course_id);
      
        if ($stmt->execute()) {
            // If update is successful, set session message for success
            $_SESSION['message'] = [
                'type' => 'success',
                'text' => 'Course updated successfully!'
            ];
        } else {
            // If update fails, set session message for error
            $_SESSION['message'] = [
                'type' => 'error',
                'text' => 'Error updating course. Please try again.'
            ];
        }

        $stmt->close();
    } else {
        // Handle the case where lecture calculation failed or took too long
        echo $total_lectures; // This will show either an error message or "Too many lectures"
    }

    $conn->close();
              // Redirect back to the course view page
              header('Location: show_courses.php');
}
?>
