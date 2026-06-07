<?php
/**
 * Direct File Write Deployment
 * No FTP needed - writes files directly
 */

$basePath = '/home/u396044097/domains/phcm-viotrack.online/public_html';

// Files to create/update
$files = [
    [
        'path' => $basePath . '/js/offline-auth-manager.js',
        'content' => file_get_contents(__DIR__ . '/js/offline-auth-manager.js'),
        'name' => 'offline-auth-manager.js'
    ],
    [
        'path' => $basePath . '/login.php',
        'content' => file_get_contents(__DIR__ . '/login.php'),
        'name' => 'login.php'
    ],
    [
        'path' => $basePath . '/test-offline-login.php',
        'content' => file_get_contents(__DIR__ . '/test-offline-login.php'),
        'name' => 'test-offline-login.php'
    ]
];

echo "<h2>🚀 Deploying Offline Login System</h2>";

foreach ($files as $file) {
    if (!file_exists($file['path'])) {
        // Create the file
        $dir = dirname($file['path']);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        
        if (file_put_contents($file['path'], $file['content']) !== false) {
            echo "✅ " . $file['name'] . " - Created<br>";
        } else {
            echo "❌ " . $file['name'] . " - Failed to create<br>";
        }
    } else {
        // Update existing file
        if (file_put_contents($file['path'], $file['content']) !== false) {
            echo "✅ " . $file['name'] . " - Updated<br>";
        } else {
            echo "❌ " . $file['name'] . " - Failed to update<br>";
        }
    }
}

echo "<hr>";
echo "<h3>✅ System Deployed!</h3>";
echo "<p><a href='https://phcm-viotrack.online/login.php' target='_blank'>→ Go to Login Page</a></p>";
?>
