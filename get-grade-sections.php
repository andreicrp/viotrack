<?php
session_start();

// Require login
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

header('Content-Type: application/json');

require_once('connect.php');

if (!$conn) {
    error_log("Get-grades: Database connection failed");
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

try {
    // Fetch grade/section combinations from student table that don't have an adviser assigned
    // Handle both "Grade X" and plain number formats
    $availableSQL = "
        SELECT DISTINCT 
            CASE 
                WHEN s.grade REGEXP '^[0-9]+$' THEN s.grade
                WHEN s.grade LIKE 'Grade %' THEN TRIM(REPLACE(s.grade, 'Grade ', ''))
                ELSE s.grade
            END as grade_num,
            s.section 
        FROM student s
        WHERE s.grade IS NOT NULL AND s.grade != '' 
        AND s.section IS NOT NULL AND s.section != ''
        AND NOT EXISTS (
            SELECT 1 FROM adviser a 
            WHERE (a.grade_level = s.grade OR a.grade_level = CONCAT('Grade ', s.grade) OR a.grade_level = TRIM(REPLACE(s.grade, 'Grade ', '')))
            AND a.class_section = s.section
        )
        ORDER BY CAST(CASE 
                WHEN s.grade REGEXP '^[0-9]+$' THEN s.grade
                WHEN s.grade LIKE 'Grade %' THEN TRIM(REPLACE(s.grade, 'Grade ', ''))
                ELSE s.grade
            END AS UNSIGNED) ASC, s.section ASC
    ";
    
    $availableResult = $conn->query($availableSQL);
    
    if (!$availableResult) {
        throw new Exception("Query error: " . $conn->error);
    }
    
    $availableCombinations = [];
    if ($availableResult->num_rows > 0) {
        while ($row = $availableResult->fetch_assoc()) {
            // Format the grade as "X" (just the number)
            $gradeNum = trim($row['grade_num']);
            $availableCombinations[] = [
                'grade' => $gradeNum,
                'section' => $row['section']
            ];
        }
    }
    
    error_log("Get-grades: Found " . count($availableCombinations) . " available grade/section combinations");
    
    echo json_encode([
        'success' => true,
        'available' => $availableCombinations
    ]);

} catch (Exception $e) {
    error_log("Get-grades error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching data: ' . $e->getMessage()
    ]);
}

$conn->close();
?>

