<?php
// addbulkviolations-modal.php
// Specialized modal for adding bulk violations specific to a student's grade/section

// Fetch violation data for the modal
$minorQuery = "SELECT id, title FROM violation WHERE type = 'minor' ORDER BY title";
$seriousQuery = "SELECT id, title FROM violation WHERE type = 'serious' ORDER BY title";
$majorQuery = "SELECT id, title FROM violation WHERE type = 'major' ORDER BY title";

$minorResult = $conn->query($minorQuery);
$seriousResult = $conn->query($seriousQuery);
$majorResult = $conn->query($majorQuery);

$minorViolations = [];
$seriousViolations = [];
$majorViolations = [];

if ($minorResult) {
    while ($row = $minorResult->fetch_assoc()) {
        $minorViolations[] = $row;
    }
}

if ($seriousResult) {
    while ($row = $seriousResult->fetch_assoc()) {
        $seriousViolations[] = $row;
    }
}

if ($majorResult) {
    while ($row = $majorResult->fetch_assoc()) {
        $majorViolations[] = $row;
    }
}
?>

<!-- Add Bulk Violations Modal -->
<div class="addrecord-modal-overlay" id="addBulkViolationsModal">
    <div class="addrecord-modal">
        <div class="addrecord-modal-header">
            <h2>Add Bulk Violations</h2>
            <button class="addrecord-modal-close" onclick="closeAddBulkViolationsModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="addrecord-modal-body">
            <form method="POST" id="addBulkViolationsForm" onsubmit="submitAddBulkViolationsForm(event)">
                
                <!-- Grade & Section Info -->
                <div class="form-section">
                    <label class="form-label" style="font-weight: 600; color: #1f2937; margin-bottom: 8px;">
                        <i class="fas fa-info-circle" style="color: #3b82f6; margin-right: 6px;"></i>
                        Location Information
                    </label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px;">
                        <div style="padding: 12px 14px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                            <p style="margin: 0; font-size: 12px; color: #1e40af; font-weight: 600; text-transform: uppercase;">Grade Level</p>
                            <p style="margin: 4px 0 0 0; font-size: 16px; font-weight: 700; color: #1f2937;" id="bulkGradeDisplay">-</p>
                        </div>
                        <div style="padding: 12px 14px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                            <p style="margin: 0; font-size: 12px; color: #1e40af; font-weight: 600; text-transform: uppercase;">Section</p>
                            <p style="margin: 4px 0 0 0; font-size: 16px; font-weight: 700; color: #1f2937;" id="bulkSectionDisplay">-</p>
                        </div>
                    </div>
                </div>

                <!-- Student Selection with Checkboxes -->
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-users" style="color: #8b5cf6; margin-right: 6px;"></i>
                        Students <span class="required">*</span>
                    </label>
                    <div style="max-height: 280px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 8px; background: #f9fafb;">
                        <div id="bulkStudentsCheckboxList" style="padding: 8px 0;">
                            <!-- Students will be populated here by JavaScript -->
                        </div>
                    </div>
                    <p style="margin: 6px 0 0 0; font-size: 12px; color: #6b7280;">
                        <i class="fas fa-check-square" style="margin-right: 4px;"></i><span id="bulkSelectedStudentsCount">0 selected</span>
                    </p>
                </div>

                <!-- Violations Selection -->
                <div class="form-section">
                    <label class="form-label">
                        <i class="fas fa-exclamation-triangle" style="color: #ef4444; margin-right: 6px;"></i>
                        Violations <span class="required">*</span>
                    </label>
                    <div class="violation-dropdown-wrapper">
                        <div class="violation-dropdown-trigger" onclick="toggleBulkViolationDropdown(event)">
                            <span id="selectedBulkViolationsDisplay">Select violations...</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        
                        <div class="violation-dropdown-menu" id="bulkViolationDropdownMenu">
                            <div class="violation-search">
                                <input type="text" id="bulkViolationSearchInput" placeholder="Search violations..." oninput="filterBulkViolations(this.value)">
                            </div>
                            
                            <div class="violation-categories">
                                <!-- Minor Offenses -->
                                <div class="violation-category">
                                    <div class="category-header" style="color: #10b981;">Minor Offenses</div>
                                    <div class="violation-list">
                                        <?php foreach ($minorViolations as $violation): ?>
                                            <label class="violation-checkbox-item">
                                                <input type="checkbox" 
                                                       class="bulk-violation-checkbox" 
                                                       name="bulk_violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
                                                       data-type="minor"
                                                       onchange="updateBulkViolationDisplay()">
                                                <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Serious Offenses -->
                                <div class="violation-category">
                                    <div class="category-header" style="color: #f59e0b;">Serious Offenses</div>
                                    <div class="violation-list">
                                        <?php foreach ($seriousViolations as $violation): ?>
                                            <label class="violation-checkbox-item">
                                                <input type="checkbox" 
                                                       class="bulk-violation-checkbox" 
                                                       name="bulk_violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
                                                       data-type="serious"
                                                       onchange="updateBulkViolationDisplay()">
                                                <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Major Offenses -->
                                <div class="violation-category">
                                    <div class="category-header" style="color: #ef4444;">Major Offenses</div>
                                    <div class="violation-list">
                                        <?php foreach ($majorViolations as $violation): ?>
                                            <label class="violation-checkbox-item">
                                                <input type="checkbox" 
                                                       class="bulk-violation-checkbox" 
                                                       name="bulk_violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
                                                       data-type="major"
                                                       onchange="updateBulkViolationDisplay()">
                                                <span><?php echo htmlspecialchars($violation['title']); ?></span>
                                            </label>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Preview -->
                <div style="margin-top: 20px; padding: 16px; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 8px;">
                    <p style="margin: 0 0 12px 0; font-size: 14px; font-weight: 600; color: #1e40af;">
                        <i class="fas fa-info-circle" style="margin-right: 6px;"></i>Summary
                    </p>
                    <p style="margin: 0; font-size: 13px; color: #1f2937;">
                        <span id="bulkSummaryText">Select students and violations to create records</span>
                    </p>
                </div>
            </form>
        </div>
        
        <div class="addrecord-modal-footer">
            <button type="submit" class="addrecord-modal-btn btn-add-record" id="addBulkViolationsBtn" form="addBulkViolationsForm" disabled>
                <i class="fas fa-check" style="margin-right: 4px;"></i>Add Records
            </button>
            <button type="button" class="addrecord-modal-btn btn-cancel-add-record" onclick="closeAddBulkViolationsModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// ============ ADD BULK VIOLATIONS MODAL FUNCTIONS ============

