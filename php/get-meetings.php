<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

require_once('../connect.php');

if (!$conn) {
    echo json_encode(['success' => false, 'meetings' => []]);
    exit;
}

// Fetch all meetings from report table
$query = "SELECT r.id, r.date, r.comment, r.type, r.status, s.fname, s.lname, s.guardiancontact 
          FROM report r 
          LEFT JOIN student s ON r.sid = s.id 
          ORDER BY r.date DESC";
$result = $conn->query($query);

$meetings = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        // Extract date part (YYYY-MM-DD) from datetime
        $datePart = substr($row['date'], 0, 10);
        
        // Get student name or use report type as name
        $name = '';
        if ($row['fname'] && $row['lname']) {
            $name = $row['fname'] . ' ' . $row['lname'];
        } else {
            $name = $row['type'];
        }
        
        // Extract time from datetime if available
        $time = substr($row['date'], 11, 5); // HH:MM format
        if (empty($time) || $time == '00:00') {
            $time = '3:00 PM'; // Default time if not available
        }
        
        $reason = $row['comment'] ?? '';
        $phone = $row['guardiancontact'] ?? '';
        
        if (!isset($meetings[$datePart])) {
            $meetings[$datePart] = [];
        }
        
        $meetings[$datePart][] = [
            'id' => (int)$row['id'],
            'name' => $name,
            'time' => $time,
            'reason' => $reason,
            'status' => $row['status'],
            'type' => $row['type'],
            'phone' => $phone
        ];
    }
}

echo json_encode(['success' => true, 'meetings' => $meetings]);
$conn->close();
?>


