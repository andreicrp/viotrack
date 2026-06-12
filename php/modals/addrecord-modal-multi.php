<?php
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

// Fetch all students
$studentQuery = "SELECT id, fname, mname, lname, lrn, grade, section, image FROM student ORDER BY fname, lname";
$studentResult = $conn->query($studentQuery);
$students = [];

if ($studentResult) {
    while ($row = $studentResult->fetch_assoc()) {
        $fullName = trim($row['fname'] . ' ' . ($row['mname'] ? $row['mname'] . ' ' : '') . $row['lname']);
        $students[] = [
            'id' => $row['id'],
            'name' => $fullName,
            'lrn' => $row['lrn'],
            'grade' => $row['grade'],
            'section' => $row['section']
        ];
    }
}
?>

<!-- Add Record Modal -->
<div class="addrecord-modal-overlay" id="addRecordModal">
    <div class="addrecord-modal">
        <div class="addrecord-modal-header">
            <h2>Add Record</h2>
            <button class="addrecord-modal-close" onclick="closeAddRecordModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="addrecord-modal-body">
            <form method="POST" id="addRecordForm" onsubmit="submitAddRecordForm(event)">
                <!-- Student Selection -->
                <div class="form-section">
                    <label class="form-label">Student <span class="required">*</span></label>
                    <div class="student-dropdown-wrapper">
                        <div class="student-dropdown-trigger" onclick="toggleStudentDropdown(event)">
                            <span id="selectedStudentsDisplay">Select students...</span>
                            <i class="fas fa-chevron-down"></i>
                        </div>
                        
                        <div class="student-dropdown-menu" id="studentDropdownMenu">
                            <div class="student-search">
                                <input type="text" id="studentSearchInput" placeholder="Search students..." oninput="filterStudents(this.value)">
                            </div>
                            
                            <div class="student-list">
                                <?php foreach ($students as $student): ?>
                                    <label class="student-checkbox-item">
                                        <input type="checkbox" 
                                               class="student-checkbox" 
                                               name="students[]" 
                                               value="<?php echo $student['id']; ?>"
                                               data-name="<?php echo htmlspecialchars($student['name']); ?>"
                                               onchange="updateStudentDisplay()">
                                        <span><?php echo htmlspecialchars($student['name']); ?> (<?php echo $student['lrn']; ?>)</span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Violation Selection -->
                <div class="form-section">
                    <label class="form-label">Violations <span class="required">*</span></label>
                    <div class="violation-dropdown-wrapper">
                        <div class="violation-dropdown-trigger" onclick="toggleViolationDropdown(event)">
                            <span id="selectedViolationsDisplay">Select violations...</span>
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
                                    <div class="violation-list">
                                        <?php foreach ($minorViolations as $violation): ?>
                                            <label class="violation-checkbox-item">
                                                <input type="checkbox" 
                                                       class="violation-checkbox" 
                                                       name="violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
                                                       onchange="updateViolationDisplay()">
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
                                                       class="violation-checkbox" 
                                                       name="violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
                                                       onchange="updateViolationDisplay()">
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
                                                       class="violation-checkbox" 
                                                       name="violations[]" 
                                                       value="<?php echo $violation['id']; ?>"
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
            <button type="submit" class="addrecord-modal-btn btn-add-record" id="addRecordBtn" form="addRecordForm" disabled>
                Add
            </button>
            <button type="button" class="addrecord-modal-btn btn-cancel-add-record" onclick="closeAddRecordModal()">
                Close
            </button>
        </div>
    </div>
</div>

