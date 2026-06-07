<?php
// get-sms-reports.php - Fetch SMS reports for calendar

session_start();

// Require login
require_once 'auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

require_once('connect.php');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Get all reports with student and teacher info
$query = "SELECT 
    r.id,
    r.sid,
    r.type,
    r.date,
    r.comment,
    r.status,
    s.fname,
    s.lname,
    s.guardiancontact
FROM report r
INNER JOIN student s ON r.sid = s.id
ORDER BY r.date DESC";

$result = $conn->query($query);
$reports = [];

while ($row = $result->fetch_assoc()) {
    $reports[] = [
        'id' => $row['id'],
        'student_id' => $row['sid'],
        'student_name' => $row['fname'] . ' ' . $row['lname'],
        'meeting_type' => $row['type'],
        'date' => $row['date'],
        'message' => $row['comment'],
        'status' => $row['status'],
        'phone' => $row['guardiancontact']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $reports
]);

$conn->close();
?>
