<?php
/**
 * Sync Adviser Sections with Student Data
 * 
 * Updates adviser records to match the current student section assignments.
 * This ensures the adviser displays the correct section based on which students are enrolled.
 */

require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

echo "<h2>Sync Adviser Sections with Students</h2>";
echo "<p>Updating adviser sections to match student data...</p>";

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
$unchanged = 0;
$errors = [];

if ($advisersResult->num_rows > 0) {
    while ($adviser = $advisersResult->fetch_assoc()) {
        $adviserId = $adviser['id'];
        $gradeLevel = trim($adviser['grade_level']);
        $currentSection = trim($adviser['class_section']);
        
        // Find the most common/current section for students with this grade
        $studentSQL = "SELECT section FROM student 
                      WHERE (grade = ? OR grade = CONCAT('Grade ', ?) OR grade = TRIM(REPLACE(?, 'Grade ', '')))
                      GROUP BY section
                      ORDER BY COUNT(*) DESC
                      LIMIT 1";
        
        $gradeNum = trim(str_replace('Grade ', '', $gradeLevel));
        
        $studentStmt = $conn->prepare($studentSQL);
        if (!$studentStmt) {
            $errors[] = "Prepare failed for adviser $adviserId: " . $conn->error;
            continue;
        }
        
        $studentStmt->bind_param('sss', $gradeLevel, $gradeNum, $gradeLevel);
        if (!$studentStmt->execute()) {
            $errors[] = "Execute failed for adviser $adviserId: " . $studentStmt->error;
            $studentStmt->close();
            continue;
        }
        
        $studentResult = $studentStmt->get_result();
        
        if ($studentResult->num_rows > 0) {
            $student = $studentResult->fetch_assoc();
            $newSection = trim($student['section']);
            
            if ($newSection !== $currentSection && !empty($newSection)) {
                // Update the adviser with the new section name
                $updateSQL = "UPDATE adviser SET class_section = ? WHERE id = ?";
                $updateStmt = $conn->prepare($updateSQL);
                
                if (!$updateStmt) {
                    $errors[] = "Update prepare failed for adviser $adviserId: " . $conn->error;
                    $studentStmt->close();
                    continue;
                }
                
                $updateStmt->bind_param('si', $newSection, $adviserId);
                if (!$updateStmt->execute()) {
                    $errors[] = "Update execute failed for adviser $adviserId: " . $updateStmt->error;
                } else {
                    echo "<p style='color: #059669;'>✓ Adviser ID $adviserId: '$currentSection' → '$newSection'</p>";
                    $updated++;
                }
                $updateStmt->close();
            } else {
                $unchanged++;
            }
        } else {
            echo "<p style='color: #9ca3af;'>⚠ No students found for adviser $adviserId (Grade $gradeLevel)</p>";
        }
        
        $studentStmt->close();
    }
}

echo "<hr style='margin: 24px 0; border: none; border-top: 1px solid #e5e7eb;'>";

if ($updated > 0 || $unchanged > 0) {
    echo "<div style='background: #dcfce7; padding: 16px; border-radius: 8px; color: #15803d;'>";
    echo "<strong>✓ Sync Complete!</strong><br>";
    echo "• Updated: $updated record(s)<br>";
    echo "• Unchanged: $unchanged record(s)<br>";
    echo "Adviser sections now match current student assignments.";
    echo "</div>";
} else {
    echo "<div style='background: #dbeafe; padding: 16px; border-radius: 8px; color: #0c2340;'>";
    echo "<strong>✓ Already in sync</strong> - No updates needed";
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

// Show current adviser data
echo "<h3 style='margin-top: 32px;'>Current Adviser Sections:</h3>";
echo "<table style='border-collapse: collapse; width: 100%; margin: 16px 0;'>";
echo "<tr style='background: #f3f4f6;'>";
echo "<th style='border: 1px solid #d1d5db; padding: 12px; text-align: left;'>ID</th>";
echo "<th style='border: 1px solid #d1d5db; padding: 12px; text-align: left;'>Grade Level</th>";
echo "<th style='border: 1px solid #d1d5db; padding: 12px; text-align: left;'>Section</th>";
echo "</tr>";

$verifySQL = "SELECT id, grade_level, class_section FROM adviser ORDER BY id";
$verifyResult = $conn->query($verifySQL);

if ($verifyResult) {
    while ($row = $verifyResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td style='border: 1px solid #d1d5db; padding: 12px;'>" . htmlspecialchars($row['id']) . "</td>";
        echo "<td style='border: 1px solid #d1d5db; padding: 12px;'>" . htmlspecialchars($row['grade_level']) . "</td>";
        echo "<td style='border: 1px solid #d1d5db; padding: 12px; font-weight: 600;'>" . htmlspecialchars($row['class_section']) . "</td>";
        echo "</tr>";
    }
}
echo "</table>";

$conn->close();
?>
