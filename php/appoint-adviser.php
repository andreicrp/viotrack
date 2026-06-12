<?php
// Start output buffering to prevent accidental output
ob_start();

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Check if user is admin
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
if (!$isAdmin) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only admins can appoint advisers']);
    exit();
}

header('Content-Type: application/json');

require_once('../connect.php');

if (!$conn) {
    error_log("Appoint-adviser: Database connection failed");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

// Log incoming data
error_log("Appoint-adviser: Received data: " . json_encode($data));

// Validate required fields
if (!$data || !isset($data['tid']) || !isset($data['grade_level']) || !isset($data['class_section'])) {
    error_log("Appoint-adviser: Missing required fields");
    echo json_encode(['success' => false, 'message' => 'Missing required fields (tid, grade_level, class_section)']);
    exit();
}

$tid = intval($data['tid']);
$gradeLevel = trim($data['grade_level']);
$classSection = trim($data['class_section']);

// Validate inputs
if ($tid <= 0 || empty($gradeLevel) || empty($classSection)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

try {
    // First, fetch teacher data from teacher table
    $teacherSQL = "SELECT id, fname, mname, lname, email, image FROM teacher WHERE id = ?";
    $teacherStmt = $conn->prepare($teacherSQL);
    if (!$teacherStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $teacherStmt->bind_param('i', $tid);
    
    if (!$teacherStmt->execute()) {
        throw new Exception("Execute failed: " . $teacherStmt->error);
    }
    
    $teacherResult = $teacherStmt->get_result();
    if ($teacherResult->num_rows === 0) {
        throw new Exception("Teacher not found with ID: $tid");
    }
    
    $teacher = $teacherResult->fetch_assoc();
    $teacherStmt->close();
    
    // Check if this teacher is already assigned to another class
    $checkTeacherSQL = "SELECT id, grade_level, class_section FROM adviser WHERE tid = ?";
    $checkTeacherStmt = $conn->prepare($checkTeacherSQL);
    if (!$checkTeacherStmt) {
        throw new Exception("Prepare check teacher failed: " . $conn->error);
    }
    
    $checkTeacherStmt->bind_param('i', $tid);
    if (!$checkTeacherStmt->execute()) {
        throw new Exception("Check teacher execute failed: " . $checkTeacherStmt->error);
    }
    
    $checkTeacherResult = $checkTeacherStmt->get_result();
    if ($checkTeacherResult->num_rows > 0) {
        $existingAssignment = $checkTeacherResult->fetch_assoc();
        $checkTeacherStmt->close();
        throw new Exception("This teacher is already assigned as adviser for " . $existingAssignment['grade_level'] . " - Section " . $existingAssignment['class_section'] . ". A teacher can only be assigned to one class.");
    }
    $checkTeacherStmt->close();
    
    // Check if adviser already exists for this grade/section combination
    $checkSQL = "SELECT id FROM adviser WHERE grade_level = ? AND class_section = ?";
    $checkStmt = $conn->prepare($checkSQL);
    if (!$checkStmt) {
        throw new Exception("Prepare check failed: " . $conn->error);
    }
    
    $checkStmt->bind_param('ss', $gradeLevel, $classSection);
    if (!$checkStmt->execute()) {
        throw new Exception("Check execute failed: " . $checkStmt->error);
    }
    
    $checkResult = $checkStmt->get_result();
    $existingAdviser = $checkResult->fetch_assoc();
    $checkStmt->close();
    
    if ($existingAdviser) {
        throw new Exception("An adviser is already assigned to $gradeLevel - Section $classSection");
    }
    
    // Insert new adviser record
    $fname = $teacher['fname'];
    $mname = $teacher['mname'];
    $lname = $teacher['lname'];
    $email = $teacher['email'];
    $image = $teacher['image'];
    
    $insertSQL = "INSERT INTO adviser (tid, fname, mname, lname, email, grade_level, class_section, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertSQL);
    if (!$insertStmt) {
        throw new Exception("Insert prepare failed: " . $conn->error);
    }
    
    $insertStmt->bind_param('isssssss', $tid, $fname, $mname, $lname, $email, $gradeLevel, $classSection, $image);
    
    if (!$insertStmt->execute()) {
        throw new Exception("Insert execute failed: " . $insertStmt->error);
    }
    
    $newAdviserID = $conn->insert_id;
    error_log("Appoint-adviser: Successfully created adviser ID $newAdviserID for teacher ID $tid");
    $insertStmt->close();
    
    // Log activity
    $activity_desc = "Appointed $fname $lname as adviser for $gradeLevel - Section $classSection";
    $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activity_stmt) {
        $aid = $_SESSION['user_id'];
        $activity_stmt->bind_param('is', $aid, $activity_desc);
        $activity_stmt->execute();
        $activity_stmt->close();
    }
    
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Adviser appointed successfully',
        'adviser_id' => $newAdviserID,
        'adviser' => [
            'id' => $newAdviserID,
            'tid' => $tid,
            'fname' => $fname,
            'mname' => $mname,
            'lname' => $lname,
            'email' => $email,
            'grade_level' => $gradeLevel,
            'class_section' => $classSection,
            'image' => $image
        ]
    ]);
    exit();

} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log("Appoint-adviser error: " . $errorMsg);
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $errorMsg], JSON_UNESCAPED_SLASHES);
    exit();
}

ob_end_clean();
$conn->close();
