<?php
// Deploy fixed offline-violations.js
$target_file = '/home/u396044097/domains/phcm-viotrack.online/public_html/js/offline-violations.js';

// Read the local fixed file
$local_file = __DIR__ . '/js/offline-violations.js';
if (!file_exists($local_file)) {
    die("Local file not found: $local_file");
}

$code = file_get_contents($local_file);

$backup = $target_file . '.backup-' . date('YmdHis');
if (file_exists($target_file)) {
    copy($target_file, $backup);
    echo "✅ Backup created: $backup<br>";
}

if (file_put_contents($target_file, $code) !== false) {
    echo "✅ OFFLINE-VIOLATIONS.JS DEPLOYED!<br>";
    echo "File: $target_file<br>";
    echo "Size: " . filesize($target_file) . " bytes<br>";
    echo "Fix: Clear ALL localStorage after sync (don't rely on filtering)<br>";
    echo "Better console logging for debugging<br>";
    echo "<br><a href='https://phcm-viotrack.online/'>→ Go to VIOTRACK</a>";
} else {
    echo "❌ Failed to write";
}
?>
