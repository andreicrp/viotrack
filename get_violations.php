<?php
// get_violations.php - Fetch all violations grouped by type

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');
require_once('connect.php');

// Fetch all violations ordered by type
$query = "SELECT id, title, type FROM violation ORDER BY type ASC, title ASC";
$result = $conn->query($query);

$violations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $violations[] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'type' => $row['type']
        ];
    }
}

echo json_encode([
    'success' => true,
    'violations' => $violations,
    'total' => count($violations)
]);

exit;
?>
