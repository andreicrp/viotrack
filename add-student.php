<?php
session_start();

// Require login
require_once 'auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

// Database connection
$host = 'localhost';
$dbname = 'viotrack';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Check if form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

try {
    // Get form data
    $fname = trim($_POST['fname'] ?? '');
    $mname = trim($_POST['mname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $lrn = trim($_POST['lrn'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $academicyear = trim($_POST['academicyear'] ?? '');
    $grade = trim($_POST['grade'] ?? '');
    $section = trim($_POST['section'] ?? '');
    $guardian = trim($_POST['guardian'] ?? '');
    $guardiancontact = trim($_POST['guardiancontact'] ?? '');
    $email = trim($_POST['email'] ?? '');
    
    // Validate required fields
    if (empty($fname) || empty($lname) || empty($lrn) || empty($gender) || 
        empty($academicyear) || empty($grade) || empty($section) || 
        empty($guardian) || empty($guardiancontact) || empty($email)) {
        echo json_encode([
            'success' => false,
            'message' => 'All required fields must be filled'
        ]);
        exit();
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid email format'
        ]);
        exit();
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT id FROM student WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists'
        ]);
        exit();
    }
    
    // Check if LRN already exists
    $stmt = $conn->prepare("SELECT id FROM student WHERE lrn = ?");
    $stmt->execute([$lrn]);
    if ($stmt->fetch()) {
        echo json_encode([
            'success' => false,
            'message' => 'Student ID (LRN) already exists'
        ]);
        exit();
    }
    
    // Generate random 8-character password
    $password = substr(str_shuffle('0123456789abcdef'), 0, 8);
    
    // Handle image upload - Default to image/default.png
    $imagePath = 'image/default.png';
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = 'image/';
        
        // Create directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileInfo = pathinfo($_FILES['image']['name']);
        $extension = strtolower($fileInfo['extension']);
        
        // Validate file type
        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($extension, $allowedTypes)) {
            echo json_encode([
                'success' => false,
                'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed'
            ]);
            exit();
        }
        
        // Validate file size (max 5MB)
        if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
            echo json_encode([
                'success' => false,
                'message' => 'File size exceeds 5MB limit'
            ]);
            exit();
        }
        
        // Generate unique filename
        $newFileName = date('dmYHis') . '_' . uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $newFileName;
        
        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = $targetPath;
        }
    }
    
    // Insert student into database with default image/default.png if no file uploaded
    $sql = "INSERT INTO student (fname, mname, lname, lrn, gender, academicyear, grade, section, guardian, guardiancontact, email, password, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sql);
    $result = $stmt->execute([
        $fname,
        $mname,
        $lname,
        $lrn,
        $gender,
        $academicyear,
        $grade,
        $section,
        $guardian,
        $guardiancontact,
        $email,
        $password,
        $imagePath  // Will be 'image/default.png' if no file uploaded
    ]);
    
    if ($result) {
        // Log activity if admin is logged in
        if (isset($_SESSION['aid'])) {
            $activitySql = "INSERT INTO activity (aid, description, date) VALUES (?, 'Insert New Student', ?)";
            $activityStmt = $conn->prepare($activitySql);
            $activityStmt->execute([
                $_SESSION['aid'],
                date('Y-m-d H:i:s')
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Student added successfully',
            'password' => $password
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to add student'
        ]);
    }
    
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

?>