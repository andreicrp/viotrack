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

// Get POST data - read only once
$rawInput = file_get_contents('php://input');

if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Empty request body']);
    exit();
}

$data = json_decode($rawInput, true);

if ($data === null) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid JSON: ' . json_last_error_msg(),
        'code' => json_last_error()
    ]);
    exit();
}

// Validate required fields
$adminId = isset($data['id']) ? (int)$data['id'] : 0;
$fname = isset($data['fname']) ? trim($data['fname']) : '';
$mname = isset($data['mname']) ? trim($data['mname']) : '';
$lname = isset($data['lname']) ? trim($data['lname']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$role = isset($data['role']) ? trim($data['role']) : '';
$password = isset($data['password']) ? trim($data['password']) : '';

// Debug: Log what we received
error_log("Admin Update - ID: $adminId, fname: '$fname', mname: '$mname', lname: '$lname', email: '$email', role: '$role', password: " . (empty($password) ? 'EMPTY' : 'PROVIDED'));

if ($adminId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID: ' . $adminId]);
    exit();
}

if (empty($fname) || empty($lname) || empty($email) || empty($role)) {
    http_response_code(400);
    $missing = [];
    if (empty($fname)) $missing[] = 'fname';
    if (empty($lname)) $missing[] = 'lname';
    if (empty($email)) $missing[] = 'email';
    if (empty($role)) $missing[] = 'role';
    echo json_encode(['success' => false, 'message' => 'Missing required fields: ' . implode(', ', $missing)]);
    exit();
}

// Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit();
}

// Validate role
$validRoles = ['Admin', 'Super Admin', 'System Admin'];
if (!in_array($role, $validRoles)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid admin role']);
    exit();
}

// Check if email already exists for other admins
$checkEmail = $conn->prepare("SELECT id FROM admin WHERE email = ? AND id != ?");
if (!$checkEmail) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$checkEmail->bind_param("si", $email, $adminId);
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

// Get current admin data
$getCurrentData = $conn->prepare("SELECT image, password, token, code FROM admin WHERE id = ?");
if (!$getCurrentData) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$getCurrentData->bind_param("i", $adminId);
if (!$getCurrentData->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute error: ' . $getCurrentData->error]);
    exit();
}
$currentData = $getCurrentData->get_result()->fetch_assoc();
$getCurrentData->close();
if (!$currentData) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    exit();
}

// Handle image upload
$imagePath = $currentData['image'];
$imageChanged = false;

if (!empty($data['imageData'])) {
    $imageData = $data['imageData'];
    
    if (strpos($imageData, 'data:image') === 0) {
        // Decode base64 image
        if (preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
            $ext = $matches[1];
            if (!in_array($ext, ['jpeg', 'png', 'gif', 'jpg'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Invalid image type']);
                exit();
            }
            
            // Convert jpeg to jpg if needed
            if ($ext === 'jpeg') $ext = 'jpg';
            
            $imageDataBase64 = substr($imageData, strpos($imageData, ',') + 1);
            $decodedImage = base64_decode($imageDataBase64);
            
            if ($decodedImage === false) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Failed to decode image']);
                exit();
            }
            
            // Delete old image if not default
            if ($currentData['image'] && $currentData['image'] !== 'image/default.png' && file_exists($currentData['image'])) {
                @unlink($currentData['image']);
            }
            
            // Create image directory if not exists
            if (!is_dir('image')) {
                mkdir('image', 0755, true);
            }
            
            // Generate unique filename
            $filename = date('dmYHis') . '_' . uniqid() . '.' . $ext;
            $imagePath = 'image/' . $filename;
            
            // Save image
            if (file_put_contents($imagePath, $decodedImage) === false) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'Failed to save image']);
                exit();
            }
            
            $imageChanged = true;
        }
    }
}

// Determine password to use
if (!empty($password)) {
    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters long']);
        exit();
    }
    $finalPassword = password_hash($password, PASSWORD_DEFAULT);
} else {
    $finalPassword = $currentData['password'];
}

// Update admin in database
$token = isset($currentData['token']) && !empty($currentData['token']) ? $currentData['token'] : null;
$code = isset($currentData['code']) && !empty($currentData['code']) ? $currentData['code'] : null;

$stmt = $conn->prepare("UPDATE admin SET fname = ?, mname = ?, lname = ?, email = ?, role = ?, password = ?, image = ? WHERE id = ?");
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("sssssssi", $fname, $mname, $lname, $email, $role, $finalPassword, $imagePath, $adminId);
if (!$stmt->execute()) {
    http_response_code(500);
    $errorMsg = $stmt->error;
    error_log("Admin update error: " . $errorMsg);
    echo json_encode(['success' => false, 'message' => 'Execute error: ' . $errorMsg]);
    $stmt->close();
    exit();
}

$stmt->close();

// Log activity regardless of affected rows
$userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
$description = "Edit Admin (ID: $adminId, Name: $fname $lname, Email: $email)";
$activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
if ($activityStmt) {
    $activityStmt->bind_param("is", $userId, $description);
    $activityStmt->execute();
    $activityStmt->close();
}

echo json_encode([
    'success' => true,
    'message' => 'Admin updated successfully',
    'imageChanged' => $imageChanged
]);

$conn->close();
?>
