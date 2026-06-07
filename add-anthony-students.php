<?php
// Add test students to Grade 7, Section Anthony
require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

echo "Adding test students to Grade 7, Section Anthony...\n\n";

// Sample student data to insert
$students = [
    ['lrn' => '202401001', 'fname' => 'John', 'mname' => 'A.', 'lname' => 'Smith', 'email' => 'john.smith@school.com', 'gender' => 'Male'],
    ['lrn' => '202401002', 'fname' => 'Maria', 'mname' => 'B.', 'lname' => 'Garcia', 'email' => 'maria.garcia@school.com', 'gender' => 'Female'],
    ['lrn' => '202401003', 'fname' => 'James', 'mname' => 'C.', 'lname' => 'Brown', 'email' => 'james.brown@school.com', 'gender' => 'Male'],
    ['lrn' => '202401004', 'fname' => 'Angela', 'mname' => 'D.', 'lname' => 'Davis', 'email' => 'angela.davis@school.com', 'gender' => 'Female'],
    ['lrn' => '202401005', 'fname' => 'Michael', 'mname' => 'E.', 'lname' => 'Wilson', 'email' => 'michael.wilson@school.com', 'gender' => 'Male'],
];

$addedCount = 0;

foreach($students as $student) {
    $lrn = $student['lrn'];
    $fname = $student['fname'];
    $mname = $student['mname'];
    $lname = $student['lname'];
    $email = $student['email'];
    $gender = $student['gender'];
    $grade = 'Grade 7';
    $section = 'Anthony';
    $academicyear = '2024-2025';
    $guardian = 'Parent';
    $guardiancontact = '0000000000';
    $image = 'image/default.jpg';
    
    $insertSQL = "INSERT INTO student (lrn, fname, mname, lname, email, gender, grade, section, academicyear, guardian, guardiancontact, image) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $insertStmt = $conn->prepare($insertSQL);
    if (!$insertStmt) {
        echo "✗ Error preparing statement: " . $conn->error . "\n";
        continue;
    }
    
    $insertStmt->bind_param('ssssssssssss', $lrn, $fname, $mname, $lname, $email, $gender, $grade, $section, $academicyear, $guardian, $guardiancontact, $image);
    
    if ($insertStmt->execute()) {
        echo "✓ Added: {$fname} {$lname} (LRN: {$lrn})\n";
        $addedCount++;
    } else {
        if (strpos($insertStmt->error, 'Duplicate entry') !== false) {
            echo "⚠️  {$fname} {$lname} already exists (LRN: {$lrn})\n";
        } else {
            echo "✗ Error adding {$fname} {$lname}: " . $insertStmt->error . "\n";
        }
    }
    
    $insertStmt->close();
}

echo "\n✓ Added {$addedCount} student(s) to Grade 7, Section Anthony\n";
echo "\nGo to: Advisers page to see Miss Violet with her students!\n";

$conn->close();
?>
