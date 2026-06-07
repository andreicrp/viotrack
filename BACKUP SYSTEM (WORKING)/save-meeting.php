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

// Database connection
require_once('connect.php');
if (!$conn) {
    die(json_encode(['success' => false, 'message' => 'Database connection failed']));
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['date']) || !isset($data['name'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$date = $data['date'];
$name = $data['name'];
$time = $data['time'] ?? '';
$reason = $data['reason'] ?? '';
$type = 'Meeting';

// Insert into report table
$stmt = $conn->prepare("INSERT INTO report (sid, type, date, comment, status) VALUES (0, ?, ?, ?, 'Scheduled')");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$comment = "Name: $name | Time: $time | Reason: $reason";
$stmt->bind_param('sss', $type, $date, $comment);

if ($stmt->execute()) {
    $report_id = $stmt->insert_id;
    error_log("Meeting created successfully. ID: $report_id");
    
    // Log activity with correct columns (aid, description, date)
    $activity_desc = "Created parent meeting: $name on $date";
    $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activity_stmt) {
        $aid = 1; // Default admin ID
        $activity_stmt->bind_param('is', $aid, $activity_desc);
        if (!$activity_stmt->execute()) {
            error_log("Activity log failed: " . $activity_stmt->error);
        }
        $activity_stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Meeting saved successfully', 'report_id' => $report_id]);
} else {
    error_log("Meeting insert failed: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to save meeting: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
