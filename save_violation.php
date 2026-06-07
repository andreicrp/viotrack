<?php
/**
 * Save Violation Handler
 * Handles both adding new violations and updating existing ones
 */

session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 0); // Don't display errors to browser
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'php_errors.log');

// Set response header
header('Content-Type: application/json');

// Initialize response
$response = [
    'success' => false,
    'message' => '',
    'data' => null,
    'debug' => []
];

// Include database connection
try {
    if (!file_exists('connect.php')) {
        throw new Exception('Database connection file not found');
    }
    require_once 'connect.php';
    $response['debug'][] = 'DB connection file loaded';
} catch (Exception $e) {
    $response['message'] = 'Database connection error: ' . $e->getMessage();
    echo json_encode($response);
    exit;
}

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    $response['debug'][] = 'Starting validation';
    
    // Check if request method is POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    }
    $response['debug'][] = 'Request method is POST';
    
    // Check database connection
    if (!isConnected()) {
        throw new Exception('Database connection is not active');
    }
    $response['debug'][] = 'Database connected';

    // Get and sanitize input data
    $violationId = isset($_POST['violation_id']) ? intval($_POST['violation_id']) : 0;
    $title = isset($_POST['violation_title']) ? sanitize($_POST['violation_title']) : '';
    $description = isset($_POST['violation_description']) ? sanitize($_POST['violation_description']) : '';
    $type = isset($_POST['violation_type']) ? sanitize($_POST['violation_type']) : '';
    
    $response['debug'][] = "Received: ID=$violationId, Title=$title, Type=$type";

    // Validate required fields
    if (empty($title)) {
        throw new Exception('Violation title is required');
    }
    if (empty($description)) {
        throw new Exception('Description is required');
    }
    if (empty($type)) {
        throw new Exception('Violation type is required');
    }

    // Validate violation type
    $allowedTypes = ['Minor', 'Serious', 'Major'];
    if (!in_array($type, $allowedTypes)) {
        throw new Exception('Invalid violation type: ' . $type);
    }
    
    // Check for duplicate violation names (case-insensitive)
    if ($violationId > 0) {
        // For updates: check if another violation has the same name
        $dupCheckSql = "SELECT id FROM violation WHERE LOWER(title) = LOWER('$title') AND id != $violationId";
    } else {
        // For new violations: check if any violation has this name
        $dupCheckSql = "SELECT id FROM violation WHERE LOWER(title) = LOWER('$title')";
    }
    
    $dupCheckResult = fetchSingle($dupCheckSql);
    if ($dupCheckResult) {
        throw new Exception('A violation with this name already exists. Please use a different name.');
    }
    
    $response['debug'][] = 'Duplicate check passed';

    // Start transaction
    beginTransaction();
    $response['debug'][] = 'Transaction started';

    if ($violationId > 0) {
        // UPDATE existing violation
        $sql = "UPDATE violation 
                SET title = '$title', 
                    description = '$description', 
                    type = '$type' 
                WHERE id = $violationId";
        
        $response['debug'][] = "Update SQL: $sql";
        
        if (!executeNonQuery($sql)) {
            throw new Exception('Failed to update violation in database');
        }

        // Log activity if session exists
        if (isset($_SESSION['id'])) {
            $adminId = $_SESSION['id'];
            $date = date('Y-m-d H:i:s');
            $activitySql = "INSERT INTO activity (aid, description, date) 
                           VALUES ('$adminId', 'Edit Violation', '$date')";
            executeNonQuery($activitySql);
            $response['debug'][] = 'Activity logged';
        }

        $response['message'] = 'Violation updated successfully!';
        $response['data'] = [
            'id' => $violationId,
            'title' => $title,
            'description' => $description,
            'type' => $type
        ];
    } else {
        // INSERT new violation
        $sql = "INSERT INTO violation (title, description, type) 
                VALUES ('$title', '$description', '$type')";
        
        $response['debug'][] = "Insert SQL: $sql";
        
        if (!executeNonQuery($sql)) {
            throw new Exception('Failed to add violation to database');
        }

        $newId = getLastInsertId();
        $response['debug'][] = "New ID: $newId";

        // Log activity if session exists
        if (isset($_SESSION['id'])) {
            $adminId = $_SESSION['id'];
            $date = date('Y-m-d H:i:s');
            $activitySql = "INSERT INTO activity (aid, description, date) 
                           VALUES ('$adminId', 'Insert New Violation', '$date')";
            executeNonQuery($activitySql);
            $response['debug'][] = 'Activity logged';
        }

        $response['message'] = 'Violation added successfully!';
        $response['data'] = [
            'id' => $newId,
            'title' => $title,
            'description' => $description,
            'type' => $type
        ];
    }

    // Commit transaction
    commitTransaction();
    $response['debug'][] = 'Transaction committed';
    $response['success'] = true;

} catch (Exception $e) {
    // Rollback transaction on error
    rollbackTransaction();
    $response['message'] = $e->getMessage();
    $response['debug'][] = 'Exception: ' . $e->getMessage();
    $response['debug'][] = 'Trace: ' . $e->getTraceAsString();
}

// Remove debug info in production
// unset($response['debug']);

// Send response
echo json_encode($response);
exit;
?>