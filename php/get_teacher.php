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

// Get and validate teacher ID
$teacherId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($teacherId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid teacher ID']);
    exit();
}

// Fetch teacher data using prepared statement
$stmt = $conn->prepare("SELECT id, fname, mname, lname, email, password, position, image FROM teacher WHERE id = ?");

if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
    exit();
}

$stmt->bind_param("i", $teacherId);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Execute failed: ' . $stmt->error]);
    exit();
}

$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Teacher not found']);
    $stmt->close();
    exit();
}

$teacher = $result->fetch_assoc();
$stmt->close();

// Prepare response with proper escaping
$response = [
    'success' => true,
    'teacher' => [
        'id' => (int)$teacher['id'],
        'fname' => htmlspecialchars($teacher['fname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'mname' => htmlspecialchars($teacher['mname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'lname' => htmlspecialchars($teacher['lname'] ?? '', ENT_QUOTES, 'UTF-8'),
        'email' => htmlspecialchars($teacher['email'] ?? '', ENT_QUOTES, 'UTF-8'),
        'password' => $teacher['password'],
        'position' => htmlspecialchars($teacher['position'] ?? 'Teacher', ENT_QUOTES, 'UTF-8'),
        'image' => htmlspecialchars($teacher['image'] ?? 'image/default.jpg', ENT_QUOTES, 'UTF-8')
    ]
];

echo json_encode($response);
$conn->close();
?>