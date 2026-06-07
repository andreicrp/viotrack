<?php
// Check and fix report table structure
require_once 'connect.php';

echo "Checking report table...\n";

// First, let's see what columns exist
$result = $conn->query("DESCRIBE report");
echo "Current structure:\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['Field'] . " (" . $row['Type'] . ") Key: " . $row['Key'] . " Extra: " . $row['Extra'] . "\n";
}

// Drop and recreate the table with proper structure
echo "\nFixing report table...\n";

$sql = "DROP TABLE IF EXISTS report";
if ($conn->query($sql)) {
    echo "✓ Dropped existing report table\n";
} else {
    echo "✗ Error dropping table: " . $conn->error . "\n";
}

$sql = "CREATE TABLE report (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sid INT NOT NULL,
    type VARCHAR(100),
    date DATETIME,
    comment TEXT,
    status VARCHAR(50) DEFAULT 'Pending',
    FOREIGN KEY (sid) REFERENCES student(id) ON DELETE CASCADE
)";

if ($conn->query($sql)) {
    echo "✓ Created report table with AUTO_INCREMENT PRIMARY KEY\n";
} else {
    echo "✗ Error creating table: " . $conn->error . "\n";
}

$conn->close();
echo "\nDone!\n";
?>
