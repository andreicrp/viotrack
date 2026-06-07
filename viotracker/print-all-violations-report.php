<?php
// print-all-violations-report.php
session_start();

// Require login
require_once 'auth_check.php';
requireLogin();

// Set timezone to Asia/Manila
date_default_timezone_set('Asia/Manila');

// Database connection
$servername = "127.0.0.1";
$username = "root";
$password = "";
$dbname = "vio";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get filter parameters
$academicYear = isset($_GET['year']) ? $_GET['year'] : '';
$gradeLevel = isset($_GET['grade']) ? $_GET['grade'] : '';
$violationType = isset($_GET['type']) ? $_GET['type'] : '';

// Build query for all violations with filters
$query = "SELECT r.id as record_id,
    r.date,
    r.status,
    v.title as violation_title,
    v.type as violation_type,
    v.description as violation_description,
    s.id as student_id,
    CONCAT(s.fname, ' ', IFNULL(CONCAT(s.mname, ' '), ''), s.lname) as student_name,
    s.lrn,
    s.grade,
    s.section,
    s.academicyear,
    s.guardian,
    s.guardiancontact
    FROM record r
    INNER JOIN violation v ON r.vid = v.id
    INNER JOIN student s ON r.sid = s.id
    WHERE 1=1";

// Add filters
if (!empty($academicYear)) {
    $query .= " AND s.academicyear = '" . $conn->real_escape_string($academicYear) . "'";
}
if (!empty($gradeLevel)) {
    $query .= " AND s.grade = '" . $conn->real_escape_string($gradeLevel) . "'";
}
if (!empty($violationType)) {
    $query .= " AND LOWER(v.type) = LOWER('" . $conn->real_escape_string($violationType) . "')";
}

$query .= " ORDER BY s.academicyear DESC, s.grade, s.section, s.lname, r.date DESC";

$result = $conn->query($query);
$violations = [];
while ($row = $result->fetch_assoc()) {
    $violations[] = $row;
}

// Count violations by type
$minorCount = 0;
$seriousCount = 0;
$majorCount = 0;
$studentList = [];

foreach ($violations as $v) {
    if (strtolower($v['violation_type']) == 'minor') $minorCount++;
    elseif (strtolower($v['violation_type']) == 'serious') $seriousCount++;
    elseif (strtolower($v['violation_type']) == 'major') $majorCount++;
    
    // Track unique students
    if (!isset($studentList[$v['student_id']])) {
        $studentList[$v['student_id']] = $v['student_name'];
    }
}

