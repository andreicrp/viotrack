    <?php
// violation-record.php - SQL Database Integration with existing 'record' table
include 'auth_check.php';
include 'header.php';
include 'sidebar.php';

// Require login - only admin and teacher can access
requireAdminOrTeacher();

// Check user type
$isAdmin = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
$isTeacher = (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'teacher');

// Fetch violation records from database with student and violation details
$query = "SELECT r.id, r.status, r.date, r.sid as student_db_id, r.vid as violation_id,
                 s.fname, s.mname, s.lname, s.lrn, s.grade, s.section, s.academicyear, s.image,
                 v.title as violation, v.type
          FROM record r
          JOIN student s ON r.sid = s.id
          JOIN violation v ON r.vid = v.id
          ORDER BY r.date DESC";

$result = $conn->query($query);
$violations = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $studentFullName = trim($row['fname'] . ' ' . ($row['mname'] ? $row['mname'] . ' ' : '') . $row['lname']);
        
        // Determine avatar path
        $avatar = 'https://ui-avatars.com/api/?name=' . urlencode($studentFullName) . '&background=e5e7eb&color=6b7280&size=36';
        
        // Check if student has a custom image
        if (!empty($row['image']) && $row['image'] != 'image/default.png') {
            if (strpos($row['image'], 'http') === 0 || file_exists($row['image'])) {
                $avatar = $row['image'];
            }
        } else if (file_exists('image/default.png')) {
            $avatar = 'image/default.png';
        }
        
        $violations[] = [
            'id' => $row['id'],
            'student_name' => $studentFullName,
            'student_id' => $row['lrn'],
            'student_db_id' => $row['student_db_id'],
            'grade' => $row['grade'],
            'section' => $row['section'],
            'school_year' => $row['academicyear'],
            'violation' => $row['violation'],
            'date_reported' => date('M d, Y h:i A', strtotime($row['date'])),
            'type' => $row['type'],
            'status' => $row['status'],
            'avatar' => $avatar
        ];
    }
}

$totalViolations = count($violations);
?>

<!-- Include CSS -->
<link rel="stylesheet" href="css/violation-record.css">
<link rel="stylesheet" href="css/status-modal.css">
<link rel="stylesheet" href="css/resolution-modal.css">

