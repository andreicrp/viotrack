<?php
// Auto-fix adviser sections to match students from students.php
require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

echo "Auto-fixing adviser assignments based on students...\n\n";

// Get distinct grades/sections from students (same as students.php)
$gradesSQL = "SELECT DISTINCT grade, section FROM student WHERE grade IS NOT NULL AND section IS NOT NULL ORDER BY grade ASC, section ASC";
$gradesResult = $conn->query($gradesSQL);

if (!$gradesResult || $gradesResult->num_rows === 0) {
    echo "No students found in database. Cannot assign advisers.\n";
    $conn->close();
    exit();
}

$gradeSections = [];
while($row = $gradesResult->fetch_assoc()) {
    $gradeSections[] = ['grade' => $row['grade'], 'section' => $row['section']];
}

echo "Found " . count($gradeSections) . " grade/section combinations in students:\n";
foreach($gradeSections as $gs) {
    echo "  - {$gs['grade']} / Section {$gs['section']}\n";
}

// Get all advisers
$advisersSQL = "SELECT * FROM adviser ORDER BY id ASC";
$advisersResult = $conn->query($advisersSQL);

if (!$advisersResult) {
    echo "Error fetching advisers: " . $conn->error . "\n";
    $conn->close();
    exit();
}

if ($advisersResult->num_rows === 0) {
    echo "\nNo advisers found to update.\n";
    $conn->close();
    exit();
}

echo "\nUpdating advisers:\n";
$index = 0;
$updateCount = 0;

while($adviser = $advisersResult->fetch_assoc()) {
    // Cycle through grade/section combinations
    if ($index >= count($gradeSections)) {
        $index = 0;
    }
    
    $gradeSection = $gradeSections[$index];
    $newGrade = $gradeSection['grade'];
    $newSection = $gradeSection['section'];
    
    $updateSQL = "UPDATE adviser SET grade_level = ?, class_section = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateSQL);
    
    if (!$updateStmt) {
        echo "  ✗ {$adviser['fname']} {$adviser['lname']}: Prepare failed - " . $conn->error . "\n";
    } else {
        $id = $adviser['id'];
        $updateStmt->bind_param('ssi', $newGrade, $newSection, $id);
        
        if ($updateStmt->execute()) {
            // Count students in this assignment
            $countSQL = "SELECT COUNT(*) as count FROM student WHERE grade = ? AND section = ?";
            $countStmt = $conn->prepare($countSQL);
            $countStmt->bind_param('ss', $newGrade, $newSection);
            $countStmt->execute();
            $countResult = $countStmt->get_result();
            $countRow = $countResult->fetch_assoc();
            $studentCount = $countRow['count'];
            $countStmt->close();
            
            echo "  ✓ {$adviser['fname']} {$adviser['lname']} → {$newGrade} / Section {$newSection} ({$studentCount} students)\n";
            $updateCount++;
        } else {
            echo "  ✗ {$adviser['fname']} {$adviser['lname']}: Execute failed - " . $updateStmt->error . "\n";
        }
        
        $updateStmt->close();
    }
    
    $index++;
}

echo "\n✓ Successfully updated {$updateCount} adviser(s)!\n";
echo "\nGo to: Advisers page to view all advisers with their students\n";

$conn->close();
?>
