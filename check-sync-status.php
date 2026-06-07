<?php
session_start();
require 'auth_check.php';
require 'connect.php';

header('Content-Type: application/json');

$user_id = $_SESSION['id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    // Count pending (unsynced) records
    $pending_stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM record 
        WHERE is_synced = 0 AND aid = ?
    ");
    $pending_stmt->bind_param('i', $user_id);
    $pending_stmt->execute();
    $pending_result = $pending_stmt->get_result();
    $pending_data = $pending_result->fetch_assoc();
    $pending_count = $pending_data['count'] ?? 0;
    $pending_stmt->close();

    // Get last sync time
    $last_sync_stmt = $conn->prepare("
        SELECT sync_date FROM sync_history 
        WHERE user_id = ? 
        ORDER BY sync_date DESC LIMIT 1
    ");
    $last_sync_stmt->bind_param('i', $user_id);
    $last_sync_stmt->execute();
    $last_sync_result = $last_sync_stmt->get_result();
    $last_sync_data = $last_sync_result->fetch_assoc();
    $last_sync_stmt->close();

    echo json_encode([
        'success' => true,
        'pending_count' => $pending_count,
        'last_sync' => $last_sync_data['sync_date'] ?? null,
        'online' => true
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
