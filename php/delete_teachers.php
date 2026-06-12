<?php
session_start();
require_once('../connect.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['ids']) || !is_array($data['ids'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$ids = array_map('intval', $data['ids']);
$ids = array_filter($ids, function($id) { return $id > 0; });

if (empty($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid teacher IDs provided']);
    exit();
}

// Check if user is trying to delete themselves
$userIds = array_filter($ids, function($id) { return $id === (int)$_SESSION['user_id']; });
if (!empty($userIds)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'You cannot delete your own account']);
    exit();
}

// Get teachers to delete (including images and check for admin)
$placeholders = implode(',', array_fill(0, count($ids), '?'));
$stmt = $conn->prepare("SELECT id, image, position FROM teacher WHERE id IN ($placeholders)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$types = str_repeat('i', count($ids));
$stmt->bind_param($types, ...$ids);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();

$teachersToDelete = [];
$imagesToDelete = [];
$adminCount = 0;

while ($row = $result->fetch_assoc()) {
    // Don't delete admin accounts
    if (strtolower($row['position']) === 'admin') {
        $adminCount++;
        continue;
    }
    
    $teachersToDelete[] = (int)$row['id'];
    
    if (!empty($row['image']) && $row['image'] !== 'image/default.jpg' && file_exists($row['image'])) {
        $imagesToDelete[] = $row['image'];
    }
}
$stmt->close();

if (empty($teachersToDelete)) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'No teachers available to delete' . ($adminCount > 0 ? ' (admin accounts cannot be deleted)' : '')
    ]);
    exit();
}

// Delete teachers
$placeholders = implode(',', array_fill(0, count($teachersToDelete), '?'));
$deleteStmt = $conn->prepare("DELETE FROM teacher WHERE id IN ($placeholders)");

if (!$deleteStmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$types = str_repeat('i', count($teachersToDelete));
$deleteStmt->bind_param($types, ...$teachersToDelete);

if ($deleteStmt->execute()) {
    $deletedCount = $deleteStmt->affected_rows;
    
    // Delete image files
    foreach ($imagesToDelete as $image) {
        if (file_exists($image)) {
            @unlink($image);
        }
    }
    
    // Log activity
    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = "Delete Multiple Teachers (Count: $deletedCount)";
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $adminId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "Successfully deleted $deletedCount teacher(s)" . ($adminCount > 0 ? " ($adminCount admin account(s) skipped)" : ""),
        'deletedCount' => $deletedCount,
        'skippedCount' => $adminCount
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to delete teachers: ' . $deleteStmt->error]);
}

$deleteStmt->close();
$conn->close();
?>