// violation-record.js - Pagination and Table Management - CORRECTED

// Pagination Variables
let currentPage = 1;
let entriesPerPage = 10;
let filteredRows = [];

document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const violationCheckboxes = document.querySelectorAll('.violation-checkbox');
    const resolveSelectedBtn = document.getElementById('resolveSelectedBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const entriesSelect = document.getElementById('entriesPerPage');
    const searchInput = document.getElementById('searchInput');

    // Initialize pagination
    initializePagination();

    // Entries per page change
    entriesSelect.addEventListener('change', function() {
        entriesPerPage = parseInt(this.value);
        currentPage = 1;
        applyFiltersAndPagination();
    });

    // Search functionality
    searchInput.addEventListener('input', function() {
        currentPage = 1;
        applyFiltersAndPagination();
    });

    function updateActionButtons() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        
        resolveSelectedBtn.style.display = count > 0 ? 'inline-flex' : 'none';
        deleteSelectedBtn.style.display = count > 0 ? 'inline-flex' : 'none';
    }

    // Select All functionality (only for current page)
    selectAllCheckbox.addEventListener('change', function() {
        const visibleCheckboxes = document.querySelectorAll('#violationTableBody tr:not([style*="display: none"]) .violation-checkbox');
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateActionButtons();
    });

    // Individual checkbox change
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('violation-checkbox')) {
            const visibleCheckboxes = document.querySelectorAll('#violationTableBody tr:not([style*="display: none"]) .violation-checkbox');
            const visibleChecked = document.querySelectorAll('#violationTableBody tr:not([style*="display: none"]) .violation-checkbox:checked');
            
            selectAllCheckbox.checked = visibleCheckboxes.length > 0 && visibleChecked.length === visibleCheckboxes.length;
            selectAllCheckbox.indeterminate = visibleChecked.length > 0 && visibleChecked.length < visibleCheckboxes.length;
            updateActionButtons();
        }
    });
});

function initializePagination() {
    const allRows = document.querySelectorAll('#violationTableBody tr');
    filteredRows = Array.from(allRows);
    applyFiltersAndPagination();
}

function applyFiltersAndPagination() {
    const searchTerm = document.getElementById('searchInput').value.toLowerCase();
    const allRows = document.querySelectorAll('#violationTableBody tr');
    
    // Filter rows based on search
    filteredRows = Array.from(allRows).filter(row => {
        const text = row.textContent.toLowerCase();
        return text.includes(searchTerm);
    });
    
    // Hide all rows first
    allRows.forEach(row => row.style.display = 'none');
    
    // Calculate pagination
    const totalEntries = filteredRows.length;
    const totalPages = Math.ceil(totalEntries / entriesPerPage);
    
    // Ensure current page is valid
    if (currentPage > totalPages && totalPages > 0) {
        currentPage = totalPages;
    }
    if (currentPage < 1) {
        currentPage = 1;
    }
    
    // Show rows for current page
    const startIndex = (currentPage - 1) * entriesPerPage;
    const endIndex = Math.min(startIndex + entriesPerPage, totalEntries);
    
    for (let i = startIndex; i < endIndex; i++) {
        filteredRows[i].style.display = '';
    }
    
    // Update showing text
    updateShowingText(startIndex + 1, endIndex, totalEntries);
    
    // Update pagination buttons
    updatePaginationButtons(totalPages);
    
    // Update select all checkbox
    document.getElementById('selectAll').checked = false;
    document.getElementById('selectAll').indeterminate = false;
}

function updateShowingText(start, end, total) {
    const showingText = document.querySelector('.showing-text');
    if (total === 0) {
        showingText.textContent = 'Showing 0 to 0 of 0 entries';
    } else {
        showingText.textContent = `Showing ${start} to ${end} of ${total} entries`;
    }
}

function updatePaginationButtons(totalPages) {
    const paginationDiv = document.querySelector('.pagination');
    paginationDiv.innerHTML = '';
    
    // First page button
    const firstBtn = createPageButton('«', 1, currentPage === 1);
    paginationDiv.appendChild(firstBtn);
    
    // Previous page button
    const prevBtn = createPageButton('‹', currentPage - 1, currentPage === 1);
    paginationDiv.appendChild(prevBtn);
    
    // Page number buttons
    const pageNumbers = getPageNumbers(currentPage, totalPages);
    pageNumbers.forEach(pageNum => {
        if (pageNum === '...') {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.textContent = '...';
            paginationDiv.appendChild(ellipsis);
        } else {
            const pageBtn = createPageButton(pageNum, pageNum, false, pageNum === currentPage);
            paginationDiv.appendChild(pageBtn);
        }
    });
    
    // Next page button
    const nextBtn = createPageButton('›', currentPage + 1, currentPage === totalPages || totalPages === 0);
    paginationDiv.appendChild(nextBtn);
    
    // Last page button
    const lastBtn = createPageButton('»', totalPages, currentPage === totalPages || totalPages === 0);
    paginationDiv.appendChild(lastBtn);
}

