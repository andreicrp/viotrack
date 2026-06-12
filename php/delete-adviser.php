<?php
session_start();

// Require login and admin access
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Admin access required']);
    exit();
}

header('Content-Type: application/json');

require_once('../connect.php');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['id']) || $data['id'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid adviser ID']);
    exit();
}

$id = intval($data['id']);

try {
    // Get adviser info before deleting for activity log
    $selectSQL = "SELECT fname, lname, grade_level, class_section FROM adviser WHERE id = ?";
    $selectStmt = $conn->prepare($selectSQL);
    if (!$selectStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $selectStmt->bind_param('i', $id);
    $selectStmt->execute();
    $result = $selectStmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception("Adviser not found");
    }
    
    $adviser = $result->fetch_assoc();
    $selectStmt->close();
    
    // Delete the adviser record
    $deleteSQL = "DELETE FROM adviser WHERE id = ?";
    $deleteStmt = $conn->prepare($deleteSQL);
    if (!$deleteStmt) {
        throw new Exception("Prepare delete failed: " . $conn->error);
    }
    
    $deleteStmt->bind_param('i', $id);
    
    if (!$deleteStmt->execute()) {
        throw new Exception("Delete failed: " . $deleteStmt->error);
    }
    
    $deleteStmt->close();
    
    // Log activity
    $adviserName = $adviser['fname'] . ' ' . $adviser['lname'];
    $activity_desc = "Removed $adviserName as adviser for {$adviser['grade_level']} - Section {$adviser['class_section']}";
    $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activity_stmt) {
        $aid = $_SESSION['user_id'];
        $activity_stmt->bind_param('is', $aid, $activity_desc);
        $activity_stmt->execute();
        $activity_stmt->close();
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Adviser removed successfully'
    ]);

} catch (Exception $e) {
    error_log("Delete adviser error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

$conn->close();
?>
