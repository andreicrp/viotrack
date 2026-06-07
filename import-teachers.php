<?php
session_start();
require_once('connect.php');
require_once('auth_check.php');

header('Content-Type: application/json');

// Check if user is logged in and is admin
requireAdmin();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['teachers']) || !is_array($data['teachers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$teachers = $data['teachers'];
$imported = 0;
$errors = [];

// Process each teacher
foreach ($teachers as $index => $teacher) {
    // Validate required fields
    if (empty($teacher['fname']) || empty($teacher['lname']) || empty($teacher['email']) || empty($teacher['position']) || empty($teacher['password'])) {
        $errors[] = "Row " . ($index + 2) . ": Missing required fields (first name, last name, email, position, password)";
        continue;
    }
    
    $fname = trim($teacher['fname']);
    $mname = trim($teacher['mname'] ?? '');
    $lname = trim($teacher['lname']);
    $email = trim($teacher['email']);
    $position = trim($teacher['position']);
    $password = trim($teacher['password']);
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Row " . ($index + 2) . ": Invalid email format ($email)";
        continue;
    }
    
    // Validate password strength
    if (strlen($password) < 6) {
        $errors[] = "Row " . ($index + 2) . ": Password must be at least 6 characters";
        continue;
    }
    
    // Validate position
    $validPositions = ['Teacher', 'Head Teacher', 'Department Head', 'Admin'];
    if (!in_array($position, $validPositions)) {
        $errors[] = "Row " . ($index + 2) . ": Invalid position ($position)";
        continue;
    }
    
    // Check if email already exists
    $checkEmail = $conn->prepare("SELECT id FROM teacher WHERE email = ?");
    if (!$checkEmail) {
        $errors[] = "Row " . ($index + 2) . ": Database error";
        continue;
    }
    
    $checkEmail->bind_param("s", $email);
    if (!$checkEmail->execute()) {
        $errors[] = "Row " . ($index + 2) . ": Database error";
        $checkEmail->close();
        continue;
    }
    
    $result = $checkEmail->get_result();
    if ($result->num_rows > 0) {
        $errors[] = "Row " . ($index + 2) . ": Email already exists ($email)";
        $checkEmail->close();
        continue;
    }
    $checkEmail->close();
    
    // Hash the password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Set default image
    $imagePath = 'image/default.png';
    
    // Insert teacher into database
    $stmt = $conn->prepare("INSERT INTO teacher (fname, mname, lname, email, password, position, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $errors[] = "Row " . ($index + 2) . ": Database error - " . $conn->error;
        continue;
    }
    
    $stmt->bind_param("sssssss", $fname, $mname, $lname, $email, $hashedPassword, $position, $imagePath);
    
    if ($stmt->execute()) {
        $teacherId = $conn->insert_id;
        $imported++;
        
        // Log activity
        $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        $description = "Import Teacher (ID: $teacherId, Name: $fname $lname, Email: $email)";
        $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
        if ($activityStmt) {
            $activityStmt->bind_param("is", $adminId, $description);
            $activityStmt->execute();
            $activityStmt->close();
        }
    } else {
        $errors[] = "Row " . ($index + 2) . ": Failed to insert teacher - " . $stmt->error;
    }
    
    $stmt->close();
}

$response = [
    'success' => $imported > 0,
    'imported' => $imported,
    'total' => count($teachers),
    'message' => $imported > 0 ? "Successfully imported $imported teacher(s)" : 'No teachers were imported'
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

echo json_encode($response);

$conn->close();
?>
