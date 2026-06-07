<?php
// delete_activity.php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
require_once('connect.php');

if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Handle single activity deletion
    if (isset($_POST['activity_id'])) {
        $activityId = (int)$_POST['activity_id'];
        
        if ($activityId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid activity ID']);
            exit();
        }
        
        // Prepare and execute delete query
        $stmt = $conn->prepare("DELETE FROM activity WHERE id = ?");
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        
        $stmt->bind_param('i', $activityId);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                // Log the deletion
                $userId = $_SESSION['user_id'];
                $description = "Deleted activity log (ID: $activityId)";
                $date = date('Y-m-d H:i:s');
                
                $logStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, ?)");
                $logStmt->bind_param('sss', $userId, $description, $date);
                $logStmt->execute();
                $logStmt->close();
                
                echo json_encode(['success' => true, 'message' => 'Activity deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Activity not found']);
            }
        } else {
            throw new Exception('Failed to execute delete query');
        }
        
        $stmt->close();
    }
    // Handle multiple activities deletion
    elseif (isset($_POST['activity_ids'])) {
        $activityIds = json_decode($_POST['activity_ids'], true);
        
        if (!is_array($activityIds) || empty($activityIds)) {
            echo json_encode(['success' => false, 'message' => 'Invalid activity IDs']);
            exit();
        }
        
        // Sanitize IDs
        $activityIds = array_map('intval', $activityIds);
        $activityIds = array_filter($activityIds, function($id) { return $id > 0; });
        
        if (empty($activityIds)) {
            echo json_encode(['success' => false, 'message' => 'No valid activity IDs']);
            exit();
        }
        
        // Create placeholders for prepared statement
        $placeholders = implode(',', array_fill(0, count($activityIds), '?'));
        $query = "DELETE FROM activity WHERE id IN ($placeholders)";
        
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement');
        }
        
        // Bind parameters dynamically
        $types = str_repeat('i', count($activityIds));
        $stmt->bind_param($types, ...$activityIds);
        
        if ($stmt->execute()) {
            $deletedCount = $stmt->affected_rows;
            
            if ($deletedCount > 0) {
                // Log the deletion
                $userId = $_SESSION['user_id'];
                $description = "Deleted $deletedCount activity log(s) (IDs: " . implode(', ', $activityIds) . ")";
                $date = date('Y-m-d H:i:s');
                
                $logStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, ?)");
                $logStmt->bind_param('sss', $userId, $description, $date);
                $logStmt->execute();
                $logStmt->close();
                
                echo json_encode([
                    'success' => true, 
                    'message' => "$deletedCount activity log(s) deleted successfully",
                    'deleted_count' => $deletedCount
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No activities were deleted']);
            }
        } else {
            throw new Exception('Failed to execute delete query');
        }
        
        $stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'No activity ID(s) provided']);
    }
    
} catch (Exception $e) {
    error_log('Delete Activity Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
} finally {
    $conn->close();
}
?>