<?php
// update-student.php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

// Include database connection
include '../connect.php';

// Log all incoming data for debugging
error_log("=== UPDATE STUDENT REQUEST ===");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get student ID
$student_id = $_POST['student_id'] ?? null;

error_log("Student ID received: " . $student_id);

if (!$student_id) {
    echo json_encode(['success' => false, 'message' => 'Student ID is required']);
    exit;
}

// Get form data
$fname = trim($_POST['fname'] ?? '');
$mname = trim($_POST['mname'] ?? '');
$lname = trim($_POST['lname'] ?? '');
$email = trim($_POST['email'] ?? '');
$gender = trim($_POST['gender'] ?? '');
$lrn = trim($_POST['lrn'] ?? '');
$grade = trim($_POST['grade'] ?? '');
$section = trim($_POST['section'] ?? '');
$academicyear = trim($_POST['academicyear'] ?? '');
$guardian = trim($_POST['guardian'] ?? '');
$guardiancontact = trim($_POST['guardiancontact'] ?? '');

error_log("Form data - fname: $fname, lname: $lname, email: $email, lrn: $lrn");

// Validate required fields
if (empty($fname) || empty($lname) || empty($email) || empty($lrn)) {
    echo json_encode(['success' => false, 'message' => 'Required fields are missing: fname=' . $fname . ', lname=' . $lname . ', email=' . $email . ', lrn=' . $lrn]);
    exit;
}

// Handle image upload
$image_path = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'image/';
    
    // Create directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    $file_tmp = $_FILES['image']['tmp_name'];
    $file_name = $_FILES['image']['name'];
    $file_size = $_FILES['image']['size'];
    $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Allowed extensions
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    // Validate file
    if (!in_array($file_ext, $allowed_extensions)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.']);
        exit;
    }
    
    if ($file_size > 5000000) { // 5MB max
        echo json_encode(['success' => false, 'message' => 'File size too large. Maximum 5MB allowed.']);
        exit;
    }
    
    // Generate unique filename
    $new_filename = date('dmYHis') . '_' . uniqid() . '.' . $file_ext;
    $image_path = $upload_dir . $new_filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file_tmp, $image_path)) {
        echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
        exit;
    }
    
    error_log("Image uploaded: $image_path");
}

// Use prepared statement for safe update
global $conn;
$update_query = "UPDATE student SET fname = ?, mname = ?, lname = ?, email = ?, gender = ?, lrn = ?, grade = ?, section = ?, academicyear = ?, guardian = ?, guardiancontact = ?";

$params = [$fname, $mname, $lname, $email, $gender, $lrn, $grade, $section, $academicyear, $guardian, $guardiancontact];
$types = "sssssssssss";

// Add image to update if uploaded
if ($image_path !== null) {
    $update_query .= ", image = ?";
    $params[] = $image_path;
    $types .= "s";
}

$update_query .= " WHERE id = ?";
$params[] = intval($student_id);
$types .= "i";

error_log("Query: $update_query");
error_log("Types: $types");

// Execute update with prepared statement
try {
    $stmt = $conn->prepare($update_query);
    
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    error_log("Statement prepared successfully");
    
    // Bind parameters
    $stmt->bind_param($types, ...$params);
    
    error_log("Parameters bound successfully");
    
    // Execute statement
    if ($stmt->execute()) {
        error_log("Statement executed successfully");
        
        // Log activity
        $activity_date = date('Y-m-d H:i:s');
        $teacher_id = $_SESSION['teacher_id'] ?? '1';
        
        $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, ?)");
        $activity_stmt->bind_param("iss", $teacher_id, $desc, $activity_date);
        $desc = 'Edit Student Details';
        $activity_stmt->execute();
        $activity_stmt->close();
        
        error_log("Activity logged successfully");
        
        echo json_encode([
            'success' => true, 
            'message' => 'Student updated successfully',
            'image_path' => $image_path
        ]);
    } else {
        throw new Exception($stmt->error);
    }
    
    $stmt->close();
} catch (Exception $e) {
    error_log("Update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

closeConnection();
?>