function createPageButton(text, page, disabled, active = false) {
    const btn = document.createElement('button');
    btn.className = 'page-btn';
    btn.textContent = text;
    
    if (disabled) {
        btn.disabled = true;
    }
    
    if (active) {
        btn.classList.add('active');
    }
    
    if (!disabled && !active) {
        btn.addEventListener('click', () => {
            currentPage = page;
            applyFiltersAndPagination();
        });
    }
    
    return btn;
}

function getPageNumbers(current, total) {
    const pages = [];
    
    if (total <= 7) {
        // Show all pages if 7 or fewer
        for (let i = 1; i <= total; i++) {
            pages.push(i);
        }
    } else {
        // Always show first page
        pages.push(1);
        
        if (current <= 3) {
            // Near start
            pages.push(2, 3, 4, '...', total);
        } else if (current >= total - 2) {
            // Near end
            pages.push('...', total - 3, total - 2, total - 1, total);
        } else {
            // Middle
            pages.push('...', current - 1, current, current + 1, '...', total);
        }
    }
    
    return pages;
}

// Export Records
function exportRecords() {
    const date = document.getElementById('exportDate').value;
    const dateObj = new Date(date);
    const formattedDate = dateObj.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
    
    if (!date) {
        alert('Please select a date to export records.');
        return;
    }
    
    let csvContent = "data:text/csv;charset=utf-8,";
    csvContent += "Student Name,Student ID,Grade,Violation,Date Reported,Type,Status\n";
    
    let exportCount = 0;
    filteredRows.forEach(row => {
        const studentName = row.dataset.studentName;
        const studentId = row.dataset.studentId;
        const grade = row.dataset.grade;
        const violation = row.dataset.violation;
        const dateReported = row.dataset.date;
        const type = row.dataset.type;
        const status = row.dataset.status;
        
        csvContent += `"${studentName}","${studentId}","${grade}","${violation}","${dateReported}","${type}","${status}"\n`;
        exportCount++;
    });
    
    if (exportCount === 0) {
        alert('No records to export.');
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

// Resolve Selected
function resolveSelected() {
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one violation to resolve.');
        return;
    }
    
    if (!confirm(`Are you sure you want to resolve ${checkedBoxes.length} selected violation(s)?`)) {
        return;
    }
    
    const recordIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    fetch('php/update-status-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=bulk_resolve&record_ids=' + JSON.stringify(recordIds)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Selected violations have been resolved!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to resolve violations'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while resolving violations');
    });
}

// Delete Selected
function deleteSelected() {
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    if (checkedBoxes.length === 0) {
        alert('Please select at least one violation to delete.');
        return;
    }
    
    if (!confirm(`Are you sure you want to delete ${checkedBoxes.length} selected violation(s)?`)) {
        return;
    }
    
    const recordIds = Array.from(checkedBoxes).map(cb => cb.value);
    
    fetch('php/delete-record-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=bulk_delete&record_ids=' + JSON.stringify(recordIds)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Selected violations have been deleted!');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete violations'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting violations');
    });
}