<main class="main-content">
    <!-- Violation Record Card -->
    <div class="violation-record-card">
        <div class="violation-card-header">
            <div class="violation-title-section">
                <h3>Violation Record List</h3>
                <p class="subtitle"><?php echo $totalViolations; ?> violation record(s)</p>
            </div>
            <div class="violation-header-actions">
                <?php if ($isAdmin): ?>
                <div class="export-group">
                    <span class="export-label">Export</span>
                    <input type="date" class="date-input" id="exportDate" value="<?php echo date('Y-m-29'); ?>">
                    <button class="btn-export" onclick="exportRecords()">
                        <i class="fas fa-file-pdf"></i> Export
                    </button>
                </div>
                <?php endif; ?>
                <button class="btn-add-record" onclick="openAddRecordModal()">
                    <i class="fas fa-plus"></i> Add new record
                </button>
                <button class="btn-resolve-selected" id="resolveSelectedBtn" onclick="resolveSelected()" style="display: none;">
                    <i class="fas fa-check"></i> Resolve Selected
                </button>
                <button class="btn-delete-selected" id="deleteSelectedBtn" onclick="deleteSelected()" style="display: none;">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
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
                        <th>STUDENT</th>
                        <th>YEAR</th>
                        <th>VIOLATION</th>
                        <th>DATE REPORTED</th>
                        <th>TYPE</th>
                        <th>STATUS</th>
                        <th>ACTIONS</th>
                    </tr>
                </thead>
                <tbody id="violationTableBody">
                    <?php foreach ($violations as $violation): ?>
                    <tr data-violation-id="<?php echo $violation['id']; ?>" data-student-name="<?php echo htmlspecialchars($violation['student_name']); ?>" data-student-id="<?php echo $violation['student_id']; ?>" data-grade="<?php echo $violation['grade']; ?>" data-violation="<?php echo htmlspecialchars($violation['violation']); ?>" data-type="<?php echo $violation['type']; ?>" data-status="<?php echo $violation['status']; ?>" data-date="<?php echo $violation['date_reported']; ?>" data-student-db-id="<?php echo $violation['id']; ?>">
                        <td class="checkbox-col" data-label="">
                            <input type="checkbox" class="violation-checkbox" value="<?php echo $violation['id']; ?>">
                        </td>
                        <td data-label="STUDENT">
                            <div class="student-cell">
                                <img src="<?php echo $violation['avatar']; ?>" alt="<?php echo $violation['student_name']; ?>" class="student-cell-avatar" onerror="this.src='https://ui-avatars.com/api/?name=<?php echo urlencode($violation['student_name']); ?>&background=e5e7eb&color=6b7280&size=36';">
                                <div class="student-cell-info">
                                    <span class="student-cell-name"><?php echo $violation['student_name']; ?></span>
                                    <span class="student-cell-id">ID: <?php echo $violation['student_id']; ?></span>
                                </div>
                            </div>
                        </td>
                        <td data-label="YEAR">
                            <div class="year-cell">
                                <span class="year-grade"><?php echo $violation['grade']; ?></span>
                                <span class="year-school"><?php echo $violation['school_year']; ?></span>
                            </div>
                        </td>
                        <td data-label="VIOLATION"><?php echo $violation['violation']; ?></td>
                        <td data-label="DATE"><?php echo $violation['date_reported']; ?></td>
                        <td data-label="TYPE">
                            <span class="type-badge <?php echo strtolower($violation['type']); ?>">
                                <?php echo $violation['type']; ?>
                            </span>
                        </td>
                        <td data-label="STATUS">
                            <span class="status-badge <?php echo strtolower($violation['status']); ?>">
                                <?php echo $violation['status']; ?>
                            </span>
                        </td>
                        <td data-label="ACTIONS">
                            <div class="action-buttons">
                                <?php if ($isAdmin): ?>
                                <button class="btn-status" onclick="openStatusModal(<?php echo $violation['id']; ?>)">
                                    <i class="fas fa-flag"></i> <span>Status</span>
                                </button>
                                <?php endif; ?>
                                <?php if ($violation['status'] === 'Resolved'): ?>
                                <button class="btn-doc-proof" onclick="viewResolutionLetter(<?php echo $violation['id']; ?>)">
                                    <i class="fas fa-file-alt"></i> <span>Doc Proof</span>
                                </button>
                                <?php endif; ?>
                                <?php if ($isAdmin): ?>
                                <button class="btn-delete" onclick="deleteViolation(<?php echo $violation['id']; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="table-footer">
            <span class="showing-text">Showing 1 to <?php echo $totalViolations; ?> of <?php echo $totalViolations; ?> entries</span>
            <div class="pagination" id="paginationContainer">
                <!-- Pagination buttons will be generated by JavaScript -->
            </div>
        </div>
    </div>
</main>

