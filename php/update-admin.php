<?php
// update_admin.php
session_start();

// Require login
require_once '../auth_check.php';
requireLogin();

// Config
$userId = $_SESSION['user_id'] ?? null;
$userType = $_SESSION['user_type'] ?? 'admin';

if (!$userId) {
    redirect_with(['error' => 'not_logged_in']);
}

$uploadDir = __DIR__ . '/image/'; // must be writable
$maxFileSize = 2 * 1024 * 1024; // 2 MB
$allowedExt = ['jpg','jpeg','png','gif'];

// Helper: redirect back with message
function redirect_with($params = []) {
    $qs = http_build_query($params);
    header("Location: profile.php" . ($qs ? "?$qs" : ""));
    exit;
}

// Connect to DB
require_once('../connect.php');
$mysqli = $conn;
if (!$mysqli) {
    redirect_with(['error' => 'db']);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect_with(['error' => 'invalid_method']);
}

// Basic sanitize
$first_name  = isset($_POST['first_name'])  ? trim($_POST['first_name'])  : '';
$middle_name = isset($_POST['middle_name']) ? trim($_POST['middle_name']) : '';
$last_name   = isset($_POST['last_name'])   ? trim($_POST['last_name'])   : '';
$email       = isset($_POST['email'])       ? trim($_POST['email'])       : '';
$new_pass    = isset($_POST['new_password']) ? $_POST['new_password'] : '';
$confirm_pass= isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

// basic validation
if ($first_name === '' || $last_name === '' || $email === '') {
    redirect_with(['error' => 'missing_fields']);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    redirect_with(['error' => 'invalid_email']);
}

// fetch current record (so we can preserve fields not updated and delete old image if needed)
// Both admin and teachers are in the teacher table
$stmt = $mysqli->prepare("SELECT email, password, image FROM teacher WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) {
    $stmt->close();
    redirect_with(['error' => 'not_found']);
}
$current = $res->fetch_assoc();
$stmt->close();

// Handle password update
$final_password = $current['password']; // default: keep current

if ($new_pass !== '' || $confirm_pass !== '') {
    // user wants to change password
    if ($new_pass !== $confirm_pass) {
        redirect_with(['error' => 'password_mismatch']);
    }
    if (strlen($new_pass) < 5) {
        redirect_with(['error' => 'password_short']);
    }
    // Hash password using password_hash
    $final_password = password_hash($new_pass, PASSWORD_DEFAULT);
}

// Handle image upload (if any)
$final_image = $current['image']; // default keep existing
$uploaded_image_path = null;

if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
    $file = $_FILES['profile_image'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        redirect_with(['error' => 'upload_error']);
    }

    if ($file['size'] > $maxFileSize) {
        redirect_with(['error' => 'file_too_large']);
    }

    // Validate extension
    $originalName = $file['name'];
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        redirect_with(['error' => 'invalid_file_type']);
    }

    // Create unique filename
    $safeBase = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($originalName, PATHINFO_FILENAME));
    $newFilename = time() . '_' . $safeBase . '.' . $ext;
    $destination = $uploadDir . $newFilename;

    // Ensure upload directory exists
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        redirect_with(['error' => 'upload_dir']);
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        redirect_with(['error' => 'move_failed']);
    }

    // store path relative to project (as your DB uses 'image/...' paths)
    $uploaded_image_path = 'image/' . $newFilename;
    $final_image = $uploaded_image_path;
}

// Perform update query (prepared)
// Both admin and teachers update the teacher table
$update_stmt = $mysqli->prepare("
    UPDATE teacher
    SET fname = ?, mname = ?, lname = ?, email = ?, password = ?, image = ?
    WHERE id = ?
");

if (!$update_stmt) {
    // If prepared failed, clean up uploaded file if any
    if ($uploaded_image_path && file_exists(__DIR__ . '/' . $uploaded_image_path)) {
        @unlink(__DIR__ . '/' . $uploaded_image_path);
    }
    redirect_with(['error' => 'db_prepare']);
}

$update_stmt->bind_param(
    "ssssssi",
    $first_name,
    $middle_name,
    $last_name,
    $email,
    $final_password,
    $final_image,
    $userId
);

$exec = $update_stmt->execute();

if (!$exec) {
    // cleanup uploaded file on failure
    if ($uploaded_image_path && file_exists(__DIR__ . '/' . $uploaded_image_path)) {
        @unlink(__DIR__ . '/' . $uploaded_image_path);
    }
    error_log("Update failed: " . $update_stmt->error);
    redirect_with(['error' => 'update_failed']);
}

$affected = $update_stmt->affected_rows;
$update_stmt->close();

// Verify the update was successful
if ($affected <= 0) {
    error_log("No rows affected by update. Affected rows: " . $affected);
    redirect_with(['error' => 'update_failed']);
}

// If we uploaded a new image and the old image exists and is not the default placeholder, delete old one
if ($uploaded_image_path) {
    $oldImage = $current['image'];
    // only remove if it's not empty and not the default image path and file exists
    if (!empty($oldImage) && $oldImage !== 'image/default.jpg' && $oldImage !== 'images/admin-avatar.png' && file_exists(__DIR__ . '/' . $oldImage)) {
        @unlink(__DIR__ . '/' . $oldImage);
    }
}

// Success
redirect_with(['success' => 1]);

// Close database connection
if (isset($mysqli) && $mysqli instanceof mysqli) {
    $mysqli->close();
}
?>
