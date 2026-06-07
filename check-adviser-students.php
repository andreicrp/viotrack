<?php
// Fix adviser data - match with actual student grades and sections
require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

echo "Fixing adviser data...\n\n";

// Get all current advisers
$advisersSQL = "SELECT * FROM adviser";
$advisersResult = $conn->query($advisersSQL);

if (!$advisersResult || $advisersResult->num_rows === 0) {
    echo "No advisers found.\n";
    $conn->close();
    exit();
}

$advisers = [];
while($adviser = $advisersResult->fetch_assoc()) {
    $advisers[] = $adviser;
}

echo "Found " . count($advisers) . " advisers\n";
echo "Checking each adviser's students...\n\n";

foreach($advisers as $adviser) {
    $grade = $adviser['grade_level'];
    $section = $adviser['class_section'];
    
    // Check if students exist with this grade and section
    $checkSQL = "SELECT COUNT(*) as count FROM student WHERE grade = ? AND section = ?";
    $checkStmt = $conn->prepare($checkSQL);
    $checkStmt->bind_param('ss', $grade, $section);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    $row = $result->fetch_assoc();
    $studentCount = $row['count'];
    $checkStmt->close();
    
    echo "{$adviser['fname']} {$adviser['lname']} → {$grade} / Section {$section}: {$studentCount} students";
    
    if ($studentCount === 0) {
        echo " (NO STUDENTS FOUND - Section may be incorrect)\n";
    } else {
        echo " ✓\n";
    }
}

echo "\n";
echo "If sections are incorrect, they need to match student section values.\n";
echo "Common section values: A, B, C, D, E, F, etc.\n";

$conn->close();
?>
