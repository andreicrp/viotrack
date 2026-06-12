<?php
// delete-record-handler.php - Handle deletion of violation records

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');
require_once '../connect.php';

$response = ['success' => false, 'message' => ''];

try {
    $action = $_POST['action'] ?? '';

    if ($action === 'delete') {
        $recordId = intval($_POST['record_id'] ?? 0);
        
        if ($recordId <= 0) {
            throw new Exception('Invalid record ID');
        }

        $query = "DELETE FROM record WHERE id = $recordId";
        
        if ($conn->query($query)) {
            $response['success'] = true;
            $response['message'] = 'Record deleted successfully';
        } else {
            throw new Exception('Failed to delete record: ' . $conn->error);
        }
    } elseif ($action === 'bulk_delete') {
        $recordIds = json_decode($_POST['record_ids'] ?? '[]', true);
        
        if (empty($recordIds) || !is_array($recordIds)) {
            throw new Exception('No records selected');
        }

        $safeIds = array_filter(array_map('intval', $recordIds));
        if (empty($safeIds)) {
            throw new Exception('Invalid record IDs');
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
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);
exit;
?>
