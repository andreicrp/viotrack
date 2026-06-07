<?php
// update-status-handler.php - Handle violation status updates

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

require_once 'connect.php';

// Log all POST data for debugging
error_log("=== REQUEST RECEIVED ===");
error_log("POST action: " . ($_POST['action'] ?? 'NOT SET'));
error_log("All POST keys: " . implode(', ', array_keys($_POST)));
error_log("Raw POST dump: " . json_encode($_POST));

$response = [
    'success' => false,
    'message' => ''
];

try {
    $action = $_POST['action'] ?? '';
    
    error_log("Processing action: '" . $action . "'");

    if ($action === 'update_status') {
        $recordId = intval($_POST['record_id'] ?? 0);
        $newStatus = $conn->real_escape_string($_POST['new_status'] ?? '');
        
        error_log("Update Status - Record ID: $recordId, New Status: $newStatus");
        
        if ($recordId <= 0 || empty($newStatus)) {
            throw new Exception("Invalid record ID ($recordId) or status ('$newStatus')");
        }

        // Validate status
        if (!in_array($newStatus, ['Pending', 'Resolved', 'Escalated'])) {
            throw new Exception('Invalid status value');
        }

        // Update the record table (only status, no date_resolved column in DB)
        $query = "UPDATE record SET status = '$newStatus' WHERE id = $recordId";
        
        error_log("Query: $query");
        
        if ($conn->query($query)) {
            $response['success'] = true;
            $response['message'] = 'Status updated successfully';
        } else {
            throw new Exception('Failed to update status: ' . $conn->error);
        }
    } elseif ($action === 'bulk_resolve') {
        error_log("bulk_resolve action detected");
        
        $recordIds = json_decode($_POST['record_ids'] ?? '[]', true);
        
        error_log("Decoded record_ids: " . json_encode($recordIds));
        
        if (empty($recordIds) || !is_array($recordIds)) {
            throw new Exception('No records selected or invalid format');
        }

        // Sanitize IDs
        $safeIds = array_filter(array_map('intval', $recordIds));
        
        if (empty($safeIds)) {
            throw new Exception('No valid record IDs after sanitization');
        }

        $idString = implode(',', $safeIds);
        $query = "UPDATE record SET status = 'Resolved' WHERE id IN ($idString)";
        
        if ($conn->query($query)) {
            $updated = $conn->affected_rows;
            $response['success'] = true;
            $response['message'] = "Records resolved successfully ($updated records updated)";
        } else {
            throw new Exception('Failed to resolve records: ' . $conn->error);
        }
    } elseif ($action === 'bulk_delete') {
        $recordIds = json_decode($_POST['record_ids'] ?? '[]', true);

        if (empty($recordIds) || !is_array($recordIds)) {
            throw new Exception('No records selected or invalid format. Received: ' . json_encode($recordIds));
        }

        // Sanitize IDs
        $safeIds = array_filter(array_map('intval', $recordIds));
        
        if (empty($safeIds)) {
            throw new Exception('Invalid record IDs. Original: ' . json_encode($recordIds) . ', Sanitized: ' . json_encode($safeIds));
        }

        $idString = implode(',', $safeIds);
        $query = "DELETE FROM record WHERE id IN ($idString)";
        
        if ($conn->query($query)) {
            $response['success'] = true;
            $response['message'] = 'Records deleted successfully';
        } else {
            throw new Exception('Failed to delete records: ' . $conn->error);
        }
    } else {
        throw new Exception("Invalid or missing action. Received: '$action'");
    }

} catch (Exception $e) {
    error_log("Error in update-status-handler: " . $e->getMessage());
    $response['message'] = $e->getMessage();
}

error_log("Response: " . json_encode($response));
echo json_encode($response);
exit;