function openAddBulkViolationModal() {
    // Get all visible students from the filtered list
    const visibleStudents = [];
    document.querySelectorAll('.student-card').forEach(card => {
        if (card.style.display !== 'none') {
            const studentId = parseInt(card.getAttribute('data-id'));
            const name = card.querySelector('h4').textContent;
            const lrn = card.querySelector('strong').textContent;
            
            visibleStudents.push({
                id: studentId,
                name: name,
                lrn: lrn
            });
        }
    });
    
    // Open the modal
    const modal = document.getElementById('addBulkViolationsModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Populate grade and section from page context
        const infoSection = document.querySelector('[data-grade]');
        if (infoSection) {
            const grade = infoSection.getAttribute('data-grade');
            const section = infoSection.getAttribute('data-section');
            document.getElementById('bulkGradeDisplay').textContent = grade;
            document.getElementById('bulkSectionDisplay').textContent = section;
        }
        
        // Populate students list
        updateBulkStudentsList(visibleStudents);
        
        // Initialize form state
        document.querySelectorAll('.bulk-student-checkbox').forEach(cb => cb.checked = false);
        document.querySelectorAll('.bulk-violation-checkbox').forEach(cb => cb.checked = false);
        checkBulkFormValidity();
        updateBulkSummary();
    }
}

function closeAddBulkViolationsModal() {
    const modal = document.getElementById('addBulkViolationsModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        resetAddBulkViolationsForm();
    }
}

function resetAddBulkViolationsForm() {
    const form = document.getElementById('addBulkViolationsForm');
    if (form) form.reset();
    
    document.querySelectorAll('.bulk-student-checkbox, .bulk-violation-checkbox').forEach(cb => cb.checked = false);
    updateBulkViolationDisplay();
    updateBulkStudentSelection();
    
    document.getElementById('bulkViolationDropdownMenu').classList.remove('open');
    document.getElementById('bulkSummaryText').textContent = 'Select students and violations to create records';
}

function toggleBulkViolationDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('bulkViolationDropdownMenu');
    if (dropdown) {
        dropdown.classList.toggle('open');
    }
}

function filterBulkViolations(searchTerm) {
    const items = document.querySelectorAll('#bulkViolationDropdownMenu .violation-checkbox-item');
    const term = searchTerm.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

function updateBulkViolationDisplay() {
    const checkedBoxes = document.querySelectorAll('.bulk-violation-checkbox:checked');
    const display = document.getElementById('selectedBulkViolationsDisplay');
    
    if (checkedBoxes.length === 0) {
        display.textContent = 'Select violations...';
    } else if (checkedBoxes.length === 1) {
        display.textContent = checkedBoxes[0].parentElement.querySelector('span').textContent;
    } else {
        display.textContent = `${checkedBoxes.length} violations selected`;
    }
    
    checkBulkFormValidity();
    updateBulkSummary();
}

function updateBulkStudentsList(selectedStudents) {
    const container = document.getElementById('bulkStudentsCheckboxList');
    
    if (selectedStudents.length === 0) {
        container.innerHTML = '<p style="margin: 20px; color: #9ca3af; text-align: center; font-size: 14px;"><i class="fas fa-inbox" style="margin-right: 6px;"></i>No students in this section</p>';
    } else {
        let html = '';
        selectedStudents.forEach((student, index) => {
            html += `
                <label class="student-checkbox-item" style="display: flex; align-items: center; gap: 10px; padding: 10px 16px; cursor: pointer; transition: background-color 0.2s; user-select: none; border-bottom: 1px solid #e5e7eb;">
                    <input type="checkbox" class="bulk-student-checkbox" value="${student.id}" data-name="${student.name}" style="width: 18px; height: 18px; cursor: pointer; accent-color: #8b5cf6; flex-shrink: 0;" onchange="updateBulkStudentSelection()">
                    <span style="flex: 1; min-width: 0;">
                        <div style="font-size: 14px; font-weight: 600; color: #1f2937;">${student.name}</div>
                        <div style="font-size: 12px; color: #6b7280;">ID: ${student.lrn}</div>
                    </span>
                </label>
            `;
        });
        container.innerHTML = html;
        
        // Add hover effect
        document.querySelectorAll('#bulkStudentsCheckboxList .student-checkbox-item').forEach(item => {
            item.addEventListener('mouseover', function() {
                this.style.backgroundColor = '#f3f4f6';
            });
            item.addEventListener('mouseout', function() {
                this.style.backgroundColor = 'transparent';
            });
        });
    }
    
    updateBulkStudentSelection();
}

function updateBulkStudentSelection() {
    const selectedCount = document.querySelectorAll('.bulk-student-checkbox:checked').length;
    const countDisplay = document.getElementById('bulkSelectedStudentsCount');
    
    if (countDisplay) {
        if (selectedCount === 0) {
            countDisplay.textContent = '0 selected';
        } else if (selectedCount === 1) {
            countDisplay.textContent = '1 student selected';
        } else {
            countDisplay.textContent = selectedCount + ' students selected';
        }
    }
    
    checkBulkFormValidity();
    updateBulkSummary();
}

function checkBulkFormValidity() {
    const studentsSelected = document.querySelectorAll('.bulk-student-checkbox:checked').length > 0;
    const violationsSelected = document.querySelectorAll('.bulk-violation-checkbox:checked').length > 0;
    
    const btn = document.getElementById('addBulkViolationsBtn');
    if (btn) {
        btn.disabled = !(studentsSelected && violationsSelected);
    }
}

function updateBulkSummary() {
    const selectedStudents = document.querySelectorAll('.bulk-student-checkbox:checked').length;
    const selectedViolations = document.querySelectorAll('.bulk-violation-checkbox:checked').length;
    const totalRecords = selectedStudents * selectedViolations;
    
    let summaryText = 'Select students and violations to create records';
    
    if (selectedStudents > 0 && selectedViolations > 0) {
        summaryText = `Creating <strong>${totalRecords}</strong> violation record${totalRecords !== 1 ? 's' : ''} 
                      (<strong>${selectedStudents}</strong> student${selectedStudents !== 1 ? 's' : ''} × 
                      <strong>${selectedViolations}</strong> violation${selectedViolations !== 1 ? 's' : ''})`;
    } else if (selectedStudents > 0) {
        summaryText = `<strong>${selectedStudents}</strong> student${selectedStudents !== 1 ? 's' : ''} selected. Please select violations.`;
    } else if (selectedViolations > 0) {
        summaryText = `<strong>${selectedViolations}</strong> violation${selectedViolations !== 1 ? 's' : ''} selected. Please select students.`;
    }
    
    document.getElementById('bulkSummaryText').innerHTML = summaryText;
}

function submitAddBulkViolationsForm(event) {
    event.preventDefault();
    
    const selectedStudents = Array.from(document.querySelectorAll('.bulk-student-checkbox:checked')).map(cb => ({
        id: cb.value,
        name: cb.getAttribute('data-name')
    }));
    const selectedViolations = Array.from(document.querySelectorAll('.bulk-violation-checkbox:checked')).map(cb => ({
        id: cb.value,
        type: cb.getAttribute('data-type')
    }));
    
    if (selectedStudents.length === 0 || selectedViolations.length === 0) {
        alert('Please select at least one student and one violation');
        return;
    }
    
    // Disable button while submitting
    const btn = document.getElementById('addBulkViolationsBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 4px;"></i>Getting location...';
    
    // Get geolocation
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const latitude = position.coords.latitude;
                const longitude = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                submitBulkViolationsWithLocation(selectedStudents, selectedViolations, latitude, longitude, accuracy, btn);
            },
            function(error) {
                console.warn('Geolocation error:', error);
                // Fallback to default location
                submitBulkViolationsWithLocation(selectedStudents, selectedViolations, '14.6124466', '120.9879835', 0, btn);
            },
            { timeout: 5000 }
        );
    } else {
        // Geolocation not supported, use fallback
        submitBulkViolationsWithLocation(selectedStudents, selectedViolations, '14.6124466', '120.9879835', 0, btn);
    }
}

