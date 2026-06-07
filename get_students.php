<?php
/**
 * get_students.php - Fetch all students for add record modal
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

try {
    require_once 'connect.php';

    // Fetch all students with necessary info
    $query = "SELECT id, fname, mname, lname, lrn, grade, section FROM student ORDER BY fname ASC";
    $result = executeQuery($query);

    $students = [];
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fullName = trim($row['fname'] . ' ' . $row['mname'] . ' ' . $row['lname']);
            $students[] = [
                'id' => $row['id'],
                'name' => $fullName,
                'lrn' => $row['lrn'],
                'grade' => $row['grade'],
                'section' => $row['section'],
                'displayText' => $fullName . ' (' . $row['lrn'] . ')'
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'students' => $students,
        'total' => count($students)
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'students' => []
    ]);
}

exit;
?>
