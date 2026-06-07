<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Check if user is admin
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
if (!$isAdmin) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Only admins can save meetings']);
    exit();
}

header('Content-Type: application/json');

require_once('connect.php');

if (!$conn) {
    error_log("Save-meeting: Database connection failed");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Modify table schema to allow NULL for sid (if not already)
$alterTableSQL = "ALTER TABLE report MODIFY sid INT NULL";
$conn->query($alterTableSQL); // Don't fail if already modified

$data = json_decode(file_get_contents('php://input'), true);

// Log incoming data
error_log("Save-meeting: Received data: " . json_encode($data));

// Validate required fields
if (!$data || !isset($data['date']) || !isset($data['time']) || !isset($data['name'])) {
    error_log("Save-meeting: Missing required fields - date: " . isset($data['date']) . ", time: " . isset($data['time']) . ", name: " . isset($data['name']));
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit;
}

$date = trim($data['date']);
$time = trim($data['time']);
$name = trim($data['name']);
$reason = isset($data['reason']) ? trim($data['reason']) : '';
$id = isset($data['id']) ? intval($data['id']) : null;

// Validate inputs
if (empty($date) || empty($time) || empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Date, time, and name are required']);
    exit;
}

// Combine date and time (date is YYYY-MM-DD format from datepicker, time is HH:MM format)
$datetime = $date . ' ' . $time . ':00';

// Validate datetime format
if (!strtotime($datetime)) {
    echo json_encode(['success' => false, 'message' => 'Invalid date or time format']);
    exit;
}

try {
    if ($id) {
        // UPDATE existing meeting
        error_log("Save-meeting: Updating meeting ID $id with name: $name, datetime: $datetime");
        $stmt = $conn->prepare("UPDATE report SET comment = ?, date = ? WHERE id = ?");
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param('ssi', $reason, $datetime, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        error_log("Save-meeting: Successfully updated meeting ID $id");
        $stmt->close();
        
        // Log activity
        $activity_desc = "Updated meeting: $name (Report ID: $id)";
        $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
        if ($activity_stmt) {
            $aid = $_SESSION['user_id'];
            $activity_stmt->bind_param('is', $aid, $activity_desc);
            $activity_stmt->execute();
            $activity_stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Meeting updated successfully', 'id' => $id, 'report_id' => $id]);
    } else {
        // INSERT new meeting
        // Use the meeting name as the type field
        $type = $name; // Store the meeting name in the type field
        $status = 'Pending';
        $sid = null; // NULL for admin-created meetings (no specific student)
        
        error_log("Save-meeting: Creating new meeting - name: $name, type: $type, datetime: $datetime, reason: $reason");
        
        // Insert with sid as NULL (for general admin meetings)
        $stmt = $conn->prepare("INSERT INTO report (sid, comment, date, type, status) VALUES (?, ?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Save-meeting: Prepare failed: " . $conn->error);
            throw new Exception("Prepare failed: " . $conn->error);
        }
        
        // Bind with sid parameter (NULL value is allowed after schema modification)
        $stmt->bind_param('issss', $sid, $reason, $datetime, $type, $status);
        
        if (!$stmt->execute()) {
            error_log("Save-meeting: Execute failed: " . $stmt->error);
            throw new Exception("Execute failed: " . $stmt->error);
        }
        
        $newId = $conn->insert_id;
        error_log("Save-meeting: Successfully created meeting ID $newId");
        $stmt->close();
        
        // Log activity
        $activity_desc = "Created new meeting: $name (Report ID: $newId, Date: $datetime)";
        $activity_stmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
        if ($activity_stmt) {
            $aid = $_SESSION['user_id'];
            $activity_stmt->bind_param('is', $aid, $activity_desc);
            $activity_stmt->execute();
            $activity_stmt->close();
        }
        
        echo json_encode(['success' => true, 'message' => 'Meeting saved successfully', 'id' => $newId, 'report_id' => $newId]);
    }
} catch (Exception $e) {
    $errorMsg = $e->getMessage();
    error_log("Meeting save error: " . $errorMsg);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $errorMsg], JSON_UNESCAPED_SLASHES);
}

$conn->close();
?>
