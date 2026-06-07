<?php
session_start();

// Require login
require_once 'auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

require_once('connect.php');
date_default_timezone_set('Asia/Manila');

$sql = "SELECT lat, lng FROM record WHERE lat IS NOT NULL";
$result = $conn->query($sql);

$locations = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = $row;
    }
}
$con->close();
echo json_encode($locations);
?>