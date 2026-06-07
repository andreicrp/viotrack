<?php
// Deploy addrecord-modal-multi.php with actual GPS location tracking
$target_file = '/home/u396044097/domains/phcm-viotrack.online/public_html/addrecord-modal-multi.php';
$local_file = __DIR__ . '/addrecord-modal-multi.php';

if (!file_exists($local_file)) {
    die("Local file not found");
}

$code = file_get_contents($local_file);
$backup = $target_file . '.backup-' . date('YmdHis');

if (file_exists($target_file)) {
    copy($target_file, $backup);
    echo "✅ Backup created<br>";
}

if (file_put_contents($target_file, $code) !== false) {
    echo "✅ GPS LOCATION TRACKING DEPLOYED!<br>";
    echo "File: $target_file<br>";
    echo "Feature: Now captures actual GPS coordinates when offline<br>";
    echo "Fallback: Uses Manila coordinates if GPS unavailable<br>";
    echo "Data captured: latitude, longitude, accuracy<br>";
    echo "<br><a href='https://phcm-viotrack.online/'>→ Test GPS tracking</a>";
} else {
    echo "❌ Failed to deploy";
}
?>
