<?php
require '../auth_check.php';
require '../connect.php';

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

// Get data from frontend
$input = json_decode(file_get_contents('php://input'), true);
$records = $input['records'] ?? [];

if (empty($records)) {
    echo json_encode(['success' => true, 'error' => 'No records to sync', 'synced' => [], 'failed' => []]);
    exit();
}

$synced = [];
$failed = [];
$skipped = [];

// Prepare duplicate check statement
$checkStmt = $conn->prepare("
    SELECT id FROM record 
    WHERE sid = ? AND vid = ? AND offline_recorded_at = ?
    LIMIT 1
");

// Process each record
foreach ($records as $record) {
    try {
        $sid = $record['sid'] ?? null;
        $vid = $record['vid'] ?? null;
        $date = $record['date'] ?? date('Y-m-d H:i:s');
        $offline_time = $record['offline_recorded_at'] ?? $date;
        $status = $record['status'] ?? 'Pending';
        $aid = $user_id;
        $type = $record['type'] ?? 'Violation';
        $lat = $record['lat'] ?? '0';
        $lng = $record['lng'] ?? '0';
        $proof = $record['proof'] ?? null;
        $accuracy = $record['accuracy'] ?? 0;

        // DUPLICATE CHECK: Skip if same (sid, vid, offline_recorded_at) already exists
        if ($checkStmt && $sid && $vid && $offline_time) {
            $checkStmt->bind_param('sss', $sid, $vid, $offline_time);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            if ($checkResult && $checkResult->num_rows > 0) {
                // Already synced, mark as synced so localStorage clears it
                $synced[] = $record['id'];
                $skipped[] = $record['id'];
                error_log('Skipping duplicate record: sid=' . $sid . ', vid=' . $vid);
                continue;
            }
        }

        // Prepare INSERT statement
        $stmt = $conn->prepare("
            INSERT INTO record 
            (status, vid, date, sid, aid, type, lat, lng, proof, is_synced, offline_recorded_at, sync_timestamp, accuracy)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW(), ?)
        ");
        
        if (!$stmt) {
            $failed[] = $record['id'];
            error_log('Prepare failed: ' . $conn->error);
            continue;
        }
        
        $stmt->bind_param(
            'ssssssssssd',
            $status,
            $vid,
            $date,
            $sid,
            $aid,
            $type,
            $lat,
            $lng,
            $proof,
            $offline_time,
            $accuracy
        );
        
        if ($stmt->execute()) {
            $synced[] = $record['id'];
            error_log('Synced record: ' . $record['id']);
        } else {
            $failed[] = $record['id'];
            error_log('Execute failed: ' . $stmt->error);
        }
        
        $stmt->close();
        
    } catch (Exception $e) {
        $failed[] = $record['id'];
        error_log('Sync error: ' . $e->getMessage());
    }
}

if ($checkStmt) {
    $checkStmt->close();
}

// Log sync attempt in sync_history (only if something was actually inserted)
if (!empty($synced)) {
    try {
        $log_stmt = $conn->prepare("
            INSERT INTO sync_history (user_id, record_ids, status)
            VALUES (?, ?, ?)
        ");
        
        if ($log_stmt) {
            $synced_json = json_encode($synced);
            $log_status = count($failed) === 0 ? 'success' : 'partial';
            
            $log_stmt->bind_param('iss', $user_id, $synced_json, $log_status);
            $log_stmt->execute();
            $log_stmt->close();
        }
    } catch (Exception $e) {
        error_log('Sync log error: ' . $e->getMessage());
    }
}

$inserted_count = count($synced) - count($skipped);

// Return response
echo json_encode([
    'success' => true,
    'synced' => $synced,
    'failed' => $failed,
    'skipped' => $skipped,
    'message' => $inserted_count . ' violations synced successfully (' . count($skipped) . ' duplicates skipped)',
    'synced_count' => count($synced),
    'inserted_count' => $inserted_count,
    'failed_count' => count($failed)
]);

$conn->close();
?>
