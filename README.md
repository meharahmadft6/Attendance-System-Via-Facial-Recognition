Project Overview
This project is an Attendance System with Facial Recognition designed to streamline the process of recording attendance. It leverages modern technologies, including facial recognition, to authenticate users and manage attendance efficiently. The application is built using HTML, CSS, JavaScript, PHP, and Python, with key libraries like DeepFace, OpenCV, Pillow (PIL), and Flask for facial recognition and backend functionality.
The system includes three types of users:
1.Admin: Configures the system, manages users, and defines attendance schedules.
2.Teachers: Log in using facial recognition and can take attendance for students.
3.Students: Log in using a username and password to check their attendance records.

Features
1. Facial Recognition for Secure Login
Teachers log in by scanning their faces through the webcam.
Students’ attendance is recorded only after facial recognition is matched with the database.


2. Role-Based Authentication
Admin: Configures system settings, manages user roles, and defines attendance schedules.
Teacher: Takes attendance for students and monitors records.
Student: Views attendance records after logging in with credentials.
3. Time-Sensitive Attendance
Attendance can only be recorded at a specific time and date as determined by the admin or teacher.
4. Web-Based Interface
Built with HTML, CSS, and JavaScript for a user-friendly experience.
Responsive and intuitive design for seamless navigation.

System Configuration
Frontend Technologies
HTML: For structuring the web pages.
CSS: For styling and making the UI visually appealing.
JavaScript: For client-side interactions.
Backend Technologies
PHP: Handles user authentication and connects the frontend with the database.
Python (Flask): Provides API endpoints for facial recognition and handles image processing.
Key Python Libraries
DeepFace: Performs facial recognition and matching.
OpenCV (cv2): Captures images from webcams and processes them for analysis.
Pillow (PIL): Handles image manipulation.
Database
MySQL: Used to store user credentials, attendance records, and facial recognition data.

System Workflow
1. Admin Configuration
The admin logs in with their credentials.
Admin can:
Add or remove students and teachers.
Set attendance schedules (specific day and time).
Monitor attendance reports.
2. Teacher Login
Teachers authenticate using facial recognition.
After successful login:
Teachers can mark attendance for students.
Teachers can define the time window for student attendance.
3. Student Login
Students log in with a username and password.
Once logged in:
Students can view their attendance records.
Attendance can only be recorded during the predefined time and day using facial recognition.

Implementation Details
Backend Configuration
1.Facial Recognition Setup:
oFacial data is stored as embeddings using the DeepFace library.
oDuring recognition, a real-time image is compared with the stored embeddings.
oAuthentication is granted if the similarity score exceeds a threshold.

2.Image Capture:
oOpenCV accesses the webcam to capture real-time images for authentication.
oImages are preprocessed using Pillow (PIL) for better accuracy.


3.API Endpoints (Flask):
/login_teacher: Validates teacher login via facial recognition.
/mark_attendance: Verifies the student’s face and records attendance if the time condition is met.

How to Run the Project
Prerequisites
1.Python (3.7 or later).
2.MySQL server.
3.Web server (e.g., Apache).
4.Required Python libraries:
pip install flask opencv-python-headless deepface pillow
Steps to Configure
1.Set up the database:
oImport the provided SQL file (attendance_system.sql) into MySQL.
2.Configure the Flask Server:
oNavigate to the backend directory.
oRun the Flask server:
bash
Copy code
python app.py
3.Launch the Web Interface:
oDeploy the PHP files to your web server directory.
oOpen the application in a browser (e.g., http://localhost/attendance-system).
4.Test the System:
oAdd admin, teacher, and student profiles.
oTest teacher login via facial recognition.
oTest student attendance within the scheduled time.

Future Enhancements
Add support for mobile-based facial recognition.
Implement notifications for attendance updates.
Introduce advanced reporting features for attendance analytics.


This project represents a step toward automating the traditional attendance process with modern technology. It ensures accuracy, efficiency, and security while offering a user-friendly experience.
