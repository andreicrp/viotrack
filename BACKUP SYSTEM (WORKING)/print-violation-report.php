<?php
// print-violation-report.php
session_start();

// Set timezone first
date_default_timezone_set('Asia/Manila');

// Require login
require_once 'auth_check.php';
requireLogin();

// Include database connection
require_once 'connect.php';

if (!$conn) {
    die("Connection failed: No database connection");
}

$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId === 0) {
    die("Invalid student ID");
}

// Fetch student data
$studentQuery = "SELECT s.*, 
    CONCAT(s.fname, ' ', IFNULL(CONCAT(s.mname, ' '), ''), s.lname) as full_name
    FROM student s 
    WHERE s.id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$stmt->close();

if (!$student) {
    die("Student not found");
}

// Fetch violation records
$violationQuery = "SELECT r.*, 
    v.title as violation_title,
    v.type as violation_type,
    CONCAT(s.fname, ' ', s.mname, ' ', s.lname) as student_name,
    s.lrn,
    s.grade,
    s.section,
    s.academicyear
    FROM record r
    INNER JOIN violation v ON r.vid = v.id
    INNER JOIN student s ON r.sid = s.id
    WHERE r.sid = ?
    ORDER BY r.date DESC";

$stmt = $conn->prepare($violationQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$violationsResult = $stmt->get_result();
$violations = [];
while ($row = $violationsResult->fetch_assoc()) {
    $violations[] = $row;
}
$stmt->close();

$totalViolations = count($violations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Violation Report - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; background: #f5f5f5; }
        .print-container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; }

        .button-group { display: flex; gap: 10px; margin-bottom: 15px; justify-content: center; }
        .button-group button { padding: 8px 20px; border: none; border-radius: 6px; font-size: 12px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
        .btn-print { background: #003366; color: white; }
        .btn-print:hover { background: #001f40; }
        .btn-close { background: #e5e7eb; color: #1f2937; }
        .btn-close:hover { background: #d1d5db; }

        /* Header */
        .report-header { text-align: center; margin-bottom: 12px; border-bottom: 2px solid #003366; padding-bottom: 10px; }
        .header-top { display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 8px; }
        .school-logo { width: 50px; height: 50px; }
        .school-logo img { max-width: 100%; height: auto; }
        .school-info { text-align: center; }
        .school-name { font-size: 13px; font-weight: 700; color: #003366; }
        .school-motto { font-size: 9px; color: #666; font-style: italic; }
        .report-title { font-size: 11px; font-weight: 700; color: #003366; margin-top: 6px; text-transform: uppercase; }

        /* Student Info */
        .student-info { background: #f9fafb; border: 1px solid #003366; border-radius: 4px; padding: 8px 12px; margin: 10px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
        .info-group { display: flex; flex-direction: column; }
        .info-label { font-size: 8px; font-weight: 600; color: #003366; text-transform: uppercase; margin-bottom: 2px; }
        .info-value { font-size: 9px; color: #1f2937; font-weight: 500; }

        /* Table */
        .violations-table { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 8px; }
        .violations-table thead { background: #003366; color: white; }
        .violations-table th { padding: 5px 6px; text-align: left; font-weight: 600; border: 1px solid #d1d5db; }
        .violations-table td { padding: 4px 6px; border: 1px solid #e5e7eb; word-wrap: break-word; }
        .violations-table tbody tr:nth-child(odd) { background: #f9fafb; }

        .violation-type { display: inline-block; padding: 2px 5px; border-radius: 3px; font-weight: 600; font-size: 7px; text-transform: uppercase; white-space: nowrap; }
        .violation-type.minor { background: #22c55e; color: white; }
        .violation-type.serious { background: #eab308; color: #333; }
        .violation-type.major { background: #ef4444; color: white; }

        .status-badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-weight: 600; font-size: 7px; text-transform: uppercase; white-space: nowrap; }
        .status-badge.pending { background: #dbeafe; color: #1e40af; }
        .status-badge.resolved { background: #dcfce7; color: #166534; }
        .status-badge.completed { background: #dcfce7; color: #166534; }

        .no-violations { background: #dcfce7; border: 1px dashed #16a34a; padding: 10px; text-align: center; border-radius: 4px; margin: 10px 0; }
        .no-violations h3 { color: #15803d; font-size: 10px; margin-bottom: 3px; }
        .no-violations p { color: #15803d; font-size: 8px; }

        .report-footer { margin-top: 10px; padding-top: 8px; border-top: 1px solid #e5e7eb; font-size: 8px; color: #6b7280; text-align: center; }

        @media print {
            body { margin: 0; padding: 0; }
            .button-group { display: none; }
            .print-container { padding: 10px; margin: 0; max-width: 100%; }
            .violations-table { page-break-inside: avoid; }
            .report-header { margin-bottom: 8px; }
            .student-info { margin: 8px 0; }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Buttons -->
        <div class="button-group">
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
        </div>

        <!-- Header -->
        <div class="report-header">
            <div class="header-top">
                <div class="school-logo">
                    <img src="images/phcm-logo2.png" alt="School Logo">
                </div>
                <div class="school-info">
                    <div class="school-name">Perpetual Help College of Manila</div>
                    <div class="school-motto">Character Building is Nation Building</div>
                </div>
            </div>
            <div class="report-title">STUDENT VIOLATION RECORD</div>
        </div>

        <!-- Student Info -->
        <div class="student-info">
            <div class="info-group">
                <div class="info-label">Name</div>
                <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">ID (LRN)</div>
                <div class="info-value"><?php echo htmlspecialchars($student['lrn']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Grade & Section</div>
                <div class="info-value"><?php echo htmlspecialchars($student['grade']); ?> - <?php echo htmlspecialchars($student['section']); ?></div>
            </div>
            <div class="info-group">
                <div class="info-label">Year</div>
                <div class="info-value"><?php echo htmlspecialchars($student['academicyear']); ?></div>
            </div>
        </div>

        <!-- Violations Table -->
        <?php if ($totalViolations > 0): ?>
        <table class="violations-table">
            <thead>
                <tr>
                    <th>VIOLATION</th>
                    <th>TYPE</th>
                    <th>DATE</th>
                    <th>STATUS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($violations as $violation): ?>
                <tr>
                    <td><?php echo htmlspecialchars($violation['violation_title']); ?></td>
                    <td><span class="violation-type <?php echo strtolower($violation['violation_type']); ?>"><?php echo htmlspecialchars($violation['violation_type']); ?></span></td>
                    <td><?php echo date('M d, Y', strtotime($violation['date'])); ?></td>
                    <td><span class="status-badge <?php echo strtolower($violation['status']); ?>"><?php echo htmlspecialchars($violation['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="no-violations">
            <h3>No Violations Found</h3>
            <p>This student has a clean discipline record.</p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="report-footer">
            <p><strong>Perpetual Help College of Manila</strong></p>
            <p>Generated on <?php echo date('F d, Y'); ?></p>
        </div>
    </div>
</body>
</html>
<?php 
$conn->close(); 
?>
