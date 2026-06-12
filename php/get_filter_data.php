<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$type = isset($_GET['type']) ? trim($_GET['type']) : '';

if (empty($type)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Type parameter is required']);
    exit();
}

// Fetch grades by academic year
if ($type === 'grades') {
    $academicyear = isset($_GET['academicyear']) ? trim($_GET['academicyear']) : '';
    
    if (empty($academicyear)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'School year parameter is required']);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT DISTINCT grade FROM student WHERE academicyear = ? AND grade IS NOT NULL AND grade != '' ORDER BY grade ASC");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("s", $academicyear);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
        exit();
    }
    
    $result = $stmt->get_result();
    $grades = [];
    
    while ($row = $result->fetch_assoc()) {
        $grades[] = htmlspecialchars($row['grade'], ENT_QUOTES, 'UTF-8');
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'grades' => $grades]);
    $conn->close();
    exit();
}

// Fetch sections by grade
elseif ($type === 'sections') {
    $grade = isset($_GET['grade']) ? trim($_GET['grade']) : '';
    
    if (empty($grade)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Grade parameter is required']);
        exit();
    }
    
    $stmt = $conn->prepare("SELECT DISTINCT section FROM student WHERE grade = ? AND section IS NOT NULL AND section != '' ORDER BY section ASC");
    if (!$stmt) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    
    $stmt->bind_param("s", $grade);
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Execute error: ' . $stmt->error]);
        exit();
    }
    
    $result = $stmt->get_result();
    $sections = [];
    
    while ($row = $result->fetch_assoc()) {
        $sections[] = htmlspecialchars($row['section'], ENT_QUOTES, 'UTF-8');
    }
    
    $stmt->close();
    
    echo json_encode(['success' => true, 'sections' => $sections]);
    $conn->close();
    exit();
}

// Invalid type
http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Invalid filter type']);
$conn->close();
?>
