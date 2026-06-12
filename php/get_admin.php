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

// Get and validate admin ID
$adminId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($adminId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid admin ID']);
    exit();
}

// Fetch admin data using prepared statement from admin table
$stmt = $conn->prepare("SELECT id, fname, mname, lname, email, password, role, image FROM admin WHERE id = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $adminId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Admin user not found']);
    $stmt->close();
    exit();
}

$admin = $result->fetch_assoc();
$stmt->close();

// Prepare response with proper escaping
$response = [
    'success' => true,
    'admin' => [
        'id' => (int)$admin['id'],
        'fname' => htmlspecialchars($admin['fname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'mname' => htmlspecialchars($admin['mname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'lname' => htmlspecialchars($admin['lname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($admin['email'] ?? '', ENT_QUOTES, 'UTF-8'),
        'password' => $admin['password'],
        'role' => htmlspecialchars($admin['role'] ?? 'Admin', ENT_QUOTES, 'UTF-8'),
        'image' => $admin['image'] ?? null
    ]
];

echo json_encode($response);
$conn->close();
?>
    