$totalViolations = count($violations);
$totalStudents = count($studentList);

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Student Violations Report</title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: #333;
            background: #f5f5f5;
        }

        .print-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            min-height: 100vh;
        }

        /* Header Styles */
        .report-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 3px solid #1f2937;
            padding-bottom: 30px;
            position: relative;
        }

        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .school-logo {
            width: 120px;
            height: 120px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .school-logo img {
            max-width: 100%;
            height: auto;
        }

        .school-info {
            flex: 1;
            text-align: center;
            margin: 0 30px;
        }

        .school-info .republic {
            font-size: 11px;
            color: #666;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .school-info .school-name {
            font-size: 20px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 3px;
        }

        .school-info .school-motto {
            font-size: 12px;
            color: #666;
            font-style: italic;
        }

        .report-title {
            font-size: 18px;
            font-weight: 700;
            color: #1f2937;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .report-subtitle {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }

        /* Message Section */
        .message-box {
            background: #eff6ff;
            border-left: 4px solid #2563eb;
            padding: 15px 20px;
            margin: 20px 0;
            border-radius: 4px;
            font-size: 13px;
            color: #1e40af;
            line-height: 1.6;
        }

        .message-box strong {
            display: block;
            margin-bottom: 5px;
            color: #1f2937;
        }

        /* Summary Stats */
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin: 25px 0;
        }

        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
        }

        .stat-box.students {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .stat-box.total {
            background: linear-gradient(135deg, #06b6d4 0%, #0891b2 100%);
        }

        .stat-box.minor {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .stat-box.serious {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        .stat-box.major {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Filter Info */
        .filter-info {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            margin: 20px 0;
            font-size: 13px;
            color: #666;
        }

        .filter-info strong {
            color: #1f2937;
        }

        /* Table Styles */
        .violations-table {
            width: 100%;
            border-collapse: collapse;
            margin: 30px 0;
            font-size: 11px;
        }

        .violations-table thead {
            background: linear-gradient(135deg, #1f2937 0%, #374151 100%);
            color: white;
            position: sticky;
            top: 0;
        }

        .violations-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border: 1px solid #d1d5db;
        }

        .violations-table td {
            padding: 10px 12px;
            border: 1px solid #e5e7eb;
            vertical-align: top;
        }

        .violations-table tbody tr:nth-child(odd) {
            background: #f9fafb;
        }

        .violations-table tbody tr:hover {
            background: #f3f4f6;
        }

        .student-link {
            font-weight: 600;
            color: #2563eb;
            text-decoration: none;
        }

        .lrn-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 10px;
            font-weight: 600;
        }

        .violation-type {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }

        .violation-type.minor {
            background: #fef3c7;
            color: #92400e;
        }

        .violation-type.serious {
            background: #fee2e2;
            color: #991b1b;
        }

        .violation-type.major {
            background: #ede9fe;
            color: #5b21b6;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 10px;
            text-transform: uppercase;
        }

        .status-badge.pending {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.resolved {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.completed {
            background: #dcfce7;
            color: #166534;
        }

        /* Footer */
        .report-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 2px solid #e5e7eb;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
            line-height: 1.8;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
            }

            .print-container {
                max-width: 100%;
                padding: 20px;
                margin: 0;
                box-shadow: none;
            }

            .no-print {
                display: none !important;
            }

            .violations-table {
                page-break-inside: avoid;
                font-size: 10px;
            }

            .violations-table tbody tr {
                page-break-inside: avoid;
            }
        }

        .button-group {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            justify-content: center;
        }

        .button-group button {
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-print {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }

        .btn-close {
            background: #e5e7eb;
            color: #1f2937;
        }

        .btn-close:hover {
            background: #d1d5db;
        }

        @media print {
            .button-group {
                display: none;
            }
        }

        .no-data {
            background: #dcfce7;
            border: 2px dashed #16a34a;
            padding: 30px;
            text-align: center;
            border-radius: 8px;
            margin: 30px 0;
        }

        .no-data h3 {
            color: #15803d;
            font-size: 16px;
            margin-bottom: 10px;
        }

        .no-data p {
            color: #15803d;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <!-- Buttons -->
        <div class="button-group">
            <button class="btn-print" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <button class="btn-close" onclick="window.history.back()"><i class="fas fa-times"></i> Close</button>
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
            <div class="report-title">ALL STUDENT VIOLATIONS REPORT</div>
        </div>


        <!-- Violations Table -->
        <?php if ($totalViolations > 0): ?>
        <div style="margin-top: 30px;">
            <h3 style="font-size: 16px; margin-bottom: 15px; color: #1f2937; border-bottom: 2px solid #e5e7eb; padding-bottom: 10px;">
                VIOLATION RECORDS (<?php echo $totalViolations; ?> Total)
            </h3>
            <table class="violations-table">
                <thead>
                    <tr>
                        <th>STUDENT NAME</th>
                        <th>ID (LRN)</th>
                        <th>GRADE</th>
                        <th>YEAR</th>
                        <th>VIOLATION</th>
                        <th>CATEGORY</th>
                        <th>DATE REPORTED</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($violations as $violation): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($violation['student_name']); ?></strong>
                        </td>
                        <td>
                            <span class="lrn-badge"><?php echo htmlspecialchars($violation['lrn']); ?></span>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($violation['grade']); ?> - <?php echo htmlspecialchars($violation['section']); ?>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($violation['academicyear']); ?>
                        </td>
                        <td>
                            <strong><?php echo htmlspecialchars($violation['violation_title']); ?></strong>
                        </td>
                        <td>
                            <span class="violation-type <?php echo strtolower($violation['violation_type']); ?>">
                                <?php echo htmlspecialchars($violation['violation_type']); ?>
                            </span>
                        </td>
                        <td>
                            <?php echo date('M d, Y', strtotime($violation['date'])); ?>
                        </td>
                        <td>
                            <span class="status-badge <?php echo strtolower($violation['status']); ?>">
                                <?php echo htmlspecialchars($violation['status']); ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="no-data">
            <h3><i class="fas fa-check-circle"></i> No Violations Found</h3>
            <p>There are no violations matching the selected criteria.</p>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="report-footer">
            <p><strong>Perpetual Help College of Manila</strong></p>
            <p>Established 1928 | Character Building is Nation Building</p>
            <p style="margin-top: 10px; font-size: 10px; color: #999;">
                This is an official document generated by the Student Violation Management System. 
                <br>Generated on <?php echo date('F d, Y \a\t h:i A'); ?>
            </p>
        </div>
    </div>
</body>
</html>
