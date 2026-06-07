<?php
/**
 * Authentication and Authorization Check
 * Include this file at the beginning of protected pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Function to check if user is authenticated
function requireLogin() {
    if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
        header('Location: login.php?error=not_logged_in');
        exit();
    }
}

// Function to check if user is admin
function requireAdmin() {
    requireLogin();
    
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        header('Location: dashboard.php?error=unauthorized');
        exit();
    }
}

// Function to check if user is admin or teacher
function requireAdminOrTeacher() {
    requireLogin();
    
    $userType = $_SESSION['user_type'] ?? '';
    if ($userType !== 'admin' && $userType !== 'teacher') {
        header('Location: dashboard.php?error=unauthorized');
        exit();
    }
}

// Function to get current user ID safely
function getCurrentUserId() {
    return isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
}

// Function to get current user type
function getCurrentUserType() {
    return isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Function to check if user is admin
function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

// Function to check if user is teacher
function isTeacher() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher';
}
?>
