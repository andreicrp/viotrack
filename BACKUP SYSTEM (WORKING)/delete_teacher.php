<?php
session_start();
require_once('connect.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$teacherId = isset($data['id']) ? (int)$data['id'] : 0;

if ($teacherId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
    exit();
}

// Prevent self-deletion
if ($teacherId === (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

// Check if teacher exists and get info
$checkStmt = $conn->prepare("SELECT image, position FROM teacher WHERE id = ?");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$checkStmt->bind_param("i", $teacherId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    $checkStmt->close();
    exit();
}

$teacher = $result->fetch_assoc();
$checkStmt->close();

// Prevent deletion of admin accounts
if (strtolower($teacher['position']) === 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Cannot delete admin account']);
    exit();
}

// Delete teacher from database
$stmt = $conn->prepare("DELETE FROM teacher WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $teacherId);

if ($stmt->execute()) {
    // Delete image file if not default
    if (!empty($teacher['image']) && $teacher['image'] !== 'image/default.jpg' && file_exists($teacher['image'])) {
        @unlink($teacher['image']);
    }
    
    // Log activity
    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = 'Delete Teacher (ID: ' . $teacherId . ')';
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $adminId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Teacher deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete teacher: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>