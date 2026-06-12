<!-- addrecord-modal.php -->
<?php
// Database connection
require_once('../../connect.php');

try {
    // Get all students
    $studentsQuery = $conn->query("
        SELECT id, fname, lname, mname, lrn, grade, section, academicyear, image 
        FROM student 
        ORDER BY lname, fname
    ");
    
    if (!$studentsQuery) {
        throw new Exception("Students query error: " . $conn->error);
    }
    
    $students = [];
    while ($row = $studentsQuery->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get all violations grouped by type
    $violationsQuery = $conn->query("
        SELECT id, title, description, type 
        FROM violation 
        ORDER BY type, title
    ");
    
    if (!$violationsQuery) {
        throw new Exception("Violations query error: " . $conn->error);
    }
    
    $allViolations = [];
    while ($row = $violationsQuery->fetch_assoc()) {
        $allViolations[] = $row;
    }
    
    // Group violations by type
    $minorViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'minor'; });
    $seriousViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'serious'; });
    $majorViolations = array_filter($allViolations, function($v) { return strtolower($v['type']) == 'major'; });
    
} catch(Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_record'])) {
    try {
        $pdo->beginTransaction();
        
        $studentIds = isset($_POST['student_ids']) ? $_POST['student_ids'] : [];
        $aid = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : '1';
        $currentDate = date('Y-m-d H:i:s');
        
        // Collect all selected violations
        $selectedViolations = [];
        
        if (isset($_POST['minor_offenses']) && is_array($_POST['minor_offenses'])) {
            $selectedViolations = array_merge($selectedViolations, array_filter($_POST['minor_offenses']));
        }
        if (isset($_POST['serious_offenses']) && is_array($_POST['serious_offenses'])) {
            $selectedViolations = array_merge($selectedViolations, array_filter($_POST['serious_offenses']));
        }
        if (isset($_POST['major_offenses']) && is_array($_POST['major_offenses'])) {
            $selectedViolations = array_merge($selectedViolations, array_filter($_POST['major_offenses']));
        }
        
        if (empty($studentIds)) {
            throw new Exception('Please select at least one student');
        }
        
        if (empty($selectedViolations)) {
            throw new Exception('Please select at least one violation');
        }
        
        $totalRecords = 0;
        
        // Get location from form if captured
        $latitude = isset($_POST['record_latitude']) && $_POST['record_latitude'] !== '' ? floatval($_POST['record_latitude']) : '';
        $longitude = isset($_POST['record_longitude']) && $_POST['record_longitude'] !== '' ? floatval($_POST['record_longitude']) : '';
        $accuracy = isset($_POST['record_accuracy']) && $_POST['record_accuracy'] !== '' ? intval($_POST['record_accuracy']) : 0;
        $recordType = isset($_POST['record_type']) && $_POST['record_type'] !== '' ? $_POST['record_type'] : null;
        $recordProof = isset($_POST['record_proof']) && $_POST['record_proof'] !== '' ? $_POST['record_proof'] : null;
        
        // Insert records for each student and each violation
        $stmt = $pdo->prepare("
            INSERT INTO record (status, vid, date, sid, aid, type, lat, lng, proof, accuracy) 
            VALUES ('Pending', :vid, :date, :sid, :aid, :type, :lat, :lng, :proof, :accuracy)
        ");
        
        $activityStmt = $pdo->prepare("
            INSERT INTO activity (aid, description, date) 
            VALUES (:aid, 'Insert New Record', :date)
        ");
        
        foreach ($studentIds as $studentId) {
            foreach ($selectedViolations as $violationId) {
                $stmt->execute([
                    ':vid' => $violationId,
                    ':date' => $currentDate,
                    ':sid' => $studentId,
                    ':aid' => $aid,
                    ':type' => $recordType,
                    ':lat' => $latitude,
                    ':lng' => $longitude,
                    ':proof' => $recordProof,
                    ':accuracy' => $accuracy
                ]);
                
                $activityStmt->execute([
                    ':aid' => $aid,
                    ':date' => $currentDate
                ]);
                
                $totalRecords++;
            }
        }
        
        $pdo->commit();
        
        echo "<script>
            alert('Successfully added " . $totalRecords . " record(s)!');
            window.location.href = window.location.pathname;
        </script>";
        exit();
        
    } catch(Exception $e) {
        $pdo->rollBack();
        echo "<script>alert('Error: " . addslashes($e->getMessage()) . "');</script>";
    }
}
?>

<link rel="stylesheet" href="css/addrecord-modal.css">

<!-- Add Record Modal -->
<div class="addrecord-modal-overlay" id="addRecordModal">
    <div class="addrecord-modal">
        <div class="addrecord-modal-header">
            <h2>Add Record</h2>
            <button class="addrecord-modal-close" onclick="closeAddRecordModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <form method="POST" id="addRecordForm">
            <div class="addrecord-modal-body">
                <!-- Student Selection -->
                <div class="addrecord-form-group">
                    <label class="addrecord-form-label">
                        Student <span class="addrecord-required">*</span>
                    </label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="studentSelectDisplay" onclick="toggleDropdown('student')">
                            <span class="student-placeholder">Select students...</span>
                        </div>
                        <div class="student-dropdown" id="studentDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="studentSearchInput" placeholder="Search students..." onclick="event.stopPropagation()" oninput="searchItems('student', this.value)">
                            </div>
                            <div class="student-options no-scroll" id="studentOptions">
                                <?php foreach ($students as $index => $student): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection('student', <?php echo $index; ?>, event)">
                                        <input type="checkbox" 
                                               class="student-option-checkbox" 
                                               name="student_ids[]" 
                                               value="<?php echo $student['id']; ?>"
                                               id="student-<?php echo $index; ?>" 
                                               data-index="<?php echo $index; ?>"
                                               onclick="toggleSelection('student', <?php echo $index; ?>, event)">
                                        <label for="student-<?php echo $index; ?>" class="student-option-label">
                                            <?php echo htmlspecialchars($student['fname'] . ' ' . $student['mname'] . ' ' . $student['lname']); ?> 
                                            (<?php echo htmlspecialchars($student['lrn']); ?>)
                                        </label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="studentSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Minor Offenses -->
                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #10b981; font-weight: 600;">
                        Minor Offenses
                    </label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="minorSelectDisplay" onclick="toggleDropdown('minor')">
                            <span class="student-placeholder">Select minor offenses...</span>
                        </div>
                        <div class="student-dropdown" id="minorDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="minorSearchInput" placeholder="Search minor offenses..." onclick="event.stopPropagation()" oninput="searchItems('minor', this.value)">
                            </div>
                            <div class="student-options no-scroll" id="minorOptions">
                                <?php foreach ($minorViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection('minor', <?php echo $index; ?>, event)">
                                        <input type="checkbox" 
                                               class="student-option-checkbox" 
                                               name="minor_offenses[]" 
                                               value="<?php echo $violation['id']; ?>"
                                               id="minor-<?php echo $index; ?>" 
                                               data-index="<?php echo $index; ?>"
                                               onclick="toggleSelection('minor', <?php echo $index; ?>, event)">
                                        <label for="minor-<?php echo $index; ?>" class="student-option-label">
                                            <?php echo htmlspecialchars($violation['title']); ?>
                                        </label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="minorSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Serious Offenses -->
                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #f59e0b; font-weight: 600;">
                        Serious Offenses
                    </label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="seriousSelectDisplay" onclick="toggleDropdown('serious')">
                            <span class="student-placeholder">Select serious offenses...</span>
                        </div>
                        <div class="student-dropdown" id="seriousDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="seriousSearchInput" placeholder="Search serious offenses..." onclick="event.stopPropagation()" oninput="searchItems('serious', this.value)">
                            </div>
                            <div class="student-options no-scroll" id="seriousOptions">
                                <?php foreach ($seriousViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection('serious', <?php echo $index; ?>, event)">
                                        <input type="checkbox" 
                                               class="student-option-checkbox" 
                                               name="serious_offenses[]" 
                                               value="<?php echo $violation['id']; ?>"
                                               id="serious-<?php echo $index; ?>" 
                                               data-index="<?php echo $index; ?>"
                                               onclick="toggleSelection('serious', <?php echo $index; ?>, event)">
                                        <label for="serious-<?php echo $index; ?>" class="student-option-label">
                                            <?php echo htmlspecialchars($violation['title']); ?>
                                        </label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="seriousSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Major Offenses -->
                <div class="addrecord-form-group">
                    <label class="addrecord-form-label" style="color: #ef4444; font-weight: 600;">
                        Major Offenses
                    </label>
                    <div class="student-multiselect">
                        <div class="student-select-display" id="majorSelectDisplay" onclick="toggleDropdown('major')">
                            <span class="student-placeholder">Select major offenses...</span>
                        </div>
                        <div class="student-dropdown" id="majorDropdown">
                            <div class="student-dropdown-search">
                                <input type="text" id="majorSearchInput" placeholder="Search major offenses..." onclick="event.stopPropagation()" oninput="searchItems('major', this.value)">
                            </div>
                            <div class="student-options" id="majorOptions">
                                <?php foreach ($majorViolations as $index => $violation): ?>
                                    <div class="student-option" data-index="<?php echo $index; ?>" onclick="toggleSelection('major', <?php echo $index; ?>, event)">
                                        <input type="checkbox" 
                                               class="student-option-checkbox" 
                                               name="major_offenses[]" 
                                               value="<?php echo $violation['id']; ?>"
                                               id="major-<?php echo $index; ?>" 
                                               data-index="<?php echo $index; ?>"
                                               onclick="toggleSelection('major', <?php echo $index; ?>, event)">
                                        <label for="major-<?php echo $index; ?>" class="student-option-label">
                                            <?php echo htmlspecialchars($violation['title']); ?>
                                        </label>
                                        <i class="fas fa-check student-check-icon"></i>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="student-dropdown-footer">
                                <span class="selected-count" id="majorSelectedCount">Nothing selected</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Location Fields (hidden) -->
            <input type="hidden" id="recordLatitude" name="record_latitude" value="">
            <input type="hidden" id="recordLongitude" name="record_longitude" value="">
            <input type="hidden" id="recordAccuracy" name="record_accuracy" value="">
            <input type="hidden" id="recordType" name="record_type" value="">
            <input type="hidden" id="recordProof" name="record_proof" value="">
            
            <!-- Location Status Display -->
            <div class="addrecord-form-group" id="locationStatusGroup" style="display: none;">
                <div style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 6px; padding: 12px; text-align: center;">
                    <i class="fas fa-map-pin" style="color: #16a34a; margin-right: 8px;"></i>
                    <span id="locationStatusText" style="color: #16a34a; font-size: 13px; font-weight: 500;">
                        Location captured
                    </span>
                </div>
            </div>
            
            <div class="addrecord-modal-footer">
                <button type="submit" name="submit_record" class="addrecord-modal-btn btn-add-record" id="addRecordBtn" disabled>
                    Add
                </button>
                <button type="button" class="addrecord-modal-btn btn-cancel-add-record" onclick="closeAddRecordModal()">
                    Close
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Toggle dropdown
function toggleDropdown(type) {
    const dropdown = document.getElementById(`${type}Dropdown`);
    const display = document.getElementById(`${type}SelectDisplay`);
    
    // Close all other dropdowns
    ['student', 'minor', 'serious', 'major'].forEach(t => {
        if (t !== type) {
            const otherDropdown = document.getElementById(`${t}Dropdown`);
            const otherDisplay = document.getElementById(`${t}SelectDisplay`);
            if (otherDropdown) otherDropdown.classList.remove('show');
            if (otherDisplay) otherDisplay.classList.remove('active');
        }
    });
    
    if (dropdown.classList.contains('show')) {
        dropdown.classList.remove('show');
        display.classList.remove('active');
    } else {
        dropdown.classList.add('show');
        display.classList.add('active');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    ['student', 'minor', 'serious', 'major'].forEach(type => {
        const dropdown = document.getElementById(`${type}Dropdown`);
        const display = document.getElementById(`${type}SelectDisplay`);
        
        if (dropdown && display && !dropdown.contains(e.target) && !display.contains(e.target)) {
            dropdown.classList.remove('show');
            display.classList.remove('active');
        }
    });
});

// Toggle selection
function toggleSelection(type, index, event) {
    event.stopPropagation();
    
    const checkbox = document.getElementById(`${type}-${index}`);
    const option = document.querySelector(`#${type}Options .student-option[data-index="${index}"]`);
    
    checkbox.checked = !checkbox.checked;
    
    if (checkbox.checked) {
        option.classList.add('selected');
    } else {
        option.classList.remove('selected');
    }
    
    updateSelectedDisplay(type);
    checkFormValidity();
}

// Update selected display
function updateSelectedDisplay(type) {
    const display = document.getElementById(`${type}SelectDisplay`);
    const checkboxes = document.querySelectorAll(`#${type}Options .student-option-checkbox:checked`);
    const count = checkboxes.length;
    
    if (count === 0) {
        document.getElementById(`${type}SelectedCount`).textContent = 'Nothing selected';
        const placeholderText = type === 'student' ? 'Select students...' : `Select ${type} offenses...`;
        display.innerHTML = `<span class="student-placeholder">${placeholderText}</span>`;
    } else {
        document.getElementById(`${type}SelectedCount`).textContent = `${count} selected`;
        
        const names = Array.from(checkboxes).map(cb => {
            return cb.parentElement.querySelector('label').textContent.trim();
        }).join(', ');
        
        display.innerHTML = `<span class="student-selected-text">${names}</span>`;
    }
}

// Search items
function searchItems(type, searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    const options = document.querySelectorAll(`#${type}Options .student-option`);
    
    options.forEach(option => {
        const text = option.textContent.toLowerCase();
        option.style.display = text.includes(searchTerm) ? 'flex' : 'none';
    });
}

// Check form validity
function checkFormValidity() {
    const hasStudents = document.querySelectorAll('#studentOptions .student-option-checkbox:checked').length > 0;
    
    const minorSelected = document.querySelectorAll('#minorOptions .student-option-checkbox:checked').length > 0;
    const seriousSelected = document.querySelectorAll('#seriousOptions .student-option-checkbox:checked').length > 0;
    const majorSelected = document.querySelectorAll('#majorOptions .student-option-checkbox:checked').length > 0;
    
    const hasViolation = minorSelected || seriousSelected || majorSelected;
    const isValid = hasStudents && hasViolation;
    
    const addBtn = document.getElementById('addRecordBtn');
    if (addBtn) addBtn.disabled = !isValid;
}

// Get cookie by name
function getCookie(name) {
    const nameEQ = name + "=";
    const cookies = document.cookie.split(';');
    for (let i = 0; i < cookies.length; i++) {
        let cookie = cookies[i].trim();
        if (cookie.indexOf(nameEQ) === 0) {
            return decodeURIComponent(cookie.substring(nameEQ.length));
        }
    }
    return "";
}

// Open modal
function openAddRecordModal() {
    const modal = document.getElementById('addRecordModal');
    if (modal) {
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        // Try to get location from cookies (from QR scan)
        const cookieLat = getCookie('user_lat');
        const cookieLng = getCookie('user_lng');
        const cookieAccuracy = getCookie('user_accuracy');
        
        if (cookieLat && cookieLng) {
            // Use location from cookies (QR scan)
            console.log('Using location from QR scan cookies:', cookieLat, cookieLng);
            document.getElementById('recordLatitude').value = cookieLat;
            document.getElementById('recordLongitude').value = cookieLng;
            
            // Show alert about cookie location
            const accuracyText = cookieAccuracy && cookieAccuracy !== '0' ? ' (Accuracy: ' + cookieAccuracy + 'm)' : ' (Default Location)';
            alert('📍 Location Loaded\n\nLocation has been loaded from your QR scan' + accuracyText + '\n\nYou can now record the violation.');
        } else {
            // No cookies, ask user for permission
            if (confirm('📍 Location Permission Required\n\nThis system needs your device location to track where violations are recorded.\n\nPlease click OK to allow location access.')) {
                captureLocation();
            } else {
                alert('⚠️ Warning\n\nLocation permission was denied. The violation will be recorded with a default location.');
                // Use default location
                document.getElementById('recordLatitude').value = '14.6124466';
                document.getElementById('recordLongitude').value = '120.9879835';
            }
        }
    }
}

// Close modal
function closeAddRecordModal() {
    const modal = document.getElementById('addRecordModal');
    if (modal) {
        modal.classList.remove('show');
        document.body.style.overflow = '';
        resetAddRecordForm();
    }
}

// Reset form
function resetAddRecordForm() {
    const form = document.getElementById('addRecordForm');
    if (form) form.reset();
    
    ['student', 'minor', 'serious', 'major'].forEach(type => {
        document.querySelectorAll(`#${type}Options .student-option-checkbox`).forEach(cb => cb.checked = false);
        document.querySelectorAll(`#${type}Options .student-option`).forEach(opt => opt.classList.remove('selected'));
        updateSelectedDisplay(type);
    });
    
    checkFormValidity();
}

// Close modal with Escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('addRecordModal')?.classList.contains('show')) {
        closeAddRecordModal();
    }
});

// Initialize on load
document.addEventListener('DOMContentLoaded', function() {
    ['student', 'minor', 'serious', 'major'].forEach(type => {
        updateSelectedDisplay(type);
    });
    
    // Request geolocation when modal opens
    const addRecordForm = document.getElementById('addRecordForm');
    if (addRecordForm) {
        addRecordForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // CHECK OFFLINE MODE FIRST
            if (!navigator.onLine) {
                // Get form data
                const studentCheckboxes = document.querySelectorAll('#studentOptions .student-option-checkbox:checked');
                const violationCheckboxes = document.querySelectorAll('[id$="Options"] .student-option-checkbox:checked:not(#studentOptions .student-option-checkbox)');
                
                if (studentCheckboxes.length === 0 || violationCheckboxes.length === 0) {
                    alert('Please select at least one student and violation');
                    return;
                }
                
                // Get location from form
                const lat = document.getElementById('recordLatitude')?.value || '0';
                const lng = document.getElementById('recordLongitude')?.value || '0';
                
                // Create records array
                const records = [];
                studentCheckboxes.forEach(studentCheckbox => {
                    const studentId = studentCheckbox.id.split('-')[1];
                    violationCheckboxes.forEach(violationCheckbox => {
                        const violationId = violationCheckbox.id.split('-')[1];
                        records.push({
                            sid: studentId,
                            vid: violationId,
                            lat: lat,
                            lng: lng,
                            date: new Date().toISOString().split('T')[0],
                            status: 'Pending',
                            type: 'Violation'
                        });
                    });
                });
                
                // Save offline
                records.forEach(record => {
                    offlineManager.saveOffline(record);
                });
                
                console.log('✓ Saved ' + records.length + ' violation(s) offline');
                alert('✓ ' + records.length + ' violation(s) saved offline. Will sync when connected.');
                
                // Close modal and reload
                closeAddRecordModal();
                setTimeout(() => location.reload(), 1500);
                return; // IMPORTANT: Stop here, don't continue
            }
            
            // ONLINE MODE: Check if location is already captured
            const lat = document.getElementById('recordLatitude').value;
            const lng = document.getElementById('recordLongitude').value;
            
            if (lat && lng) {
                // Location already captured, submit form
                this.submit();
            } else {
                // Try to get location first
                captureLocationAndSubmit(this);
            }
        });
    }
    
    // Request location when modal opens
    setTimeout(function() {
        captureLocation();
    }, 500);
});

