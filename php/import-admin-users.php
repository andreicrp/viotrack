<?php
session_start();
require_once('../connect.php');
require_once('../auth_check.php');

header('Content-Type: application/json');

// Check if user is logged in and is admin
requireAdmin();

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['adminUsers']) || !is_array($data['adminUsers'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input data']);
    exit();
}

$adminUsers = $data['adminUsers'];
$imported = 0;
$errors = [];

// Process each admin user
foreach ($adminUsers as $index => $admin) {
    // Validate required fields
    if (empty($admin['fname']) || empty($admin['lname']) || empty($admin['email']) || empty($admin['role']) || empty($admin['password'])) {
        $errors[] = "Row " . ($index + 2) . ": Missing required fields (first name, last name, email, role, password)";
        continue;
    }
    
    $fname = trim($admin['fname']);
    $mname = trim($admin['mname'] ?? '');
    $lname = trim($admin['lname']);
    $email = trim($admin['email']);
    $role = trim($admin['role']);
    $password = trim($admin['password']);
    
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
    
    // Validate role
    $validRoles = ['Admin', 'Super Admin', 'System Admin'];
    if (!in_array($role, $validRoles)) {
        $errors[] = "Row " . ($index + 2) . ": Invalid role ($role). Valid roles are: " . implode(', ', $validRoles);
        continue;
    }
    
    // Check if email already exists in admin table
    $checkEmail = $conn->prepare("SELECT id FROM admin WHERE email = ?");
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
    
    // Insert admin into database
    $stmt = $conn->prepare("INSERT INTO admin (fname, mname, lname, email, password, role, image) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        $errors[] = "Row " . ($index + 2) . ": Database error - " . $conn->error;
        continue;
    }
    
    $stmt->bind_param("sssssss", $fname, $mname, $lname, $email, $hashedPassword, $role, $imagePath);
    
    if ($stmt->execute()) {
        $adminId = $conn->insert_id;
        $imported++;
        
        // Log activity
        $userId = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 1;
        $description = "Import Admin (ID: $adminId, Name: $fname $lname, Email: $email, Role: $role)";
        $activityStmt = $conn->prepare("INSERT INTO activity (aid, description, date) VALUES (?, ?, NOW())");
        if ($activityStmt) {
            $activityStmt->bind_param("is", $userId, $description);
            $activityStmt->execute();
            $activityStmt->close();
        }
    } else {
        $errors[] = "Row " . ($index + 2) . ": Failed to insert admin - " . $stmt->error;
    }
    
    $stmt->close();
}

$response = [
    'success' => $imported > 0,
    'imported' => $imported,
    'total' => count($adminUsers),
    'message' => $imported > 0 ? "Successfully imported $imported admin user(s)" : 'No admin users were imported'
];

if (!empty($errors)) {
    $response['errors'] = $errors;
}

echo json_encode($response);

$conn->close();
?>
