<!-- Sidebar -->
<div class="fixed inset-y-0 left-0 w-64 bg-black text-white z-50 transform -translate-x-full md:translate-x-0 transition-transform duration-300 ease-in-out" id="sidebar">
    <div class="flex flex-col h-full">
        <!-- Logo -->
        <div class="flex items-center justify-center py-4 mr-3">
            <img src="../assets/sidebar.png" alt="Logo" class="h-64 rounded">
        </div>

        <!-- Navigation Links -->
        <nav class="flex-1 mt-4">
            <ul class="space-y-2">
                <li>
                    <a href="teacher_dashboard.php" class="block py-2 px-4 rounded hover:bg-white hover:text-black transition <?php echo (basename($_SERVER['PHP_SELF']) == 'teacher_dashboard.php') ? 'bg-white text-black' : ''; ?>">
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="teacher_courses.php" class="block py-2 px-4 rounded hover:bg-white hover:text-black transition <?php echo (basename($_SERVER['PHP_SELF']) == 'teacher_courses.php') ? 'bg-white text-black' : ''; ?>">
                        Courses
                    </a>
                </li>
                <li>
                    <a href="view_teacher_enrolled_students.php" class="block py-2 px-4 rounded hover:bg-white hover:text-black transition <?php echo (basename($_SERVER['PHP_SELF']) == 'view_teacher_enrolled_students.php') ? 'bg-white text-black' : ''; ?>">
                        Enrolled Students
                    </a>
                </li>
                <li>
                    <a href="teacher_notification.php" class="block py-2 px-4 rounded hover:bg-white hover:text-black transition <?php echo (basename($_SERVER['PHP_SELF']) == 'teacher_notification.php') ? 'bg-white text-black' : ''; ?>">
                        Send Notifications
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Logout Button -->
        <div class="p-4">
            <button onclick="location.href='teacher_logout.php'" class="w-full bg-white text-black py-2 px-4 rounded hover:bg-gray-200 transition">
                Logout
            </button>
        </div>
    </div>
</div>

<!-- Mobile Navbar Toggle Button -->
<button class="fixed top-4 left-4 z-50 text-white bg-black p-2 rounded md:hidden" id="menu-button">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay for Sidebar -->
<div class="fixed inset-0 bg-black bg-opacity-50 hidden z-40" id="sidebar-overlay"></div>

<!-- Tailwind Script and Style -->
<script src="https://cdn.tailwindcss.com"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">

<script>
    const sidebar = document.getElementById('sidebar');
    const menuButton = document.getElementById('menu-button');
    const sidebarOverlay = document.getElementById('sidebar-overlay');

    // Toggle sidebar on mobile
    menuButton.addEventListener('click', () => {
        sidebar.classList.toggle('-translate-x-full');  // Slide in/out the sidebar
        sidebarOverlay.classList.toggle('hidden');     // Toggle the overlay visibility
    });

    // Close sidebar when clicking on overlay
    sidebarOverlay.addEventListener('click', () => {
        sidebar.classList.add('-translate-x-full');  // Hide sidebar
        sidebarOverlay.classList.add('hidden');     // Hide overlay
    });
</script>
