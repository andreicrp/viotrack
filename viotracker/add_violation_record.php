<?php
// add_violation_record.php - Backend for adding violation records to a single student

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

header('Content-Type: application/json');

// Include database connection
require_once('connect.php');

if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Handle both single student (student_id) and multiple students (students array)
    if (!$input) {
        throw new Exception('Invalid JSON input');
    }
    
    $studentIds = [];
    $violations = $input['violations'] ?? [];
    
    // Check if it's single student (from adminstudentviolation.php) or multiple (from violation-record.php)
    if (isset($input['student_id'])) {
        // Single student mode
        $studentIds = [intval($input['student_id'])];
    } elseif (isset($input['students']) && is_array($input['students'])) {
        // Multiple students mode
        $studentIds = array_map('intval', $input['students']);
    } else {
        throw new Exception('Missing required fields: student_id or students');
    }
    
    if (empty($studentIds)) {
        throw new Exception('No students selected');
    }
    
    if (!is_array($violations) || count($violations) === 0) {
        throw new Exception('No violations selected');
    }
    
    // Verify all students exist
    $studentQuery = "SELECT id FROM student WHERE id = ?";
    $studentCheckStmt = $conn->prepare($studentQuery);
    if (!$studentCheckStmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    foreach ($studentIds as $id) {
        $studentCheckStmt->bind_param("i", $id);
        $studentCheckStmt->execute();
        $result = $studentCheckStmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Student with ID ' . $id . ' not found');
        }
    }
    $studentCheckStmt->close();
    
    // Get admin ID from session (or default to 1)
    $adminId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '1';
    $adminId = intval($adminId);
    $currentDate = date('Y-m-d H:i:s');
    $totalRecords = 0;
    
    // Get location from form submission
    $latitude = isset($input['record_latitude']) ? floatval($input['record_latitude']) : null;
    $longitude = isset($input['record_longitude']) ? floatval($input['record_longitude']) : null;
    
    // Validate and set default location if needed
    if ($latitude === null || $latitude === 0.0 || $latitude < -90 || $latitude > 90) {
        $latitude = 14.6124466; // Default to Manila
        error_log('Invalid latitude received, using default Manila location');
    }
    
    if ($longitude === null || $longitude === 0.0 || $longitude < -180 || $longitude > 180) {
        $longitude = 120.9879835; // Default to Manila
        error_log('Invalid longitude received, using default Manila location');
    }
    
    error_log('Location coordinates: LAT=' . $latitude . ', LNG=' . $longitude);
    
    // Begin transaction
    $conn->begin_transaction();
    
    // Prepare insert statement - INSERT INTO record (status, vid, date, sid, aid, lat, lng)
    $insertQuery = "INSERT INTO record (status, vid, date, sid, aid, lat, lng) VALUES ('Pending', ?, ?, ?, ?, ?, ?)";
    $insertStmt = $conn->prepare($insertQuery);
    
    if (!$insertStmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    // Insert record for each student and each violation
    foreach ($studentIds as $studentId) {
        foreach ($violations as $violationId) {
            $violationId = intval($violationId);
            
            // Insert record with location data: vid(i), date(s), sid(i), aid(i), lat(d), lng(d)
            $insertStmt->bind_param("isiidd", $violationId, $currentDate, $studentId, $adminId, $latitude, $longitude);
            
            if (!$insertStmt->execute()) {
                error_log('Insert failed for violation ' . $violationId . ' and student ' . $studentId . ': ' . $insertStmt->error);
                throw new Exception('Execute error: ' . $insertStmt->error);
            }
            
            error_log('Successfully inserted violation ' . $violationId . ' for student ' . $studentId . ' with location: ' . $latitude . ', ' . $longitude);
            $totalRecords++;
        }
    }
    
    $insertStmt->close();
    
    // Log activity
    $activityQuery = "INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())";
    $activityStmt = $conn->prepare($activityQuery);
    
    if (!$activityStmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    $adminIdStr = (string)$adminId;
    $description = "Added $totalRecords violation record(s) for student ID $studentId";
    $activityStmt->bind_param("ss", $adminIdStr, $description);
    
    if (!$activityStmt->execute()) {
        throw new Exception('Activity log error: ' . $activityStmt->error);
    }
    
    $activityStmt->close();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully added $totalRecords violation record(s)",
        'count' => $totalRecords
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    if ($conn) {
        $conn->rollback();
    }
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
    
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>
