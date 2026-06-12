<?php
ob_start();

// Set error handler to catch all errors
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'PHP Error: ' . $errstr,
        'error_details' => [
            'error' => $errstr,
            'file' => $errfile,
            'line' => $errline
        ]
    ]);
    exit;
});

try {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    
    session_start();
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: *');
    
    // Check authentication
    if (!isset($_SESSION['user_id'])) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Load database connection
    require_once '../connect.php';
    
    if (!isset($conn) || !$conn) {
        ob_end_clean();
        echo json_encode(['success' => false, 'message' => 'Connection error']);
        exit;
    }
} catch (Exception $e) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Init error: ' . $e->getMessage()]);
    exit;
}

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
    exit;
}

// Validate records array
if (!isset($data['records']) || !is_array($data['records']) || count($data['records']) === 0) {
    ob_end_clean();
    echo json_encode(['success' => false, 'message' => 'No records provided']);
    exit;
}

$records = $data['records'];
$grade = $data['grade'] ?? null;
$section = $data['section'] ?? null;
$successCount = 0;
$failureCount = 0;
$failedRecords = [];
$defaultLat = 14.6124466;
$defaultLng = 120.9879835;

// Get the admin/user ID who is creating the records
$createdBy = intval($_SESSION['user_id'] ?? 0);

// Get the teacher/adviser ID for this grade and section
$adviserId = null;
if ($grade && $section && $grade !== '-' && $section !== '-') {
    $adviserQuery = "SELECT id FROM adviser WHERE grade_level = ? AND class_section = ?";
    $adviserStmt = $conn->prepare($adviserQuery);
    if ($adviserStmt) {
        $adviserStmt->bind_param('ss', $grade, $section);
        $adviserStmt->execute();
        $adviserResult = $adviserStmt->get_result();
        if ($adviserResult->num_rows > 0) {
            $adviserRow = $adviserResult->fetch_assoc();
            $adviserId = intval($adviserRow['id']);
        }
        $adviserStmt->close();
    }
}

// Fallback to current user if no adviser found
if (!$adviserId) {
    $adviserId = $createdBy;
}

try {
    // Prepare the insert statement with correct column order
    // Try with created_by column first (for new schema), fallback to old schema if it fails
    // Note: Using NOW() for date or passing explicit date
    $currentDate = date('Y-m-d H:i:s');
    
    $insertSQL = "INSERT INTO record (status, vid, date, sid, aid, lat, lng, type, proof, accuracy) VALUES ('Pending', ?, ?, ?, ?, ?, ?, NULL, NULL, 0)";
    $stmt = $conn->prepare($insertSQL);
    
    if (!$stmt) {
        throw new Exception('Prepare error: ' . $conn->error);
    }
    
    // Process each record
    foreach ($records as $record) {
        try {
            $studentId = intval($record['student_id'] ?? 0);
            $violationId = intval($record['violation_id'] ?? 0);
            $violationType = $record['violation_type'] ?? 'minor';
            $latitude = floatval($record['latitude'] ?? $defaultLat);
            $longitude = floatval($record['longitude'] ?? $defaultLng);
            
            // Handle zero coordinates (fallback)
            if ($latitude == 0) $latitude = $defaultLat;
            if ($longitude == 0) $longitude = $defaultLng;
            
            // Validate IDs
            if ($studentId <= 0 || $violationId <= 0) {
                $failureCount++;
                $failedRecords[] = ['student_id' => $studentId, 'error' => 'Invalid IDs'];
                continue;
            }
            
            // Check if student exists
            $checkStmt = $conn->prepare("SELECT id FROM student WHERE id = ?");
            if (!$checkStmt) {
                throw new Exception("Check statement failed");
            }
            
            $checkStmt->bind_param('i', $studentId);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            
            if ($result->num_rows === 0) {
                $failureCount++;
                $failedRecords[] = ['student_id' => $studentId, 'error' => 'Student not found'];
                $checkStmt->close();
                continue;
            }
            $checkStmt->close();
            
            // Insert the record with adviser/teacher as the class adviser and admin as the creator
            // bind_param order: vid(i), date(s), sid(i), aid(i), lat(d), lng(d)
            // Type string: i=vid, s=date, i=sid, i=aid, d=lat, d=lng
            if (!$stmt->bind_param('isiidd', $violationId, $currentDate, $studentId, $adviserId, $latitude, $longitude)) {
                throw new Exception('Bind param failed: ' . $stmt->error);
            }
            
            if ($stmt->execute()) {
                $successCount++;
            } else {
                $failureCount++;
                $failedRecords[] = ['student_id' => $studentId, 'error' => 'Execute failed: ' . $stmt->error];
            }
            
        } catch (Exception $e) {
            $failureCount++;
            $failedRecords[] = ['student_id' => $studentId ?? 0, 'error' => $e->getMessage()];
        }
    }
    
    $stmt->close();
    $conn->close();
    
    // Build response
    if ($successCount > 0) {
        $response = [
            'success' => true,
            'message' => $successCount . ' record(s) added successfully' . ($failureCount > 0 ? " ($failureCount failed)" : ''),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'failed_records' => $failedRecords,
            'debug' => [
                'adviserId' => $adviserId,
                'createdBy' => $createdBy,
                'date' => $currentDate
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No violations were added. Details: ' . ($failureCount > 0 ? $failureCount . ' records failed to insert' : 'Check student/violation IDs'),
            'success_count' => 0,
            'failure_count' => $failureCount,
            'failed_records' => $failedRecords,
            'debug' => [
                'adviserId' => $adviserId,
                'createdBy' => $createdBy,
                'date' => $currentDate,
                'grade' => $grade,
                'section' => $section
            ]
        ];
    }
    
    ob_end_clean();
    http_response_code(200);
    echo json_encode($response);
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
exit;
?>
