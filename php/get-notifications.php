<?php
session_start();
include '../connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['violations' => [], 'offenders' => [], 'escalated' => []]);
    exit;
}

$violations = [];
$offenders = [];
$escalated = [];

try {
    // Fetch recent violations (last 24 hours)
    $sql_violations = "
        SELECT 
            r.id,
            r.date,
            v.violation_type,
            s.student_name
        FROM record r
        INNER JOIN violation v ON r.vid = v.id
        INNER JOIN student s ON r.sid = s.id
        WHERE r.date >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY r.date DESC
        LIMIT 5
    ";
    
    $result = $conn->query($sql_violations);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $violations[] = $row;
        }
    }

    // Fetch new repeat offenders (last 7 days with 3+ violations total)
    $sql_offenders = "
        SELECT 
            s.id,
            s.student_name as name,
            COUNT(r.id) as count,
            MAX(r.date) as latest_date
        FROM student s
        INNER JOIN record r ON s.id = r.sid
        WHERE r.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY s.id
        HAVING COUNT(r.id) >= 3
        ORDER BY COUNT(r.id) DESC, r.date DESC
        LIMIT 5
    ";
    
    $result = $conn->query($sql_offenders);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $offenders[] = $row;
        }
    }

    // Fetch escalated violations (status = 'Escalated')
    $sql_escalated = "
        SELECT 
            r.id,
            r.date,
            v.violation_type,
            s.student_name
        FROM record r
        INNER JOIN violation v ON r.vid = v.id
        INNER JOIN student s ON r.sid = s.id
        WHERE r.status = 'Escalated'
        ORDER BY r.date DESC
        LIMIT 5
    ";
    
    $result = $conn->query($sql_escalated);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $escalated[] = $row;
        }
    }

    echo json_encode([
        'violations' => $violations,
        'offenders' => $offenders,
        'escalated' => $escalated
    ]);

} catch (Exception $e) {
    echo json_encode([
        'violations' => [],
        'offenders' => [],
        'escalated' => [],
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
