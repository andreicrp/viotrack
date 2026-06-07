<?php
// print-student-id.php
session_start();

// Require login
require_once 'auth_check.php';
require_once 'connect.php';
requireLogin();

// Get student ID from URL parameter or from calling page
$studentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($studentId === 0) {
    die("Invalid student ID");
}

// Fetch student data from database
$studentQuery = "SELECT id, lrn, fname, mname, lname, grade, section, academicyear, image,
    CONCAT(fname, ' ', IFNULL(CONCAT(mname, ' '), ''), lname) as full_name
    FROM student
    WHERE id = ?";

$stmt = $conn->prepare($studentQuery);
if (!$stmt) {
    die("Database error: " . $conn->error);
}

$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();
$student_data = $result->fetch_assoc();
$stmt->close();

if (!$student_data) {
    die("Student not found");
}

// Generate a secure QR token for this student (expires in 30 minutes)
$qrToken = bin2hex(random_bytes(32));
$tokenStmt = $conn->prepare("INSERT INTO qr_tokens (student_id, token, created_at) VALUES (?, ?, NOW())");
$tokenStmt->bind_param("is", $studentId, $qrToken);
$tokenStmt->execute();
$tokenStmt->close();

// Build the base URL
$domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'];
$qr_data = $domain . "/scan-qr.php?id=" . $studentId . "&token=" . urlencode($qrToken);

// Prepare student info for display
$student = [
    'id' => $studentId,
    'lrn' => htmlspecialchars($student_data['lrn']),
    'name' => htmlspecialchars($student_data['full_name']),
    'grade' => htmlspecialchars($student_data['grade']),
    'section' => htmlspecialchars($student_data['section']),
    'school_year' => htmlspecialchars($student_data['academicyear']),
    'avatar' => htmlspecialchars($student_data['image']),
    'qr_data' => $qr_data
];

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print Student ID - <?php echo $student['name']; ?></title>
    <link rel="icon" type="image/x-icon" href="images/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, #f0f4f8 0%, #e8f0f7 100%);
            padding: 30px 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            min-height: 100vh;
        }

        .print-container {
            width: 100%;
            max-width: 450px;
            margin-bottom: 30px;
        }

        .id-card {
            width: 100%;
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 40px rgba(30, 41, 59, 0.1);
            position: relative;
        }

        /* Header Section */
        .id-header {
            background: linear-gradient(135deg, #27367f 0%, #1f2947 100%);
            padding: 24px 20px;
            text-align: center;
            color: #fff;
        }

        .school-name {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .card-title {
            font-size: 12px;
            font-weight: 500;
            color: rgba(255, 255, 255, 0.8);
            letter-spacing: 1px;
            margin-top: 8px;
        }

        /* Main Content */
        .id-content {
            padding: 28px 24px;
        }

        /* Photo Section */
        .photo-section {
            text-align: center;
            margin-bottom: 24px;
        }

        .student-photo {
            width: 100px;
            height: 100px;
            border-radius: 12px;
            object-fit: cover;
            border: 3px solid #e2e8f0;
            box-shadow: 0 4px 12px rgba(30, 41, 59, 0.08);
        }

        /* Info Section */
        .info-section {
            margin-bottom: 24px;
        }

        .info-item {
            display: grid;
            grid-template-columns: 80px 1fr;
            gap: 12px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }

        .info-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .info-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }

        /* QR Code Section */
        .qr-section {
            border-top: 2px solid #e2e8f0;
            padding-top: 20px;
            text-align: center;
        }

        .qr-label {
            font-size: 11px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            display: block;
        }

        .qr-code-container {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 140px;
            height: 140px;
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin: 0 auto;
        }

        .qr-code-container img {
            width: 100%;
            height: 100%;
            display: block;
        }

        /* Footer Section */
        .id-footer {
            background: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 11px;
            font-weight: 500;
            color: #64748b;
            margin: 4px 0;
        }

        /* Print Buttons */
        .print-actions {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-family: inherit;
        }

        .btn-print {
            background: linear-gradient(135deg, #27367f 0%, #1f2947 100%);
            color: #fff;
            border: none;
        }

        .btn-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(39, 54, 127, 0.2);
        }

        .btn-close {
            background: #e2e8f0;
            color: #475569;
            border: none;
        }

        .btn-close:hover {
            background: #cbd5e1;
        }

        /* Print Styles */
        @media print {
            @page {
                size: A4 portrait;
                margin: 10mm;
            }

            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }

            .print-container {
                max-width: 100%;
                width: 100%;
                margin: 0;
            }

            .print-actions {
                display: none !important;
            }

            .id-card {
                box-shadow: none;
                max-width: 380px;
                margin: 0 auto;
            }

            .id-header,
            .id-content,
            .id-footer,
            .qr-code-container {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                color-adjust: exact;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 10px;
            }

            .id-header {
                padding: 20px 16px;
            }

            .id-content {
                padding: 20px 16px;
            }

            .qr-code-container {
                width: 120px;
                height: 120px;
            }

            .student-photo {
                width: 80px;
                height: 80px;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="id-card">
            <!-- Header with School Info -->
            <div class="id-header">
                <div class="school-name">VIOLATION ID CARD</div>
                <div class="card-title">VIOTRACK</div>
            </div>

            <!-- Main Content -->
            <div class="id-content">
                <!-- Student Photo -->
                <div class="photo-section">
                    <img src="<?php echo htmlspecialchars($student['avatar']); ?>" 
                         alt="<?php echo $student['name']; ?>" 
                         class="student-photo"
                         onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['name']); ?>&background=1e293b&color=fff&size=100'">
                </div>

                <!-- Student Information -->
                <div class="info-section">
                    <div class="info-item">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?php echo $student['name']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">LRN</span>
                        <span class="info-value"><?php echo $student['lrn']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Grade</span>
                        <span class="info-value"><?php echo $student['grade']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">Section</span>
                        <span class="info-value"><?php echo $student['section']; ?></span>
                    </div>
                    
                    <div class="info-item">
                        <span class="info-label">School Year</span>
                        <span class="info-value"><?php echo $student['school_year']; ?></span>
                    </div>
                </div>

                <!-- QR Code Section -->
                <div class="qr-section">
                    <span class="qr-label">Violation Tracking QR Code</span>
                    <div class="qr-code-container">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=140x140&data=<?php echo urlencode($student['qr_data']); ?>&ecc=H" 
                             alt="QR Code" 
                             loading="lazy">
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="id-footer">
                <div class="footer-text"><strong>Issued:</strong> <?php echo date('F d, Y'); ?></div>
                <div class="footer-text">Valid for SY <?php echo $student['school_year']; ?></div>
            </div>
        </div>
    </div>

    <!-- Print Controls -->
    <div class="print-actions">
        <button class="btn btn-print" onclick="window.print()">
            <span>🖨️</span> Print ID Card
        </button>
        <button class="btn btn-close" onclick="window.close()">
            <span>✕</span> Close
        </button>
    </div>

    <script>
        // Auto-focus and prepare for printing
        document.addEventListener('DOMContentLoaded', function() {
            // Focus print button
            document.querySelector('.btn-print')?.focus();
            
            // Optional: Auto-print
            // Uncomment the line below if you want to auto-open print dialog
            // window.print();
        });

        // Print shortcut (Ctrl+P)
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                window.print();
            }
        });

        // Close on Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                window.close();
            }
        });
    </script>
</body>
</html>