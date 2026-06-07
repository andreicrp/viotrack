    <?php
    // adminstudentviolation.php

    // Set timezone first
    date_default_timezone_set('Asia/Manila');

    // Include database connection FIRST
    require_once 'connect.php';

    // Include authentication check
    require_once 'auth_check.php';

    // Require login - only admin and teacher can access
    requireAdminOrTeacher();

    if (!$conn) {
        die("Connection failed: No database connection");
    }

    // Check user type (Admin or Teacher)
    $isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
    $isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

    // Get student ID from session (from QR scan) or URL parameter
    $studentId = 0;
    $fromQRScan = false;

    // Check if coming from QR scan (session variable)
    if (isset($_SESSION['temp_student_id'])) {
        $studentId = intval($_SESSION['temp_student_id']);
        $fromQRScan = true;
        unset($_SESSION['temp_student_id']); // Clear after use
    } elseif (isset($_GET['id'])) {
        // Fallback to URL parameter for direct access
        $studentId = intval($_GET['id']);
    }

    // Debug: Check if ID is received
    if ($studentId === 0) {
        // No ID provided, redirect back
        header("Location: students.php?error=no_id");
        exit();
    }

    // Generate a secure QR token for this student (expires in 30 minutes)
    $qrToken = bin2hex(random_bytes(32));
    $tokenStmt = $conn->prepare("INSERT INTO qr_tokens (student_id, token, created_at) VALUES (?, ?, NOW())");
    if ($tokenStmt) {
        $tokenStmt->bind_param("is", $studentId, $qrToken);
        if (!$tokenStmt->execute()) {
            error_log("QR Token insert error: " . $conn->error);
            // Continue anyway - QR code will still work
        }
        $tokenStmt->close();
    } else {
        error_log("QR Token statement prepare error: " . $conn->error);
        // Continue anyway - QR code will still work
    }

    // Fetch student data from database
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

    // If student not found, redirect back BEFORE any output
    if (!$student) {
        header("Location: students.php?error=student_not_found&id=" . $studentId);
        exit();
    }

    // Now include header and sidebar after validation
    include 'header.php';
    include 'sidebar.php';

    // Fetch violation records for this student
    $violationQuery = "SELECT r.*, 
        v.title as violation_title,
        v.type as violation_type,
        CONCAT(s.fname, ' ', s.mname, ' ', s.lname) as student_name,
        s.lrn,
        s.grade,
        s.section,
        s.academicyear,
        s.image as student_image
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

    // Fetch all violations grouped by type for the add record modal
    try {
        // Use prepared statement with MySQLi
        $violationsQuery = $conn->query("SELECT id, title, description, type FROM violation ORDER BY type, title");
        
        if (!$violationsQuery) {
            throw new Exception("Query error: " . $conn->error);
        }
        
        $allViolations = [];
        while ($row = $violationsQuery->fetch_assoc()) {
            $allViolations[] = $row;
        }
        
        // Group violations by type
        $minorViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'minor'; });
        $seriousViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'serious'; });
        $majorViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'major'; });
        
        // Debug: Log to file
        error_log("Total violations: " . count($allViolations));
        error_log("Minor: " . count($minorViolations) . ", Serious: " . count($seriousViolations) . ", Major: " . count($majorViolations));
    } catch(Exception $e) {
        error_log("Query Error: " . $e->getMessage());
        $minorViolations = $seriousViolations = $majorViolations = [];
    }
    ?>

    <!-- Include CSS -->
    <link rel="stylesheet" href="css/adminstudentviolation.css">
    <link rel="stylesheet" href="css/addrecord-modal.css">
    <link rel="stylesheet" href="css/status-modal.css">
    <link rel="stylesheet" href="css/resolution-modal.css">

    <!-- Include QRCode.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

    <main class="main-content">

        <!-- Student Information Card -->
        <div class="student-info-card">
            <!-- Student Photo -->
            <div class="student-photo-wrapper">
                <img src="<?php echo htmlspecialchars($student['image']); ?>" 
                    alt="<?php echo htmlspecialchars($student['full_name']); ?>" 
                    class="student-photo" 
                    onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($student['full_name']); ?>&background=3b82f6&color=fff&size=160'">
            </div>
            
            <!-- Student Information -->
            <div class="student-info-content">
                <div class="info-section">
                    <h2 class="info-section-title">Student Information</h2>
                    <div class="info-grid">
                        <!-- Details Column -->
                        <div class="info-column">
                            <div class="info-column-header">Details</div>
                            <div class="info-item">
                                <div class="info-label">Learner Reference Number</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['lrn']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Full Name</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Grade Level</div>
                                <div class="info-value">
                                    <?php echo htmlspecialchars($student['grade']); ?> - 
                                    <?php echo htmlspecialchars($student['section']); ?> 
                                    (<?php echo htmlspecialchars($student['academicyear']); ?>)
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-label">Gender</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['gender']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Personal Information Column -->
                        <div class="info-column">
                            <div class="info-column-header">Personal Information</div>
                            <div class="info-item">
                                <div class="info-value"><?php echo htmlspecialchars($student['lrn']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                            </div>
                            <div class="info-item">
                                <div class="info-value">
                                    <?php echo htmlspecialchars($student['grade']); ?> - 
                                    <?php echo htmlspecialchars($student['section']); ?> 
                                    (<?php echo htmlspecialchars($student['academicyear']); ?>)
                                </div>
                            </div>
                            <div class="info-item">
                                <div class="info-value"><?php echo htmlspecialchars($student['gender']); ?></div>
                            </div>
                        </div>
                        
                        <!-- Contact Information Column -->
                        <div class="info-column">
                            <div class="info-column-header">Contact Information</div>
                            <div class="info-item">
                                <div class="contact-label">E-mail</div>
                                <a href="mailto:<?php echo htmlspecialchars($student['email']); ?>" 
                                class="contact-value"><?php echo htmlspecialchars($student['email']); ?></a>
                            </div>
                            <div class="info-item">
                                <div class="contact-label">Guardian</div>
                                <span class="contact-value"><?php echo htmlspecialchars($student['guardian']); ?></span>
                            </div>
                            <div class="info-item">
                                <div class="contact-label">Guardian Contact #</div>
                                <span class="contact-value phone"><?php echo htmlspecialchars($student['guardiancontact']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- QR Code -->
            <div class="qr-code-section">
                <div class="qr-code" id="qrcode" style="width: 200px !important; height: 200px !important;">
                    <!-- QR code will be generated here by JavaScript -->
                </div>
            </div>
        </div>

        <!-- Violation Record Card -->
        <div class="violation-record-card">
            <div class="violation-card-header">
                <div class="violation-title-section">
                    <h3><?php echo htmlspecialchars($student['full_name']); ?> Violation Record List</h3>
                    <p class="subtitle"><?php echo htmlspecialchars($student['full_name']); ?> have <?php echo $totalViolations; ?> violation record(s)</p>
                </div>
                <div class="violation-header-actions">
                    <?php if ($isAdmin): ?>
                    <button class="btn-send-message" onclick="sendMessage()">
                        <i class="fas fa-envelope"></i> Send Message
                    </button>
                    <button class="btn-generate-report" onclick="generateReport()">
                        <i class="fas fa-file-alt"></i> Generate Report
                    </button>
                    <?php endif; ?>
                    <button class="btn-add-record" onclick="addNewRecord()">
                        <i class="fas fa-user-plus"></i> Add new record
                    </button>
                    <?php if ($isAdmin): ?>
                    <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelectedViolations()" style="display: none;">
                        <i class="fas fa-trash"></i> Delete Selected
                    </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Table Controls -->
            <div class="table-controls">
                <div class="entries-control">
                    <select id="entriesPerPage" class="entries-select">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                    <span>entries per page</span>
                </div>
                <div class="search-control">
                    <label>Search:</label>
                    <input type="text" id="searchInput" class="search-input" placeholder="">
                </div>
            </div>

            <!-- Violation Table -->
            <div class="table-container">
                <table class="violation-table">
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" id="selectAll">
                            </th>
                            <th class="sortable">STUDENT <i class="fas fa-sort"></i></th>
                            <th class="sortable">YEAR <i class="fas fa-sort"></i></th>
                            <th class="sortable">VIOLATION <i class="fas fa-sort"></i></th>
                            <th class="sortable">DATE REPORTED <i class="fas fa-sort"></i></th>
                            <th class="sortable">VIOLATION TYPE <i class="fas fa-sort"></i></th>
                            <th class="sortable">STATUS <i class="fas fa-sort"></i></th>
                            <th>ACTION</th>
                        </tr>
                    </thead>
                    <tbody id="violationTableBody">
                        <?php if (count($violations) > 0): ?>
                            <?php foreach ($violations as $violation): ?>
                            <tr data-violation-id="<?php echo $violation['id']; ?>">
                                <td class="checkbox-col">
                                    <input type="checkbox" class="violation-checkbox" value="<?php echo $violation['id']; ?>">
                                </td>
                                <td>
                                    <div class="student-cell">
                                        <img src="<?php echo htmlspecialchars($violation['student_image']); ?>" 
                                            alt="<?php echo htmlspecialchars($violation['student_name']); ?>" 
                                            class="student-cell-avatar" 
                                            onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($violation['student_name']); ?>&background=f59e0b&color=fff&size=40'">
                                        <div class="student-cell-info">
                                            <span class="student-cell-name"><?php echo htmlspecialchars($violation['student_name']); ?></span>
                                            <span class="student-cell-lrn">LRN: <?php echo htmlspecialchars($violation['lrn']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="year-cell">
                                        <span class="year-grade"><?php echo htmlspecialchars($violation['grade']); ?> - <?php echo htmlspecialchars($violation['section']); ?></span>
                                        <span class="year-school"><?php echo htmlspecialchars($violation['academicyear']); ?></span>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($violation['violation_title']); ?></td>
                                <td><?php echo date('M d, Y h:i A', strtotime($violation['date'])); ?></td>
                                <td>
                                    <span class="violation-type <?php echo strtolower($violation['violation_type']); ?>">
                                        <?php echo htmlspecialchars($violation['violation_type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo strtolower($violation['status']); ?>">
                                        <?php echo htmlspecialchars($violation['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($isAdmin): ?>
                                        <button class="btn-action btn-delete" onclick="removeViolation(<?php echo $violation['id']; ?>)">
                                            <i class="far fa-trash-alt"></i> Remove
                                        </button>
                                        <?php endif; ?>
                                        <?php if (strtolower($violation['status']) === 'resolved'): ?>
                                        <button class="btn-action btn-doc-proof" onclick="viewDocProof(<?php echo $violation['id']; ?>)">
                                            <i class="far fa-image"></i> Doc. Proof
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center; padding: 40px;">
                                    <i class="fas fa-inbox" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                                    <p style="color: #64748b; font-size: 16px;">No violation records found for this student.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Table Footer -->
            <div class="table-footer">
                <span class="showing-text">Showing 1 to <?php echo min(10, $totalViolations); ?> of <?php echo $totalViolations; ?> entries</span>
                <div class="pagination" id="paginationContainer">
                    <!-- Pagination buttons will be generated by JavaScript -->
                </div>
            </div>
        </div>
    </main>

    <script>
    // Pagination functionality
    let currentPage = 1;
    let entriesPerPage = 10;
    let allRows = [];

    function initializePagination() {
        // Get all rows from the table body
        allRows = Array.from(document.querySelectorAll('#violationTableBody tr[data-violation-id]'));
        
        // Get entries per page selector
        const entriesSelect = document.getElementById('entriesPerPage');
        if (entriesSelect) {
            entriesPerPage = parseInt(entriesSelect.value);
            entriesSelect.addEventListener('change', function() {
                entriesPerPage = parseInt(this.value);
                currentPage = 1;
                displayPage(currentPage);
                updatePaginationButtons();
            });
        }
        
        // Initialize display
        displayPage(currentPage);
        updatePaginationButtons();
    }

    function displayPage(pageNum) {
        const startIdx = (pageNum - 1) * entriesPerPage;
        const endIdx = startIdx + entriesPerPage;
        
        // Hide all rows
        allRows.forEach(row => {
            row.style.display = 'none';
        });
        
        // Show rows for current page
        allRows.slice(startIdx, endIdx).forEach(row => {
            row.style.display = '';
        });
        
        // Update showing text
        const totalPages = Math.ceil(allRows.length / entriesPerPage);
        const displayStart = Math.min(startIdx + 1, allRows.length);
        const displayEnd = Math.min(endIdx, allRows.length);
        document.querySelector('.showing-text').textContent = `Showing ${displayStart} to ${displayEnd} of ${allRows.length} entries`;
        
        currentPage = pageNum;
    }

    function updatePaginationButtons() {
        const totalPages = Math.ceil(allRows.length / entriesPerPage);
        const paginationContainer = document.getElementById('paginationContainer');
        
        if (totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }
        
        let html = '';
        
        // First button
        html += `<button class="page-btn" onclick="goToPage(1)" ${currentPage === 1 ? 'disabled' : ''}>«</button>`;
        
        // Previous button
        html += `<button class="page-btn" onclick="goToPage(${currentPage - 1})" ${currentPage === 1 ? 'disabled' : ''}>‹</button>`;
        
        // Page numbers
        let startPage = Math.max(1, currentPage - 2);
        let endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            html += `<button class="page-btn" onclick="goToPage(1)">1</button>`;
            if (startPage > 2) {
                html += `<span class="page-ellipsis">...</span>`;
            }
        }
        
        for (let i = startPage; i <= endPage; i++) {
            html += `<button class="page-btn ${i === currentPage ? 'active' : ''}" onclick="goToPage(${i})">${i}</button>`;
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                html += `<span class="page-ellipsis">...</span>`;
            }
            html += `<button class="page-btn" onclick="goToPage(${totalPages})">${totalPages}</button>`;
        }
        
        // Next button
        html += `<button class="page-btn" onclick="goToPage(${currentPage + 1})" ${currentPage === totalPages ? 'disabled' : ''}>›</button>`;
        
        // Last button
        html += `<button class="page-btn" onclick="goToPage(${totalPages})" ${currentPage === totalPages ? 'disabled' : ''}>»</button>`;
        
        paginationContainer.innerHTML = html;
    }

    function goToPage(pageNum) {
        const totalPages = Math.ceil(allRows.length / entriesPerPage);
        if (pageNum >= 1 && pageNum <= totalPages) {
            displayPage(pageNum);
            updatePaginationButtons();
        }
    }

    // QR Code Generation
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize pagination
        initializePagination();
        
        // IMPORTANT: Replace this with your actual InfinityFree domain when deploying
        var domain = window.location.origin;
        // Uncomment and use your actual InfinityFree domain:
        // var domain = "https://yourdomain.infinityfreeapp.com";
        // OR if you have a custom domain:
        // var domain = "https://www.yourdomain.com";
        
        // Build the full URL to this student's violation page
        // Use scan-qr.php which handles authentication with secure token
        var studentId = <?php echo $studentId; ?>;
        var qrToken = '<?php echo $qrToken; ?>';
        var url = domain + "/scan-qr.php?id=" + studentId + "&token=" + encodeURIComponent(qrToken);
        
        // Clear any existing QR code
        var qrcodeContainer = document.getElementById("qrcode");
        qrcodeContainer.innerHTML = "";
        
        // Generate the QR code
        var qrcode = new QRCode(qrcodeContainer, {
            text: url,
            width: 200,
            height: 200,
            colorDark: "#000000",
            colorLight: "#ffffff",
            correctLevel: QRCode.CorrectLevel.H
        });
        
        // Force the QR code image to be larger (override any CSS)
        setTimeout(function() {
            var qrImg = qrcodeContainer.querySelector(  'img');
            if (qrImg) {
                qrImg.style.width = '200px';
                qrImg.style.height = '200px';
                qrImg.style.maxWidth = '200px';
                qrImg.style.maxHeight = '200px';
            }
        }, 100);
        
        // Auto-open Add Record Modal if coming from QR scan
        var fromQRScan = <?php echo $fromQRScan ? 'true' : 'false'; ?>;
        if (fromQRScan) {
            setTimeout(function() {
                openAddRecordModal();
            }, 500);
        }
    });

    // Checkbox and Delete functionality
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const violationCheckboxes = document.querySelectorAll('.violation-checkbox');
        const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

        function updateDeleteButton() {
            const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
            deleteSelectedBtn.style.display = checkedBoxes.length > 0 ? 'inline-flex' : 'none';
        }

        // Select All functionality
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                violationCheckboxes.forEach(checkbox => {
                    checkbox.checked = this.checked;
                });
                updateDeleteButton();
            });
        }

        // Individual checkbox change
        violationCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = document.querySelectorAll('.violation-checkbox:checked').length === violationCheckboxes.length;
                const someChecked = document.querySelectorAll('.violation-checkbox:checked').length > 0;
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = allChecked;
                    selectAllCheckbox.indeterminate = someChecked && !allChecked;
                }
                updateDeleteButton();
            });
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('#violationTableBody tr');
                
                rows.forEach(row => {
                    // Get all cells except the first one (checkbox) and exclude LRN from search
                    const cells = Array.from(row.querySelectorAll('td')).slice(1);
                    let textContent = '';
                    
                    cells.forEach((cell, index) => {
                        if (index === 0) {
                            // First cell (student info) - get only name, exclude LRN
                            const studentName = cell.querySelector('.student-cell-name')?.textContent || '';
                            textContent += studentName.toLowerCase() + ' ';
                        } else {
                            textContent += cell.textContent.toLowerCase() + ' ';
                        }
                    });
                    
                    row.style.display = textContent.includes(searchTerm) ? '' : 'none';
                });
                updateShowingText();
            });
        }
    });

    function updateShowingText() {
        const visibleRows = document.querySelectorAll('#violationTableBody tr:not([style*="display: none"])').length;
        const showingText = document.querySelector('.showing-text');
        if (showingText) {
            showingText.textContent = `Showing 1 to ${visibleRows} of ${visibleRows} entries`;
        }
    }

    // Action functions
    function sendMessage() {
        const guardianPhone = '<?php echo htmlspecialchars($student['guardiancontact'] ?? ''); ?>';
        const smsModal = document.getElementById('smsModal');
        if (smsModal) {
            // Auto-populate phone number if available
            const phoneInput = document.getElementById('smsPhoneNumber');
            if (phoneInput && guardianPhone) {
                phoneInput.value = guardianPhone;
            }
            smsModal.classList.add('active');
            // Focus on the first empty required field
            setTimeout(() => {
                const reportTypeInput = document.getElementById('smsReportType');
                if (reportTypeInput) {
                    reportTypeInput.focus();
                }
            }, 100);
        }
    }

    function closeSmsModal() {
        const smsModal = document.getElementById('smsModal');
        if (smsModal) {
            smsModal.classList.remove('active');
        }
    }

    function submitSmsForm(event) {
        event.preventDefault();
        
        const studentId = <?php echo $studentId; ?>;
        const phoneNumber = document.getElementById('smsPhoneNumber').value.trim();
        const message = document.getElementById('smsMessage').value.trim();
        const reportType = document.getElementById('smsReportType').value.trim();
        const reportDate = document.getElementById('smsReportDate').value.trim();
        
        if (!phoneNumber) {
            alert('Please enter a phone number');
            return;
        }
        
        if (!reportType) {
            alert('Please select a report type');
            return;
        }
        
        if (!reportDate) {
            alert('Please select a report date');
            return;
        }
        
        const submitBtn = event.target.querySelector('.btn-proceed');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        }
        
        fetch('send-sms.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                student_id: studentId,
                phone_number: phoneNumber,
                message: message,
                report_type: reportType,
                report_date: reportDate
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const smsStatus = data.sms_status === 200 ? '✓ SMS sent successfully' : '✓ Report created (SMS sending attempted)';
                alert(smsStatus + '\nPhone: ' + data.phone + '\nReport ID: ' + data.report_id);
                closeSmsModal();
                document.getElementById('smsForm').reset();
                // Reload the page to show the new report
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                alert('Error: ' + data.message);
            }
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Proceed';
            }
        })
        .catch(error => {
            console.error('Fetch Error:', error);
            console.error('Error details:', error.message);
            alert('Error: ' + error.message);
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Proceed';
            }
        });
    }

    function generateReport() {
        const studentId = <?php echo $studentId; ?>;
        window.open(`print-violation-report.php?id=${studentId}`, '_blank');
    }

    function addNewRecord() {
        openAddRecordModal();
    }

    function changeStatus(id) {
        openStatusModal(id);
    }

    function deleteSelectedViolations() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (count === 0) {
            alert('Please select at least one violation record to delete.');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${count} selected violation record(s)?`)) {
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            formData.append('record_ids', JSON.stringify(ids));
            
            fetch('update-status-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    checkedBoxes.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        row.remove();
                    });
                    document.getElementById('selectAll').checked = false;
                    document.getElementById('deleteSelectedBtn').style.display = 'none';
                    updateShowingText();
                    alert(`Successfully deleted ${count} violation record(s).`);
                    location.reload();
                } else {
                    alert('Error deleting records: ' + (data.message || 'Failed to delete violations'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting records.');
            });
        }
    }

    function removeViolation(id) {
        if (confirm('Are you sure you want to remove this violation record?')) {
            const formData = new FormData();
            formData.append('action', 'bulk_delete');
            formData.append('record_ids', JSON.stringify([id]));
            
            fetch('update-status-handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const row = document.querySelector(`tr[data-violation-id="${id}"]`);
                    row.remove();
                    updateShowingText();
                    alert('Violation record removed successfully.');
                    location.reload();
                } else {
                    alert('Error removing record: ' + (data.message || 'Failed to delete violation'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while removing the record.');
            });
        }
    }
        
    function viewDocProof(id) {
        viewResolutionLetter(id);
    }

    // ============ ADD RECORD MODAL FUNCTIONS ============
    function getCookie(name) {
        const nameEQ = name + "=";
        const cookies = document.cookie.split(';');
        for(let i = 0; i < cookies.length; i++) {
            let cookie = cookies[i].trim();
            if (cookie.indexOf(nameEQ) === 0) {
                return decodeURIComponent(cookie.substring(nameEQ.length));
            }
        }
        return "";
    }

    function openAddRecordModal() {
        const modal = document.getElementById('addRecordModal');
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Get location from cookies
            const lat = getCookie('user_lat');
            const lng = getCookie('user_lng');
            const accuracy = getCookie('user_accuracy');
            
            console.log('openAddRecordModal - Checking cookies:', {lat, lng, accuracy});
            
            // Default location (Manila, Philippines)
            const defaultLat = 14.6124466;
            const defaultLng = 120.9879835;
            
            // Ensure location fields always have values
            const latField = document.getElementById('recordLatitude');
            const lngField = document.getElementById('recordLongitude');
            
            // If cookies exist and are valid, use them; otherwise use default
            if (lat && lng && lat.trim() !== '' && lng.trim() !== '') {
                console.log('✅ Using location from cookies:', lat, lng, 'Accuracy:', accuracy);
                if (latField) latField.value = lat;
                if (lngField) lngField.value = lng;
                
                // Show status
                const statusGroup = document.getElementById('locationStatusGroup');
                const statusText = document.getElementById('locationStatusText');
                if (statusGroup && statusText) {
                    statusGroup.style.display = 'block';
                    const accuracyDisplay = accuracy && accuracy !== '0' ? ' (Accuracy: ' + accuracy + 'm)' : ' (Default Location)';
                    statusText.innerHTML = '<i class="fas fa-map-pin" style="color: #16a34a; margin-right: 8px;"></i> Location loaded from QR scan' + accuracyDisplay;
                }
                
                // Show alert that location was loaded
                setTimeout(function() {
                    alert('📍 Location Loaded\n\nLocation has been captured from your QR scan.\n\nAccuracy: ' + (accuracy || 'Default Location') + '\n\nYou can now record the violation.');
                }, 300);
            } else {
                // No cookies - set default location
                console.log('❌ No location cookies found - using default location');
                if (latField) latField.value = defaultLat;
                if (lngField) lngField.value = defaultLng;
                
                // Show alert asking for permission
                if (confirm('📍 Location Permission Required\n\nThis system needs your device location to track where violations are recorded.\n\nPlease click OK to allow access to your location.')) {
                    // User clicked OK - request location
                    console.log('User approved location request - capturing location...');
                    if (typeof captureLocation === 'function') {
                        captureLocation();
                    } else {
                        console.error('captureLocation function not found');
                        console.log('⚠️ Warning: Location function not available. Using default location.');
                    }
                } else {
                    // User clicked Cancel - inform about default location
                    console.log('User denied location request - using default location');
                    alert('⚠️ Location Denied\n\nUsing default location (Manila). The violation will still be recorded.');
                }
            }
        }
    }

    function closeAddRecordModal() {
        const modal = document.getElementById('addRecordModal');
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
            resetAddRecordForm();
        }
    }

    // Capture location from device geolocation API
    function captureLocation() {
        console.log('captureLocation() called');
        
        if ("geolocation" in navigator) {
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    const accuracy = Math.round(position.coords.accuracy);
                    
                    console.log('✅ Location captured:', lat, lng, 'Accuracy:', accuracy + 'm');
                    
                    // Store in hidden fields
                    document.getElementById('recordLatitude').value = lat;
                    document.getElementById('recordLongitude').value = lng;
                    
                    // Store in cookies as well
                    const expiryTime = new Date();
                    expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
                    document.cookie = "user_lat=" + encodeURIComponent(lat) + "; expires=" + expiryTime.toUTCString() + "; path=/";
                    document.cookie = "user_lng=" + encodeURIComponent(lng) + "; expires=" + expiryTime.toUTCString() + "; path=/";
                    document.cookie = "user_accuracy=" + accuracy + "; expires=" + expiryTime.toUTCString() + "; path=/";
                    
                    // Show success status
                    const statusGroup = document.getElementById('locationStatusGroup');
                    const statusText = document.getElementById('locationStatusText');
                    if (statusGroup && statusText) {
                        statusGroup.style.display = 'block';
                        statusGroup.querySelector('div').style.background = '#f0fdf4';
                        statusGroup.querySelector('div').style.borderColor = '#86efac';
                        statusText.innerHTML = '<i class="fas fa-map-pin" style="color: #16a34a; margin-right: 8px;"></i> Location captured (Accuracy: ' + accuracy + 'm)';
                        statusText.style.color = '#16a34a';
                    }
                },
                function(error) {
                    console.warn('❌ Geolocation error:', error.message);
                    
                    // Use default location (Manila)
                    const defaultLat = '14.6124466';
                    const defaultLng = '120.9879835';
                    
                    document.getElementById('recordLatitude').value = defaultLat;
                    document.getElementById('recordLongitude').value = defaultLng;
                    
                    // Store in cookies
                    const expiryTime = new Date();
                    expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
                    document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
                    document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
                    document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
                    
                    // Show error status
                    const statusGroup = document.getElementById('locationStatusGroup');
                    if (statusGroup) {
                        statusGroup.style.display = 'block';
                        statusGroup.querySelector('div').style.background = '#fef2f2';
                        statusGroup.querySelector('div').style.borderColor = '#fca5a5';
                        const statusText = document.getElementById('locationStatusText');
                        if (statusText) {
                            statusText.innerHTML = '<i class="fas fa-map-pin" style="color: #dc2626; margin-right: 8px;"></i> Using default location (Manila)';
                            statusText.style.color = '#dc2626';
                        }
                    }
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        } else {
            console.log('❌ Geolocation not supported by browser');
            
            // Use default location
            const defaultLat = '14.6124466';
            const defaultLng = '120.9879835';
            
            document.getElementById('recordLatitude').value = defaultLat;
            document.getElementById('recordLongitude').value = defaultLng;
            
            // Store in cookies
            const expiryTime = new Date();
            expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
            document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
            document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
        }
    }

    function resetAddRecordForm() {
        const form = document.getElementById('addRecordForm');
        if (form) form.reset();
        
        document.querySelectorAll('.violation-checkbox').forEach(cb => cb.checked = false);
        updateViolationDisplay();
        
        const dropdown = document.getElementById('violationDropdownMenu');
        if (dropdown) dropdown.classList.remove('open');
    }

    function toggleViolationDropdown(event) {
        event.stopPropagation();
        const dropdown = document.getElementById('violationDropdownMenu');
        if (dropdown) {
            dropdown.classList.toggle('open');
        }
    }

    function filterViolations(searchTerm) {
        const items = document.querySelectorAll('.violation-checkbox-item');
        const term = searchTerm.toLowerCase();
        
        items.forEach(item => {
            const text = item.textContent.toLowerCase();
            item.style.display = text.includes(term) ? 'flex' : 'none';
        });
    }

    function updateViolationDisplay() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const display = document.getElementById('selectedViolationsDisplay');
        
        if (checkedBoxes.length === 0) {
            display.textContent = 'Nothing selected';
        } else {
            const selected = Array.from(checkedBoxes).map(cb => {
                const label = cb.parentElement.querySelector('span');
                return label ? label.textContent : '';
            }).join(', ');
            display.textContent = selected;
        }
    }

    function submitAddRecordForm(event) {
        event.preventDefault();
        
        const studentId = <?php echo $studentId; ?>;
        const selectedViolations = Array.from(document.querySelectorAll('.violation-checkbox:checked')).map(cb => cb.value);
        
        if (selectedViolations.length === 0) {
            alert('Please select at least one violation');
            return;
        }
        
        // Get location from form fields
        let latitude = document.getElementById('recordLatitude')?.value || '';
        let longitude = document.getElementById('recordLongitude')?.value || '';
        let accuracy = getCookie('user_accuracy') || '0';
        
        // Ensure location values are valid numbers
        latitude = parseFloat(latitude) || 14.6124466; // Default to Manila if invalid
        longitude = parseFloat(longitude) || 120.9879835; // Default to Manila if invalid
        
        console.log('submitAddRecordForm - Location data:', {latitude, longitude, accuracy});
        
        const payload = {
            student_id: studentId,
            violations: selectedViolations,
            record_latitude: latitude,
            record_longitude: longitude,
            accuracy: accuracy
        };
        
        console.log('Payload being sent:', payload);
        
        fetch('add_violation_record.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully added ${data.count} violation record(s)!`);
                closeAddRecordModal();
                location.reload();
            } else {
                alert('Error: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Offline detected, saving to localStorage:', error);
            
            // Get actual location if available
            let recordLatitude = latitude.toString();
            let recordLongitude = longitude.toString();
            let recordAccuracy = accuracy || '0';
            
            if (typeof geoHandler !== 'undefined') {
                geoHandler.getLocation().then(location => {
                    recordLatitude = (location.latitude || latitude).toString();
                    recordLongitude = (location.longitude || longitude).toString();
                    recordAccuracy = (location.accuracy || accuracy || 0).toString();
                    
                    // OFFLINE: Save to localStorage with actual location
                    const records = [];
                    const now = new Date();
                    const year = now.getFullYear();
                    const month = String(now.getMonth() + 1).padStart(2, '0');
                    const day = String(now.getDate()).padStart(2, '0');
                    const hours = String(now.getHours()).padStart(2, '0');
                    const minutes = String(now.getMinutes()).padStart(2, '0');
                    const seconds = String(now.getSeconds()).padStart(2, '0');
                    const dateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                    
                    selectedViolations.forEach(vid => {
                        records.push({
                            sid: studentId,
                            vid: vid,
                            lat: recordLatitude,
                            lng: recordLongitude,
                            accuracy: recordAccuracy,
                            date: dateTime
                        });
                    });
                    
                    records.forEach(r => {
                        let pending = JSON.parse(localStorage.getItem("pendingViolationRecords") || "[]");
                        pending.push(r);
                        localStorage.setItem("pendingViolationRecords", JSON.stringify(pending));
                    });
                    
                    console.log('Saved ' + records.length + ' records with location:', {lat: recordLatitude, lng: recordLongitude, accuracy: recordAccuracy});
                    alert('✓ ' + records.length + ' violation(s) saved offline. Will sync when connected.');
                    closeAddRecordModal();
                });
            } else {
                // Fallback if geolocation handler not available
                const records = [];
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const dateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                
                selectedViolations.forEach(vid => {
                    records.push({
                        sid: studentId,
                        vid: vid,
                        lat: recordLatitude,
                        lng: recordLongitude,
                        accuracy: recordAccuracy,
                        date: dateTime
                    });
                });
                
                records.forEach(r => {
                    let pending = JSON.parse(localStorage.getItem("pendingViolationRecords") || "[]");
                    pending.push(r);
                    localStorage.setItem("pendingViolationRecords", JSON.stringify(pending));
                });
                
                alert('✓ ' + records.length + ' violation(s) saved offline. Will sync when connected.');
                closeAddRecordModal();
            }
        });
    }

    // ============ STATUS MODAL FUNCTIONS ============
    // Removed - Change Status functionality removed per user request

    // ============ RESOLUTION LETTER FUNCTIONS ============
    function viewResolutionLetter(recordId) {
        // First fetch the clicked violation to get student ID
        fetch('get-student-violations.php?id=' + recordId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.violation) {
                const violation = data.violation;
                const studentId = data.violation.student_db_id;
                
                // Now fetch ALL resolved violations for this student
                return fetch('get-student-violations.php?student_id=' + studentId + '&status=Resolved')
                    .then(response => response.json())
                    .then(allData => {
                        if (allData.success) {
                            // Populate modal header with student info
                            document.getElementById('resolutionStudent').textContent = allData.student.name || 'N/A';
                            document.getElementById('resolutionStudentId').textContent = allData.student.lrn || 'N/A';
                            document.getElementById('resolutionGradeSection').textContent = (allData.student.grade || 'N/A') + ' - ' + (allData.student.section || 'N/A');
                            document.getElementById('resolutionDate').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                            document.getElementById('letterStudentNameBody').textContent = allData.student.name || 'Student';
                            
                            // Clear and populate table with ALL resolved violations
                            const tableBody = document.getElementById('letterViolationsTable');
                            tableBody.innerHTML = '';
                            
                            if (allData.violations && allData.violations.length > 0) {
                                allData.violations.forEach(v => {
                                    const row = document.createElement('tr');
                                    const typeClass = 'type-' + (v.type ? v.type.toLowerCase() : 'minor');
                                    row.innerHTML = `
                                        <td>${v.violation || 'N/A'}</td>
                                        <td class="${typeClass}">${v.type || 'N/A'}</td>
                                        <td>${v.date_reported || 'N/A'}</td>
                                    `;
                                    tableBody.appendChild(row);
                                });
                            }
                            
                            // Show the modal
                            document.getElementById('resolutionModal').classList.add('show');
                            document.body.style.overflow = 'hidden';
                        } else {
                            alert('Error loading violations: ' + (allData.message || 'Failed to load'));
                        }
                    });
            } else {
                alert('Error loading violation data: ' + (data.message || 'Violation not found'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading violation data: ' + error.message);
        });
    }

    function closeResolutionModal() {
        document.getElementById('resolutionModal').classList.remove('show');
        document.body.style.overflow = '';
    }

    function printLetter() {
        const content = document.getElementById('letterContent').innerHTML;
        const printWindow = window.open('', '_blank', 'width=800,height=600');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Violation Resolution Letter</title>
                <link rel="icon" type="image/x-icon" href="images/favicon.ico">
                <style>
                    @page {
                        size: letter;
                        margin: 0.75in;
                    }
                    
                    @media print {
                        html, body {
                            width: 100%;
                            height: 100%;
                        }
                        
                        body {
                            margin: 0;
                            padding: 0;
                        }
                    }
                    
                    * {
                        -webkit-print-color-adjust: exact !important;
                        print-color-adjust: exact !important;
                        box-sizing: border-box;
                    }
                    
                    body { 
                        font-family: 'Times New Roman', Times, serif; 
                        padding: 0;
                        margin: 0;
                        line-height: 1.5;
                        background: white;
                        font-size: 12px;
                    }
                    
                    .school-header {
                        margin-bottom: 20px;
                    }
                    
                    .school-header-content {
                        display: flex;
                        align-items: center;
                        gap: 12px;
                        margin-bottom: 8px;
                    }
                    
                    .school-logo {
                        width: 50px;
                        height: 50px;
                        object-fit: contain;
                    }
                    
                    .school-header-text {
                        flex: 1;
                    }
                    
                    .school-country {
                        font-size: 10px;
                        margin: 0;
                        color: #333;
                    }
                    
                    .school-name {
                        font-size: 14px;
                        font-weight: bold;
                        margin: 2px 0;
                        color: #000;
                    }
                    
                    .school-motto {
                        font-size: 9px;
                        margin: 0;
                        color: #333;
                    }
                    
                    .school-header-line {
                        border-top: 2px solid #000;
                        margin-top: 8px;
                    }
                    
                    .letter-title { 
                        text-align: center; 
                        font-size: 16px; 
                        font-weight: bold; 
                        margin: 20px 0; 
                        text-transform: uppercase; 
                    }
                    
                    .letter-info p { 
                        margin: 6px 0; 
                        font-size: 12px; 
                    }
                    
                    .letter-body p { 
                        margin: 10px 0; 
                        font-size: 12px; 
                        text-align: justify;
                        line-height: 1.5;
                    }
                    
                    .letter-table { 
                        width: 100%; 
                        border-collapse: collapse; 
                        margin: 15px 0; 
                    }
                    
                    .letter-table th, .letter-table td { 
                        border: 1px solid #333; 
                        padding: 4px; 
                        text-align: left; 
                        font-size: 9px; 
                    }
                    
                    .letter-table th { 
                        background: #f0f0f0; 
                    }
                    
                    .type-minor { 
                        color: #07df00ff; 
                    }
                    
                    .type-serious { 
                        color: #f59e0b; 
                    }
                    
                    .type-major { 
                        color: #ef4444; 
                    }
                    
                    .signature-line { 
                        width: 200px; 
                        border-top: 1px solid #000; 
                        margin-top: 30px; 
                        margin-bottom: 4px; 
                    }
                    
                    .letter-signature p { 
                        margin: 3px 0; 
                        font-size: 12px; 
                    }
                </style>
            </head>
            <body onload="window.print(); window.onafterprint = function(){ window.close(); }">${content}</body>
            </html>
        `);
        printWindow.document.close();
    }

    function downloadPDF() {
        alert('PDF generation feature requires a PDF library (e.g., jsPDF).\n\nFor now, please use the Print button and save as PDF from your browser\'s print dialog.');
    }

    function printResolutionLetter() {
        window.print();
    }

    // Close modals with Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const statusModal = document.getElementById('statusModal');
            const resolutionModal = document.getElementById('resolutionModal');
            const addRecordModal = document.getElementById('addRecordModal');
            
            if (statusModal && statusModal.classList.contains('show')) {
                closeStatusModal();
            }
            if (resolutionModal && resolutionModal.classList.contains('show')) {
                closeResolutionModal();
            }
            if (addRecordModal && addRecordModal.classList.contains('show')) {
                closeAddRecordModal();
            }
        }
    });

    // Close modals when clicking outside
    document.addEventListener('click', function(e) {
        const statusModal = document.getElementById('statusModal');
        const resolutionModal = document.getElementById('resolutionModal');
        const addRecordModal = document.getElementById('addRecordModal');
        
        if (statusModal && e.target === statusModal) {
            closeStatusModal();
        }
        if (resolutionModal && e.target === resolutionModal) {
            closeResolutionModal();
        }
        if (addRecordModal && e.target === addRecordModal) {
            closeAddRecordModal();
        }
    });
    
    // Mobile optimization: Prevent scrolling on body when modal is open
    function lockBodyScroll() {
        document.body.style.overflow = 'hidden';
        if (window.innerWidth <= 768) {
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
        }
    }
    
    function unlockBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.position = '';
        document.body.style.width = '';
    }
    
    // Enhance openAddRecordModal for mobile
    const originalOpenAddRecordModal = openAddRecordModal;
    openAddRecordModal = function() {
        originalOpenAddRecordModal.apply(this, arguments);
        lockBodyScroll();
    }
    
    // Enhance closeAddRecordModal for mobile
    const originalCloseAddRecordModal = closeAddRecordModal;
    closeAddRecordModal = function() {
        originalCloseAddRecordModal.apply(this, arguments);
        unlockBodyScroll();
    }

    </script>

    <!-- Add Record Modal -->
    <div class="addrecord-modal-overlay" id="addRecordModal">
        <div class="addrecord-modal">
            <div class="addrecord-modal-header">
                <h2>Add Violation Record for <?php echo htmlspecialchars($student['full_name']); ?></h2>
                <button class="addrecord-modal-close" onclick="closeAddRecordModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="addrecord-modal-body">
                <form method="POST" id="addRecordForm" onsubmit="submitAddRecordForm(event)">
                    <!-- Hidden Location Fields -->
                    <input type="hidden" id="recordLatitude" name="record_latitude" value="">
                    <input type="hidden" id="recordLongitude" name="record_longitude" value="">
                    
                    <!-- Location Status Display -->
                    <div id="locationStatusGroup" style="display: none; margin-bottom: 15px;">
                        <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 12px;">
                            <div id="locationStatusText" style="color: #16a34a; font-size: 14px; display: flex; align-items: center;">
                                <i class="fas fa-map-pin" style="margin-right: 8px;"></i> Location captured
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dropdown Selection -->
                    <div class="violation-selector">
                        <div class="violation-dropdown-wrapper">
                            <div class="violation-dropdown-trigger" onclick="toggleViolationDropdown(event)">
                                <span id="selectedViolationsDisplay">Nothing selected</span>
                                <i class="fas fa-chevron-down"></i>
                            </div>
                            
                            <div class="violation-dropdown-menu" id="violationDropdownMenu">
                                <div class="violation-search">
                                    <input type="text" id="violationSearchInput" placeholder="Search violations..." oninput="filterViolations(this.value)">
                                </div>
                                
                                <div class="violation-categories">
                                    <!-- Minor Offenses -->
                                    <div class="violation-category">
                                        <div class="category-header" style="color: #10b981;">Minor Offenses</div>
                                        <div class="violation-list" id="minorViolationsList">
                                            <?php foreach ($minorViolations as $violation): ?>
                                                <label class="violation-checkbox-item">
                                                    <input type="checkbox" 
                                                        class="violation-checkbox" 
                                                        name="violations[]" 
                                                        value="<?php echo $violation['id']; ?>"
                                                        data-category="minor"
                                                        onchange="updateViolationDisplay()">
                                                    <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Serious Offenses -->
                                    <div class="violation-category">
                                        <div class="category-header" style="color: #f59e0b;">Serious Offenses</div>
                                        <div class="violation-list" id="seriousViolationsList">
                                            <?php foreach ($seriousViolations as $violation): ?>
                                                <label class="violation-checkbox-item">
                                                    <input type="checkbox" 
                                                        class="violation-checkbox" 
                                                        name="violations[]" 
                                                        value="<?php echo $violation['id']; ?>"
                                                        data-category="serious"
                                                        onchange="updateViolationDisplay()">
                                                    <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Major Offenses -->
                                    <div class="violation-category">
                                        <div class="category-header" style="color: #ef4444;">Major Offenses</div>
                                        <div class="violation-list" id="majorViolationsList">
                                            <?php foreach ($majorViolations as $violation): ?>
                                                <label class="violation-checkbox-item">
                                                    <input type="checkbox" 
                                                        class="violation-checkbox" 
                                                        name="violations[]" 
                                                        value="<?php echo $violation['id']; ?>"
                                                        data-category="major"
                                                        onchange="updateViolationDisplay()">
                                                    <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="addrecord-modal-footer">
                <button type="submit" class="addrecord-modal-btn btn-add-record" id="addRecordBtn" form="addRecordForm">
                    Add Record
                </button>
                <button type="button" class="addrecord-modal-btn btn-cancel-add-record" onclick="closeAddRecordModal()">
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- Include Resolution Letter Modal -->
    <?php include 'resolution-modal.php'; ?>

    <!-- SMS Modal -->
    <div class="sms-modal-overlay" id="smsModal">
        <div class="sms-modal">
            <div class="sms-modal-header">
                <h2>Send SMS Report</h2>
                <button type="button" class="sms-modal-close" onclick="closeSmsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form class="sms-form" id="smsForm" onsubmit="submitSmsForm(event)">
                <div class="sms-modal-body">
                    <div class="form-group">
                        <label for="smsPhoneNumber">Phone Number <span class="required">*</span></label>
                        <input type="tel" id="smsPhoneNumber" name="phone_number" class="form-control" 
                            placeholder="e.g., +63912345678" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="smsReportType">Report Type <span class="required">*</span></label>
                        <input type="text" id="smsReportType" name="report_type" class="form-control" 
                            placeholder="e.g., Parents Needed" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="smsReportDate">Date & Time <span class="required">*</span></label>
                        <input type="datetime-local" id="smsReportDate" name="report_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="smsMessage">Message</label>
                        <textarea id="smsMessage" name="message" class="form-control" 
                                placeholder="Optional message to include in SMS" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="sms-modal-footer">
                    <button type="button" class="btn-close" onclick="closeSmsModal()">
                        <i class="fas fa-times"></i> Close
                    </button>
                    <button type="submit" class="btn-proceed">
                        <i class="fas fa-paper-plane"></i> Proceed
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Close SMS modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const smsModal = document.getElementById('smsModal');
            if (smsModal && smsModal.classList.contains('active')) {
                closeSmsModal();
            }
        }
    });

    // Close modal when clicking outside
    document.getElementById('smsModal')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeSmsModal();
        }
    });
    </script>

    <?php 
    $conn->close();
    include 'footer.php'; 
    ?>