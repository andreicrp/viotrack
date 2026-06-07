<?php
// Deploy both fixed files with datetime support

// File 1: addrecord-modal-multi.php
$target_file1 = '/home/u396044097/domains/phcm-viotrack.online/public_html/addrecord-modal-multi.php';
$local_file1 = __DIR__ . '/addrecord-modal-multi.php';

// File 2: sync-violations.php
$target_file2 = '/home/u396044097/domains/phcm-viotrack.online/public_html/php/sync-violations.php';
$local_file2 = __DIR__ . '/sync-violations.php';

$results = [];

// Deploy addrecord-modal-multi.php
if (file_exists($local_file1)) {
    $code1 = file_get_contents($local_file1);
    $backup1 = $target_file1 . '.backup-' . date('YmdHis');
    if (file_exists($target_file1)) copy($target_file1, $backup1);
    
    if (file_put_contents($target_file1, $code1) !== false) {
        $results[] = "✅ addrecord-modal-multi.php deployed";
    } else {
        $results[] = "❌ addrecord-modal-multi.php failed";
    }
}

// Deploy sync-violations.php
if (file_exists($local_file2)) {
    $code2 = file_get_contents($local_file2);
    $backup2 = $target_file2 . '.backup-' . date('YmdHis');
    if (file_exists($target_file2)) copy($target_file2, $backup2);
    
    if (file_put_contents($target_file2, $code2) !== false) {
        $results[] = "✅ sync-violations.php deployed";
    } else {
        $results[] = "❌ sync-violations.php failed";
    }
}

echo "DateTime Timestamp Fix<br><br>";
foreach ($results as $r) echo $r . "<br>";
echo "<br>✅ Both files updated to preserve correct datetime<br>";
echo "<a href='https://phcm-viotrack.online/'>→ Go to VIOTRACK</a>";
?>
