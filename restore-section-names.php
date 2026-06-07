<?php
/**
 * Restore full section names in adviser table
 * 
 * The adviser table had truncated section names (e.g., "Perpetuali" instead of "Perpetualite")
 * because the column was VARCHAR(10). Now that it's VARCHAR(100), we need to restore the full names
 * from the student table.
 */

require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>Restore Full Section Names</h2>";
echo "<p>Fixing truncated section names in adviser table...</p>";

// Get all advisers
$advisersSQL = "SELECT id, grade_level, class_section FROM adviser";
$advisersResult = $conn->query($advisersSQL);

if (!$advisersResult) {
    echo "<div style='background: #fee2e2; padding: 16px; border-radius: 8px; color: #991b1b;'>";
    echo "<strong>✗ Error:</strong> " . htmlspecialchars($conn->error);
    echo "</div>";
    $conn->close();
    exit;
}

$updated = 0;
$errors = [];

if ($advisersResult->num_rows > 0) {
    while ($adviser = $advisersResult->fetch_assoc()) {
        $adviserId = $adviser['id'];
        $gradeLevel = trim($adviser['grade_level']);
        $currentSection = trim($adviser['class_section']);
        
        // Find the full section name from student table
        // Try to match students with this grade and section (allowing for partial matches)
        $studentSQL = "SELECT DISTINCT section FROM student 
                      WHERE (grade = ? OR grade = CONCAT('Grade ', ?) OR grade = TRIM(REPLACE(?, 'Grade ', '')))
                      AND section LIKE ?
                      LIMIT 1";
        
        $gradeNum = trim(str_replace('Grade ', '', $gradeLevel));
        $searchPattern = $currentSection . '%';
        
        $studentStmt = $conn->prepare($studentSQL);
        if (!$studentStmt) {
            $errors[] = "Prepare failed for adviser $adviserId: " . $conn->error;
            continue;
        }
        
        $studentStmt->bind_param('ssss', $gradeLevel, $gradeNum, $gradeLevel, $searchPattern);
        if (!$studentStmt->execute()) {
            $errors[] = "Execute failed for adviser $adviserId: " . $studentStmt->error;
            $studentStmt->close();
            continue;
        }
        
        $studentResult = $studentStmt->get_result();
        
        if ($studentResult->num_rows > 0) {
            $student = $studentResult->fetch_assoc();
            $fullSection = trim($student['section']);
            
            if ($fullSection !== $currentSection && !empty($fullSection)) {
                // Update the adviser with the full section name
                $updateSQL = "UPDATE adviser SET class_section = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSQL);
                
                if (!$updateStmt) {
                    $errors[] = "Update prepare failed for adviser $adviserId: " . $conn->error;
                    $studentStmt->close();
                    continue;
                }
                
                $updateStmt->bind_param('si', $fullSection, $adviserId);
                if (!$updateStmt->execute()) {
                    $errors[] = "Update execute failed for adviser $adviserId: " . $updateStmt->error;
                } else {
                    echo "<p>✓ Updated adviser $adviserId: '$currentSection' → '$fullSection'</p>";
                    $updated++;
                }
                $updateStmt->close();
            }
        }
        
        $studentStmt->close();
    }
}

if ($updated > 0) {
    echo "<div style='background: #dcfce7; padding: 16px; border-radius: 8px; color: #15803d; margin-top: 24px;'>";
    echo "<strong>✓ Success!</strong> Updated $updated adviser record(s) with full section names<br>";
    echo "Section names like 'Perpetualite' will now display completely.";
    echo "</div>";
} else {
    echo "<div style='background: #dbeafe; padding: 16px; border-radius: 8px; color: #0c2340; margin-top: 24px;'>";
    echo "<strong>✓ No updates needed</strong> - All section names are already at full length";
    echo "</div>";
}

if (!empty($errors)) {
    echo "<div style='background: #fee2e2; padding: 16px; border-radius: 8px; color: #991b1b; margin-top: 16px;'>";
    echo "<strong>⚠ Issues encountered:</strong><br>";
    foreach ($errors as $error) {
        echo "• " . htmlspecialchars($error) . "<br>";
    }
    echo "</div>";
}

$conn->close();
?>
