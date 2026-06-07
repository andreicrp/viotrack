<?php
// This PHP file COPIES from the local directory to the server
// It reads files from the same folder and writes them

$sourcePath = __DIR__;
$files = [
    'offline-auth-manager.js' => 'js/offline-auth-manager.js',
    'login.php' => 'login.php',
    'test-offline-login.php' => 'test-offline-login.php'
];

echo "<h2>Offline Auth System - File Sync</h2>";

foreach ($files as $source => $target) {
    $sourceFull = $sourcePath . '/' . $source;
    $targetFull = $sourcePath . '/' . $target;
    
    if (!file_exists($sourceFull)) {
        echo "❌ Source not found: " . $source . "<br>";
        continue;
    }
    
    // Create target directory if needed
    $targetDir = dirname($targetFull);
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }
    
    // Copy file
    if (copy($sourceFull, $targetFull)) {
        echo "✅ " . $target . " - Synced<br>";
    } else {
        echo "❌ " . $target . " - Failed<br>";
    }
}

echo "<hr>";
echo "<p><a href='login.php'>→ Go to Login</a></p>";
?>