// Capture location and store in cookie
function captureLocationAndStore() {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                const accuracy = position.coords.accuracy;
                
                // Store location in cookies (expires in 30 minutes)
                const expiryTime = new Date();
                expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000)); // 30 minutes
                document.cookie = "user_lat=" + lat + "; expires=" + expiryTime.toUTCString() + "; path=/";
                document.cookie = "user_lng=" + lng + "; expires=" + expiryTime.toUTCString() + "; path=/";
                document.cookie = "user_accuracy=" + Math.round(accuracy) + "; expires=" + expiryTime.toUTCString() + "; path=/";
                
                // Also store in hidden fields for form submission
                document.getElementById('recordLatitude').value = lat;
                document.getElementById('recordLongitude').value = lng;
                
                // Show success message
                const statusGroup = document.getElementById('locationStatusGroup');
                const statusText = document.getElementById('locationStatusText');
                if (statusGroup && statusText) {
                    statusGroup.style.display = 'block';
                    statusText.innerHTML = '<i class="fas fa-map-pin" style="color: #16a34a; margin-right: 8px;"></i> Location captured (Accuracy: ' + Math.round(accuracy) + 'm)';
                }
                
                console.log('Location captured and stored in cookies:', lat, lng, 'Accuracy:', accuracy);
            },
            function(error) {
                console.warn('Geolocation error:', error.message);
                // Use default location if geolocation fails (similar to old system)
                const defaultLat = '14.6124466';
                const defaultLng = '120.9879835';
                
                // Store default location in cookies
                const expiryTime = new Date();
                expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000)); // 30 minutes
                document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
                document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
                document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
                
                document.getElementById('recordLatitude').value = defaultLat;
                document.getElementById('recordLongitude').value = defaultLng;
                
                const statusGroup = document.getElementById('locationStatusGroup');
                if (statusGroup) {
                    statusGroup.style.display = 'block';
                    statusGroup.querySelector('div').style.background = '#fef2f2';
                    statusGroup.querySelector('div').style.borderColor = '#fca5a5';
                    const statusText = document.getElementById('locationStatusText');
                    if (statusText) {
                        statusText.innerHTML = '<i class="fas fa-map-pin" style="color: #dc2626; margin-right: 8px;"></i> Using default location';
                        statusText.style.color = '#dc2626';
                    }
                }
                console.log('Using default location (Manila)');
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    } else {
        console.log('Geolocation not supported - using default location');
        const defaultLat = '14.6124466';
        const defaultLng = '120.9879835';
        
        const expiryTime = new Date();
        expiryTime.setTime(expiryTime.getTime() + (30 * 60 * 1000));
        document.cookie = "user_lat=" + defaultLat + "; expires=" + expiryTime.toUTCString() + "; path=/";
        document.cookie = "user_lng=" + defaultLng + "; expires=" + expiryTime.toUTCString() + "; path=/";
        document.cookie = "user_accuracy=0; expires=" + expiryTime.toUTCString() + "; path=/";
        
        document.getElementById('recordLatitude').value = defaultLat;
        document.getElementById('recordLongitude').value = defaultLng;
    }
}

// Capture location from device
function captureLocation() {
    captureLocationAndStore();
}

// Capture location and submit form
function captureLocationAndSubmit(form) {
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(
            function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                // Store location
                document.getElementById('recordLatitude').value = lat;
                document.getElementById('recordLongitude').value = lng;
                
                // Submit form
                form.submit();
            },
            function(error) {
                console.warn('Geolocation error on submit:', error.message);
                // Allow submission without location
                form.submit();
            },
            {
                enableHighAccuracy: true,
                timeout: 5000,
                maximumAge: 0
            }
        );
    } else {
        // No geolocation support, submit anyway
        form.submit();
    }
}


// Enhanced location capture with offline support
async function captureLocationAndSubmitOffline(form) {
    if (!navigator.onLine) {
        // Offline mode
        const location = await geoHandler.getLocation();
        document.getElementById('recordLatitude').value = location.latitude;
        document.getElementById('recordLongitude').value = location.longitude;
        document.getElementById('recordAccuracy').value = location.accuracy;
        
        // Trigger the submit handler which will handle offline
        form.dispatchEvent(new Event('submit'));
    } else {
        // Online mode - use existing function
        captureLocationAndSubmit(form);
    }
}
</script>