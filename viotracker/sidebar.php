<?php
// sidebar.php - Mobile Friendly
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
$managementPages = ['management', 'violation-record', 'violation'];
$isManagementActive = in_array($currentPage, $managementPages);

// Check user type
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');
?>

<!-- Mobile Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<aside class="sidebar" id="sidebar">
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <li class="nav-item <?php echo $currentPage == 'dashboard' ? 'active' : ''; ?>" data-title="Dashboard">
                <a href="dashboard.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </li>
        </ul>
        
        <div class="nav-section">
            <span class="nav-section-title">MANAGEMENT</span>
            <ul class="nav-list">
                <li class="nav-item has-submenu <?php echo $isManagementActive ? 'active open' : ''; ?>" data-title="Management">
                    <a href="#" class="nav-link submenu-toggle">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span class="nav-text">Management</span>
                        <i class="fas fa-chevron-down nav-arrow"></i>
                    </a>
                    <ul class="submenu <?php echo $isManagementActive ? 'show' : ''; ?>">
                        <li class="submenu-item <?php echo $currentPage == 'violation-record' ? 'active' : ''; ?>">
                            <a href="violation-record.php" class="submenu-link">Violation Record</a>
                        </li>
                        <?php if ($isAdmin): ?>
                        <li class="submenu-item <?php echo $currentPage == 'violation' ? 'active' : ''; ?>">
                            <a href="violation.php" class="submenu-link">Violation</a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item <?php echo $currentPage == 'teacher' ? 'active' : ''; ?>" data-title="Teacher">
                    <a href="teacher.php" class="nav-link">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span class="nav-text">Teacher</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item <?php echo $currentPage == 'student' || $currentPage == 'students' ? 'active' : ''; ?>" data-title="Students">
                    <a href="students.php" class="nav-link">
                        <i class="fas fa-user-graduate"></i>
                        <span class="nav-text">Students</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="nav-section">
            <span class="nav-section-title">SETTINGS</span>
            <ul class="nav-list">
                <li class="nav-item <?php echo $currentPage == 'profile' ? 'active' : ''; ?>" data-title="Profile">
                    <a href="profile.php" class="nav-link">
                        <i class="fas fa-user-circle"></i>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
                <?php if ($isAdmin): ?>
                <li class="nav-item <?php echo $currentPage == 'activity' ? 'active' : ''; ?>" data-title="Activity">
                    <a href="activity.php" class="nav-link">
                        <i class="fas fa-calendar-alt"></i>
                        <span class="nav-text">Activity</span>
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item" data-title="Sign Out">
                    <a href="logout.php" class="nav-link">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-text">Sign Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
</aside>

<script>
// Global sidebar functions
function isMobile() {
    return window.innerWidth <= 768;
}

function openSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.add('active');
    if (overlay) overlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    if (sidebar) sidebar.classList.remove('active');
    if (overlay) overlay.classList.remove('show');
    document.body.style.overflow = '';
}

function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (isMobile()) {
        if (sidebar.classList.contains('active')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    } else {
        // Desktop: collapse/expand
        sidebar.classList.toggle('collapsed');
        if (mainContent) {
            mainContent.classList.toggle('expanded');
        }
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Attach toggle to hamburger menu
    const menuToggle = document.getElementById('menuToggle');
    if (menuToggle) {
        menuToggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            toggleSidebar();
        });
    }
    
    // Handle submenu toggles
    const submenuToggles = document.querySelectorAll('.submenu-toggle');
    submenuToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            const parentItem = this.closest('.nav-item');
            const submenu = parentItem.querySelector('.submenu');
            
            parentItem.classList.toggle('open');
            if (submenu) {
                submenu.classList.toggle('show');
            }
        });
    });
    
    // Close sidebar when clicking nav links on mobile
    const navLinks = document.querySelectorAll('.sidebar .nav-link:not(.submenu-toggle), .sidebar .submenu-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function() {
            if (isMobile()) {
                closeSidebar();
            }
        });
    });
    
    // Close on resize to desktop
    window.addEventListener('resize', function() {
        if (!isMobile() && document.getElementById('sidebar').classList.contains('active')) {
            closeSidebar();
        }
    });
});
</script>