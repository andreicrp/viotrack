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

$adminId = isset($data['id']) ? (int)$data['id'] : 0;

if ($adminId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

// Prevent self-deletion
if ($adminId === (int)$_SESSION['user_id']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

// Check if admin exists and get info
$checkStmt = $conn->prepare("SELECT image, role FROM admin WHERE id = ?");
if (!$checkStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$checkStmt->bind_param("i", $adminId);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    $checkStmt->close();
    exit();
}

$admin = $result->fetch_assoc();
$checkStmt->close();

// Delete admin from database
$stmt = $conn->prepare("DELETE FROM admin WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $adminId);

if ($stmt->execute()) {
    // Delete image file if not default
    if (!empty($admin['image']) && $admin['image'] !== 'image/default.png' && file_exists($admin['image'])) {
        @unlink($admin['image']);
    }
    
    // Log activity
    $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = 'Delete Admin (ID: ' . $adminId . ')';
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $userId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
    
    echo json_encode(['success' => true, 'message' => 'Admin deleted successfully']);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete admin: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>
