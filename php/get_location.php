<?php
session_start();

require_once '../auth_check.php';
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit();
}

require_once('../connect.php');
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Fetch violation records with location + metadata for heatmap
$sql = "SELECT 
            r.lat, r.lng, r.type, r.vid, r.date, r.status,
            CONCAT(s.fname, ' ', s.lname) as student_name,
            s.grade, s.section
        FROM record r
        LEFT JOIN student s ON r.sid = s.id
        WHERE r.lat IS NOT NULL AND r.lng IS NOT NULL
          AND r.lat != 0 AND r.lng != 0
        ORDER BY r.date DESC
        LIMIT 500";

$result = $conn->query($sql);

$locations = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $locations[] = [
            'lat'          => (float)$row['lat'],
            'lng'          => (float)$row['lng'],
            'type'         => $row['type'],
            'violation'    => $row['vid'],
            'date'         => $row['date'],
            'status'       => $row['status'],
            'student_name' => $row['student_name'],
            'grade'        => $row['grade'],
            'section'      => $row['section'],
        ];
    }
}

$conn->close();
echo json_encode(['success' => true, 'locations' => $locations, 'count' => count($locations)]);
?>