// Delete Violation
function deleteViolation(id) {
    if (!confirm('Are you sure you want to delete this violation record?')) {
        return;
    }
    
    fetch('php/delete-record-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'action=delete&record_id=' + id
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Violation record deleted successfully.');
            location.reload();
        } else {
            alert('Error: ' + (data.message || 'Failed to delete violation'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while deleting the violation');
    });
}

// View Resolution Letter - CORRECTED TO SHOW SPECIFIC VIOLATION
function viewResolutionLetter(id) {
    console.log('Looking for violation ID:', id);
    console.log('ID type:', typeof id);
    
    // Convert id to string for comparison
    const searchId = String(id);
    
    // Try to find the row - search in ALL rows including hidden ones
    let row = null;
    const allRows = document.querySelectorAll('#violationTableBody tr[data-violation-id]');
    console.log('Total rows available:', allRows.length);
    
    // Log all available IDs for debugging
    console.log('Available violation IDs:');
    allRows.forEach(r => {
        const rowId = r.getAttribute('data-violation-id');
        console.log('  - Row ID:', rowId, '(type:', typeof rowId, ')');
        if (String(rowId) === searchId) {
            row = r;
        }
    });
    
    if (!row) {
        console.error('Row not found for ID:', id);
        alert('Error: Could not find violation record. ID: ' + id);
        return;
    }
    
    console.log('Row found:', row);
    
    // Check if this violation is actually resolved
    const violationStatus = row.getAttribute('data-status');
    console.log('Violation status:', violationStatus);
    
    if (violationStatus !== 'Resolved') {
        alert('This violation is not marked as Resolved. Current status: ' + violationStatus);
        return;
    }
    
    const studentDbId = row.getAttribute('data-student-db-id');
    console.log('Student DB ID:', studentDbId);
    
    if (!studentDbId) {
        console.error('Student DB ID is missing from row');
        alert('Error: Student ID not found in violation record.');
        return;
    }
    
    console.log('Fetching student violations...');
    
    // Fetch ALL resolved violations for this student
    fetch(`get-student-violations.php?student_id=${studentDbId}&status=Resolved`)
        .then(response => {
            console.log('Fetch response status:', response.status);
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                if (data.violations && data.violations.length > 0) {
                    populateResolutionLetter(data.student, data.violations);
                } else {
                    // If no resolved violations found from database, create one from the current row data
                    console.log('No resolved violations in database, using current row data');
                    const student = {
                        name: row.getAttribute('data-student-name'),
                        lrn: row.getAttribute('data-student-id'),
                        grade: row.getAttribute('data-grade').split(' - ')[0],
                        section: row.getAttribute('data-grade').split(' - ')[1] || ''
                    };
                    const violations = [{
                        violation: row.getAttribute('data-violation'),
                        type: row.getAttribute('data-type'),
                        date_reported: row.getAttribute('data-date'),
                        status: 'Resolved'
                    }];
                    populateResolutionLetter(student, violations);
                }
            } else {
                alert('Error loading violation data: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while loading the letter: ' + error.message);
        });
}

function populateResolutionLetter(student, violations) {
    console.log('Populating letter with:', student, violations);
    
    document.getElementById('letterDate').textContent = new Date().toLocaleDateString('en-US', { 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    document.getElementById('letterStudentName').textContent = student.name;
    document.getElementById('letterStudentId').textContent = student.lrn;
    document.getElementById('letterGrade').textContent = student.grade + ' - ' + student.section;
    document.getElementById('letterStudentNameBody').textContent = student.name;
    
    let tableHTML = '';
    violations.forEach(v => {
        const typeClass = v.type.toLowerCase() === 'minor' ? 'type-minor' : 
                         (v.type.toLowerCase() === 'serious' ? 'type-serious' : 'type-major');
        const committedDate = new Date(v.date_reported);
        const resolvedDate = new Date();
        
        tableHTML += `
            <tr>
                <td>${v.violation}</td>
                <td class="${typeClass}">${v.type}</td>
                <td>${committedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</td>
                <td>${resolvedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</td>
            </tr>
        `;
    });
    document.getElementById('letterViolationsTable').innerHTML = tableHTML;
    
    const modal = document.getElementById('resolutionModal');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    console.log('Modal opened successfully');
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
            <style>
                @page { size: letter; margin: 0.75in; }
                @media print {
                    html, body { width: 100%; height: 100%; }
                    body { margin: 0; padding: 0; }
                }
                * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; box-sizing: border-box; }
                body { font-family: 'Times New Roman', Times, serif; padding: 0; margin: 0; line-height: 1.5; background: white; font-size: 12px; }
                .school-header { margin-bottom: 20px; }
                .school-header-content { display: flex; align-items: center; gap: 12px; margin-bottom: 8px; }
                .school-logo { width: 50px; height: 50px; object-fit: contain; }
                .school-header-text { flex: 1; }
                .school-country { font-size: 10px; margin: 0; color: #333; }
                .school-name { font-size: 14px; font-weight: bold; margin: 2px 0; color: #000; }
                .school-motto { font-size: 9px; margin: 0; color: #333; }
                .school-header-line { border-top: 2px solid #000; margin-top: 8px; }
                .letter-title { text-align: center; font-size: 16px; font-weight: bold; margin: 20px 0; text-transform: uppercase; }
                .letter-info p { margin: 6px 0; font-size: 12px; }
                .letter-body p { margin: 10px 0; font-size: 12px; text-align: justify; line-height: 1.5; }
                .letter-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .letter-table th, .letter-table td { border: 1px solid #333; padding: 6px; text-align: left; font-size: 11px; }
                .letter-table th { background: #f0f0f0; }
                .type-minor { color: #07df00ff; }
                .type-serious { color: #f59e0b; }
                .type-major { color: #ef4444; }
                .signature-line { width: 200px; border-top: 1px solid #000; margin-top: 30px; margin-bottom: 4px; }
                .letter-signature p { margin: 3px 0; font-size: 12px; }
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

// Modal Event Listeners
document.getElementById('resolutionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResolutionModal();
    }
});

document.addEventListener('keydown', function(e) {
    const resolutionModal = document.getElementById('resolutionModal');
    if (e.key === 'Escape' && resolutionModal?.classList.contains('show')) {
        closeResolutionModal();
    }
});

// Add CSS for pagination ellipsis
const style = document.createElement('style');
style.textContent = `
    .page-ellipsis {
        padding: 6px 12px;
        color: #6b7280;
        user-select: none;
    }
`;
document.head.appendChild(style);