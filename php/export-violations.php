<?php
// export-violations.php - Export all violations as CSV
session_start();
include '../auth_check.php';
requireAdminOrTeacher();

header('Content-Type: application/json');
require_once '../connect.php';

// Fetch all violations with all fields
$query = "SELECT id, title, description, type FROM violation ORDER BY id DESC";
$result = mysqli_query($conn, $query);

if (!$result) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit();
}

$violations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $violations[] = $row;
}

echo json_encode(['success' => true, 'violations' => $violations]);
mysqli_close($conn);
?>
