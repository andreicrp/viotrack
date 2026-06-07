<?php
// import-violations-handler.php
session_start();
include 'connect.php';
include 'auth_check.php';

// Require login and admin access
requireAdmin();

// Set content type to JSON
header('Content-Type: application/json');

// Get JSON data
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || !isset($data['violations']) || !is_array($data['violations'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$violations = $data['violations'];
$imported = 0;
$errors = [];

// Prepare statement
$stmt = $conn->prepare("INSERT INTO violation (title, description, type) VALUES (?, ?, ?)");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

// Process each violation
foreach ($violations as $violation) {
    $title = isset($violation['title']) ? trim($violation['title']) : '';
    $description = isset($violation['description']) ? trim($violation['description']) : '';
    $type = isset($violation['type']) ? trim($violation['type']) : 'Minor';
    
    // Validate required fields
    if (empty($title)) {
        $errors[] = 'Violation title cannot be empty';
        continue;
    }
    
    // Validate type
    $validTypes = ['Minor', 'Serious', 'Major'];
    if (!in_array($type, $validTypes)) {
        $type = 'Minor'; // Default to Minor if invalid
    }
    
    // Bind parameters and execute
    $stmt->bind_param("sss", $title, $description, $type);
    
    if ($stmt->execute()) {
        $imported++;
    } else {
        $errors[] = "Failed to import: $title - " . $stmt->error;
    }
}

$stmt->close();

// Log import activity
if ($imported > 0) {
    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = "Imported $imported violation(s) from CSV";
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $adminId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
}

$conn->close();

// Return response
echo json_encode([
    'success' => true,
    'imported' => $imported,
    'total' => count($violations),
    'errors' => $errors
]);
?>
