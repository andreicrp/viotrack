<?php
// Create adviser table and populate with sample data
require_once('connect.php');

if (!$conn) {
    die("Database connection failed");
}

// SQL to create adviser table
$createTableSQL = "CREATE TABLE IF NOT EXISTS `adviser` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `tid` INT NOT NULL,
    `fname` VARCHAR(50) NOT NULL,
    `mname` VARCHAR(50) DEFAULT NULL,
    `lname` VARCHAR(50) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `grade_level` VARCHAR(50) NOT NULL,
    `class_section` VARCHAR(10) NOT NULL,
    `image` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_grade_section` (`grade_level`, `class_section`),
    KEY `idx_tid` (`tid`),
    KEY `idx_grade_level` (`grade_level`),
    KEY `idx_class_section` (`class_section`)
) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

echo "Creating adviser table...\n";

if ($conn->query($createTableSQL) === TRUE) {
    echo "✓ Adviser table created successfully!\n\n";
    
    // Get some teachers to use as advisers
    $teachersSQL = "SELECT id, fname, mname, lname, email, image FROM teacher WHERE position != 'admin' LIMIT 5";
    $teachersResult = $conn->query($teachersSQL);
    
    if ($teachersResult && $teachersResult->num_rows > 0) {
        echo "Adding sample adviser assignments...\n";
        
        $grades = ['Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11'];
        $sections = ['A', 'B', 'C', 'D', 'E'];
        $index = 0;
        
        while($teacher = $teachersResult->fetch_assoc()) {
            if ($index >= 5) break;
            
            $tid = $teacher['id'];
            $fname = $conn->real_escape_string($teacher['fname']);
            $mname = $conn->real_escape_string($teacher['mname']);
            $lname = $conn->real_escape_string($teacher['lname']);
            $email = $conn->real_escape_string($teacher['email']);
            $image = !empty($teacher['image']) ? $conn->real_escape_string($teacher['image']) : NULL;
            $grade = $grades[$index];
            $section = $sections[$index];
            
            $insertSQL = "INSERT INTO adviser (tid, fname, mname, lname, email, grade_level, class_section, image) 
                         VALUES ($tid, '$fname', '$mname', '$lname', '$email', '$grade', '$section', " . ($image ? "'$image'" : "NULL") . ")
                         ON DUPLICATE KEY UPDATE updated_at = CURRENT_TIMESTAMP";
            
            if ($conn->query($insertSQL) === TRUE) {
                echo "  ✓ {$fname} {$lname} assigned to {$grade} - Section {$section}\n";
            } else {
                echo "  ✗ Error assigning {$fname} {$lname}: " . $conn->error . "\n";
            }
            
            $index++;
        }
    }
    
    echo "\n✓ Adviser table setup complete!\n";
    echo "\nYou can now go to:\n";
    echo "  - Teachers page: Appoint more teachers as advisers\n";
    echo "  - Advisers page: View all assigned advisers and their students\n";
    
} else {
    echo "✗ Error creating adviser table: " . $conn->error . "\n";
}

$conn->close();
?>
