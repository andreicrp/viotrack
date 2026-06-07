<?php
// send-sms.php - Send SMS messages to guardian contacts

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to user
ini_set('log_errors', 1); // Log errors

// Start session first before any headers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Set content type header
header('Content-Type: application/json');

// Prevent any output buffering issues
ob_clean();

// Database connection
require_once 'connect.php';

try {
    // Get POST data
    $input = json_decode(file_get_contents('php://input'), true);
    
    // Debug: Log received data
    error_log('SMS Request: ' . print_r($input, true));
    
    if (!$input) {
        throw new Exception('Invalid input data - could not decode JSON');
    }
    
    $studentId = isset($input['student_id']) ? intval($input['student_id']) : 0;
    $phoneNumber = isset($input['phone_number']) ? trim($input['phone_number']) : '';
    $message = isset($input['message']) ? trim($input['message']) : '';
    $reportType = isset($input['report_type']) ? trim($input['report_type']) : '';
    $reportDate = isset($input['report_date']) ? trim($input['report_date']) : '';
    
    // Debug: Log parsed values
    error_log("Parsed values - ID: $studentId, Phone: $phoneNumber, Type: $reportType, Date: $reportDate");
    
    // Validate inputs - message can be empty, but report type and date are required
    if ($studentId === 0) {
        throw new Exception('Missing or invalid student ID');
    }
    
    if (empty($phoneNumber)) {
        throw new Exception('Missing phone number');
    }
    
    if (empty($reportType)) {
        throw new Exception('Missing report type');
    }
    
    if (empty($reportDate)) {
        throw new Exception('Missing report date');
    }
    
    // Verify student exists
    $stmt = $conn->prepare("SELECT id, fname, lname, guardiancontact FROM student WHERE id = ?");
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param("i", $studentId);
    if (!$stmt->execute()) {
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $student = $result->fetch_assoc();
    $stmt->close();
    
    if (!$student) {
        throw new Exception('Student not found with ID: ' . $studentId);
    }
    
    // Sanitize phone number (remove special characters except +)
    $sanitizedPhone = preg_replace('/[^\d+]/', '', $phoneNumber);
    
    // Check if report table exists
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'report'");
    if ($tableCheckResult && $tableCheckResult->num_rows === 0) {
        throw new Exception('Report table does not exist in database');
    }
    
    // Insert report into database using prepared statement
    $reportStatus = 'Pending';
    
    // Use positional parameters for better compatibility
    $insertSQL = "INSERT INTO report (sid, type, date, comment, status) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSQL);
    
    if (!$stmt) {
        throw new Exception('Prepare failed: ' . $conn->error);
    }
    
    // Bind parameters: i=int, s=string
    if (!$stmt->bind_param("issss", $studentId, $reportType, $reportDate, $message, $reportStatus)) {
        throw new Exception('Bind failed: ' . $stmt->error);
    }
    
    // Execute the statement
    if (!$stmt->execute()) {
        throw new Exception('Execute failed: ' . $stmt->error);
    }
    
    // Get the inserted ID using multiple methods for reliability
    $reportId = $stmt->insert_id;
    
    if (!$reportId) {
        // Try LAST_INSERT_ID()
        $result = $conn->query("SELECT LAST_INSERT_ID() as lastid");
        if ($result) {
            $row = $result->fetch_assoc();
            $reportId = intval($row['lastid']);
        }
    }
    
    if (!$reportId) {
        // Get the max id
        $result = $conn->query("SELECT MAX(id) as maxid FROM report WHERE sid = " . intval($studentId));
        if ($result) {
            $row = $result->fetch_assoc();
            $reportId = intval($row['maxid']);
        }
    }
    
    $stmt->close();
    
    // Ensure we have a valid ID
    if (!$reportId || $reportId <= 0) {
        error_log('Warning: Report inserted but ID could not be determined');
        $reportId = 0;
    }
    
    error_log('Report insert attempt - ID: ' . $reportId . ', Student: ' . $studentId . ', Type: ' . $reportType);
    
    // Log the SMS attempt to activity table (optional)
    $adminId = isset($_SESSION['user_id']) ? intval($_SESSION['user_id']) : 0;
    $description = "SMS report sent to $sanitizedPhone for report type: $reportType on $reportDate (Report ID: $reportId)";
    
    @$conn->query("INSERT INTO activity (aid, description, date) VALUES ($adminId, '$description', NOW())");
    
    // Send SMS via iprogtech API
    $smsMessage = "PHCM VioTrack: Hi guardian, the school administrator has scheduled a meeting ($reportType) on $reportDate. Message: $message";
    
    $smsUrl = 'https://sms.iprogtech.com/api/v1/sms_messages';
    $smsData = [
        'api_token' => 'pt6f83e98e7618ee2d99981fb2f13510ec294def36',
        'message' => $smsMessage,
        'phone_number' => $sanitizedPhone,
        'sender_id' => 'PHCM VioTrack'
    ];
    
    $ch = curl_init($smsUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($smsData));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $smsResponse = curl_exec($ch);
    $smsHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $smsError = curl_error($ch);
    curl_close($ch);
    
    // Log SMS API response
    error_log('SMS API Response: ' . $smsResponse . ' (HTTP ' . $smsHttpCode . ')');
    error_log('SMS API Request to: ' . $sanitizedPhone . ' Message: ' . substr($smsMessage, 0, 50) . '...');
    if ($smsError) {
        error_log('SMS API Error: ' . $smsError);
    }
    
    // Determine SMS success (API returns 200 for success, but we'll accept anything 200-299)
    $smsSent = ($smsHttpCode >= 200 && $smsHttpCode < 300);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => $smsSent ? 'Report created and SMS sent successfully' : 'Report created (SMS delivery attempted)',
        'phone' => $sanitizedPhone,
        'student_name' => $student['fname'] . ' ' . $student['lname'],
        'report_id' => $reportId,
        'sms_status' => $smsHttpCode,
        'sms_sent' => $smsSent,
        'sms_response' => $smsResponse
    ]);
    
} catch (Exception $e) {
    error_log('SMS Error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($conn) && $conn) {
        $conn->close();
    }
}
?>
