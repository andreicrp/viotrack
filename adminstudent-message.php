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

// Check if POST data exists
if (!isset($_POST['ssid']) || !isset($_POST['reporttype']) || !isset($_POST['reportdate'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

// Sanitize inputs
$ssid = mysqli_real_escape_string($conn, $_POST['ssid']);
$reporttype = mysqli_real_escape_string($conn, $_POST['reporttype']);
$reportdate = mysqli_real_escape_string($conn, $_POST['reportdate']);
$reportcomment = isset($_POST['reportcomment']) ? mysqli_real_escape_string($conn, $_POST['reportcomment']) : '';

// Get student information
$sql = mysqli_query($con, "SELECT * FROM student WHERE id='{$ssid}'");

if (!$sql || mysqli_num_rows($sql) == 0) {
    echo json_encode(['success' => false, 'message' => 'Student not found']);
    exit();
}

$row = mysqli_fetch_array($sql);

// Format the date for display
$displayDate = date('M d, Y h:i A', strtotime($reportdate));

// Prepare SMS message
$message = sprintf(
    "Hi %s, the school administrator has scheduled a meeting (%s) at guidance office on %s. Comment: %s", 
    $row['guardian'], 
    $reporttype, 
    $displayDate, 
    $reportcomment
);

// Send SMS
$url = 'https://sms.iprogtech.com/api/v1/sms_messages';
$data = [
    'api_token' => 'pt6f83e98e7618ee2d99981fb2f13510ec294def36',
    'message' => $message,
    'phone_number' => $row['guardiancontact']
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded'
]);

$sms_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Insert report into database
$insertQuery = "INSERT INTO report (sid, type, date, comment, status) VALUES (
    '{$ssid}', 
    '{$reporttype}', 
    '{$reportdate}', 
    '{$reportcomment}', 
    'Pending'
)";

$insertResult = mysqli_query($con, $insertQuery);

if (!$insertResult) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . mysqli_error($con),
        'sms_response' => $sms_response,
        'sms_status' => $http_code
    ]);
    exit();
}

// Return success response
echo json_encode([
    'success' => true, 
    'message' => 'Report created and SMS sent successfully',
    'report_id' => mysqli_insert_id($con),
    'sms_response' => $sms_response,
    'sms_status' => $http_code
]);
?>