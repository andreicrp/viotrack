<?php
// Deploy offline login caching system

// File 1: offline-login-manager.js
$target_file1 = '/home/u396044097/domains/phcm-viotrack.online/public_html/js/offline-login-manager.js';
$local_file1 = __DIR__ . '/js/offline-login-manager.js';

// File 2: login.php (updated)
$target_file2 = '/home/u396044097/domains/phcm-viotrack.online/public_html/login.php';
$local_file2 = __DIR__ . '/login.php';

$results = [];

// Deploy offline-login-manager.js
if (file_exists($local_file1)) {
    $code1 = file_get_contents($local_file1);
    
    if (file_put_contents($target_file1, $code1) !== false) {
        $results[] = "✅ offline-login-manager.js deployed";
    } else {
        $results[] = "❌ offline-login-manager.js failed";
    }
}

// Deploy login.php
if (file_exists($local_file2)) {
    $code2 = file_get_contents($local_file2);
    $backup2 = $target_file2 . '.backup-' . date('YmdHis');
    if (file_exists($target_file2)) copy($target_file2, $backup2);
    
    if (file_put_contents($target_file2, $code2) !== false) {
        $results[] = "✅ login.php deployed with offline caching";
    } else {
        $results[] = "❌ login.php failed";
    }
}

echo "Offline Login Caching System<br><br>";
foreach ($results as $r) echo $r . "<br>";
echo "<br>✅ Users can now work offline after logging in once!<br>";
echo "<a href='https://phcm-viotrack.online/login.php'>→ Test Login</a>";
?>
