<?php
/**
 * Delete Violation Handler
 * Handles deletion of violations
 */

session_start();

// Require login
require_once '../auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Include database connection
require_once '../connect.php';

// Set response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null
];

try {
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Check if request is JSON (bulk delete) or form data (single delete)
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'application/json') !== false) {
        // Handle bulk delete from JSON
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (!isset($data['ids']) || !is_array($data['ids']) || empty($data['ids'])) {
            throw new Exception('Invalid violation IDs');
        }
        
        $violationIds = array_map('intval', $data['ids']);
        $deletedCount = 0;
        
        // Start transaction
        beginTransaction();
        
        foreach ($violationIds as $violationId) {
            if ($violationId <= 0) continue;
            
            // Check if violation exists
            $checkSql = "SELECT id FROM violation WHERE id = $violationId";
            $checkResult = fetchSingle($checkSql);
            
            if (!$checkResult) {
                continue;
            }
            
            // Check if violation is used in records
            $recordCheckSql = "SELECT COUNT(*) as count FROM record WHERE vid = '$violationId'";
            $recordCheck = fetchSingle($recordCheckSql);
            
            if ($recordCheck && $recordCheck['count'] > 0) {
                continue; // Skip this one if it's being used
            }
            
            // Delete violation
            $sql = "DELETE FROM violation WHERE id = $violationId";
            
            if (executeNonQuery($sql)) {
                $deletedCount++;
            }
        }
        
        // Log activity
        if (isset($_SESSION['id']) && $deletedCount > 0) {
            $adminId = $_SESSION['id'];
            $date = date('Y-m-d H:i:s');
            $activitySql = "INSERT INTO activity (aid, description, date) 
                           VALUES ('$adminId', 'Deleted $deletedCount violation(s)', '$date')";
            executeNonQuery($activitySql);
        }
        
        // Commit transaction
        commitTransaction();
        
        $response['success'] = true;
        $response['message'] = "Successfully deleted $deletedCount violation(s)";
        $response['data'] = ['deleted' => $deletedCount];
        
    } else {
        // Handle single delete from form data
        $violationId = isset($_POST['violation_id']) ? intval($_POST['violation_id']) : 0;

        // Validate violation ID
        if ($violationId <= 0) {
            throw new Exception('Invalid violation ID');
        }

        // Check if violation exists
        $checkSql = "SELECT id FROM violation WHERE id = $violationId";
        $checkResult = fetchSingle($checkSql);
        
        if (!$checkResult) {
            throw new Exception('Violation not found');
        }

        // Check if violation is used in records
        $recordCheckSql = "SELECT COUNT(*) as count FROM record WHERE vid = '$violationId'";
        $recordCheck = fetchSingle($recordCheckSql);
        
        if ($recordCheck && $recordCheck['count'] > 0) {
            throw new Exception('Cannot delete violation: It is being used in ' . $recordCheck['count'] . ' record(s)');
        }

        // Start transaction
        beginTransaction();

        // Delete violation
        $sql = "DELETE FROM violation WHERE id = $violationId";
        
        if (!executeNonQuery($sql)) {
            throw new Exception('Failed to delete violation');
        }

        // Log activity if session exists
        if (isset($_SESSION['id'])) {
            $adminId = $_SESSION['id'];
            $date = date('Y-m-d H:i:s');
            $activitySql = "INSERT INTO activity (aid, description, date) 
                           VALUES ('$adminId', 'Delete Violation', '$date')";
            executeNonQuery($activitySql);
        }

        // Commit transaction
        commitTransaction();
        
        $response['success'] = true;
        $response['message'] = 'Violation deleted successfully!';
        $response['data'] = ['id' => $violationId];
    }

} catch (Exception $e) {
    // Rollback transaction on error
    rollbackTransaction();
    $response['message'] = $e->getMessage();
}

// Send response
echo json_encode($response);
exit;
?>