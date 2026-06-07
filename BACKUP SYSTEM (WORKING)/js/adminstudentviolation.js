// ============================================================================
// PAGINATION SYSTEM
// ============================================================================

// Pagination variables
let currentPage = 1;
let entriesPerPage = 10;
let allViolations = [];

// Initialize pagination on page load
document.addEventListener('DOMContentLoaded', function() {
    // Store all violation rows
    const rows = document.querySelectorAll('#violationTableBody tr');
    rows.forEach(row => {
        allViolations.push(row);
    });
    
    // Initialize pagination
    initializePagination();
    
    // Entries per page selector
    const entriesSelect = document.getElementById('entriesPerPage');
    if (entriesSelect) {
        entriesSelect.addEventListener('change', function() {
            entriesPerPage = parseInt(this.value);
            currentPage = 1;
            displayPage(currentPage);
        });
    }
    
    // Search functionality
    const searchInput = document.getElementById('searchInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentPage = 1;
            displayPage(currentPage);
        });
    }
});

function initializePagination() {
    displayPage(1);
}

function getFilteredViolations() {
    const searchTerm = document.getElementById('searchInput')?.value.toLowerCase() || '';
    
    if (!searchTerm) {
        return allViolations;
    }
    
    return allViolations.filter(row => {
        const text = row.textContent.toLowerCase();
        return text.includes(searchTerm);
    });
}

function displayPage(page) {
    const filteredViolations = getFilteredViolations();
    const totalEntries = filteredViolations.length;
    const totalPages = Math.ceil(totalEntries / entriesPerPage);
    
    // Validate page number
    if (page < 1) page = 1;
    if (page > totalPages && totalPages > 0) page = totalPages;
    currentPage = page;
    
    // Hide all rows first
    allViolations.forEach(row => {
        row.style.display = 'none';
    });
    
    // Calculate start and end indices
    const startIndex = (page - 1) * entriesPerPage;
    const endIndex = Math.min(startIndex + entriesPerPage, totalEntries);
    
    // Show only the rows for current page
    for (let i = startIndex; i < endIndex; i++) {
        filteredViolations[i].style.display = '';
    }
    
    // Update showing text
    updateShowingText(startIndex + 1, endIndex, totalEntries);
    
    // Update pagination controls
    updatePaginationControls(totalPages);
    
    // Show "No results" message if needed
    const tbody = document.getElementById('violationTableBody');
    const noResultsRow = tbody.querySelector('.no-results-row');
    
    if (totalEntries === 0) {
        if (!noResultsRow) {
            const row = document.createElement('tr');
            row.className = 'no-results-row';
            row.innerHTML = `
                <td colspan="8" style="text-align: center; padding: 40px;">
                    <i class="fas fa-search" style="font-size: 48px; color: #cbd5e1; margin-bottom: 16px;"></i>
                    <p style="color: #64748b; font-size: 16px;">No violation records found matching your search.</p>
                </td>
            `;
            tbody.appendChild(row);
        }
    } else if (noResultsRow) {
        noResultsRow.remove();
    }
}

function updateShowingText(start, end, total) {
    const showingText = document.querySelector('.showing-text');
    if (showingText) {
        if (total === 0) {
            showingText.textContent = 'Showing 0 to 0 of 0 entries';
        } else {
            showingText.textContent = `Showing ${start} to ${end} of ${total} entries`;
        }
    }
}

function updatePaginationControls(totalPages) {
    const pagination = document.querySelector('.pagination');
    if (!pagination) return;
    
    // Clear existing pagination
    pagination.innerHTML = '';
    
    // First page button
    const firstBtn = createPageButton('«', 1, currentPage === 1);
    pagination.appendChild(firstBtn);
    
    // Previous page button
    const prevBtn = createPageButton('‹', currentPage - 1, currentPage === 1);
    pagination.appendChild(prevBtn);
    
    // Page number buttons
    const pageNumbers = getPageNumbers(currentPage, totalPages);
    pageNumbers.forEach(pageNum => {
        if (pageNum === '...') {
            const ellipsis = document.createElement('span');
            ellipsis.className = 'page-ellipsis';
            ellipsis.textContent = '...';
            ellipsis.style.padding = '8px';
            ellipsis.style.color = '#64748b';
            pagination.appendChild(ellipsis);
        } else {
            const pageBtn = createPageButton(pageNum, pageNum, false, pageNum === currentPage);
            pagination.appendChild(pageBtn);
        }
    });
    
    // Next page button
    const nextBtn = createPageButton('›', currentPage + 1, currentPage === totalPages || totalPages === 0);
    pagination.appendChild(nextBtn);
    
    // Last page button
    const lastBtn = createPageButton('»', totalPages, currentPage === totalPages || totalPages === 0);
    pagination.appendChild(lastBtn);
}