<!-- Resolution Letter Modal -->
<div class="resolution-modal-overlay" id="resolutionModal">
    <div class="resolution-modal">
        <div class="resolution-modal-header">
            <h2>Violation Resolution Letter</h2>
            <div class="resolution-header-actions">
                <?php if ($isAdmin): ?>
                <button class="btn-print" onclick="printLetter()">
                    <i class="fas fa-print"></i> Print
                </button>
                <?php endif; ?>
                <button class="resolution-modal-close" onclick="closeResolutionModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        <div class="resolution-modal-body">
            <div class="letter-content" id="letterContent">
                <!-- School Header -->
                <div class="school-header">
                    <div class="school-header-content">
                        <img src="images/phcm-logo.png" alt="School Logo" class="school-logo">
                        <div class="school-header-text">
                            <p class="school-country">Republic of the Philippines</p>
                            <h2 class="school-name">PERPETUAL HELP COLLEGE OF MANILA</h2>
                            <p class="school-motto">CHARACTER BUILDING IS NATION BUILDING</p>
                        </div>
                    </div>
                    <div class="school-header-line"></div>
                </div>
                
                <h1 class="letter-title">VIOLATION RESOLUTION LETTER</h1>
                
                <div class="letter-info">
                    <p><strong>Date:</strong> <span id="letterDate">November 21, 2025</span></p>
                    <p><strong>Student Name:</strong> <span id="letterStudentName">Student Number One</span></p>
                    <p><strong>Student ID:</strong> <span id="letterStudentId">00-0000-001</span></p>
                    <p><strong>Grade/Section:</strong> <span id="letterGrade">Grade 7 - Love</span></p>
                </div>
                
                <div class="letter-body">
                    <p>To whom it may concern,</p>
                    
                    <p>This letter serves to inform you that the following violation(s) committed by <strong id="letterStudentNameBody">Student Number One</strong> have been officially resolved:</p>
                    
                    <table class="letter-table">
                        <thead>
                            <tr>
                                <th style="font-size: 10px;">Violation</th>
                                <th style="font-size: 10px;">Type</th>
                                <th style="font-size: 10px;">Date Committed</th>
                                <th style="font-size: 10px;">Resolved Date</th>
                            </tr>
                        </thead>
                        <tbody id="letterViolationsTable">
                            <!-- Violations will be inserted here -->
                        </tbody>
                    </table>
                    
                    <p>The student has complied with the required corrective actions, and the matter(s) have now been closed in accordance with the school's disciplinary procedures.</p>
                    
                    <p>We encourage the student to continue demonstrating positive behavior and adhering to all school rules moving forward.</p>
                    
                    <p>If further clarification is needed, please contact the school administration.</p>
                    
                    <p>Respectfully,</p>
                </div>
                
                <div class="letter-signature">
                    <div class="signature-line"></div>
                    <p>Sheryl B. Gamboa</p>
                    <p>Prefect of Discipline</p>
                    <p>Perpetual Help College of Manila</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include Status Change Modal -->
<?php include 'status-modal.php'; ?>

<!-- Include Add Record Modal (if exists) -->
<?php 
if (file_exists('addrecord-modal-multi.php')) {
    include 'addrecord-modal-multi.php'; 
} else if (file_exists('addrecord-modal.php')) {
    include 'addrecord-modal.php'; 
}
?>

<script>
// Pagination Variables
let currentPage = 1;
let entriesPerPage = 10;
const totalViolations = <?php echo $totalViolations; ?>;

// Ensure openStatusModal is available globally (will be overridden by status-modal.php)
if (typeof openStatusModal === 'undefined') {
    console.error('openStatusModal function not found!');
    window.openStatusModal = function(id) {
        alert('Status modal function not loaded properly. Please refresh the page.');
    };
}

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const violationCheckboxes = document.querySelectorAll('.violation-checkbox');
    const resolveSelectedBtn = document.getElementById('resolveSelectedBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const entriesPerPageSelect = document.getElementById('entriesPerPage');

    function updateActionButtons() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        
        resolveSelectedBtn.style.display = count > 0 ? 'inline-flex' : 'none';
        deleteSelectedBtn.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    // Initialize pagination on page load
    initializePagination();

    // Entries per page change
    entriesPerPageSelect.addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        updatePagination();
        updateShowingText();
    });

    // Select All functionality
    selectAllCheckbox.addEventListener('change', function() {
        violationCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateActionButtons();
    });

    // Individual checkbox change
    violationCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const allChecked = document.querySelectorAll('.violation-checkbox:checked').length === violationCheckboxes.length;
            const someChecked = document.querySelectorAll('.violation-checkbox:checked').length > 0;
            selectAllCheckbox.checked = allChecked;
            selectAllCheckbox.indeterminate = someChecked && !allChecked;
            updateActionButtons();
        });
    });

    // Search functionality
    document.getElementById('searchInput').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const rows = document.querySelectorAll('#violationTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const isMatch = text.includes(searchTerm);
            row.dataset.searchHidden = !isMatch ? 'true' : 'false';
        });
        currentPage = 1;
        updatePagination();
        updateShowingText();
    });
});

