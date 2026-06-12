<?php
// delete-students-bulk.php
session_start();

// Require login
require_once '../auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

// Include database connection
include '../connect.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$student_ids = $input['ids'] ?? [];

if (empty($student_ids)) {
    echo json_encode(['success' => false, 'message' => 'No student IDs provided']);
    exit;
}

// Sanitize IDs
$student_ids = array_map('intval', $student_ids);
$ids_string = implode(',', $student_ids);

// Begin transaction
beginTransaction();

try {
    // Delete related records first
    $delete_records = "DELETE FROM record WHERE sid IN ($ids_string)";
    executeNonQuery($delete_records);
    
    // Delete related reports
    $delete_reports = "DELETE FROM report WHERE sid IN ($ids_string)";
    executeNonQuery($delete_reports);
    
    // Delete the students
    $delete_students = "DELETE FROM student WHERE id IN ($ids_string)";
    executeNonQuery($delete_students);
    
    // Get affected rows count
    global $conn;
    $deleted_count = $conn->affected_rows;
    
    // Commit transaction
    commitTransaction();
    
    echo json_encode([
        'success' => true, 
        'message' => "$deleted_count student(s) deleted successfully",
        'count' => $deleted_count
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    rollbackTransaction();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

closeConnection();
?>