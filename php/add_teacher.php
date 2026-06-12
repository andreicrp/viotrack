<?php
session_start();
require_once('../connect.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

// Validate required fields
$fname = isset($data['fname']) ? trim($data['fname']) : '';
$mname = isset($data['mname']) ? trim($data['mname']) : '';
$lname = isset($data['lname']) ? trim($data['lname']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$position = isset($data['position']) ? trim($data['position']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

if (empty($fname) || empty($lname) || empty($email) || empty($position) || empty($password)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    exit();
}

// Validate field lengths
if (strlen($fname) > 100 || strlen($lname) > 100 || strlen($email) > 150) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Some field values are too long']);
    exit();
}

// Validate password strength
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate position
$validPositions = ['Teacher', 'Head Teacher', 'Department Head'];
if (!in_array($position, $validPositions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid position']);
    exit();
}

// Check if email already exists
$checkEmail = $conn->prepare("SELECT id FROM teacher WHERE email = ?");
if (!$checkEmail) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}

$checkEmail->bind_param("s", $email);
if (!$checkEmail->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute error: ' . $checkEmail->error]);
    exit();
}

$result = $checkEmail->get_result();
if ($result->num_rows > 0) {
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Email already exists']);
    $checkEmail->close();
    exit();
}
$checkEmail->close();

// Handle image upload
$imagePath = 'image/default.png';
if (!empty($data['imageData'])) {
    // Extract base64 image data
    $imageData = $data['imageData'];
    if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $type)) {
        $imageData = substr($imageData, strpos($imageData, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, gif
        
        if (!in_array($type, ['jpg', 'jpeg', 'png', 'gif'])) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }
        
        $imageData = base64_decode($imageData);
        
        if ($imageData === false) {
            echo json_encode(['success' => false, 'message' => 'Failed to decode image']);
            exit;
        }
        
        // Generate unique filename
        $filename = date('dmYHis') . 'teacher.' . $type;
        $imagePath = 'image/' . $filename;
        
        // Save image
        if (!file_put_contents($imagePath, $imageData)) {
            echo json_encode(['success' => false, 'message' => 'Failed to save image']);
            exit;
        }
    }
}

// Hash the password
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Insert teacher into database
$stmt = $conn->prepare("INSERT INTO teacher (fname, mname, lname, email, password, position, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit();
}

$stmt->bind_param("sssssss", $fname, $mname, $lname, $email, $hashedPassword, $position, $imagePath);

if ($stmt->execute()) {
    $teacherId = $conn->insert_id;
    
    // Log activity
    $adminId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
    $description = "Insert New Teacher (ID: $teacherId, Name: $fname $lname)";
    $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
    if ($activityStmt) {
        $activityStmt->bind_param("is", $adminId, $description);
        $activityStmt->execute();
        $activityStmt->close();
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'Teacher added successfully',
        'teacherId' => $teacherId
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to add teacher: ' . $stmt->error]);
}

$stmt->close();
$conn->close();
?>