function initializePagination() {
    updatePagination();
    updateShowingText();
}

function updatePagination() {
    const rows = document.querySelectorAll('#violationTableBody tr');
    
    // Get rows that are not hidden by search (data-search-hidden = false or not set)
    const visibleRows = Array.from(rows).filter(row => {
        return row.dataset.searchHidden !== 'true';
    });
    
    const totalVisibleRows = visibleRows.length;
    const totalPages = Math.ceil(totalVisibleRows / entriesPerPage) || 1;
    
    // Ensure current page is valid
    if (currentPage > totalPages) {
        currentPage = Math.max(1, totalPages);
    }

    // First, hide all rows
    rows.forEach(row => {
        row.style.display = 'none';
    });

    // Show only rows for current page from the visible rows
    const startIndex = (currentPage - 1) * entriesPerPage;
    const endIndex = startIndex + entriesPerPage;
    visibleRows.slice(startIndex, endIndex).forEach(row => {
        row.style.display = '';
    });

    // Update pagination buttons
    updatePaginationButtons(totalPages);
}

function updatePaginationButtons(totalPages) {
    const paginationContainer = document.getElementById('paginationContainer');
    paginationContainer.innerHTML = '';

    // First button
    const firstBtn = document.createElement('button');
    firstBtn.className = 'page-btn';
    firstBtn.textContent = '«';
    firstBtn.disabled = currentPage <= 1;
    firstBtn.onclick = () => goToPage(1);
    paginationContainer.appendChild(firstBtn);

    // Previous button
    const prevBtn = document.createElement('button');
    prevBtn.className = 'page-btn';
    prevBtn.textContent = '‹';
    prevBtn.disabled = currentPage <= 1;
    prevBtn.onclick = () => goToPage(Math.max(1, currentPage - 1));
    paginationContainer.appendChild(prevBtn);

    // Page number buttons
    const startPage = Math.max(1, currentPage - 2);
    const endPage = Math.min(totalPages, currentPage + 2);

    for (let i = startPage; i <= endPage; i++) {
        const pageBtn = document.createElement('button');
        pageBtn.className = 'page-btn' + (i === currentPage ? ' active' : '');
        pageBtn.textContent = i;
        pageBtn.onclick = () => goToPage(i);
        paginationContainer.appendChild(pageBtn);
    }

    // Next button
    const nextBtn = document.createElement('button');
    nextBtn.className = 'page-btn';
    nextBtn.textContent = '›';
    nextBtn.disabled = currentPage >= totalPages;
    nextBtn.onclick = () => goToPage(Math.min(totalPages, currentPage + 1));
    paginationContainer.appendChild(nextBtn);

    // Last button
    const lastBtn = document.createElement('button');
    lastBtn.className = 'page-btn';
    lastBtn.textContent = '»';
    lastBtn.disabled = currentPage >= totalPages;
    lastBtn.onclick = () => goToPage(totalPages);
    paginationContainer.appendChild(lastBtn);
}

function goToPage(pageNumber) {
    const rows = document.querySelectorAll('#violationTableBody tr');
    const visibleRows = Array.from(rows).filter(row => row.dataset.searchHidden !== 'true');
    const totalPages = Math.ceil(visibleRows.length / entriesPerPage) || 1;

    if (pageNumber >= 1 && pageNumber <= totalPages) {
        currentPage = pageNumber;
        updatePagination();
        updateShowingText();
    }
}