<script>
// ============ ADD RECORD MODAL FUNCTIONS ============
function openAddRecordModal() {
    const modal = document.getElementById('addRecordModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
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

function resetAddRecordForm() {
    const form = document.getElementById('addRecordForm');
    if (form) form.reset();
    
    document.querySelectorAll('.student-checkbox, .violation-checkbox').forEach(cb => cb.checked = false);
    updateStudentDisplay();
    updateViolationDisplay();
    
    document.getElementById('studentDropdownMenu').classList.remove('open');
    document.getElementById('violationDropdownMenu').classList.remove('open');
}

function toggleStudentDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('studentDropdownMenu');
    const violationDropdown = document.getElementById('violationDropdownMenu');
    
    // Close violation dropdown if open
    if (violationDropdown) violationDropdown.classList.remove('open');
    
    if (dropdown) {
        dropdown.classList.toggle('open');
    }
}

function toggleViolationDropdown(event) {
    event.stopPropagation();
    const dropdown = document.getElementById('violationDropdownMenu');
    const studentDropdown = document.getElementById('studentDropdownMenu');
    
    // Close student dropdown if open
    if (studentDropdown) studentDropdown.classList.remove('open');
    
    if (dropdown) {
        dropdown.classList.toggle('open');
    }
}

function filterStudents(searchTerm) {
    const items = document.querySelectorAll('.student-checkbox-item');
    const term = searchTerm.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

function filterViolations(searchTerm) {
    const items = document.querySelectorAll('.violation-checkbox-item');
    const term = searchTerm.toLowerCase();
    
    items.forEach(item => {
        const text = item.textContent.toLowerCase();
        item.style.display = text.includes(term) ? 'flex' : 'none';
    });
}

function updateStudentDisplay() {
    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
    const display = document.getElementById('selectedStudentsDisplay');
    
    if (checkedBoxes.length === 0) {
        display.textContent = 'Select students...';
    } else if (checkedBoxes.length === 1) {
        display.textContent = checkedBoxes[0].getAttribute('data-name');
    } else {
        display.textContent = checkedBoxes[0].getAttribute('data-name') + ` + ${checkedBoxes.length - 1} more`;
    }
    
    checkFormValidity();
}

function updateViolationDisplay() {
    const checkedBoxes = document.querySelectorAll('.violation-checkbox:checked');
    const display = document.getElementById('selectedViolationsDisplay');
    
    if (checkedBoxes.length === 0) {
        display.textContent = 'Select violations...';
    } else if (checkedBoxes.length === 1) {
        display.textContent = checkedBoxes[0].parentElement.querySelector('span').textContent;
    } else {
        display.textContent = `${checkedBoxes.length} violations selected`;
    }
    
    checkFormValidity();
}

function checkFormValidity() {
    const studentsSelected = document.querySelectorAll('.student-checkbox:checked').length > 0;
    const violationsSelected = document.querySelectorAll('.violation-checkbox:checked').length > 0;
    
    const btn = document.getElementById('addRecordBtn');
    if (btn) {
        btn.disabled = !(studentsSelected && violationsSelected);
    }
}

function submitAddRecordForm(event) {
    event.preventDefault();
    
    const selectedStudents = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
    const selectedViolations = Array.from(document.querySelectorAll('.violation-checkbox:checked')).map(cb => cb.value);
    
    if (selectedStudents.length === 0 || selectedViolations.length === 0) {
        alert('Please select at least one student and one violation');
        return;
    }
    
    const payload = {
        students: selectedStudents,
        violations: selectedViolations
    };
    
    fetch('php/add_violation_record.php', {
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
        let latitude = "14.6124466";
        let longitude = "120.9879835";
        let accuracy = 0;
        
        if (typeof geoHandler !== 'undefined') {
            geoHandler.getLocation().then(location => {
                latitude = location.latitude || "14.6124466";
                longitude = location.longitude || "120.9879835";
                accuracy = location.accuracy || 0;
                
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
                
                selectedStudents.forEach(sid => {
                    selectedViolations.forEach(vid => {
                        records.push({
                            sid: sid,
                            vid: vid,
                            lat: String(latitude),
                            lng: String(longitude),
                            accuracy: accuracy,
                            date: dateTime
                        });
                    });
                });
                
                records.forEach(r => {
                    let pending = JSON.parse(localStorage.getItem("pendingViolationRecords") || "[]");
                    pending.push(r);
                    localStorage.setItem("pendingViolationRecords", JSON.stringify(pending));
                });
                
                console.log('Saved ' + records.length + ' records with location:', {lat: latitude, lng: longitude, accuracy: accuracy});
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
            
            selectedStudents.forEach(sid => {
                selectedViolations.forEach(vid => {
                    records.push({
                        sid: sid,
                        vid: vid,
                        lat: latitude,
                        lng: longitude,
                        accuracy: accuracy,
                        date: dateTime
                    });
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

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    const studentDropdown = document.getElementById('studentDropdownMenu');
    const studentTrigger = document.querySelector('.student-dropdown-trigger');
    const violationDropdown = document.getElementById('violationDropdownMenu');
    const violationTrigger = document.querySelector('.violation-dropdown-trigger');
    
    if (studentDropdown && studentTrigger && !studentTrigger.contains(e.target) && !studentDropdown.contains(e.target)) {
        studentDropdown.classList.remove('open');
    }
    
    if (violationDropdown && violationTrigger && !violationTrigger.contains(e.target) && !violationDropdown.contains(e.target)) {
        violationDropdown.classList.remove('open');
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('addRecordModal')?.classList.contains('show')) {
        closeAddRecordModal();
    }
});
</script>
