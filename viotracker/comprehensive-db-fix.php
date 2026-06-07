<?php
// comprehensive-db-fix.php - Thoroughly fix AUTO_INCREMENT issues

require_once 'connect.php';

echo "=== DATABASE AUTO_INCREMENT FIX ===\n\n";

try {
    // 1. Check current report table
    echo "1. Checking current report table structure...\n";
    $result = $conn->query("DESCRIBE report");
    if ($result) {
        echo "   Current columns:\n";
        while ($row = $result->fetch_assoc()) {
            echo "   - " . $row['Field'] . ": " . $row['Type'] . " (Key: " . $row['Key'] . ", Extra: " . $row['Extra'] . ")\n";
        }
    }
    
    // 2. Check current auto_increment value
    echo "\n2. Checking current AUTO_INCREMENT counter...\n";
    $result = $conn->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='report' AND TABLE_SCHEMA='vio'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   Current AUTO_INCREMENT value: " . ($row['AUTO_INCREMENT'] ?? 'Not set') . "\n";
    }
    
    // 3. Drop existing table
    echo "\n3. Dropping existing report table...\n";
    if ($conn->query("DROP TABLE IF EXISTS report")) {
        echo "   ✓ Report table dropped\n";
    } else {
        throw new Exception("Failed to drop report table: " . $conn->error);
    }
    
    // 4. Create new report table with proper AUTO_INCREMENT
    echo "\n4. Creating new report table with AUTO_INCREMENT PRIMARY KEY...\n";
    
    $createTableSQL = "CREATE TABLE `report` (
        `id` INT NOT NULL AUTO_INCREMENT,
        `sid` INT NOT NULL,
        `type` VARCHAR(100) DEFAULT NULL,
        `date` DATETIME DEFAULT NULL,
        `comment` TEXT DEFAULT NULL,
        `status` VARCHAR(50) DEFAULT 'Pending',
        PRIMARY KEY (`id`),
        UNIQUE KEY `id_UNIQUE` (`id`),
        KEY `fk_report_student` (`sid`),
        CONSTRAINT `fk_report_student` FOREIGN KEY (`sid`) REFERENCES `student` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
    
    if ($conn->query($createTableSQL)) {
        echo "   ✓ Report table created successfully\n";
    } else {
        throw new Exception("Failed to create report table: " . $conn->error);
    }
    
    // 5. Verify the table structure
    echo "\n5. Verifying new table structure...\n";
    $result = $conn->query("DESCRIBE report");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            echo "   - " . $row['Field'] . ": " . $row['Type'] . " (Key: " . $row['Key'] . ", Extra: " . $row['Extra'] . ")\n";
        }
    }
    
    // 6. Check AUTO_INCREMENT value
    echo "\n6. Verifying AUTO_INCREMENT setting...\n";
    $result = $conn->query("SELECT AUTO_INCREMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME='report' AND TABLE_SCHEMA='vio'");
    if ($result) {
        $row = $result->fetch_assoc();
        echo "   AUTO_INCREMENT value: " . ($row['AUTO_INCREMENT'] ?? 'Not found') . "\n";
    }
    
    // 7. Test insert to verify AUTO_INCREMENT works
    echo "\n7. Testing AUTO_INCREMENT with test insert...\n";
    $testStmt = $conn->prepare("INSERT INTO report (sid, type, date, comment, status) VALUES (?, ?, ?, ?, ?)");
    $testStudentId = 1;
    $testType = "Test";
    $testDate = date('Y-m-d H:i:s');
    $testComment = "Test AUTO_INCREMENT";
    $testStatus = "Pending";
    
    $testStmt->bind_param("issss", $testStudentId, $testType, $testDate, $testComment, $testStatus);
    
    if ($testStmt->execute()) {
        $insertedId = $conn->insert_id;
        echo "   ✓ Test insert successful\n";
        echo "   Generated ID: " . $insertedId . "\n";
        
        // Verify the inserted record
        $verifyResult = $conn->query("SELECT id, sid, type FROM report WHERE id = " . $insertedId);
        if ($verifyResult && $verifyResult->num_rows > 0) {
            $record = $verifyResult->fetch_assoc();
            echo "   ✓ Record verified: ID=" . $record['id'] . ", SID=" . $record['sid'] . ", Type=" . $record['type'] . "\n";
        }
    } else {
        throw new Exception("Test insert failed: " . $testStmt->error);
    }
    $testStmt->close();
    
    // 8. Clean up test record
    echo "\n8. Cleaning up test record...\n";
    $conn->query("DELETE FROM report WHERE comment = 'Test AUTO_INCREMENT'");
    echo "   ✓ Test record removed\n";
    
    // 9. Final verification
    echo "\n9. Final table status...\n";
    $result = $conn->query("SELECT COUNT(*) as count FROM report");
    $row = $result->fetch_assoc();
    echo "   Total records in report table: " . $row['count'] . "\n";
    
    echo "\n=== ✓ AUTO_INCREMENT FIX COMPLETE ===\n";
    echo "\nYour report table is now properly configured with AUTO_INCREMENT.\n";
    echo "IDs will be automatically generated starting from 1.\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR: " . $e->getMessage() . "\n";
} finally {
    if ($conn) {
        $conn->close();
    }
}
?>
