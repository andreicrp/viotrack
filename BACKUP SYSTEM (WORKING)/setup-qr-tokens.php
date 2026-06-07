<?php
// setup-qr-tokens.php
// Run this once to create the qr_tokens table for secure QR code functionality

$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "vio";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create the qr_tokens table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS qr_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_token (student_id, token),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (student_id) REFERENCES student(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "✓ QR tokens table created successfully!<br>";
    echo "Setup complete. You can now use secure QR codes.";
} else {
    echo "✗ Error creating table: " . $conn->error;
}

$conn->close();
?>
