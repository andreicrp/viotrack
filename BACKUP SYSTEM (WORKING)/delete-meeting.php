<?php
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
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    echo json_encode(['success' => false, 'message' => 'Missing meeting ID']);
    exit;
}

$id = intval($data['id']);
error_log("Attempting to delete meeting ID: $id");

// Get meeting info before deleting
$selectStmt = $conn->prepare("SELECT type, date FROM report WHERE id = ?");
if ($selectStmt) {
    $selectStmt->bind_param('i', $id);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    $meeting = $result->fetch_assoc();
    $selectStmt->close();
} else {
    error_log("Failed to prepare select statement: " . $conn->error);
    $meeting = null;
}

// Delete from report table
$stmt = $conn->prepare("DELETE FROM report WHERE id = ?");
if (!$stmt) {
    error_log("Prepare failed: " . $conn->error);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $affectedRows = $stmt->affected_rows;
    error_log("Meeting deleted successfully. Affected rows: $affectedRows");
    
    // Log activity
    if ($meeting) {
        $activity_desc = "Deleted parent meeting (ID: $id, Type: {$meeting['type']}, Date: {$meeting['date']})";
        $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
        if ($activity_stmt) {
            $aid = $_SESSION['aid'] ?? 1;
            $activity_stmt->bind_param('is', $aid, $activity_desc);
            if (!$activity_stmt->execute()) {
                error_log("Activity log failed: " . $activity_stmt->error);
            }
            $activity_stmt->close();
        }
    }
    
    echo json_encode(['success' => true, 'message' => 'Meeting deleted successfully', 'affected_rows' => $affectedRows]);
} else {
    error_log("Delete failed: " . $stmt->error);
    echo json_encode(['success' => false, 'message' => 'Failed to delete meeting: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
