<?php
/**
 * save_record.php - Add violation records to database
 * Handles student violations with database storage
 */

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'debug' => []
];

try {
    // Include database connection
    require_once 'connect.php';
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get and validate input
    $studentIds = isset($_POST['student_ids']) ? json_decode($_POST['student_ids'], true) : [];
    $violationType = isset($_POST['violation_type']) ? sanitize($_POST['violation_type']) : '';
    $violation = isset($_POST['violation']) ? sanitize($_POST['violation']) : '';
    $description = isset($_POST['description']) ? sanitize($_POST['description']) : '';
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    
    // Validate inputs
    if (empty($studentIds) || !is_array($studentIds)) {
        throw new Exception('No students selected');
    }
    
    if (empty($violation)) {
        throw new Exception('Violation description is required');
    }
    
    if (empty($violationType) || !in_array($violationType, ['Minor', 'Serious', 'Major'])) {
        throw new Exception('Invalid violation type');
    }
    
    // Validate and sanitize location coordinates
    if ($latitude === null || $longitude === null) {
        // Use default Manila location if not provided
        $latitude = 14.6124466;
        $longitude = 120.9879835;
    }
    
    // Ensure coordinates are within valid ranges
    if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
        // Invalid coordinates, use default
        $latitude = 14.6124466;
        $longitude = 120.9879835;
    }

    // Start transaction
    beginTransaction();
    $response['debug'][] = 'Transaction started';
    
    $dateReported = date('Y-m-d H:i:s');
    $addedCount = 0;
    $failedCount = 0;
    $failedStudents = [];

    // Insert record for each selected student
    foreach ($studentIds as $studentId) {
        $studentId = intval($studentId);
        if ($studentId <= 0) continue;

        // Verify student exists
        $studentCheck = "SELECT id, fname, lname FROM student WHERE id = $studentId";
        $studentResult = executeQuery($studentCheck);
        
        if (!$studentResult || $studentResult->num_rows === 0) {
            $failedCount++;
            $failedStudents[] = $studentId;
            continue;
        }

        // Insert violation record with location data
        $sql = "INSERT INTO record (sid, vid, type, status, date, lat, lng) 
                VALUES ($studentId, '$violation', '$violationType', 'Pending', NOW(), $latitude, $longitude)";
        
        if (executeNonQuery($sql)) {
            $addedCount++;
            $response['debug'][] = "Record added for student $studentId";
        } else {
            $failedCount++;
            $failedStudents[] = $studentId;
            $response['debug'][] = "Failed to add record for student $studentId";
        }
    }

    // Commit transaction
    commitTransaction();
    $response['debug'][] = 'Transaction committed';

    if ($addedCount > 0) {
        $response['success'] = true;
        $response['message'] = "Successfully added $addedCount violation record(s)";
        if ($failedCount > 0) {
            $response['message'] .= " ($failedCount failed)";
        }
        $response['data'] = [
            'added' => $addedCount,
            'failed' => $failedCount,
            'failedStudents' => $failedStudents
        ];
    } else {
        throw new Exception('Failed to add any violation records. Please try again.');
    }

} catch (Exception $e) {
    rollbackTransaction();
    $response['message'] = $e->getMessage();
    $response['debug'][] = 'Error: ' . $e->getMessage();
}

// Remove debug in production
// unset($response['debug']);

echo json_encode($response);
exit;
?>
