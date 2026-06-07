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

// Increase time and memory limits for large imports
set_time_limit(300); // 5 minutes
ini_set('memory_limit', '512M');

// Database connection
$host = 'localhost';
$dbname = 'u396044097_vio';
$username = 'u396044097_vio';
$password = 'L0|+vtZ1?o';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    // Enable buffered queries for better performance
    $conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
} catch(PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ]);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

// Check if file was uploaded
if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode([
        'success' => false,
        'message' => 'No file uploaded or upload error'
    ]);
    exit();
}

try {
    $file = $_FILES['csv_file'];
    
    // Validate file type
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($fileExtension !== 'csv') {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid file type. Only CSV files are allowed.'
        ]);
        exit();
    }
    
    // Validate file size (50MB max for massive imports)
    $maxFileSize = 50 * 1024 * 1024; // 50MB
    if ($file['size'] > $maxFileSize) {
        echo json_encode([
            'success' => false,
            'message' => 'File size exceeds 50MB limit. Please split the CSV into smaller files.'
        ]);
        exit();
    }
    
    // Pre-fetch all existing emails and LRNs for fast lookup
    $existingEmails = [];
    $existingLrns = [];
    
    $stmt = $conn->prepare("SELECT email, lrn FROM student");
    $stmt->execute();
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existingEmails[strtolower($row['email'])] = true;
        $existingLrns[$row['lrn']] = true;
    }
    
    $successCount = 0;
    $errorCount = 0;
    $errors = [];
    $batch = [];
    $batchSize = 100; // Insert in batches of 100 for better performance
    
    // Read CSV file line by line (memory efficient)
    $fileHandle = fopen($file['tmp_name'], 'r');
    
    if (!$fileHandle) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to open CSV file'
        ]);
        exit();
    }
    
    // Skip header row
    $header = fgetcsv($fileHandle);
    $rowNumber = 1;
    
    // Start transaction for batch inserts
    $conn->beginTransaction();
    
    while (($row = fgetcsv($fileHandle)) !== false) {
        $rowNumber++;
        
        // Skip empty rows
        if (empty(array_filter($row))) {
            continue;
        }
        
        // Validate row has enough columns (11 columns expected)
        if (count($row) < 11) {
            $errors[] = "Row {$rowNumber}: Insufficient data columns";
            $errorCount++;
            continue;
        }
        
        // Extract and trim data
        $lrn = trim($row[0]);
        $fname = trim($row[1]);
        $mname = trim($row[2]);
        $lname = trim($row[3]);
        $email = trim($row[4]);
        $gender = trim($row[5]);
        $academicyear = trim($row[6]);
        $grade = trim($row[7]);
        $section = trim($row[8]);
        $guardian = trim($row[9]);
        $guardiancontact = trim($row[10]);
        
        // Validate required fields
        if (empty($lrn) || empty($fname) || empty($lname) || empty($email) || 
            empty($gender) || empty($academicyear) || empty($grade) || 
            empty($section) || empty($guardian) || empty($guardiancontact)) {
            $errors[] = "Row {$rowNumber}: Missing required field(s)";
            $errorCount++;
            continue;
        }
        
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Row {$rowNumber}: Invalid email format ({$email})";
            $errorCount++;
            continue;
        }
        
        // Check for duplicates (in-memory lookup - much faster)
        $emailLower = strtolower($email);
        if (isset($existingEmails[$emailLower])) {
            $errors[] = "Row {$rowNumber}: Email already exists ({$email})";
            $errorCount++;
            continue;
        }
        
        if (isset($existingLrns[$lrn])) {
            $errors[] = "Row {$rowNumber}: Student ID (LRN) already exists ({$lrn})";
            $errorCount++;
            continue;
        }
        
        // Generate random password
        $passwordGenerated = substr(str_shuffle('0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, 10);
        
        // Add to batch
        $batch[] = [
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
            $passwordGenerated
        ];
        
        // Track as added
        $existingEmails[$emailLower] = true;
        $existingLrns[$lrn] = true;
        
        // Insert batch when it reaches batchSize
        if (count($batch) >= $batchSize) {
            $successCount += insertBatch($conn, $batch);
            $batch = [];
        }
    }
    
    // Insert remaining batch
    if (!empty($batch)) {
        $successCount += insertBatch($conn, $batch);
    }
    
    fclose($fileHandle);
    
    // Log activity if admin is logged in and at least one student was imported
    if (isset($_SESSION['aid']) && $successCount > 0) {
        $activitySql = "INSERT INTO activity (aid, description, date) VALUES (?, ?, ?)";
        $activityStmt = $conn->prepare($activitySql);
        $activityStmt->execute([
            $_SESSION['aid'],
            "Import {$successCount} Student(s) from CSV",
            date('Y-m-d H:i:s')
        ]);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Prepare response message
    $message = "Import completed: {$successCount} student(s) added successfully";
    if ($errorCount > 0) {
        $message .= ", {$errorCount} error(s) occurred";
    }
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'success_count' => $successCount,
        'error_count' => $errorCount,
        'errors' => array_slice($errors, 0, 20) // Return first 20 errors
    ]);
    
} catch(PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
} catch(Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}

/**
 * Insert a batch of students efficiently
 * @param PDO $conn Database connection
 * @param array $batch Batch of student data to insert
 * @return int Number of successfully inserted students
 */
function insertBatch($conn, $batch) {
    if (empty($batch)) {
        return 0;
    }
    
    $values = [];
    $params = [];
    
    foreach ($batch as $row) {
        $values[] = "(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'image/default.png')";
        $params = array_merge($params, $row);
    }
    
    $sql = "INSERT INTO student (fname, mname, lname, lrn, gender, academicyear, grade, section, guardian, guardiancontact, email, password, image) 
            VALUES " . implode(',', $values);
    
    try {
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    } catch(Exception $e) {
        error_log("Batch insert error: " . $e->getMessage());
        return 0;
    }
}
?>