function updateShowingText() {
    const rows = document.querySelectorAll('#violationTableBody tr');
    const visibleRows = Array.from(rows).filter(row => row.dataset.searchHidden !== 'true');
    const totalVisibleRows = visibleRows.length;
    
    if (totalVisibleRows === 0) {
        document.querySelector('.showing-text').textContent = 'No entries';
        return;
    }
    
    const startEntry = (currentPage - 1) * entriesPerPage + 1;
    const endEntry = Math.min(currentPage * entriesPerPage, totalVisibleRows);

    document.querySelector('.showing-text').textContent = 
        `Showing ${startEntry} to ${endEntry} of ${totalVisibleRows} entries`;
}

// Export Records - Only visible/paginated records
function exportRecords() {
    const date = document.getElementById('exportDate').value;
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    if (!date) {
        alert('Please select a date to export records.');
        return;
    }
    
    const rows = document.querySelectorAll('#violationTableBody tr');
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Student Name,Student ID,Grade,Violation,Date Reported,Type,Status\n";
    
    let exportCount = 0;
    rows.forEach(row => {
        if (row.style.display === 'none') return;
        
        const dateReported = row.dataset.date;
        
        // Extract date from dateReported (format: "M d, Y h:i A")
        // Parse to get just the date portion (e.g., "Dec 01, 2025")
        const reportedDate = dateReported.split(' ').slice(0, 3).join(' '); // Gets "Dec 01, 2025"
        const selectedDate = new Date(date).toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        
        // Only include if dates match
        if (reportedDate !== selectedDate) return;
        
        const studentName = row.dataset.studentName;
        const studentId = row.dataset.studentId;
        const grade = row.dataset.grade;
        const violation = row.dataset.violation;
        const type = row.dataset.type;
        const status = row.dataset.status;
        
        csvContent += `"${studentName}","${studentId}","${grade}","${violation}","${dateReported}","${type}","${status}"\n`;
        exportCount++;
    });
    
    if (exportCount === 0) {
        alert(`No records found for ${formattedDate}.`);
        return;
    }
    
    const link = document.createElement('a');
    link.setAttribute('href', encodeURI(csvContent));
    link.setAttribute('download', `violation_records_${date}.csv`);
    document.body.appendChild(link);
    link.click();
    link.remove();
    
    alert(`Successfully exported ${exportCount} violation record(s) for ${formattedDate}!`);
}

// Close Add Record Modal
function closeAddRecordModal() {
    const modal = document.getElementById('addRecordModal');
    if (modal) {
        modal.classList.remove('show');
    }
}

