<?php
// Quick upload script
$localPath = __DIR__;

// Create FTP connection string using PHP functions
$ftpServer = "phcm-viotrack.online";
$ftpUser = "u396044097";
$ftpPass = "1234@Aaaa";

$ftp = @ftp_connect($ftpServer, 21, 10);
if (!$ftp) {
    echo "❌ Cannot connect to FTP<br>";
    exit;
}

if (!@ftp_login($ftp, $ftpUser, $ftpPass)) {
    echo "❌ FTP login failed<br>";
    ftp_close($ftp);
    exit;
}

ftp_pasv($ftp, true);
echo "✅ Connected to FTP<br><br>";

// Files to upload
$uploads = [
    ['local' => $localPath . '/login.php', 'remote' => 'public_html/login.php', 'type' => FTP_ASCII, 'name' => 'login.php'],
];

foreach ($uploads as $upload) {
    if (!file_exists($upload['local'])) {
        echo "❌ " . $upload['name'] . " - local file not found<br>";
        continue;
    }
    
    if (ftp_put($ftp, $upload['remote'], $upload['local'], $upload['type'])) {
        echo "✅ " . $upload['name'] . " uploaded<br>";
    } else {
        echo "❌ " . $upload['name'] . " failed<br>";
    }
}

ftp_close($ftp);

echo "<br><hr>";
echo "<h2>Next: Go Online & Test</h2>";

echo "<p><strong>Steps:</strong></p>";
echo "<ol>";
echo "<li>Click the test link above</li>";
echo "<li>Show stored credentials - should be empty</li>";
echo "<li>Go to <a href='https://phcm-viotrack.online/login.php' target='_blank'>Login Page</a></li>";
echo "<li>Login and check 'Keep me signed in'</li>";
echo "<li>Come back to test page and click 'Show Stored Credentials'</li>";

echo "</ol>";
?>
