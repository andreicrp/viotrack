<?php
// FTP Upload Script for Offline Authentication Files

$ftp_host = "phcm-viotrack.online";
$ftp_user = "u396044097";
$ftp_pass = "1234@Aaaa";

// Connect to FTP
$ftp = @ftp_connect($ftp_host);
if (!$ftp) {
    die("❌ Could not connect to FTP server");
}

// Login
if (!@ftp_login($ftp, $ftp_user, $ftp_pass)) {
    die("❌ FTP login failed");
}

echo "✅ Connected to FTP<br>";

// Enable passive mode
ftp_pasv($ftp, true);

// File 1: offline-auth-manager.js
$local_js = __DIR__ . '/js/offline-auth-manager.js';
$remote_js = 'public_html/js/offline-auth-manager.js';

if (file_exists($local_js)) {
    if (@ftp_put($ftp, $remote_js, $local_js, FTP_BINARY)) {
        echo "✅ Uploaded: offline-auth-manager.js<br>";
    } else {
        echo "❌ Failed to upload: offline-auth-manager.js<br>";
    }
} else {
    echo "❌ Local file not found: offline-auth-manager.js<br>";
}

// File 2: login.php
$local_login = __DIR__ . '/login.php';
$remote_login = 'public_html/login.php';

if (file_exists($local_login)) {
    // Create backup first
    $backup_name = 'public_html/login.php.backup-' . date('YmdHis');
    @ftp_get($ftp, tempnam(sys_get_temp_dir(), 'ftp'), $remote_login, FTP_ASCII);
    
    if (@ftp_put($ftp, $remote_login, $local_login, FTP_ASCII)) {
        echo "✅ Uploaded: login.php (with backup)<br>";
    } else {
        echo "❌ Failed to upload: login.php<br>";
    }
} else {
    echo "❌ Local file not found: login.php<br>";
}

// File 3: deploy-offline-auth.php
$local_deploy = __DIR__ . '/deploy-offline-auth.php';
$remote_deploy = 'public_html/deploy-offline-auth.php';

if (file_exists($local_deploy)) {
    if (@ftp_put($ftp, $remote_deploy, $local_deploy, FTP_ASCII)) {
        echo "✅ Uploaded: deploy-offline-auth.php<br>";
    } else {
        echo "❌ Failed to upload: deploy-offline-auth.php<br>";
    }
}

ftp_close($ftp);

echo "<br>✅ Offline Authentication System Deployed!<br>";
echo "<br>🎯 Next Steps:<br>";
echo "1. Clear browser cache (Ctrl+Shift+R)<br>";
echo "2. Login normally at: https://phcm-viotrack.online/login.php<br>";
echo "3. You'll see: 'Offline Login Available!' message<br>";
echo "4. Go offline and reload - you can login without internet!<br>";
echo "5. Add violations and they'll sync when online<br>";
?>
