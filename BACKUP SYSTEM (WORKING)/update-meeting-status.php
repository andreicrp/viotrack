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

require_once('connect.php');
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id']) || !isset($data['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$id = $data['id'];
$status = $data['status'];

// Validate status
$validStatuses = ['Pending', 'Scheduled', 'Completed', 'Cancelled'];
if (!in_array($status, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update report table
$stmt = $conn->prepare("UPDATE report SET status = ? WHERE id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Prepare failed']);
    exit;
}

$stmt->bind_param('si', $status, $id);

if ($stmt->execute()) {
    error_log("Meeting status updated. ID: $id, Status: $status");
    
    // Log activity
    $activity_desc = "Updated meeting status to: $status (Report ID: $id)";
    $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activity_stmt) {
        $aid = $_SESSION['aid'] ?? 1; // Use logged in admin, default to 1
        $activity_stmt->bind_param('is', $aid, $activity_desc);
        if (!$activity_stmt->execute()) {
            error_log("Activity log failed: " . $activity_stmt->error);
        }
        $activity_stmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    error_log("Update failed: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