function createPageButton(text, page, disabled, active = false) {
    const button = document.createElement('button');
    button.className = 'page-btn';
    button.textContent = text;
    button.disabled = disabled;
    
    if (active) {
        button.classList.add('active');
    }
    
    if (!disabled) {
        button.addEventListener('click', function() {
            displayPage(page);
        });
    }
    
    return button;
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
            // Near the beginning
            pages.push(2, 3, 4, '...', total);
        } else if (current >= total - 2) {
            // Near the end
            pages.push('...', total - 3, total - 2, total - 1, total);
        } else {
            // In the middle
            pages.push('...', current - 1, current, current + 1, '...', total);
        }
    }
    
    return pages;
}

// ============================================================================
// CHECKBOX & DELETE FUNCTIONALITY
// ============================================================================

const selectAllCheckbox = document.getElementById('selectAll');
const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');

function updateDeleteButton() {
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    deleteSelectedBtn.style.display = checkedBoxes.length > 0 ? 'inline-flex' : 'none';
}

// Select All functionality (only visible rows on current page)
if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const visibleCheckboxes = Array.from(document.querySelectorAll('.violation-checkbox'))
            .filter(cb => cb.closest('tr').style.display !== 'none');
        
        visibleCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });
}

// Individual checkbox change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('violation-checkbox')) {
        const visibleCheckboxes = Array.from(document.querySelectorAll('.violation-checkbox'))
            .filter(cb => cb.closest('tr').style.display !== 'none');
        const visibleChecked = visibleCheckboxes.filter(cb => cb.checked);
        
        if (selectAllCheckbox) {
            selectAllCheckbox.checked = visibleChecked.length === visibleCheckboxes.length && visibleCheckboxes.length > 0;
            selectAllCheckbox.indeterminate = visibleChecked.length > 0 && visibleChecked.length < visibleCheckboxes.length;
        }
        updateDeleteButton();
    }
});

// Delete Selected
if (deleteSelectedBtn) {
    deleteSelectedBtn.addEventListener('click', function() {
        const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
        const count = checkedBoxes.length;
        
        if (confirm(`Are you sure you want to delete ${count} selected violation record(s)?`)) {
            const ids = Array.from(checkedBoxes).map(cb => cb.value);
            
            fetch('delete_violations.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ ids: ids })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Remove deleted rows from allViolations array
                    checkedBoxes.forEach(checkbox => {
                        const row = checkbox.closest('tr');
                        const index = allViolations.indexOf(row);
                        if (index > -1) {
                            allViolations.splice(index, 1);
                        }
                        row.remove();
                    });
                    
                    if (selectAllCheckbox) selectAllCheckbox.checked = false;
                    updateDeleteButton();
                    
                    // Recalculate pagination
                    const filteredViolations = getFilteredViolations();
                    const totalPages = Math.ceil(filteredViolations.length / entriesPerPage);
                    if (currentPage > totalPages && totalPages > 0) {
                        currentPage = totalPages;
                    }
                    displayPage(currentPage);
                    
                    alert(`Successfully deleted ${count} violation record(s).`);
                } else {
                    alert('Error deleting records: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while deleting records.');
            });
        }
    });
}

// ============================================================================
// QR CODE GENERATION
// ============================================================================

document.addEventListener('DOMContentLoaded', function() {
    // IMPORTANT: Replace this with your actual InfinityFree domain when deploying
    var domain = window.location.origin;
    // Uncomment and use your actual InfinityFree domain:
    // var domain = "https://yourdomain.infinityfreeapp.com";
    // OR if you have a custom domain:
    // var domain = "https://www.yourdomain.com";
    
    // Build the full URL to this student's violation page
    // Note: Make sure studentId is defined in your PHP
    if (typeof studentId !== 'undefined') {
        var url = domain + "/adminstudentviolation.php?id=" + studentId;
        
        // Clear any existing QR code
        var qrcodeContainer = document.getElementById("qrcode");
        if (qrcodeContainer) {
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
                var qrImg = qrcodeContainer.querySelector('img');
                if (qrImg) {
                    qrImg.style.width = '200px';
                    qrImg.style.height = '200px';
                    qrImg.style.maxWidth = '200px';
                    qrImg.style.maxHeight = '200px';
                }
            }, 100);
        }
    }
});

