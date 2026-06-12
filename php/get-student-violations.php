<?php
/**
 * get-student-violations.php - Fetch violations for a specific student
 * Used by the resolution letter to get all resolved violations
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

// Only set header if this is called directly (not included)
if (php_sapi_name() !== 'cli' && !headers_sent()) {
    header('Content-Type: application/json');
}

try {
    require_once '../connect.php';

    // Get parameters - accept both 'id' and 'student_id'
    $recordId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    $studentId = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
    
    // If a record ID is provided, fetch it and get the student ID
    if ($recordId > 0) {
        $recordQuery = "SELECT r.id, r.sid, r.vid, r.status, r.date, 
                              v.title as violation, v.type,
                              s.id as student_id, s.fname, s.mname, s.lname, s.lrn, s.grade, s.section
                       FROM record r
                       JOIN violation v ON r.vid = v.id
                       JOIN student s ON r.sid = s.id
                       WHERE r.id = " . $recordId;
        
        $recordData = fetchSingle($recordQuery);
        
        if (!$recordData) {
            throw new Exception('Record not found');
        }
        
        $studentId = $recordData['student_id'];
        $studentFullName = trim($recordData['fname'] . ' ' . ($recordData['mname'] ? $recordData['mname'] . ' ' : '') . $recordData['lname']);
        
        echo json_encode([
            'success' => true,
            'violation' => [
                'id' => $recordData['id'],
                'student_name' => $studentFullName,
                'student_id' => $recordData['lrn'],
                'student_db_id' => $recordData['student_id'],
                'violation_title' => $recordData['violation'],
                'violation_type' => $recordData['type'],
                'date_reported' => date('M d, Y h:i A', strtotime($recordData['date'])),
                'status' => $recordData['status'],
                'grade' => $recordData['grade'],
                'section' => $recordData['section']
            ]
        ]);
        exit;
    }
    
    // Otherwise, fetch all resolved violations for the student
    if ($studentId <= 0) {
        throw new Exception('Invalid student ID');
    }

    // Fetch student info using helper function
    $studentQuery = "SELECT id, fname, mname, lname, lrn, grade, section FROM student WHERE id = " . $studentId;
    $studentData = fetchSingle($studentQuery);
    
    if (!$studentData) {
        throw new Exception('Student not found');
    }

    $studentFullName = trim($studentData['fname'] . ' ' . ($studentData['mname'] ? $studentData['mname'] . ' ' : '') . $studentData['lname']);

    // Fetch violations for this student with given status
    $violationQuery = "SELECT r.id, v.title as violation, v.type, r.date
                      FROM record r
                      JOIN violation v ON r.vid = v.id
                      WHERE r.sid = " . $studentId . " AND r.status = 'Resolved'
                      ORDER BY r.date DESC";
    
    $violationResults = fetchAll($violationQuery);
    
    // Format the violations data
    $violations = [];
    foreach ($violationResults as $row) {
        $violations[] = [
            'id' => $row['id'],
            'violation' => $row['violation'],
            'type' => $row['type'],
            'date_reported' => date('M d, Y h:i A', strtotime($row['date']))
        ];
    }

    echo json_encode([
        'success' => true,
        'student' => [
            'id' => $studentData['id'],
            'name' => $studentFullName,
            'lrn' => $studentData['lrn'],
            'grade' => $studentData['grade'],
            'section' => $studentData['section']
        ],
        'violations' => $violations,
        'total' => count($violations)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'violations' => []
    ]);
}

exit;
?>
