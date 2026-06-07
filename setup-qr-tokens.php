<?php
/**
 * QR Tokens Table Setup
 * Run this file once to create the qr_tokens table on your production server
 * Access it via: https://phcm-viotrack.online/setup-qr-tokens.php
 */

// Set timezone
date_default_timezone_set('Asia/Manila');

// Include database connection
require_once 'connect.php';

// Check if database connection exists
if (!$conn) {
    die("❌ Database connection failed: " . mysqli_connect_error());
}

// SQL to create the qr_tokens table
$sql = "CREATE TABLE IF NOT EXISTS qr_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_token (student_id, token),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE
)";

// Execute the query
if ($conn->query($sql) === TRUE) {
    echo "✓ QR Tokens Table Setup Successful!<br><br>";
    echo "The qr_tokens table has been created successfully on your production database.<br>";
    echo "You can now use QR code scanning functionality.<br><br>";
    
    // Show table info
    $checkTable = $conn->query("SHOW COLUMNS FROM qr_tokens");
    if ($checkTable) {
        echo "<strong>Table Structure:</strong><br>";
        echo "<pre>";
        while ($row = $checkTable->fetch_assoc()) {
            echo $row['Field'] . " - " . $row['Type'] . "\n";
        }
        echo "</pre>";
    }
} else {
    // Check if table already exists
    if (strpos($conn->error, "already exists") !== false) {
        echo "✓ QR Tokens Table Already Exists<br><br>";
        echo "The qr_tokens table is already set up on your database.<br>";
        echo "No action needed.<br><br>";
        
        // Show table info
        $checkTable = $conn->query("SHOW COLUMNS FROM qr_tokens");
        if ($checkTable) {
            echo "<strong>Table Structure:</strong><br>";
            echo "<pre>";
            while ($row = $checkTable->fetch_assoc()) {
                echo $row['Field'] . " - " . $row['Type'] . "\n";
            }
            echo "</pre>";
        }
    } else {
        echo "❌ Error Creating QR Tokens Table<br><br>";
        echo "Error: " . $conn->error . "<br>";
        echo "Please check your database permissions or contact your hosting provider.<br>";
    }
}

// Clean up old tokens (older than 1 hour)
$cleanupSql = "DELETE FROM qr_tokens WHERE created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)";
if ($conn->query($cleanupSql) === TRUE) {
    $deletedCount = $conn->affected_rows;
    if ($deletedCount > 0) {
        echo "<br><strong>Cleanup:</strong> Deleted $deletedCount expired QR tokens.<br>";
    }
} else {
    error_log("Cleanup error: " . $conn->error);
}

$conn->close();

// Display help text
echo "<br><hr><br>";
echo "<strong>ℹ️ Information:</strong><br>";
echo "- QR tokens expire after 30 minutes of inactivity<br>";
echo "- Tokens are automatically cleaned up after 1 hour<br>";
echo "- Each QR code is one-time use and student-specific<br>";
echo "- This script is safe to run multiple times<br>";
echo "<br><strong>Next Steps:</strong><br>";
echo "1. Verify the table was created successfully (see above)<br>";
echo "2. Go to any student's page in adminstudentviolation.php<br>";
echo "3. The QR code should now work properly<br>";
?>