function submitBulkViolationsWithLocation(selectedStudents, selectedViolations, latitude, longitude, accuracy, btn) {
    // Get grade and section from modal display
    const grade = document.getElementById('bulkGradeDisplay').textContent;
    const section = document.getElementById('bulkSectionDisplay').textContent;
    
    // Create records: each student × each violation with location
    const records = [];
    selectedStudents.forEach(student => {
        selectedViolations.forEach(violation => {
            records.push({
                student_id: student.id,
                violation_id: violation.id,
                violation_type: violation.type,
                violation_title: '',
                latitude: latitude,
                longitude: longitude,
                accuracy: accuracy
            });
        });
    });
    
    const payload = {
        records: records,
        grade: grade,
        section: section
    };
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 4px;"></i>Adding...';
    
    fetch('php/save_bulk_violations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
    })
    .then(response => {
        // Check if response is ok
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            alert(`✓ Successfully added ${data.success_count} violation record${data.success_count !== 1 ? 's' : ''}!`);
            closeAddBulkViolationsModal();
            location.reload();
        } else {
            let errorMsg = data.message || 'Unknown error';
            if (data.failed_records && data.failed_records.length > 0) {
                errorMsg += '\n\nFailed records:\n';
                data.failed_records.forEach(failed => {
                    errorMsg += `- Student ${failed.student_id}: ${failed.error}\n`;
                });
            }
            alert('Error: ' + errorMsg);
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-check" style="margin-right: 4px;"></i>Add Records';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Network error: ' + error.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-check" style="margin-right: 4px;"></i>Add Records';
    });
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    const violationDropdown = document.getElementById('bulkViolationDropdownMenu');
    const violationTrigger = document.querySelector('#addBulkViolationsModal .violation-dropdown-trigger');
    
    if (violationDropdown && violationTrigger && !violationTrigger.contains(e.target) && !violationDropdown.contains(e.target)) {
        violationDropdown.classList.remove('open');
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('addBulkViolationsModal')?.classList.contains('show')) {
        closeAddBulkViolationsModal();
    }
});
</script>
