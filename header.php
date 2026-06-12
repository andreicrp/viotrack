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
        // Query based on user type to get correct profile image
        if ($user_type === 'teacher') {
            // For teachers, query the teacher table first
            $image_query = "SELECT image FROM teacher WHERE id = ?";
            $stmt = $conn->prepare($image_query);
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (!empty($row['image']) && $row['image'] != 'image/default.jpg') {
                        $profile_image = $row['image'];
                    }
                }
                $stmt->close();
            }
        } else {
            // For admins, query the admin table
            $image_query = "SELECT image FROM admin WHERE id = ?";
            $stmt = $conn->prepare($image_query);
            if ($stmt) {
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (!empty($row['image']) && $row['image'] != 'image/default.png') {
                        $profile_image = $row['image'];
                    }
                }
                $stmt->close();
            }
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
    <meta name="description" content="VioTrack - Student Violation Tracker for Perpetual Help College of Manila">
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">

    <!-- Premium Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <!-- Design System -->
    <link rel="stylesheet" href="css/global.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="css/footer.css">
    <link rel="stylesheet" href="css/students.css">
    <link rel="stylesheet" href="css/teacher.css">
    <link rel="stylesheet" href="css/profile.css">
    <link rel="stylesheet" href="css/activity.css">
    <link rel="stylesheet" href="css/addrecord-modal.css">
    <style>
        /* Inline critical overrides */
        .main-content { z-index: 1; }
    </style>
</head>
<body>
    <header class="main-header" id="mainHeader">
        <div class="header-left">
            <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu">
                <i class="fas fa-bars"></i>
            </button>
            <!-- Desktop: Logo with icon and text -->
            <a href="dashboard.php" class="logo">
                <div class="logo-icon">
                    <img src="images/phcm-logo.png" alt="VioTrack Logo">
                </div>
                <div class="logo-text">
                    VIOTRACK
                    <span>Perpetual Help College of Manila</span>
                </div>
            </a>
            <!-- Mobile: Just the text -->
            <a href="dashboard.php" class="logo-text-mobile">VIOTRACK</a>
        </div>
        
        <div class="header-right">
            <!-- Notification Bell -->
            <div class="notification-container" id="notificationContainer">
                <button class="notification-btn" id="notificationBtn" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge" id="notifBadge" style="display:none;">0</span>
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h4><i class="fas fa-bell" style="margin-right:8px;color:#6366f1;"></i>Notifications</h4>
                        <button class="close-btn" onclick="document.getElementById('notificationDropdown').classList.remove('show')">&times;</button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-empty">
                            <i class="fas fa-bell-slash"></i>
                            All caught up!
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="user-dropdown" id="userDropdown">
                <button class="user-avatar" id="userAvatarBtn" title="<?php echo htmlspecialchars($user_name); ?>">
                    <img src="<?php echo htmlspecialchars($avatar_url_40); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" onerror="this.src='<?php echo htmlspecialchars($user_avatar . '40'); ?>'">
                </button>
                <div class="dropdown-menu" id="dropdownMenu">
                    <div class="dropdown-header">
                        <img src="<?php echo htmlspecialchars($avatar_url_48); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" onerror="this.src='<?php echo htmlspecialchars($user_avatar . '48'); ?>'">
                        <div class="dropdown-user-info">
                            <span class="dropdown-user-name"><?php echo htmlspecialchars($user_name); ?></span>
                            <span class="dropdown-user-email"><?php echo htmlspecialchars($user_email); ?></span>
                            <span class="dropdown-role-badge">
                                <i class="fas fa-<?php echo $isAdmin ? 'crown' : 'chalkboard-teacher'; ?>"></i>
                                <?php echo ucfirst($user_type ?? 'Admin'); ?>
                            </span>
                        </div>
                    </div>
                    <div class="dropdown-divider"></div>
                    <a href="profile.php" class="dropdown-item">
                        <i class="fas fa-user-circle"></i>
                        <span>My Profile</span>
                    </a>
                    <?php if ($isAdmin): ?>
                    <a href="activity.php" class="dropdown-item">
                        <i class="fas fa-chart-line"></i>
                        <span>Activity Log</span>
                    </a>
                    <?php endif; ?>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout-item">
                        <i class="fas fa-arrow-right-from-bracket"></i>
                        <span>Sign Out</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Offline Violation Tracking Scripts -->
    <script src="js/geolocation-handler.js"></script>
    <script src="js/offline-violations.js"></script>

    <script>
        // ── Header scroll effect ──
        const header = document.getElementById('mainHeader');
        window.addEventListener('scroll', () => {
            header.classList.toggle('scrolled', window.scrollY > 10);
        }, { passive: true });

        // ── User dropdown ──
        document.getElementById('userAvatarBtn').addEventListener('click', function(e) {
            e.stopPropagation();
            const menu = document.getElementById('dropdownMenu');
            const notif = document.getElementById('notificationDropdown');
            notif.classList.remove('show');
            menu.classList.toggle('show');
        });

        // ── Notification dropdown ──
        const notifBtn = document.getElementById('notificationBtn');
        if (notifBtn) {
            notifBtn.addEventListener('click', function(e) {
                e.stopPropagation();
                const drop = document.getElementById('notificationDropdown');
                const menu = document.getElementById('dropdownMenu');
                menu.classList.remove('show');
                drop.classList.toggle('show');
            });
        }

        // ── Close dropdowns on outside click ──
        document.addEventListener('click', function(e) {
            const menu = document.getElementById('dropdownMenu');
            const notifDrop = document.getElementById('notificationDropdown');
            const userBtn = document.getElementById('userAvatarBtn');
            const notifBtn = document.getElementById('notificationBtn');

            if (menu && !userBtn?.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('show');
            }
            if (notifDrop && !notifBtn?.contains(e.target) && !notifDrop.contains(e.target)) {
                notifDrop.classList.remove('show');
            }
        });

        // ── Load notifications ──
        (function loadNotifications() {
            fetch('php/get-notifications.php')
                .then(r => r.json())
                .then(data => {
                    if (!data || !data.notifications) return;
                    const list = document.getElementById('notificationList');
                    const badge = document.getElementById('notifBadge');
                    if (data.notifications.length === 0) return;
                    badge.textContent = data.notifications.length;
                    badge.style.display = 'flex';
                    list.innerHTML = data.notifications.map(n => `
                        <div class="notification-item">
                            <div class="notification-icon violation"><i class="fas fa-exclamation-triangle"></i></div>
                            <div class="notification-content">
                                <div class="notification-title">${n.title || 'Violation Alert'}</div>
                                <div class="notification-desc">${n.message || ''}</div>
                                <div class="notification-time">${n.time || ''}</div>
                            </div>
                        </div>`).join('');
                })
                .catch(() => {});
        })();
    </script>