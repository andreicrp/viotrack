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

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Check if it's a single delete or bulk delete
    $studentIds = [];
    
    if (isset($data['id'])) {
        // Single delete
        $studentIds[] = $data['id'];
    } elseif (isset($data['ids']) && is_array($data['ids'])) {
        // Bulk delete
        $studentIds = $data['ids'];
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Student ID(s) required'
        ]);
        exit();
    }
    
    if (empty($studentIds)) {
        echo json_encode([
            'success' => false,
            'message' => 'No student IDs provided'
        ]);
        exit();
    }
    
    $deletedCount = 0;
    
    // Start transaction
    $conn->beginTransaction();
    
    foreach ($studentIds as $studentId) {
        // Get student image path before deleting
        $stmt = $conn->prepare("SELECT image FROM student WHERE id = ?");
        $stmt->execute([$studentId]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($student) {
            // Delete related records first (to maintain referential integrity)
            // Delete from record table
            $stmt = $conn->prepare("DELETE FROM record WHERE sid = ?");
            $stmt->execute([$studentId]);
            
            // Delete from report table
            $stmt = $conn->prepare("DELETE FROM report WHERE sid = ?");
            $stmt->execute([$studentId]);
            
            // Delete the student
            $stmt = $conn->prepare("DELETE FROM student WHERE id = ?");
            $result = $stmt->execute([$studentId]);
            
            if ($result) {
                $deletedCount++;
                
                // Delete image file if it's not the default image
                if (!empty($student['image']) && 
                    $student['image'] != 'image/default.png' && 
                    $student['image'] != 'image/default.jpg' && 
                    file_exists($student['image'])) {
                    @unlink($student['image']);
                }
            }
        }
    }
    
    if ($deletedCount > 0) {
        // Log activity if admin is logged in
        if (isset($_SESSION['aid'])) {
            $activityDescription = $deletedCount === 1 ? 'Delete Student' : "Delete {$deletedCount} Student(s)";
            $activitySql = "INSERT INTO activity (aid, description, date) VALUES (?, ?, ?)";
            $activityStmt = $conn->prepare($activitySql);
            $activityStmt->execute([
                $_SESSION['aid'],
                $activityDescription,
                date('Y-m-d H:i:s')
            ]);
        }
        
        // Commit transaction
        $conn->commit();
        
        $message = $deletedCount === 1 ? 'Student deleted successfully' : "{$deletedCount} student(s) deleted successfully";
        
        echo json_encode([
            'success' => true,
            'message' => $message,
            'deleted_count' => $deletedCount
        ]);
    } else {
        $conn->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'No students were deleted. Student(s) not found.'
        ]);
    }
    
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
?>