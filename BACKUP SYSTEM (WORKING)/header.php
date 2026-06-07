<?php
// header.php - Mobile Friendly
// Session timeout configuration based on user type
// Teachers: 30 minutes (1800 seconds)
// Admin: 1 hour (3600 seconds)

// Get logged-in user information from session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check for session timeout
if (isset($_SESSION['user_id'])) {
    $current_time = time();
    $session_start_time = $_SESSION['session_start_time'] ?? $current_time;
    $user_type = $_SESSION['user_type'] ?? 'admin';
    
    // If session exists but start time isn't set, set it now
    if (!isset($_SESSION['session_start_time'])) {
        $_SESSION['session_start_time'] = $current_time;
    }
    
    // Determine timeout based on user type
    $SESSION_TIMEOUT = ($user_type === 'teacher') ? 1800 : 3600; // 30 mins for teacher, 1 hour for admin
    
    // Check if session has expired
    if (($current_time - $session_start_time) > $SESSION_TIMEOUT) {
        // Session expired, destroy it and redirect to login
        session_destroy();
        header('Location: login.php?session_expired=1');
        exit();
    }
}

// Get user info from session (set by login.php)
$user_id = $_SESSION['user_id'] ?? null;
$user_type = $_SESSION['user_type'] ?? 'admin'; // 'admin' or 'teacher'
$user_name = $_SESSION['user_name'] ?? 'User';
$user_email = $_SESSION['user_email'] ?? 'user@viotrack.com';

// Check user type
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

// Default avatar generation with initials
$initials = strtoupper(substr($user_name, 0, 1) . (strpos($user_name, ' ') !== false ? substr(explode(' ', $user_name)[1], 0, 1) : substr($user_name, 1, 1)));
$user_avatar = 'https://ui-avatars.com/api/?name=' . urlencode($initials) . '&background=8b5cf6&color=fff&size=';

// Try to fetch user's profile image from database
$profile_image = null;
if ($user_id) {
    // Only include connect.php if not already included
    if (!function_exists('executeQuery')) {
        include 'connect.php';
    }
    
    if (isset($conn) && $conn) {
        // Query the teacher table for user's image
        $image_query = "SELECT image FROM teacher WHERE id = ?";
        $stmt = $conn->prepare($image_query);
        if ($stmt) {
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                if (!empty($row['image']) && $row['image'] != 'image/default.png' && file_exists($row['image'])) {
                    $profile_image = $row['image'];
                }
            }
            $stmt->close();
        }
    }
}

// Use profile image if available, otherwise use generated avatar
$avatar_url_40 = $profile_image ? $profile_image : ($user_avatar . '40');
$avatar_url_48 = $profile_image ? $profile_image : ($user_avatar . '48');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VIOTRACK - Student Violation Tracker</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/students.css">
    <link rel="stylesheet" href="css/teacher.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/activity.css">
    <link rel="stylesheet" href="css/addrecord-modal.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        html {
            scroll-behavior: smooth;
        }
        
        body {
            position: relative;
            min-height: 100vh;
        }
        
        .main-header {
            z-index: 1000;
        }
        
        .sidebar {
            z-index: 999;
        }
        
        .sidebar-overlay {
            z-index: 998;
        }
        
        .main-content {
            z-index: 1;
        }
    </style>
</head>
<body>
    <header class="main-header">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Desktop: Logo with icon and text -->
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <img src="images/phcm-logo.png" alt="VioTrack Logo">
                </div>
                <span class="logo-text">VIOTRACK: By Perpetual Help College of Manila</span>
            </a>
            <!-- Mobile: Just the text -->
            <a href="dashboard.php" class="logo-text-mobile">VIOTRACK</a>
        </div>
        
        <div class="header-right">
            <div class="user-dropdown" id="userDropdown">
                    <button class="user-avatar" id="userAvatarBtn">
                        <img src="<?php echo htmlspecialchars($avatar_url_40); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" onerror="this.src='<?php echo htmlspecialchars($user_avatar . '40'); ?>'">
                    </button>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <div class="dropdown-header">
                            <img src="<?php echo htmlspecialchars($avatar_url_48); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" onerror="this.src='<?php echo htmlspecialchars($user_avatar . '48'); ?>'">
                            <div class="dropdown-user-info">
                                <span class="dropdown-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                                <span class="dropdown-user-email"><?php echo htmlspecialchars($user_email); ?></span>
                            </div>
                        </div>
                        <div class="dropdown-divider"></div>
                        <a href="profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                        <?php if ($isAdmin): ?>
                        <a href="activity.php" class="dropdown-item">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Activity</span>
                        </a>
                        <?php endif; ?>
                        <div class="dropdown-divider"></div>
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Sign Out</span>
                        </a>
                    </div>
                </div>
        </div>
    </header>

    <script>
        // User dropdown toggle
        document.getElementById('userAvatarBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('dropdownMenu').classList.toggle('show');
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('dropdownMenu');
            const userBtn = document.getElementById('userAvatarBtn');
            if (!userBtn.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>