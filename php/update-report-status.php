<?php
// update-report-status.php - Update SMS report status

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

require_once('../connect.php');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['report_id']) || !isset($input['status'])) {
    echo json_encode(['success' => false, 'message' => 'Missing report_id or status']);
    exit;
}

$reportId = intval($input['report_id']);
$status = trim($input['status']);

// Allowed statuses
$allowedStatuses = ['Pending', 'Scheduled', 'Completed', 'Cancelled'];
if (!in_array($status, $allowedStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

// Update the report
$stmt = $conn->prepare("UPDATE report SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $reportId);

if ($stmt->execute()) {
    // Log activity
    if (isset($_SESSION['aid'])) {
        $adminId = $_SESSION['aid'];
        $description = "Updated SMS report $reportId status to: $status";
        $conn->query("INSERT INTO activity (aid, description, date) VALUES ($adminId, '$description', NOW())");
    }
    
    echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update status: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