// Resolve Selected
function resolveSelected() {
    console.log('=== RESOLVE SELECTED CALLED ===');
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    console.log('Checked boxes:', checkedBoxes.length);
    
    if (checkedBoxes.length === 0) {
        alert('Please select at least one violation to resolve.');
        return;
    }
    
    if (confirm(`Are you sure you want to resolve ${checkedBoxes.length} selected violation(s)?`)) {
        // Collect record IDs
        const recordIds = Array.from(checkedBoxes).map(cb => {
            const value = cb.value;
            console.log('Checkbox HTML:', cb.outerHTML);
            console.log('Value:', value);
            return parseInt(value);
        });
        
        console.log('=== RESOLVED IDS ===');
        console.log('Record IDs:', recordIds);
        console.log('IDs JSON:', JSON.stringify(recordIds));
        
        // Send to backend
        const formData = new FormData();
        formData.append('action', 'bulk_resolve');
        formData.append('record_ids', JSON.stringify(recordIds));
        
        console.log('Sending to backend with action=bulk_resolve');
        
        fetch('update-status-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Backend response:', data);
            if (data.success) {
                // Update UI after successful backend update
                checkedBoxes.forEach(checkbox => {
                    const row = checkbox.closest('tr');
                    const statusBadge = row.querySelector('.status-badge');
                    statusBadge.textContent = 'Resolved';
                    statusBadge.className = 'status-badge resolved';
                    row.dataset.status = 'Resolved';
                    checkbox.checked = false;
                    
                    // Add Doc Proof button if not exists
                    const actionButtons = row.querySelector('.action-buttons');
                    if (!actionButtons.querySelector('.btn-doc-proof')) {
                        const violationId = row.dataset.violationId;
                        const docBtn = document.createElement('button');
                        docBtn.className = 'btn-doc-proof';
                        docBtn.innerHTML = '<i class="fas fa-file-alt"></i> Doc Proof';
                        docBtn.onclick = function() { 
                            viewResolutionLetter(violationId); 
                        };
                        actionButtons.insertBefore(docBtn, actionButtons.querySelector('.btn-delete'));
                    }
                });
                document.getElementById('selectAll').checked = false;
                document.getElementById('resolveSelectedBtn').style.display = 'none';
                document.getElementById('deleteSelectedBtn').style.display = 'none';
                alert(`Successfully resolved ${recordIds.length} violation(s)!`);
            } else {
                alert('Error: ' + (data.message || 'Failed to resolve violations'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }
}

// Delete Selected
function deleteSelected() {
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one violation to delete.');
        return;
    }
    
    if (confirm(`Are you sure you want to delete ${checkedBoxes.length} selected violation(s)?`)) {
        // Collect record IDs
        const recordIds = Array.from(checkedBoxes).map(cb => parseInt(cb.value));
        
        // Send to backend
        const formData = new FormData();
        formData.append('action', 'bulk_delete');
        formData.append('record_ids', JSON.stringify(recordIds));
        
        fetch('update-status-handler.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                checkedBoxes.forEach(checkbox => {
                    checkbox.closest('tr').remove();
                });
                document.getElementById('selectAll').checked = false;
                document.getElementById('resolveSelectedBtn').style.display = 'none';
                document.getElementById('deleteSelectedBtn').style.display = 'none';
                updateShowingText();
                alert(`Successfully deleted ${recordIds.length} violation(s)!`);
            } else {
                alert('Error: ' + (data.message || 'Failed to delete violations'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }
}

// Delete Violation
function deleteViolation(id) {
    if (confirm('Are you sure you want to delete this violation record?')) {
        // Send to backend
        const formData = new FormData();
        formData.append('action', 'bulk_delete');
        formData.append('record_ids', JSON.stringify([parseInt(id)]));
        
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
                alert('Violation record deleted successfully.');
            } else {
                alert('Error: ' + (data.message || 'Failed to delete violation'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error: ' + error.message);
        });
    }
}

// View Resolution Letter
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
                        document.getElementById('letterDate').textContent = new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
                        document.getElementById('letterStudentName').textContent = allData.student.name || 'N/A';
                        document.getElementById('letterStudentId').textContent = allData.student.lrn || 'N/A';
                        document.getElementById('letterGrade').textContent = (allData.student.grade || 'N/A') + ' - ' + (allData.student.section || 'N/A');
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
                                    <td>${new Date().toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' })}</td>
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

// Close Resolution Modal
function closeResolutionModal() {
    document.getElementById('resolutionModal').classList.remove('show');
    document.body.style.overflow = '';
}

// Print Letter
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
                
                /* School Header Styles */
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

// Download PDF
function downloadPDF() {
    alert('PDF generation feature requires a PDF library (e.g., jsPDF).\n\nFor now, please use the Print button and save as PDF from your browser\'s print dialog.');
}

// Close resolution modal on overlay click
document.getElementById('resolutionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResolutionModal();
    }
});

// Close resolution modal with Escape key
document.addEventListener('keydown', function(e) {
    const resolutionModal = document.getElementById('resolutionModal');
    if (e.key === 'Escape' && resolutionModal?.classList.contains('show')) {
        closeResolutionModal();
    }
});
</script>

<?php include 'footer.php'; ?>