// ============================================================================
// MESSAGE MODAL FUNCTIONS
// ============================================================================

/**
 * Opens the message/report modal
 * Called when "Send Message" button is clicked
 * UPDATED: Now opens modal instead of redirecting to send_message.php
 */
function sendMessage() {
    document.getElementById('messageModal').classList.add('active');
}

/**
 * Closes the message modal and resets the form
 * Called when close button is clicked or after successful submission
 */
function closeMessageModal() {
    document.getElementById('messageModal').classList.remove('active');
    document.getElementById('frmMessage').reset();
}

/**
 * Sends SMS to guardian and creates a report
 * Validates form data, submits via AJAX to adminstudent-message.php
 */
function sendSms() {
    // Get form values
    const reporttype = document.getElementById('reporttype').value;
    const reportdate = document.getElementById('reportdate').value;
    const reportcomment = document.getElementById('reportcomment').value;
    
    // Validate required fields
    if (!reporttype || !reportdate) {
        alert('Please fill in all required fields (Report Type and Date)');
        return;
    }
    
    // Prepare form data for submission
    const formData = new FormData(document.getElementById('frmMessage'));
    
    // Send AJAX request to backend
    fetch('adminstudent-message.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        alert('Successfully Reported');
        closeMessageModal();
        window.location.reload(); // Reload to show updated data
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while sending the message. Please try again.');
    });
}

// ============================================================================
// MODAL EVENT LISTENERS
// ============================================================================

/**
 * Close modal when clicking outside the modal content
 */
document.addEventListener('DOMContentLoaded', function() {
    const messageModal = document.getElementById('messageModal');
    
    if (messageModal) {
        messageModal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeMessageModal();
            }
        });
    }
});

/**
 * Close modal when pressing Escape key
 */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('messageModal');
        if (modal && modal.classList.contains('active')) {
            closeMessageModal();
        }
    }
});

// ============================================================================
// ACTION FUNCTIONS
// ============================================================================

/**
 * Generates a report for the student
 * Opens report in new window
 */
function generateReport() {
    if (typeof studentId !== 'undefined') {
        window.open(`generate_report.php?student_id=${studentId}`, '_blank');
    }
}

/**
 * Opens form to add a new violation record
 */
function addNewRecord() {
    if (typeof studentId !== 'undefined') {
        window.location.href = `add_violation.php?student_id=${studentId}`;
    }
}

/**
 * Changes the status of a violation record
 * @param {number} id - The ID of the violation record
 */
function changeStatus(id) {
    const newStatus = prompt('Enter new status (Pending, Resolved, Dismissed):');
    if (newStatus && ['Pending', 'Resolved', 'Dismissed'].includes(newStatus)) {
        fetch('update_violation_status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                id: id, 
                status: newStatus 
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-violation-id="${id}"]`);
                const statusBadge = row.querySelector('.status-badge');
                statusBadge.textContent = newStatus;
                statusBadge.className = 'status-badge ' + newStatus.toLowerCase();
                alert('Status updated successfully!');
            } else {
                alert('Error updating status: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating status.');
        });
    } else if (newStatus) {
        alert('Invalid status. Please enter: Pending, Resolved, or Dismissed');
    }
}

/**
 * Removes a violation record from the database
 * @param {number} id - The ID of the violation record to remove
 */
function removeViolation(id) {
    if (confirm('Are you sure you want to remove this violation record?')) {
        fetch('delete_violations.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ ids: [id] })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const row = document.querySelector(`tr[data-violation-id="${id}"]`);
                const index = allViolations.indexOf(row);
                if (index > -1) {
                    allViolations.splice(index, 1);
                }
                row.remove();
                
                // Recalculate pagination
                const filteredViolations = getFilteredViolations();
                const totalPages = Math.ceil(filteredViolations.length / entriesPerPage);
                if (currentPage > totalPages && totalPages > 0) {
                    currentPage = totalPages;
                }
                displayPage(currentPage);
                
                alert('Violation record removed successfully.');
            } else {
                alert('Error removing record: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while removing the record.');
        });
    }
}

/**
 * Views document proof/evidence for a violation
 * @param {number} id - The ID of the violation record
 */
function viewDocProof(id) {
    window.open(`view_proof.php?violation_id=${id}`, '_blank', 'width=800,height=600');
}